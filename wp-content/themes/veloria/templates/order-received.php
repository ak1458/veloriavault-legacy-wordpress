<?php
/**
 * /checkout/order-received/{id} — the "Order Confirmed!" card. The order key
 * in the URL is verified exactly as WooCommerce does, and the standard
 * thank-you hooks fire so gateways, analytics and pixels keep working.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

global $wp;
$order_id  = absint( $wp->query_vars['order-received'] ?? 0 );
$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$order     = $order_id ? wc_get_order( $order_id ) : null;
$valid     = $order && hash_equals( $order->get_order_key(), (string) $order_key );
$failed    = $valid && $order->has_status( 'failed' );
$closed    = $valid && $order->has_status( array( 'cancelled', 'refunded' ) );
$confirmed = $valid && ! $failed && ! $closed && ( $order->is_paid() || ( (string) veloria_setting( 'cod_gateway', 'cod' ) === $order->get_payment_method() && $order->has_status( array( 'pending', 'on-hold', 'processing', 'completed' ) ) ) );
$pending   = $valid && ! $failed && ! $closed && ! $confirmed;

if ( $valid && ! $failed ) {
	// Same clean-up WooCommerce's thank-you page performs.
	WC()->cart && WC()->cart->empty_cart();
}

get_header();
?>
<div class="flex min-h-screen flex-col items-center justify-center bg-[#faf8f5] p-4 pt-24 sm:pt-32" x-data x-init="$store.cart.clearLocal(); $store.cart.load()">
	<div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 max-w-md w-full text-center animate-scaleIn">
		<?php if ( $closed ) : ?>
			<h2 class="font-serif text-2xl text-gray-900 mb-2"><?php echo $order->has_status( 'refunded' ) ? 'Order refunded' : 'Order cancelled'; ?></h2>
			<p class="text-sm text-gray-500 mb-6">Check your order tracking page for the latest details.</p>
		<?php elseif ( $pending ) : ?>
			<h2 class="font-serif text-2xl text-gray-900 mb-2">Payment pending</h2>
			<p class="text-sm text-gray-500 mb-2">Order #<?php echo esc_html( $order->get_order_number() ); ?> is waiting for payment confirmation. If you were charged, it will update shortly.</p>
			<?php if ( $order->needs_payment() ) : ?>
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="bg-[#1a1a1a] text-white px-6 py-3 rounded text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors block text-center mb-3">Complete payment</a>
			<?php endif; ?>
		<?php elseif ( $confirmed ) : ?>
			<div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4"><?php veloria_icon_e( 'check', 24, 'w-8 h-8 text-green-500' ); ?></div>
			<h2 class="font-serif text-2xl text-gray-900 mb-2">Order Confirmed!</h2>
			<p class="text-sm text-gray-500 mb-2">Thank you for your purchase.</p>
			<p class="text-lg font-semibold text-[#b59a5c] mb-2">Order #<?php echo esc_html( $order->get_order_number() ); ?></p>
			<p class="text-xs text-gray-400 mb-6">You can track your order below.</p>
		<?php elseif ( $failed ) : ?>
			<h2 class="font-serif text-2xl text-gray-900 mb-2">Payment failed</h2>
			<p class="text-sm text-gray-500 mb-6">Payment was not confirmed. If you were charged, contact support before trying again.</p>
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="bg-[#1a1a1a] text-white px-6 py-3 rounded text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors block text-center mb-3">Try again</a>
		<?php else : ?>
			<h2 class="font-serif text-2xl text-gray-900 mb-2">Order unavailable</h2>
			<p class="text-sm text-gray-500 mb-6">Use your order confirmation link or verify your details on the tracking page.</p>
		<?php endif; ?>
		<div class="space-y-3">
			<a href="<?php echo esc_url( home_url( '/track-order' ) ); ?>" class="bg-[#b59a5c] text-white px-6 py-3 rounded text-xs font-bold uppercase tracking-widest hover:bg-[#a08a4f] transition-colors block text-center">Track Your Order</a>
			<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="bg-[#1a1a1a] text-white px-6 py-3 rounded text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors block text-center">Continue Shopping</a>
		</div>
	</div>
</div>
<?php
if ( $valid ) {
	// Keep WooCommerce / gateway / analytics thank-you integrations working.
	ob_start();
	do_action( 'woocommerce_before_thankyou', $order->get_id() );
	do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
	do_action( 'woocommerce_thankyou', $order->get_id() );
	$hooks_output = ob_get_clean();
	// Gateways may print order details tables here; only scripts are kept.
	if ( preg_match_all( '#<script\b[^>]*>.*?</script>#is', (string) $hooks_output, $scripts ) ) {
		echo implode( "\n", $scripts[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
get_footer();
