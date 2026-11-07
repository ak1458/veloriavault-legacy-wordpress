<?php
define( 'ABSPATH', __DIR__ );
function absint( $v ) { return abs( (int) $v ); }
function wc_clean( $v ) { return $v; }
function wp_unslash( $v ) { return $v; }
function esc_html( $v ) { return htmlspecialchars( (string) $v ); }
function esc_url( $v ) { return $v; }
function home_url( $v ) { return $v; }
function veloria_setting( $key, $default ) { return $default; }
function get_header() {}
function get_footer() {}
function veloria_icon_e( ...$args ) {}
function do_action( ...$args ) {}
function WC() { return (object) array( 'cart' => null ); }
function wc_get_order( $id ) { return $GLOBALS['fixture']; }
class ReceiptOrder {
    public function __construct( public string $status, public string $gateway ) {}
    public function get_order_key() { return 'test-key'; }
    public function has_status( $s ) { return in_array( $this->status, (array) $s, true ); }
    public function is_paid() { return $this->has_status( array( 'processing', 'completed' ) ); }
    public function needs_payment() { return $this->has_status( array( 'pending', 'failed' ) ); }
    public function get_payment_method() { return $this->gateway; }
    public function get_order_number() { return '123'; }
    public function get_id() { return 123; }
    public function get_checkout_payment_url() { return '/pay'; }
}
$wp = (object) array( 'query_vars' => array( 'order-received' => 123 ) );
$count = 0;
foreach ( array( 'razorpay', 'cod' ) as $gateway ) {
    foreach ( array( 'cancelled', 'refunded', 'on-hold', 'pending', 'failed', 'processing', 'completed' ) as $status ) {
        $fixture = new ReceiptOrder( $status, $gateway );
        $_GET['key'] = 'test-key';
        ob_start();
        include __DIR__ . '/../wp-content/themes/veloria/templates/order-received.php';
        $html = ob_get_clean();
        $expected = match ( $status ) {
            'cancelled' => 'Order cancelled',
            'refunded' => 'Order refunded',
            'failed' => 'Payment failed',
            'pending', 'on-hold' => 'cod' === $gateway ? 'Order Confirmed!' : 'Payment pending',
            default => 'Order Confirmed!',
        };
        if ( ! str_contains( $html, '>' . $expected . '</h2>' ) || ( 'on-hold' === $status && str_contains( $html, '>Complete payment</a>' ) ) ) {
            throw new RuntimeException( "$gateway/$status render failed" );
        }
        ++$count;
    }
}
$_GET['key'] = 'invalid';
ob_start();
include __DIR__ . '/../wp-content/themes/veloria/templates/order-received.php';
$html = ob_get_clean();
if ( ! str_contains( $html, '>Order unavailable</h2>' ) || str_contains( $html, 'Order #123' ) ) {
    throw new RuntimeException( 'Invalid key disclosed order confirmation' );
}
echo ( $count + 1 ) . " receipt rendering checks passed\n";
