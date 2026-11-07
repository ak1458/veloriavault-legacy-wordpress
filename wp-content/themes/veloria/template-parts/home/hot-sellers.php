<?php
/**
 * LegacyHomePage.tsx — Hot Seller grid.
 *
 * @package Veloria
 *
 * @var array $args { products: array }
 */

defined( 'ABSPATH' ) || exit;
?>
<section id="hot-sellers" class="py-16 lg:py-24 bg-white">
	<div class="container mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-12">
			<p class="text-[11px] font-semibold tracking-[0.2em] text-[#856b35] uppercase mb-3"><?php echo esc_html( (string) veloria_setting( 'hot_seller_label' ) ); ?></p>
			<h2 class="text-2xl lg:text-3xl font-serif font-medium text-gray-900 max-w-2xl mx-auto"><?php echo esc_html( (string) veloria_setting( 'hot_seller_title' ) ); ?></h2>
		</div>
		<?php if ( $args['products'] ) : ?>
			<div class="grid grid-cols-2 md:grid-cols-4 gap-6">
				<?php foreach ( $args['products'] as $product ) : ?>
					<?php get_template_part( 'template-parts/product/card-home', null, array( 'product' => $product ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="text-center py-12">
				<p class="text-gray-500 mb-4">Our bestsellers are being restocked. Check back soon!</p>
				<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="inline-flex items-center px-6 py-3 bg-black text-white text-xs font-bold tracking-[0.2em] uppercase hover:bg-[#b59a5c] transition-colors duration-300">View All Products</a>
			</div>
		<?php endif; ?>
	</div>
</section>
