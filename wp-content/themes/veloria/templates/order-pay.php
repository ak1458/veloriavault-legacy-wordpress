<?php
/**
 * /checkout/order-pay/{id} — the "Complete Your Payment" card from the
 * headless checkout, wrapping WooCommerce's pay screen. The Razorpay plugin
 * prints its form here and opens the Razorpay modal automatically.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

global $wp;
$order_id = absint( $wp->query_vars['order-pay'] ?? 0 );
$order    = $order_id ? wc_get_order( $order_id ) : null;
// Only show order details to the holder of the order key, as WooCommerce does.
$pay_key  = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( $order && ( '' === $pay_key || ! hash_equals( $order->get_order_key(), $pay_key ) ) ) {
	$order = null;
}
$total    = $order ? (float) $order->get_total() : 0.0;
$prepaid  = $order && 'cod' !== $order->get_payment_method();

get_header();
?>
<div class="min-h-screen bg-[#faf8f5] pb-16 pt-20 sm:pt-24">
	<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm animate-slideUp">
			<div class="text-center mb-8">
				<div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4"><?php veloria_icon_e( 'loader-2', 24, 'w-8 h-8 text-blue-500 animate-spin' ); ?></div>
				<h2 class="font-serif text-2xl text-gray-900 mb-2">Complete Your Payment</h2>
				<p class="text-sm text-gray-500">Secure gateway powered by Razorpay</p>
			</div>

			<?php if ( $order ) : ?>
				<div class="bg-gray-50 p-6 rounded-xl mb-8">
					<div class="flex justify-between items-center mb-2">
						<span class="text-gray-600">Amount to Pay</span>
						<span class="text-2xl font-bold text-gray-900"><?php echo esc_html( veloria_inr( $total ) ); ?></span>
					</div>
					<?php if ( $prepaid && (float) $order->get_meta( '_prepaid_discount' ) > 0 ) : ?>
						<div class="border-t border-gray-200 my-3"></div>
						<p class="text-xs text-green-700 text-center font-medium">✓ Includes <?php echo esc_html( veloria_format_number( (float) veloria_setting( 'prepaid_percent', 5 ) ) ); ?>% extra discount for prepaid payment</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="veloria-pay veloria-wc-notices">
				<?php echo do_shortcode( '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<a href="<?php echo esc_url( home_url( '/checkout' ) ); ?>" class="block w-full mt-4 text-center text-sm text-gray-500 hover:text-gray-800 transition-colors">Cancel and go back</a>
		</div>
	</div>
</div>
<?php
get_footer();
