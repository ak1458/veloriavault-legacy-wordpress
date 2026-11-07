<?php
/**
 * Who is this visitor? A logged-in WordPress customer, or a guest who proved
 * an email address with a one-time code (port of src/lib/auth/session.ts).
 *
 * Guest sessions are a signed cookie: base64url(json) . "." . HMAC-SHA256,
 * keyed from the site's auth salt. Nothing is stored server-side.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Auth;

defined( 'ABSPATH' ) || exit;

final class Session {

	public const OTP_COOKIE       = 'veloria_otp_session';
	public const CHALLENGE_COOKIE = 'veloria_otp_challenge';
	private const TTL             = 7 * DAY_IN_SECONDS;

	public static function email(): ?string {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return $user->user_email ? strtolower( $user->user_email ) : null;
		}
		return self::otp_email();
	}

	public static function otp_email(): ?string {
		$raw = isset( $_COOKIE[ self::OTP_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::OTP_COOKIE ] ) ) : '';
		$payload = self::verify( $raw );
		if ( ! $payload || ( $payload['scope'] ?? '' ) !== 'otp' || (int) ( $payload['exp'] ?? 0 ) < time() ) {
			return null;
		}
		$email = (string) ( $payload['email'] ?? '' );
		return is_email( $email ) ? strtolower( $email ) : null;
	}

	public static function start_otp_session( string $email ): void {
		$token = self::sign(
			array(
				'email' => strtolower( trim( $email ) ),
				'scope' => 'otp',
				'exp'   => time() + self::TTL,
			)
		);
		self::set_cookie( self::OTP_COOKIE, $token, self::TTL );
	}

	public static function end_otp_session(): void {
		self::set_cookie( self::OTP_COOKIE, '', -1 );
		self::set_cookie( self::CHALLENGE_COOKIE, '', -1 );
	}

	/** @param array<string,mixed> $payload */
	public static function sign( array $payload ): string {
		$body = rtrim( strtr( base64_encode( (string) wp_json_encode( $payload ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return $body . '.' . hash_hmac( 'sha256', $body, self::key() );
	}

	/** @return array<string,mixed>|null */
	public static function verify( string $token ): ?array {
		$parts = explode( '.', $token );
		if ( 2 !== count( $parts ) ) {
			return null;
		}
		if ( ! hash_equals( hash_hmac( 'sha256', $parts[0], self::key() ), $parts[1] ) ) {
			return null;
		}
		$json = base64_decode( strtr( $parts[0], '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$data = is_string( $json ) ? json_decode( $json, true ) : null;
		return is_array( $data ) ? $data : null;
	}

	public static function key(): string {
		return hash( 'sha256', wp_salt( 'auth' ) . '|veloria-otp' );
	}

	public static function set_cookie( string $name, string $value, int $ttl ): void {
		if ( headers_sent() ) {
			return;
		}
		setcookie(
			$name,
			$value,
			array(
				'expires'  => $ttl > 0 ? time() + $ttl : time() - YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ?: '/',
				'domain'   => COOKIE_DOMAIN ?: '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		if ( $ttl > 0 ) {
			$_COOKIE[ $name ] = $value;
		} else {
			unset( $_COOKIE[ $name ] );
		}
	}
}
