<?php
/** Cancellation REST response and ownership contract; no network or database. */
namespace Veloria\Commerce\Orders {
	final class OrderService {
		public static array $result = array();
		public static int $calls = 0;
		public static function find( string $id ): ?\WC_Order { return '42' === $id ? $GLOBALS['order'] : null; }
		public static function owns( \WC_Order $order, string $email ): bool { return 'guest@example.test' === $email; }
		public static function cancel( \WC_Order $order ): array { ++self::$calls; return self::$result; }
	}
}
namespace Veloria\Commerce\Auth {
	final class Session { public static function otp_email(): ?string { return $GLOBALS['otp']; } }
}
namespace Veloria\Commerce\Security {
	final class RateLimiter { public static function hit( ...$args ): array { return array( 'success' => $GLOBALS['rate_ok'] ); } }
}
namespace {
	define( 'ABSPATH', __DIR__ );
	define( 'MINUTE_IN_SECONDS', 60 );
	class WC_Order { public int $customer = 7; public function get_customer_id(): int { return $this->customer; } }
	class WP_REST_Request {
		public function __construct( public array $body ) {}
		public function get_json_params(): array { return $this->body; }
	}
	class WP_REST_Response { public function __construct( public array $data, public int $status = 200 ) {} }
	function is_user_logged_in(): bool { return $GLOBALS['logged_in']; }
	function wp_get_current_user(): object { return (object) array( 'ID' => $GLOBALS['user'], 'user_email' => 'customer@example.test' ); }
	require dirname( __DIR__ ) . '/wp-content/plugins/veloria-commerce/src/Rest/Routes.php';
	use Veloria\Commerce\Orders\OrderService;
	use Veloria\Commerce\Rest\Routes;
	$count = 0;
	function check( bool $ok, string $name ): void { global $count; if ( ! $ok ) throw new \RuntimeException( $name ); ++$count; }
	$GLOBALS['order'] = new WC_Order();
	$GLOBALS['logged_in'] = true;
	$GLOBALS['user'] = 7;
	$GLOBALS['otp'] = null;
	$GLOBALS['rate_ok'] = true;
	$request = new WP_REST_Request( array( 'orderId' => '42', 'approve' => true, 'email' => 'guest@example.test' ) );
	OrderService::$result = array( 'success' => true, 'pendingReview' => true, 'refunded' => false, 'message' => 'Cancellation review requested. No refund has been initiated.' );
	$response = Routes::cancel( $request );
	check( 200 === $response->status && true === $response->data['pendingReview'] && false === $response->data['refunded'], 'Review acceptance must retain pendingReview without claiming a refund' );
	check( OrderService::$result['message'] === $response->data['message'], 'Review acceptance message must reach customer UI' );
	OrderService::$result = array( 'success' => true, 'refunded' => true );
	$response = Routes::cancel( $request );
	check( true === $response->data['refunded'] && false === $response->data['pendingReview'], 'Completed cancellation retains refund result' );
	OrderService::$result = array( 'success' => false, 'status' => 409, 'error' => 'Provider verification pending' );
	$response = Routes::cancel( $request );
	check( 409 === $response->status && false === $response->data['success'] && 'Provider verification pending' === $response->data['error'], 'Provider failure remains a failure' );
	$before = OrderService::$calls;
	$GLOBALS['user'] = 8;
	check( 404 === Routes::cancel( $request )->status && $before === OrderService::$calls, 'Other accounts cannot cancel or request review' );
	$GLOBALS['logged_in'] = false;
	check( 401 === Routes::cancel( $request )->status && $before === OrderService::$calls, 'Request email alone does not authenticate guest ownership' );
	$GLOBALS['order']->customer = 0;
	$GLOBALS['otp'] = 'wrong@example.test';
	check( 404 === Routes::cancel( $request )->status && $before === OrderService::$calls, 'Wrong OTP session cannot cancel guest order' );
	$GLOBALS['otp'] = 'guest@example.test';
	OrderService::$result = array( 'success' => true, 'pendingReview' => true, 'refunded' => false );
	check( true === Routes::cancel( $request )->data['pendingReview'] && $before + 1 === OrderService::$calls, 'Verified guest can request review' );
	$GLOBALS['rate_ok'] = false;
	$before = OrderService::$calls;
	check( 429 === Routes::cancel( $request )->status && $before === OrderService::$calls, 'Rate limit blocks cancellation service' );
	echo "$count cancellation REST checks passed\n";
}
