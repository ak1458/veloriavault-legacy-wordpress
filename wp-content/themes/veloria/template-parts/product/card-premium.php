<?php
/**
 * PremiumProductCard.tsx — shop, category and homepage tabs.
 *
 * @package Veloria
 *
 * @var array $args { product: array, eager?: bool, show_wishlist?: bool }
 */

defined( 'ABSPATH' ) || exit;

use Veloria\Commerce\Catalog\Catalog;

$product  = $args['product'];
$eager    = ! empty( $args['eager'] );
$wishlist = $args['show_wishlist'] ?? true;
$link     = Catalog::relative_link( $product );
$price    = (float) ( (float) $product['price'] ?: (float) $product['regular_price'] );
$regular  = (float) $product['regular_price'];
$on_sale  = $product['on_sale'] && $price < $regular;
$category = $product['categories'][0]['name'] ?? 'Luxury Bag';
$swatches = veloria_swatches( $product );
$variable = 'variable' === $product['type'];
$item     = veloria_item_payload( $product );
?>
<div class="group flex flex-col h-full bg-white transition-all duration-300" x-data="{ item: <?php echo veloria_json_attr( $item ); ?> }">
	<a href="<?php echo esc_url( home_url( $link ) ); ?>" class="block relative aspect-square overflow-hidden rounded-xl bg-[#e5e2dd]">
		<?php if ( $wishlist ) : ?>
			<?php list( $initial, $bind ) = veloria_toggle( '$store.wishlist.has(item.id)', 'bg-[#b59a5c] text-white', 'bg-white/90 text-gray-400 hover:text-red-500', false ); ?>
			<button type="button" @click.prevent.stop="$store.wishlist.toggle(item)"
				class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full flex items-center justify-center transition-all shadow-sm <?php echo esc_attr( $initial ); ?>"<?php echo $bind; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				aria-label="Add to Wishlist">
				<?php veloria_icon_e( 'heart', 16, '', array( ':fill' => "\$store.wishlist.has(item.id) ? 'currentColor' : 'none'" ) ); ?>
			</button>
		<?php endif; ?>
		<?php if ( $on_sale ) : ?>
			<div class="absolute top-3 left-3 z-10 px-2.5 py-1 bg-black/90 text-white text-[9px] font-bold tracking-[0.2em] rounded">SALE</div>
		<?php endif; ?>
		<?php echo veloria_catalog_img( $product['images'][0] ?? null, 'absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110', '(max-width: 768px) 50vw, 25vw', $eager, $product['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="absolute inset-x-0 bottom-0 p-4 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity translate-y-4 group-hover:translate-y-0 group-focus-within:translate-y-0 duration-300 pointer-events-none [@media(hover:none)]:hidden">
			<button type="button" @click.prevent.stop="veloriaCard.add(item)" class="w-full bg-white text-black py-3 rounded-lg text-[10px] font-bold tracking-widest uppercase shadow-xl pointer-events-none group-hover:pointer-events-auto focus:pointer-events-auto hover:bg-[#b59a5c] hover:text-white transition-colors">
				<?php echo $variable ? 'Choose Colour' : 'Quick Add'; ?>
			</button>
		</div>
	</a>

	<div class="pt-5 text-center flex flex-col items-center flex-grow">
		<h3 class="text-sm font-serif text-gray-900 mb-1 leading-tight group-hover:text-[#b59a5c] transition-colors">
			<a href="<?php echo esc_url( home_url( $link ) ); ?>"><?php echo esc_html( $product['name'] ); ?></a>
		</h3>
		<p class="text-[10px] text-gray-500 uppercase tracking-widest mb-3"><?php echo esc_html( $category ); ?></p>
		<?php $stars = (int) round( (float) ( $product['average_rating'] ?? 0 ) ); ?>
		<?php if ( (int) ( $product['rating_count'] ?? 0 ) > 0 ) : ?>
			<div class="flex items-center justify-center gap-0.5 mb-3" role="img" aria-label="<?php echo esc_attr( sprintf( 'Rated %d out of 5', $stars ) ); ?>">
				<?php for ( $i = 0; $i < 5; $i++ ) : ?>
					<?php veloria_icon_e( 'star', 10, $i < $stars ? 'text-[#b59a5c] fill-[#b59a5c]' : 'text-[#d9cfbf]' ); ?>
				<?php endfor; ?>
			</div>
		<?php endif; ?>
		<?php if ( count( $swatches ) > 1 ) : ?>
			<div class="flex items-center justify-center gap-2 mb-3" role="group" aria-label="Available colors">
				<?php foreach ( array_slice( $swatches, 0, 6 ) as $swatch ) : ?>
					<a href="<?php echo esc_url( home_url( $swatch['href'] ) ); ?>" @click.stop aria-label="<?php echo esc_attr( $swatch['name'] ); ?>" title="<?php echo esc_attr( $swatch['name'] ); ?>" class="block w-4 h-4 rounded-full border border-gray-300 hover:scale-110 hover:border-[#b59a5c] transition-transform" style="background-color: <?php echo esc_attr( $swatch['hex'] ); ?>">
						<span class="sr-only"><?php echo esc_html( $swatch['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
				<?php if ( count( $swatches ) > 6 ) : ?>
					<span class="text-[10px] text-gray-400 ml-1">+<?php echo (int) ( count( $swatches ) - 6 ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="flex items-center justify-center gap-3 mb-4">
			<?php if ( $on_sale ) : ?>
				<span class="text-[11px] text-gray-500 line-through tracking-tighter decoration-[#b59a5c]">&#8377;<?php echo esc_html( veloria_format_number( $regular ) ); ?></span>
			<?php endif; ?>
			<span class="text-sm font-bold text-gray-900 tracking-tight">&#8377;<?php echo esc_html( veloria_format_number( $price ) ); ?></span>
		</div>
		<button type="button" @click="veloriaCard.add(item)" class="mt-auto w-full py-3 bg-black text-white text-[10px] font-bold tracking-[0.2em] uppercase rounded hover:bg-[#b59a5c] transition-all shadow-md shadow-black/5">
			<?php echo $variable ? 'Choose Colour' : 'Add to Cart'; ?>
		</button>
	</div>
</div>
