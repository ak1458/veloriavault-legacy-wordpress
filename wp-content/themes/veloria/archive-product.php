<?php
/**
 * /shop (src/app/shop/page.tsx + ShopContent.tsx). All parent products are
 * rendered; ?category= and ?search= filter on the client exactly as before.
 *
 * @package Veloria
 * Replaces the WooCommerce template of the same name; reviewed against it.
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'elementor_theme_do_location' ) ) {
	ob_start(); $builder = veloria_elementor_location( 'archive' ); $content = ob_get_clean();
	if ( $builder ) { get_header(); echo $content; get_footer(); return; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

if ( is_tax( 'product_cat' ) ) {
	require __DIR__ . '/taxonomy-product_cat.php';
	return;
}

$catalog    = veloria_catalog();
$products   = veloria_try( static fn() => $catalog->parent_products() );
$tabs       = veloria_category_tabs();
$tab_slugs  = array_column( $tabs, 'slug' );
$categories = array_values( array_filter( veloria_try( static fn() => $catalog->categories() ), static fn( $c ) => in_array( $c['slug'], $tab_slugs, true ) ) );
usort( $categories, static fn( $a, $b ) => array_search( $a['slug'], $tab_slugs, true ) <=> array_search( $b['slug'], $tab_slugs, true ) );
$label_for  = static function ( string $slug ) use ( $tabs ): string {
	foreach ( $tabs as $tab ) {
		if ( $tab['slug'] === $slug ) {
			return $tab['label'];
		}
	}
	return ucwords( str_replace( '-', ' ', $slug ) );
};
$filter_data = array_map(
	static fn( $p ) => array(
		'name'          => $p['name'],
		'categories'    => array_column( $p['categories'], 'slug' ),
		'categoryNames' => array_column( $p['categories'], 'name' ),
		// ?sort=newest / ?sort=bestsellers (header "New Arrivals" / "Best Sellers").
		'date'          => (int) get_post_time( 'U', true, $p['id'] ),
		'sales'         => (int) get_post_meta( $p['id'], 'total_sales', true ),
	),
	$products
);

get_header();
?>
<div class="min-h-screen bg-white pb-20 lg:pb-0" x-data="shop()">
	<script type="application/json" x-ref="data"><?php echo wp_json_encode( $filter_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
	<div class="relative h-[220px] overflow-hidden bg-[#1a1a1a] sm:h-[280px] md:h-[400px]">
		<div class="absolute inset-0">
			<img src="<?php echo esc_url( veloria_sized_asset_url( 'images/covers/shop.png', '1536x1536' ) ); ?>" srcset="<?php echo esc_attr( veloria_theme_srcset( 'images/covers/shop.png' ) ); ?>" sizes="100vw" alt="" class="w-full h-full object-cover object-center lg:object-[center_35%]" style="opacity: 0.7" fetchpriority="high">
			<div class="absolute inset-0 bg-gradient-to-r from-black/50 via-black/30 to-black/20"></div>
		</div>
		<div class="absolute inset-0 flex items-center justify-center z-10 px-4">
			<h1 class="text-4xl md:text-5xl lg:text-6xl font-serif font-medium text-white text-center" x-text="search ? 'SEARCH RESULTS' : (sort === 'newest' ? 'NEW ARRIVALS' : (sort === 'bestsellers' ? 'BEST SELLERS' : 'SHOP'))">SHOP</h1>
		</div>
	</div>

	<div class="container mx-auto px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
		<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-10">
			<div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
				<div class="flex min-w-max gap-2">
					<?php
					// phpcs:disable WordPress.Security.NonceVerification.Recommended
					$current_cat    = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : '';
					$current_search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
					// phpcs:enable
					$on  = 'bg-black text-white border-black';
					$off = 'bg-white text-gray-700 border-gray-200 hover:border-black';
					list( $initial, $bind ) = veloria_toggle( '!category && !search', $on, $off, '' === $current_cat && '' === $current_search );
					?>
					<button type="button" @click="setCategory(null)" class="px-4 py-2 text-xs font-medium tracking-wider uppercase transition-all duration-200 border <?php echo esc_attr( $initial ); ?>"<?php echo $bind; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>All</button>
					<?php foreach ( $categories as $category ) : ?>
						<?php list( $initial, $bind ) = veloria_toggle( "category === '" . esc_js( $category['slug'] ) . "'", $on, $off, $current_cat === $category['slug'] ); ?>
						<button type="button" @click="setCategory('<?php echo esc_js( $category['slug'] ); ?>')" class="px-4 py-2 text-xs font-medium tracking-wider uppercase transition-all duration-200 border <?php echo esc_attr( $initial ); ?>"<?php echo $bind; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $label_for( $category['slug'] ) ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>
			<form @submit.prevent="submitSearch()" class="flex w-full md:w-auto">
				<div class="relative flex-1 md:w-64">
					<input x-ref="searchInput" id="shop-search" type="text" placeholder="Search products..." class="w-full px-4 py-2 pr-10 border border-gray-200 text-sm focus:outline-none focus:border-black transition-colors">
					<button type="submit" class="absolute right-0 top-0 h-full px-3 text-gray-400 hover:text-black transition-colors" aria-label="Search"><?php veloria_icon_e( 'search', 18 ); ?></button>
				</div>
			</form>
		</div>

		<div class="mb-6">
			<p class="text-sm text-gray-500" x-text="'Showing ' + count + ' product' + (count !== 1 ? 's' : '')">Showing <?php echo (int) count( $products ); ?> product<?php echo 1 !== count( $products ) ? 's' : ''; ?></p>
		</div>

		<div x-show="count > 0" class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 md:gap-6 lg:grid-cols-4">
			<?php foreach ( $products as $i => $product ) : ?>
				<div x-show="matches(products[<?php echo (int) $i; ?>] || {})" :style="{ order: rank(<?php echo (int) $i; ?>) }" class="veloria-in-y" style="animation-delay: <?php echo esc_attr( (string) min( $i * 0.02, 0.12 ) ); ?>s">
					<?php get_template_part( 'template-parts/product/card-premium', null, array( 'product' => $product, 'eager' => $i < 4 ) ); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div x-show="count === 0" x-cloak class="text-center py-20">
			<p class="text-gray-500 text-lg mb-4">No products found</p>
			<button type="button" @click="update({ category: '', search: '' })" class="inline-flex items-center px-6 py-3 bg-black text-white text-xs font-bold tracking-wider uppercase hover:bg-[#b59a5c] transition-colors">View All Products</button>
		</div>
	</div>
</div>
<?php
get_footer();
