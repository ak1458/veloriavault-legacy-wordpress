<?php
/**
 * Native account dashboard and extension endpoints, including password reset.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'veloria_elementor_render_page' ) && veloria_elementor_render_page() ) {
	return;
}

get_header();
?>
<div class="veloria-native-commerce min-h-screen bg-[#faf8f5] px-4 pt-24 pb-16">
	<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 max-w-7xl mx-auto">
		<h1 class="text-2xl font-serif text-gray-900 mb-6"><?php esc_html_e( 'My Account', 'veloria' ); ?></h1>
		<div class="veloria-wc-form veloria-wc-notices text-sm text-gray-600">
			<?php echo do_shortcode( '[woocommerce_my_account]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</div>
<?php
get_footer();
