<?php
/**
 * Minimal Razorpay REST client (port of src/lib/razorpay.ts).
 *
 * Credentials come from wp-config constants VELORIA_RAZORPAY_KEY_ID /
 * VELORIA_RAZORPAY_KEY_SECRET if defined, otherwise from the official
 * "Razorpay for WooCommerce" plugin settings, so keys live in one place.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Payments;

defined( 'ABSPATH' ) || exit;

final class Razorpay {

	private const API = 'https://api.razorpay.com/v1';

	/** @return array{0:string,1:string} */
	public static function credentials(): array {
		$id     = defined( 'VELORIA_RAZORPAY_KEY_ID' ) ? (string) VELORIA_RAZORPAY_KEY_ID : '';
		$secret = defined( 'VELORIA_RAZORPAY_KEY_SECRET' ) ? (string) VELORIA_RAZORPAY_KEY_SECRET : '';
		if ( '' === $id || '' === $secret ) {
			$settings = get_option( 'woocommerce_razorpay_settings', array() );
			if ( is_array( $settings ) ) {
				$id     = $id ?: (string) ( $settings['key_id'] ?? '' );
				$secret = $secret ?: (string) ( $settings['key_secret'] ?? '' );
			}
		}
		return array( trim( $id ), trim( $secret ) );
	}

	public static function configured(): bool {
		list( $id, $secret ) = self::credentials();
		return '' !== $id && '' !== $secret;
	}

	/** @return array<string,string> */
	private static function headers(): array {
		list( $id, $secret ) = self::credentials();
		return array(
			'Authorization' => 'Basic ' . base64_encode( $id . ':' . $secret ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'Content-Type'  => 'application/json',
		);
	}

	/**
	 * Refund a captured payment. Omit $amount_paise for a full refund.
	 * Fails safe: never throws. `outcome` says whether the provider definitely
	 * created no refund ("rejected", e.g. insufficient merchant balance) or the
	 * result is unknown (transport loss, 5xx, 409 in flight, malformed success).
	 * An unknown result may only be retried with the same key and body.
	 *
	 * @return array{success:bool,refundId?:string,status?:string,error?:string,outcome?:string,code?:string}
	 */
	public static function refund( string $payment_id, ?int $amount_paise = null, string $idempotency_key = '' ): array {
		if ( ! preg_match( '/^pay_[A-Za-z0-9]+$/', $payment_id ) || ( null !== $amount_paise && $amount_paise <= 0 ) ) {
			return array( 'success' => false, 'outcome' => 'rejected', 'error' => 'Invalid payment or refund amount' );
		}
		if ( ! self::configured() ) {
			return array( 'success' => false, 'outcome' => 'rejected', 'error' => 'Razorpay is not configured' );
		}
		$headers = self::headers();
		if ( '' !== $idempotency_key ) {
			$headers['X-Refund-Idempotency'] = $idempotency_key;
		}
		$response = wp_remote_post(
			self::API . '/payments/' . rawurlencode( $payment_id ) . '/refund',
			array(
				'timeout' => 15,
				'headers' => $headers,
				'body'    => wp_json_encode( null !== $amount_paise ? array( 'amount' => $amount_paise ) : new \stdClass() ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'outcome' => 'unknown', 'error' => $response->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			// 4xx validation/authorization errors are processed answers: no refund was
			// created. 409 (same key in flight), 429 and 5xx stay unknown.
			$definite = in_array( $code, array( 400, 401, 403, 404, 422 ), true );
			return array(
				'success' => false,
				'outcome' => $definite ? 'rejected' : 'unknown',
				'code'    => is_array( $data ) && isset( $data['error']['code'] ) ? (string) $data['error']['code'] : '',
				'error'   => is_array( $data ) && isset( $data['error']['description'] ) ? (string) $data['error']['description'] : 'Refund failed (' . $code . ')',
			);
		}
		return self::refund_result( $data, $payment_id, $amount_paise );
	}

	/** Re-read a recorded refund instead of submitting another external refund. */
	public static function refund_status( string $refund_id, string $payment_id, int $amount_paise ): array {
		if ( ! self::configured() || ! preg_match( '/^rfnd_[A-Za-z0-9]+$/', $refund_id ) ) {
			return array( 'success' => false, 'outcome' => 'unknown', 'error' => 'Refund cannot be verified' );
		}
		$response = wp_remote_get( self::API . '/refunds/' . rawurlencode( $refund_id ), array( 'timeout' => 15, 'headers' => self::headers() ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array( 'success' => false, 'outcome' => 'unknown', 'error' => 'Refund status could not be verified' );
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ( $data['id'] ?? '' ) !== $refund_id ) {
			return array( 'success' => false, 'outcome' => 'unknown', 'error' => 'Refund response does not match the recorded refund' );
		}
		return self::refund_result( $data, $payment_id, $amount_paise );
	}

	/**
	 * Every refund the provider holds for a payment, read before any new attempt.
	 * Null when the collection cannot be read or validated.
	 *
	 * @return array<int,array{id:string,amount:int,status:string}>|null
	 */
	public static function payment_refunds( string $payment_id ): ?array {
		if ( ! self::configured() || ! preg_match( '/^pay_[A-Za-z0-9]+$/', $payment_id ) ) {
			return null;
		}
		$response = wp_remote_get( self::API . '/payments/' . rawurlencode( $payment_id ) . '/refunds?count=100', array( 'timeout' => 15, 'headers' => self::headers() ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || 'collection' !== ( $data['entity'] ?? '' ) || ! is_array( $data['items'] ?? null ) || ! array_is_list( $data['items'] )
			|| ! is_int( $data['count'] ?? null ) || $data['count'] !== count( $data['items'] ) || $data['count'] >= 100 ) {
			return null;
		}
		$refunds = array();
		foreach ( $data['items'] as $item ) {
			if ( ! is_array( $item ) || 'refund' !== ( $item['entity'] ?? '' ) || ! is_string( $item['id'] ?? null ) || ! preg_match( '/^rfnd_[A-Za-z0-9]+$/', $item['id'] )
				|| $payment_id !== ( $item['payment_id'] ?? '' ) || ! is_int( $item['amount'] ?? null ) || ! is_string( $item['status'] ?? null ) ) {
				return null;
			}
			$refunds[] = array( 'id' => $item['id'], 'amount' => $item['amount'], 'status' => $item['status'] );
		}
		return $refunds;
	}

	/**
	 * A successful HTTP response alone does not establish a valid refund.
	 * Provider "failed" is a valid final answer: no money moved.
	 */
	private static function refund_result( $data, string $payment_id, ?int $amount_paise ): array {
		if ( ! is_array( $data ) || ! is_string( $data['id'] ?? null ) || ! preg_match( '/^rfnd_[A-Za-z0-9]+$/', $data['id'] )
			|| 'refund' !== ( $data['entity'] ?? '' ) || $payment_id !== ( $data['payment_id'] ?? '' )
			|| ! is_int( $data['amount'] ?? null ) || $data['amount'] <= 0
			|| ( null !== $amount_paise && $data['amount'] !== $amount_paise )
			|| 'INR' !== ( $data['currency'] ?? '' ) || ! in_array( $data['status'] ?? '', array( 'pending', 'processed', 'failed' ), true ) ) {
			return array( 'success' => false, 'outcome' => 'unknown', 'error' => 'Refund response is invalid; review the payment before retrying' );
		}
		if ( 'failed' === $data['status'] ) {
			return array( 'success' => false, 'outcome' => 'failed', 'refundId' => $data['id'], 'status' => 'failed', 'error' => 'The payment provider reported the refund as failed.' );
		}
		return array( 'success' => true, 'refundId' => $data['id'], 'status' => $data['status'], 'amount' => $data['amount'] );
	}

	/** Verify a captured payment and its outstanding refundable amount. */
	public static function captured_payment( string $payment_id, int $expected_paise ): array {
		if ( ! self::configured() || ! preg_match( '/^pay_[A-Za-z0-9]+$/', $payment_id ) || $expected_paise <= 0 ) {
			return array( 'success' => false, 'error' => 'Payment cannot be verified' );
		}
		$response = wp_remote_get( self::API . '/payments/' . rawurlencode( $payment_id ), array( 'timeout' => 15, 'headers' => self::headers() ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array( 'success' => false, 'error' => 'Captured payment could not be verified' );
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || $payment_id !== ( $data['id'] ?? '' ) || 'payment' !== ( $data['entity'] ?? '' )
			|| 'captured' !== ( $data['status'] ?? '' ) || true !== ( $data['captured'] ?? null ) || 'INR' !== ( $data['currency'] ?? '' )
			|| $expected_paise !== ( $data['amount'] ?? null ) || 0 !== ( $data['amount_refunded'] ?? null ) ) {
			return array( 'success' => false, 'error' => 'Payment amount, capture state, or previous refunds require staff review' );
		}
		return array( 'success' => true );
	}

	/**
	 * Captured payments created in a time window (for reconciliation).
	 *
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	public static function payments( int $from, int $to, int $skip = 0, int $count = 100 ) {
		if ( $from < 0 || $to < $from || $skip < 0 || $count < 1 || $count > 100 ) {
			return new \WP_Error( 'razorpay_invalid_window', 'Invalid reconciliation window or page.' );
		}
		if ( ! self::configured() ) {
			return new \WP_Error( 'razorpay_not_configured', 'Razorpay is not configured' );
		}
		$url      = add_query_arg( compact( 'from', 'to', 'skip', 'count' ), self::API . '/payments' );
		$response = wp_remote_get( $url, array( 'timeout' => 20, 'headers' => self::headers() ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) || 'collection' !== ( $data['entity'] ?? '' ) || ! is_array( $data['items'] ?? null )
			|| ! array_is_list( $data['items'] ) || ! is_int( $data['count'] ?? null ) || $data['count'] !== count( $data['items'] ) || count( $data['items'] ) > $count ) {
			return new \WP_Error( 'razorpay_invalid_response', 'Razorpay reconciliation failed or returned an invalid collection; no completeness claim can be made.' );
		}
		foreach ( $data['items'] as $payment ) {
			if ( ! is_array( $payment ) || 'payment' !== ( $payment['entity'] ?? '' ) || ! is_string( $payment['id'] ?? null )
				|| ! preg_match( '/^pay_[A-Za-z0-9]+$/', $payment['id'] ) || ! is_int( $payment['amount'] ?? null )
				|| ! is_int( $payment['created_at'] ?? null ) || ! is_string( $payment['status'] ?? null ) ) {
				return new \WP_Error( 'razorpay_invalid_payment', 'Razorpay returned a malformed payment; reconciliation stopped.' );
			}
		}
		return $data['items'];
	}
}
