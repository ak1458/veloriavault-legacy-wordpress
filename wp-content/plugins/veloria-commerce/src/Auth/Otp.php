<?php
/**
 * Email one-time codes for guest order access (port of src/lib/auth/otp.ts).
 *
 * The code is never stored: a transient holds an HMAC of email+code plus an
 * attempt counter, keyed by a random challenge id kept in an HttpOnly cookie.
 * Five wrong guesses burn the challenge.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Auth;

use Veloria\Commerce\Mail\Mailer;

defined( 'ABSPATH' ) || exit;

final class Otp {

	private const TTL          = 10 * MINUTE_IN_SECONDS;
	private const MAX_ATTEMPTS = 5;

	public static function generate_code(): string {
		return str_pad( (string) random_int( 0, 999999 ), 6, '0', STR_PAD_LEFT );
	}

	public static function hash( string $code, string $email ): string {
		return hash_hmac( 'sha256', strtolower( trim( $email ) ) . ':' . $code, Session::key() );
	}

	/** Always "succeeds" so the endpoint can't be used to probe which emails exist. */
	public static function request( string $email ): void {
		$email     = strtolower( trim( $email ) );
		$code      = self::generate_code();
		$challenge = bin2hex( random_bytes( 16 ) );

		set_transient(
			'veloria_otp_' . $challenge,
			array(
				'email'    => $email,
				'hash'     => self::hash( $code, $email ),
				'attempts' => 0,
			),
			self::TTL
		);
		Session::set_cookie( Session::CHALLENGE_COOKIE, $challenge, self::TTL );

		if ( ! Mailer::otp( $email, $code ) ) {
			wc_get_logger()->error( '[OTP] mail send failed for ' . substr( $email, 0, 1 ) . '***@' . substr( (string) strrchr( $email, '@' ), 1 ), array( 'source' => 'veloria-commerce' ) );
		}
	}

	/** @return string|null Verified email, or null. */
	public static function verify( string $code ): ?string {
		$challenge = isset( $_COOKIE[ Session::CHALLENGE_COOKIE ] ) ? sanitize_key( wp_unslash( $_COOKIE[ Session::CHALLENGE_COOKIE ] ) ) : '';
		if ( '' === $challenge ) {
			return null;
		}
		$key   = 'veloria_otp_' . $challenge;
		$state = get_transient( $key );
		if ( ! is_array( $state ) ) {
			return null;
		}

		// Claim one attempt slot atomically: add_option() is a unique-key INSERT, so
		// parallel guesses cannot share a slot or exceed MAX_ATTEMPTS.
		$slot = 0;
		for ( $n = 1; $n <= self::MAX_ATTEMPTS; $n++ ) {
			if ( add_option( 'veloria_otp_try_' . $challenge . '_' . $n, time(), '', false ) ) {
				$slot = $n;
				break;
			}
		}
		if ( 0 === $slot ) {
			delete_transient( $key );
			self::release_attempts( $challenge );
			return null;
		}

		if ( ! hash_equals( (string) $state['hash'], self::hash( $code, (string) $state['email'] ) ) ) {
			return null;
		}

		delete_transient( $key );
		self::release_attempts( $challenge );
		Session::set_cookie( Session::CHALLENGE_COOKIE, '', -1 );
		Session::start_otp_session( (string) $state['email'] );
		return (string) $state['email'];
	}

	private static function release_attempts( string $challenge ): void {
		for ( $n = 1; $n <= self::MAX_ATTEMPTS; $n++ ) {
			delete_option( 'veloria_otp_try_' . $challenge . '_' . $n );
		}
	}

	/** Removes attempt slots of challenges that expired unused. */
	public static function purge_expired_attempts(): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d",
				$wpdb->esc_like( 'veloria_otp_try_' ) . '%',
				time() - self::TTL
			)
		);
	}
}
