<?php
/**
 * HomeProductCard.tsx — homepage "Hot Seller" grid.
 *
 * @package Veloria
 *
 * @var array $args { product: array, eager?: bool }
 */

defined( 'ABSPATH' ) || exit;

use Veloria\Commerce\Catalog\Catalog;

$product  = $args['product'];
$link     = Catalog::relative_link( $product );
$price    = (float) ( (float) $product['price'] ?: (float) $product['regular_price'] );
$regular  = (float) $product['regular_price'];
$on_sale  = $product['on_sale'] && $price < $regular;
$category = $product['categories'][0]['name'] ?? 'Luxury Bag';
$swatches = veloria_swatches( $product );
$variable = 'variable' === $product['type'];
?>
<div class="group flex flex-col h-full" x-data="{ item: <?php echo veloria_json_attr( veloria_item_payload( $product ) ); ?> }">
	<a href="<?php echo esc_url( home_url( $link ) ); ?>" class="block relative aspect-square overflow-hidden rounded-xl bg-[#e5e2dd]">
		<?php if ( $on_sale ) : ?>
			<div class="absolute top-3 left-3 z-10 px-2 py-1 bg-black text-white text-[10px] font-bold tracking-wider rounded">SALE</div>
		<?php endif; ?>
		<?php echo veloria_catalog_img( $product['images'][0] ?? null, 'absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105', '(max-width: 768px) 50vw, 25vw', ! empty( $args['eager'] ), $product['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
	<div class="pt-3 text-center flex flex-col flex-grow">
		<h3 class="text-sm font-medium text-gray-900 mb-1 leading-tight">
			<a href="<?php echo esc_url( home_url( $link ) ); ?>" class="hover:text-[#b59a5c] transition-colors"><?php echo esc_html( $product['name'] ); ?></a>
		</h3>
		<p class="text-xs text-gray-500 mb-2"><?php echo esc_html( $category ); ?></p>
		<?php if ( count( $swatches ) > 1 ) : ?>
			<div class="flex items-center justify-center gap-2 mb-3" role="group" aria-label="Available colors">
				<?php foreach ( array_slice( $swatches, 0, 6 ) as $swatch ) : ?>
					<a href="<?php echo esc_url( home_url( $swatch['href'] ) ); ?>" aria-label="<?php echo esc_attr( $swatch['name'] ); ?>" title="<?php echo esc_attr( $swatch['name'] ); ?>" class="block w-4 h-4 rounded-full border border-gray-300 hover:scale-110 hover:border-[#b59a5c] transition-transform" style="background-color: <?php echo esc_attr( $swatch['hex'] ); ?>">
						<span class="sr-only"><?php echo esc_html( $swatch['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
				<?php if ( count( $swatches ) > 6 ) : ?>
					<span class="text-[10px] text-gray-400 ml-1">+<?php echo (int) ( count( $swatches ) - 6 ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="flex items-center justify-center space-x-2 mb-3">
			<?php if ( $on_sale ) : ?>
				<span class="text-xs text-gray-500 line-through">&#8377;<?php echo esc_html( veloria_format_number( $regular ) ); ?></span>
			<?php endif; ?>
			<span class="text-sm font-semibold text-gray-900">&#8377;<?php echo esc_html( veloria_format_number( $price ) ); ?></span>
		</div>
		<button type="button" @click.prevent="veloriaCard.add(item)" class="mt-auto w-full py-2.5 bg-black text-white text-xs font-bold tracking-wider uppercase rounded hover:bg-gray-800 transition-colors" style="font-family: var(--font-lato), sans-serif">
			<?php echo $variable ? 'CHOOSE COLOUR' : 'ADD TO CART'; ?>
		</button>
	</div>
</div>
