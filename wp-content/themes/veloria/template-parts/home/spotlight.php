<?php
/**
 * LegacyHomePage.tsx — Donna spotlight.
 *
 * @package Veloria
 *
 * @var array $args { spotlight: array, product: ?array }
 */

defined( 'ABSPATH' ) || exit;

use Veloria\Commerce\Catalog\Catalog;

$s    = $args['spotlight'];
$href = $args['product'] ? home_url( Catalog::relative_link( $args['product'] ) ) : home_url( '/shop' );
$image = veloria_sized_asset_url( (string) ( $s['image'] ?? '' ), 'large', 'images/hero/Ultra_realistic_luxury_202603301304.png' );
?>
<section class="py-16 lg:py-24">
	<div class="container mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-12">
			<p class="text-[11px] font-semibold tracking-[0.2em] text-[#856b35] uppercase mb-3"><?php echo esc_html( $s['eyebrow'] ?? '' ); ?></p>
		</div>
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
			<div class="relative aspect-[3/4] lg:aspect-[3/3] rounded-2xl overflow-hidden">
				<img src="<?php echo esc_url( $image ); ?>" alt="Donna tote spotlight" class="w-full h-full object-cover" loading="lazy">
			</div>
			<div class="mt-8 lg:mt-0 text-center lg:text-left">
				<h2 class="text-3xl lg:text-4xl font-serif font-medium text-gray-900 mb-6"><?php echo esc_html( $s['title'] ?? '' ); ?></h2>
				<?php foreach ( array_values( (array) ( $s['paragraphs'] ?? array() ) ) as $i => $paragraph ) : ?>
					<p class="text-gray-600 <?php echo $i === count( $s['paragraphs'] ) - 1 ? 'mb-8' : 'mb-4'; ?> leading-relaxed"><?php echo esc_html( $paragraph ); ?></p>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( $href ); ?>" class="inline-flex items-center px-8 py-4 bg-black text-white text-xs font-bold tracking-[0.2em] uppercase hover:bg-[#b59a5c] transition-colors duration-300">View Details</a>
			</div>
		</div>
	</div>
</section>
