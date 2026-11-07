<?php
/** Native classic cart: WooCommerce owns line items, totals, shipping and hooks. */
defined( 'ABSPATH' ) || exit;

if ( function_exists( 'veloria_elementor_render_page' ) && veloria_elementor_render_page() ) {
	return;
}

get_header();
?>
<div class="veloria-native-commerce min-h-screen bg-[#faf8f5] px-4 pt-24 pb-16 sm:px-6">
	<div class="max-w-7xl mx-auto">
		<?php if ( WC()->cart && WC()->cart->is_empty() ) : ?>
			<?php // Branded empty state (live CartPage.tsx) instead of WooCommerce's info notice. ?>
			<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-black transition-colors mb-6"><?php veloria_icon_e( 'arrow-left', 16 ); ?><?php esc_html_e( 'Continue Shopping', 'veloria' ); ?></a>
			<h1 class="font-serif text-3xl text-gray-900 mb-8"><?php esc_html_e( 'Shopping Cart', 'veloria' ); ?> <span class="text-gray-600 text-xl">(0 items)</span></h1>
			<?php wc_print_notices(); ?>
			<div class="bg-white rounded-2xl border border-gray-100 py-16 px-6 text-center">
				<div class="w-16 h-16 mx-auto mb-6 text-[#b59a5c]"><?php veloria_icon_e( 'shopping-bag', 64, 'w-full h-full', array( 'stroke-width' => 1.25 ) ); ?></div>
				<h2 class="font-serif text-2xl text-gray-900 mb-3"><?php esc_html_e( 'Your cart is empty', 'veloria' ); ?></h2>
				<p class="text-gray-500 max-w-md mx-auto mb-8"><?php esc_html_e( "Looks like you haven't added anything to your cart yet. Explore our latest premium leather collections.", 'veloria' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="inline-flex items-center px-8 py-3 bg-black text-white text-xs font-bold tracking-[0.2em] uppercase hover:bg-[#b59a5c] transition-colors"><?php esc_html_e( 'Explore Collection', 'veloria' ); ?></a>
			</div>
		<?php else : ?>
			<h1 class="font-serif text-3xl text-gray-900 mb-8"><?php esc_html_e( 'Shopping Cart', 'veloria' ); ?></h1>
			<?php echo do_shortcode( '[woocommerce_cart]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	</div>
</div>
<?php get_footer();
