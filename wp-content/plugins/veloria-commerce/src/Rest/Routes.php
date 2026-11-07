<?php
/**
 * REST endpoints under /wp-json/veloria/v1 — the WordPress replacements for
 * the Next.js /api/* routes, with the same rate limits and response shapes.
 *
 * CSRF: every state-changing route requires the `X-Veloria-Request: 1`
 * header. Browsers can't send a custom header cross-site without a CORS
 * preflight, which WordPress refuses, so a forged form post can't reach them.
 * Logged-in calls also carry the standard X-WP-Nonce.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Rest;

use Veloria\Commerce\Auth\Otp;
use Veloria\Commerce\Auth\Session;
use Veloria\Commerce\Catalog\Catalog;
use Veloria\Commerce\Chat\IntentEngine;
use Veloria\Commerce\Invoice\Invoice;
use Veloria\Commerce\Mail\Mailer;
use Veloria\Commerce\Newsletter\Newsletter;
use Veloria\Commerce\Orders\OrderService;
use Veloria\Commerce\Pricing\CartPricing;
use Veloria\Commerce\Pricing\CouponRepository;
use Veloria\Commerce\Security\RateLimiter;
use Veloria\Commerce\Settings;
use Veloria\Commerce\Shipping\Shiprocket;

defined( 'ABSPATH' ) || exit;

final class Routes {

	public const NS = 'veloria/v1';

	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
		add_action( 'wp_ajax_veloria_nonces', array( self::class, 'ajax_nonces' ) );
		add_action( 'wp_ajax_nopriv_veloria_nonces', array( self::class, 'ajax_nonces' ) );
	}

	/**
	 * Fresh nonces for cached pages. admin-ajax recognises the auth cookie
	 * without a nonce, so the REST nonce returned matches the real user.
	 */
	public static function ajax_nonces(): void {
		nocache_headers();
		wp_send_json(
			array(
				'rest'     => wp_create_nonce( 'wp_rest' ),
				'store'    => wp_create_nonce( 'wc_store_api' ),
				// Generated here, not in the (possibly cached) page, so it matches
				// the visitor's WooCommerce session at submit time.
				'checkout' => wp_create_nonce( 'woocommerce-process_checkout' ),
				'loggedIn' => is_user_logged_in(),
				'email'    => Session::email(),
			)
		);
	}

	public static function routes(): void {
		$post = array( 'methods' => 'POST', 'permission_callback' => array( self::class, 'csrf_guard' ) );
		$get  = array( 'methods' => 'GET', 'permission_callback' => '__return_true' );

		$routes = array(
			'/auth/login'         => $post + array( 'callback' => array( self::class, 'login' ) ),
			'/auth/register'      => $post + array( 'callback' => array( self::class, 'register_customer' ) ),
			'/auth/logout'        => $post + array( 'callback' => array( self::class, 'logout' ) ),
			'/auth/otp/request'   => $post + array( 'callback' => array( self::class, 'otp_request' ) ),
			'/auth/otp/verify'    => $post + array( 'callback' => array( self::class, 'otp_verify' ) ),
			'/account/me'         => $get + array( 'callback' => array( self::class, 'me' ) ),
			'/account/orders'     => $get + array( 'callback' => array( self::class, 'account_orders' ) ),
			'/orders/track'       => $get + array( 'callback' => array( self::class, 'track' ) ),
			'/orders/track-live'  => $get + array( 'callback' => array( self::class, 'track_live' ) ),
			'/orders/cancel'      => $post + array( 'callback' => array( self::class, 'cancel' ) ),
			'/orders/return'      => $post + array( 'callback' => array( self::class, 'order_return' ) ),
			'/orders/invoice'     => $get + array( 'callback' => array( self::class, 'invoice' ) ),
			'/newsletter'         => $post + array( 'callback' => array( self::class, 'newsletter' ) ),
			'/contact'            => $post + array( 'callback' => array( self::class, 'contact' ) ),
			'/chat'               => $post + array( 'callback' => array( self::class, 'chat' ) ),
			'/checkout/summary'   => $post + array( 'callback' => array( self::class, 'checkout_summary' ) ),
			'/coupons/apply'      => $post + array( 'callback' => array( self::class, 'coupon_apply' ) ),
			'/coupons/remove'     => $post + array( 'callback' => array( self::class, 'coupon_remove' ) ),
			'/instagram'          => $get + array( 'callback' => array( self::class, 'instagram' ) ),
			'/health'             => $get + array( 'callback' => array( self::class, 'health' ) ),
		);
		foreach ( $routes as $path => $args ) {
			register_rest_route( self::NS, $path, $args );
		}
	}

	/** @return true|\WP_Error */
	public static function csrf_guard( \WP_REST_Request $request ) {
		if ( '1' !== $request->get_header( 'x_veloria_request' ) ) {
			return new \WP_Error( 'veloria_forbidden', 'Missing request header.', array( 'status' => 403 ) );
		}
		return true;
	}

	private static function error( string $message, int $status, array $extra = array() ): \WP_REST_Response {
		return new \WP_REST_Response( array( 'success' => false, 'error' => $message ) + $extra, $status );
	}

	private static function limited( string $bucket, int $limit, int $window, string $message = 'Too many attempts. Please try again later.' ): ?\WP_REST_Response {
		$result = RateLimiter::hit( $bucket, $limit, $window );
		return $result['success'] ? null : self::error( $message, 429 );
	}

	/** Rate-limit identity for an email address (never stored in plain text). */
	private static function email_identity( string $email ): string {
		return 'email:' . hash( 'sha256', strtolower( trim( $email ) ) );
	}

	/** @return array<string,mixed> */
	private static function json( \WP_REST_Request $request ): array {
		$params = $request->get_json_params();
		return is_array( $params ) ? $params : (array) $request->get_body_params();
	}

	/** @return array<string,mixed> */
	private static function customer_payload( \WP_User $user ): array {
		$customer = new \WC_Customer( $user->ID );
		return array(
			'id'               => $user->ID,
			'email'            => $user->user_email,
			'firstName'        => $customer->get_first_name(),
			'lastName'         => $customer->get_last_name(),
			'displayName'      => trim( $customer->get_first_name() . ' ' . $customer->get_last_name() ),
			'billing'          => $customer->get_billing(),
			'shipping'         => $customer->get_shipping(),
			'isPayingCustomer' => $customer->get_is_paying_customer(),
			'dateCreated'      => $customer->get_date_created() ? $customer->get_date_created()->format( 'c' ) : null,
		);
	}

	// ── Auth ─────────────────────────────────────────────────────────────

	public static function login( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'login', 5, 15 * MINUTE_IN_SECONDS, 'Too many login attempts. Please try again later.' ) ) {
			return $limited;
		}
		$body     = self::json( $request );
		$email    = sanitize_email( (string) ( $body['email'] ?? '' ) );
		$password = (string) ( $body['password'] ?? '' );
		if ( ! is_email( $email ) || '' === $password ) {
			return self::error( 'Validation failed', 400 );
		}
		// Per-account budget: an attacker rotating IPs still cannot guess one password.
		if ( ! RateLimiter::hit( 'login-account', 10, HOUR_IN_SECONDS, self::email_identity( $email ) )['success'] ) {
			return self::error( 'Too many login attempts. Please try again later.', 429 );
		}

		$user = wp_signon(
			array(
				'user_login'    => $email,
				'user_password' => $password,
				'remember'      => false !== ( $body['remember'] ?? true ),
			),
			is_ssl()
		);
		if ( is_wp_error( $user ) ) {
			return self::error( 'Invalid email or password', 401 );
		}
		wp_set_current_user( $user->ID );
		Session::end_otp_session();
		return new \WP_REST_Response( array( 'success' => true, 'customer' => self::customer_payload( $user ) ) );
	}

	public static function register_customer( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'register', 3, HOUR_IN_SECONDS, 'Too many registration attempts. Please try again later.' ) ) {
			return $limited;
		}
		$body  = self::json( $request );
		$email = sanitize_email( (string) ( $body['email'] ?? '' ) );
		$pass  = (string) ( $body['password'] ?? '' );
		$first = sanitize_text_field( (string) ( $body['firstName'] ?? '' ) );
		$last  = sanitize_text_field( (string) ( $body['lastName'] ?? '' ) );

		if ( ! is_email( $email ) || strlen( $pass ) < 6 || '' === $first || '' === $last ) {
			return self::error( 'Validation failed', 400 );
		}
		if ( email_exists( $email ) ) {
			return self::error( 'An account with this email already exists', 409 );
		}

		$user_id = wc_create_new_customer( $email, '', $pass, array( 'first_name' => $first, 'last_name' => $last ) );
		if ( is_wp_error( $user_id ) ) {
			return self::error( 'Failed to create account. Please try again.', 500 );
		}
		$billing  = (array) ( $body['billing'] ?? array() );
		$customer = new \WC_Customer( $user_id );
		$customer->set_billing_first_name( $first );
		$customer->set_billing_last_name( $last );
		$customer->set_billing_email( $email );
		$customer->set_billing_country( 'IN' );
		$customer->set_billing_phone( sanitize_text_field( (string) ( $billing['phone'] ?? '' ) ) );
		$customer->set_billing_address_1( sanitize_text_field( (string) ( $billing['address'] ?? '' ) ) );
		$customer->set_billing_city( sanitize_text_field( (string) ( $billing['city'] ?? '' ) ) );
		$customer->set_billing_postcode( sanitize_text_field( (string) ( $billing['postcode'] ?? '' ) ) );
		$customer->set_shipping_first_name( $first );
		$customer->set_shipping_last_name( $last );
		$customer->set_shipping_country( 'IN' );
		$customer->save();

		wc_set_customer_auth_cookie( $user_id );
		wp_set_current_user( $user_id );
		return new \WP_REST_Response( array( 'success' => true, 'customer' => self::customer_payload( get_userdata( $user_id ) ) ) );
	}

	public static function logout(): \WP_REST_Response {
		wp_logout();
		Session::end_otp_session();
		return new \WP_REST_Response( array( 'success' => true ) );
	}

	public static function otp_request( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'otp-request', 5, 15 * MINUTE_IN_SECONDS ) ) {
			return $limited;
		}
		$email = sanitize_email( (string) ( self::json( $request )['email'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			return self::error( 'Enter a valid email', 400 );
		}
		// Per-address budget stops code-email flooding. Same response either way.
		$identity = self::email_identity( $email );
		if ( RateLimiter::hit( 'otp-email', 3, 15 * MINUTE_IN_SECONDS, $identity )['success'] && RateLimiter::hit( 'otp-email-day', 10, DAY_IN_SECONDS, $identity )['success'] ) {
			Otp::request( $email );
		}
		return new \WP_REST_Response( array( 'success' => true ) );
	}

	public static function otp_verify( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'otp-verify', 10, 15 * MINUTE_IN_SECONDS ) ) {
			return $limited;
		}
		$code = (string) ( self::json( $request )['code'] ?? '' );
		if ( ! preg_match( '/^\d{6}$/', $code ) ) {
			return self::error( 'Enter the 6-digit code', 400 );
		}
		$email = Otp::verify( $code );
		if ( ! $email ) {
			return self::error( 'Invalid or expired code', 401 );
		}
		return new \WP_REST_Response( array( 'success' => true, 'email' => $email ) );
	}

	// ── Account ─────────────────────────────────────────────────────────

	public static function me(): \WP_REST_Response {
		if ( is_user_logged_in() ) {
			return new \WP_REST_Response( array( 'success' => true, 'sessionType' => 'customer', 'customer' => self::customer_payload( wp_get_current_user() ) ) );
		}
		$email = Session::otp_email();
		if ( $email ) {
			return new \WP_REST_Response( array( 'success' => true, 'sessionType' => 'guest', 'email' => $email ) );
		}
		return self::error( 'Not authenticated', 401 );
	}

	public static function account_orders(): \WP_REST_Response {
		$orders = array();
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$orders = wc_get_orders( array( 'customer_id' => $user->ID, 'limit' => 100, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order' ) );
		}
		$email = Session::otp_email();
		if ( $email ) {
			// Proving an email permits guest records only. A newly registered,
			// unverified account must not inherit another person's historical orders.
			$guest_orders = wc_get_orders( array( 'customer_id' => 0, 'billing_email' => $email, 'limit' => 100, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order' ) );
			$orders = array_merge( $orders, array_filter( $guest_orders, static fn( $o ) => self::can_access_order( $o ) ) );
		}
		if ( is_user_logged_in() || $email ) {
			$unique = array();
			foreach ( $orders as $order ) {
				if ( self::can_access_order( $order ) ) {
					$unique[ $order->get_id() ] = $order;
				}
			}
			$orders = array_values( $unique );
			usort( $orders, static fn( $a, $b ) => ( $b->get_date_created() ? $b->get_date_created()->getTimestamp() : 0 ) <=> ( $a->get_date_created() ? $a->get_date_created()->getTimestamp() : 0 ) );
			return new \WP_REST_Response(
				array(
					'success'     => true,
					'sessionType' => is_user_logged_in() ? 'customer' : 'guest',
					'email'       => is_user_logged_in() ? wp_get_current_user()->user_email : $email,
					'orders'      => array_values( array_map( array( OrderService::class, 'dto' ), $orders ) ),
				)
			);
		}
		return self::error( 'Not authenticated', 401 );
	}

	// ── Orders ──────────────────────────────────────────────────────────

	private const NOT_FOUND = "We couldn't find an order with that number and email. Please check both and try again.";

	public static function track( \WP_REST_Request $request ): \WP_REST_Response {
		$number = (string) $request->get_param( 'orderNumber' );
		$email  = (string) $request->get_param( 'email' );
		if ( '' === $number || '' === $email ) {
			return new \WP_REST_Response( array( 'error' => 'Order number and email are required' ), 400 );
		}
		if ( ! RateLimiter::hit( 'track', 10, MINUTE_IN_SECONDS )['success'] ) {
			return new \WP_REST_Response( array( 'error' => 'Too many attempts. Please try again in a minute.' ), 429 );
		}
		list( $order, , $error ) = self::owned_order( $number );
		if ( $error ) {
			return $error;
		}
		return new \WP_REST_Response( array( 'success' => true, 'order' => OrderService::tracking_details( $order ) ) );
	}

	public static function track_live( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'track-live', 20, MINUTE_IN_SECONDS ) ) {
			return $limited;
		}
		$id    = (string) $request->get_param( 'orderId' );
		list( $order, , $error ) = self::owned_order( $id );
		if ( $error ) {
			return $error;
		}
		$shipment = (string) $order->get_meta( '_shiprocket_shipment_id' );
		if ( '' !== $shipment ) {
			$tracking = Shiprocket::track_shipment( $shipment, $order );
			if ( $tracking ) {
				return new \WP_REST_Response( array( 'success' => true, 'fallback' => false, 'orderStatus' => $order->get_status() ) + $tracking );
			}
		}
		return new \WP_REST_Response(
			array(
				'success'     => true,
				'fallback'    => true,
				'orderStatus' => $order->get_status(),
				'status'      => $order->get_status(),
				'awb'         => null,
				'etaDate'     => null,
				'checkpoints' => array(),
			)
		);
	}

	/**
	 * Registered orders belong to their customer ID. Guest records require
	 * an OTP-verified email; a request parameter or newly registered email
	 * is never proof of ownership.
	 */
	private static function can_access_order( \WC_Order $order ): bool {
		$customer_id = (int) $order->get_customer_id();
		if ( $customer_id > 0 ) {
			return is_user_logged_in() && $customer_id === (int) wp_get_current_user()->ID;
		}
		$email = Session::otp_email();
		return null !== $email && OrderService::owns( $order, $email );
	}

	/** @return array{0:?\WC_Order,1:?string,2:?\WP_REST_Response} */
	private static function owned_order( string $order_id ): array {
		if ( ! is_user_logged_in() && ! Session::otp_email() ) {
			return array( null, null, self::error( 'Sign in, or verify your order email with a one-time code, to access this order.', 401 ) );
		}
		$order = OrderService::find( $order_id );
		if ( ! $order || ! self::can_access_order( $order ) ) {
			return array( null, null, self::error( self::NOT_FOUND, 404 ) );
		}
		$email = (int) $order->get_customer_id() > 0 ? wp_get_current_user()->user_email : Session::otp_email();
		return array( $order, $email, null );
	}

	public static function cancel( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'cancel', 10, 15 * MINUTE_IN_SECONDS ) ) {
			return $limited;
		}
		$body = self::json( $request );
		list( $order, , $error ) = self::owned_order( (string) ( $body['orderId'] ?? '' ) );
		if ( $error ) {
			return $error;
		}
		$result = OrderService::cancel( $order );
		if ( ! $result['success'] ) {
			return self::error( (string) $result['error'], (int) ( $result['status'] ?? 400 ) );
		}
		return new \WP_REST_Response( array(
			'success'       => true,
			'refunded'      => (bool) ( $result['refunded'] ?? false ),
			'pendingReview' => ! empty( $result['pendingReview'] ),
			'message'       => (string) ( $result['message'] ?? '' ),
		) );
	}

	public static function order_return( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'return', 10, 15 * MINUTE_IN_SECONDS ) ) {
			return $limited;
		}
		$body   = self::json( $request );
		$reason = trim( sanitize_textarea_field( (string) ( $body['reason'] ?? '' ) ) );
		if ( mb_strlen( $reason ) < 5 ) {
			return self::error( 'Please tell us a little about the reason', 400 );
		}
		if ( mb_strlen( $reason ) > 2000 ) {
			$reason = mb_substr( $reason, 0, 2000 );
		}
		list( $order, $email, $error ) = self::owned_order( (string) ( $body['orderId'] ?? '' ) );
		if ( $error ) {
			return $error;
		}
		$result = OrderService::request_return( $order, (string) $email, $reason );
		if ( ! $result['success'] ) {
			return self::error( (string) $result['error'], (int) ( $result['status'] ?? 400 ) );
		}
		return new \WP_REST_Response( array( 'success' => true ) );
	}

	/** Streams the PDF and exits (REST can't return binary bodies cleanly). */
	public static function invoice( \WP_REST_Request $request ) {
		if ( $limited = self::limited( 'invoice', 30, 15 * MINUTE_IN_SECONDS ) ) {
			return $limited;
		}
		list( $order, , $error ) = self::owned_order( (string) $request->get_param( 'orderId' ) );
		if ( $error ) {
			return $error;
		}
		$model = Invoice::model( $order );
		$pdf   = Invoice::render( $model );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="veloria-invoice-' . sanitize_file_name( (string) $model['number'] ) . '.pdf"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		header( 'Cache-Control: private, no-store' );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary PDF.
		exit;
	}

	// ── Marketing / support ─────────────────────────────────────────────

	public static function newsletter( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'newsletter', 2, HOUR_IN_SECONDS, 'Too many subscription attempts. Please try again later.' ) ) {
			return $limited;
		}
		$email = sanitize_email( (string) ( self::json( $request )['email'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			return self::error( 'Please enter a valid email address', 400 );
		}
		$result = Newsletter::subscribe( $email );
		if ( 'error' === $result ) {
			return self::error( "We couldn't subscribe you right now. Please try again later.", 503 );
		}
		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'exists' === $result ? "You're already subscribed!" : "Thank you for subscribing! You'll receive exclusive offers and updates.",
			)
		);
	}

	public static function contact( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'contact', 5, HOUR_IN_SECONDS ) ) {
			return $limited;
		}
		$body    = self::json( $request );
		$name    = sanitize_text_field( (string) ( $body['name'] ?? '' ) );
		$email   = sanitize_email( (string) ( $body['email'] ?? '' ) );
		$subject = sanitize_text_field( (string) ( $body['subject'] ?? '' ) );
		$message = sanitize_textarea_field( (string) ( $body['message'] ?? '' ) );
		if ( '' === $name || ! is_email( $email ) || '' === $subject || '' === trim( $message ) ) {
			return self::error( 'Please fill in all fields.', 400 );
		}
		$subjects = array(
			'general'   => 'General Inquiry',
			'order'     => 'Order Status',
			'return'    => 'Returns & Exchanges',
			'wholesale' => 'Wholesale Inquiry',
			'other'     => 'Other',
		);
		$sent = Mailer::contact( Settings::support_email(), $name, $email, $subjects[ $subject ] ?? $subject, mb_substr( $message, 0, 5000 ) );
		if ( ! $sent ) {
			return self::error( "We couldn't send your message right now. Please email us directly.", 503 );
		}
		return new \WP_REST_Response( array( 'success' => true ) );
	}

	public static function chat( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'chat', 60, MINUTE_IN_SECONDS ) ) {
			return $limited;
		}
		$body    = self::json( $request );
		$message = (string) ( $body['message'] ?? '' );
		if ( '' === trim( $message ) ) {
			return new \WP_REST_Response( array( 'error' => 'Message is required' ), 400 );
		}
		try {
			return new \WP_REST_Response( IntentEngine::respond( $message, sanitize_text_field( (string) ( $body['sessionId'] ?? '' ) ) ) );
		} catch ( \Throwable $e ) {
			return new \WP_REST_Response(
				array(
					'content'      => "I'm having trouble right now. You can browse our collection at the Shop page, or try again in a moment.",
					'products'     => array(),
					'quickReplies' => array( 'Browse Shop', 'Try again' ),
					'intent'       => 'error',
				)
			);
		}
	}

	public static function instagram(): \WP_REST_Response {
		if ( ! Settings::get( 'instagram_live' ) ) {
			return new \WP_REST_Response( array( 'success' => false, 'posts' => array() ) );
		}
		$response = rest_do_request( new \WP_REST_Request( 'GET', '/veloria/v1/instagram-feed' ) );
		$data     = $response->is_error() ? array() : (array) $response->get_data();
		$posts    = ! empty( $data['success'] ) && is_array( $data['posts'] ?? null ) ? $data['posts'] : array();
		return new \WP_REST_Response( array( 'success' => (bool) $posts, 'posts' => $posts ) );
	}

	public static function health(): \WP_REST_Response {
		// Integration details are for staff only; the public sees liveness.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_REST_Response( array( 'status' => 'ok' ) );
		}
		return new \WP_REST_Response(
			array(
				'status'      => 'ok',
				'woocommerce' => class_exists( 'WooCommerce' ),
				'razorpay'    => \Veloria\Commerce\Payments\Razorpay::configured(),
				'shiprocket'  => Shiprocket::configured(),
				'version'     => VELORIA_COMMERCE_VERSION,
			)
		);
	}

	// ── Checkout / cart ─────────────────────────────────────────────────

	private static function ensure_cart(): bool {
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}
		if ( null === WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		return null !== WC()->cart;
	}

	/** @return array<string,mixed> */
	private static function calculation_payload(): array {
		WC()->cart->calculate_totals();
		$calc = CartPricing::calculation_for_cart();
		return array(
			'success'          => true,
			'isPrepaid'        => CartPricing::is_prepaid(),
			'calculation'      => $calc,
			'appliedCodes'     => CouponRepository::session_codes(),
			'hasActiveCoupons' => CouponRepository::has_active_coupons(),
			'orderTotal'       => (float) WC()->cart->get_total( 'edit' ),
		);
	}

	/**
	 * Sets the chosen payment method in the WooCommerce session (prepaid ↔
	 * COD) and returns the live calculation for the cart.
	 */
	public static function checkout_summary( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! self::ensure_cart() ) {
			return self::error( 'Cart unavailable', 500 );
		}
		$body = self::json( $request );
		if ( isset( $body['paymentMethod'] ) ) {
			$method = sanitize_key( (string) $body['paymentMethod'] );
			$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : array();
			if ( isset( $gateways[ $method ] ) ) {
				WC()->session->set( 'chosen_payment_method', $method );
			}
		}
		return new \WP_REST_Response( self::calculation_payload() );
	}

	public static function coupon_apply( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $limited = self::limited( 'coupon-apply', 10, 15 * MINUTE_IN_SECONDS ) ) {
			return $limited;
		}
		if ( ! self::ensure_cart() ) {
			return self::error( 'Cart unavailable', 500 );
		}
		$code     = strtoupper( trim( sanitize_text_field( (string) ( self::json( $request )['code'] ?? '' ) ) ) );
		$existing = CouponRepository::session_codes();
		if ( '' === $code ) {
			return self::error( 'Enter a coupon code', 400 );
		}
		if ( in_array( $code, $existing, true ) ) {
			return self::error( 'Coupon already applied', 400 );
		}
		$items    = CartPricing::cart_items();
		$subtotal = array_sum( array_map( static fn( $i ) => $i['price'] * $i['quantity'], $items ) );
		$qty      = array_sum( array_column( $items, 'quantity' ) );
		$check    = CartPricing::engine()->validate_coupon( $code, (float) $subtotal, (int) $qty, CouponRepository::engine_coupons(), $existing );
		if ( ! $check['valid'] ) {
			return self::error( (string) $check['error'], 400 );
		}

		CouponRepository::set_session_codes( array_merge( $existing, array( $code ) ) );
		$calc    = CartPricing::calculation_for_cart();
		$applied = array_map( static fn( $c ) => strtoupper( (string) $c['coupon']['code'] ), $calc['appliedCoupons'] );
		if ( ! in_array( $code, $applied, true ) ) {
			CouponRepository::set_session_codes( $existing ); // Roll back, as the headless store did.
			return self::error( 'Coupon could not be applied.', 400 );
		}
		return new \WP_REST_Response( self::calculation_payload() );
	}

	public static function coupon_remove( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! self::ensure_cart() ) {
			return self::error( 'Cart unavailable', 500 );
		}
		$code = strtoupper( trim( (string) ( self::json( $request )['code'] ?? '' ) ) );
		CouponRepository::set_session_codes( array_values( array_diff( CouponRepository::session_codes(), array( $code ) ) ) );
		return new \WP_REST_Response( self::calculation_payload() );
	}
}
