<?php
/** Native classic checkout: WooCommerce owns fields, gateways, shipping and hooks. */
defined( 'ABSPATH' ) || exit;

if ( function_exists( 'veloria_elementor_render_page' ) && veloria_elementor_render_page() ) {
	return;
}

get_header();
?>
<div class="veloria-native-commerce min-h-screen bg-[#faf8f5] px-4 pt-24 pb-16 sm:px-6">
	<div class="max-w-7xl mx-auto">
		<h1 class="font-serif text-3xl text-gray-900 mb-8"><?php esc_html_e( 'Checkout', 'veloria' ); ?></h1>
		<?php echo do_shortcode( '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
<?php get_footer();
