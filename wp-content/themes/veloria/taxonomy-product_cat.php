<?php
/**
 * /product-category/{slug} (CategoryContent.tsx).
 *
 * @package Veloria
 * Replaces the WooCommerce template of the same name; reviewed against it.
 * @version 4.7.0
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'elementor_theme_do_location' ) ) {
	ob_start(); $builder = veloria_elementor_location( 'archive' ); $content = ob_get_clean();
	if ( $builder ) { get_header(); echo $content; get_footer(); return; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

$term     = get_queried_object();
$slug     = $term instanceof WP_Term ? $term->slug : '';
$catalog  = veloria_catalog();
$products = veloria_try( static fn() => $catalog->parent_products( $slug ) );
$all_cats = veloria_try( static fn() => $catalog->categories() );

$name = $slug;
foreach ( $all_cats as $c ) {
	if ( $c['slug'] === $slug ) {
		$name = $c['name'];
	}
}
if ( $term instanceof WP_Term && $name === $slug ) {
	$name = html_entity_decode( $term->name, ENT_QUOTES );
}

$tabs       = veloria_category_tabs();
$tab_slugs  = array_column( $tabs, 'slug' );
$categories = array_values( array_filter( $all_cats, static fn( $c ) => in_array( $c['slug'], $tab_slugs, true ) ) );
usort( $categories, static fn( $a, $b ) => array_search( $a['slug'], $tab_slugs, true ) <=> array_search( $b['slug'], $tab_slugs, true ) );
$label_for = static function ( string $s ) use ( $tabs ): string {
	foreach ( $tabs as $tab ) {
		if ( $tab['slug'] === $s ) {
			return $tab['label'];
		}
	}
	return ucwords( str_replace( '-', ' ', $s ) );
};

get_header();
?>
<div class="min-h-screen bg-white pb-20 lg:pb-0">
	<div class="relative bg-[#1a1a1a] py-20 md:py-28">
		<div class="absolute inset-0 opacity-30" style="background-image: url('<?php echo esc_url( veloria_sized_asset_url( '/wp-content/uploads/2026/01/Bag-8-3-scaled.jpg', '1536x1536', 'images/covers/shop.png' ) ); ?>'); background-size: cover; background-position: center">
			<div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>
		</div>
		<div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
			<h1 class="text-4xl md:text-5xl lg:text-6xl font-serif font-medium text-white mb-4"><?php echo esc_html( $name ); ?></h1>
			<p class="text-gray-300 text-lg">Browse our <?php echo esc_html( strtolower( $name ) ); ?> collection</p>
		</div>
	</div>

	<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
		<div class="flex flex-wrap gap-2 mb-10">
			<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="px-4 py-2 text-xs font-medium tracking-wider uppercase transition-all duration-200 border bg-white text-gray-700 border-gray-200 hover:border-black">All</a>
			<?php foreach ( $categories as $category ) : ?>
				<a href="<?php echo esc_url( home_url( '/product-category/' . $category['slug'] ) ); ?>" class="px-4 py-2 text-xs font-medium tracking-wider uppercase transition-all duration-200 border <?php echo $category['slug'] === $slug ? 'bg-black text-white border-black' : 'bg-white text-gray-700 border-gray-200 hover:border-black'; ?>"><?php echo esc_html( $label_for( $category['slug'] ) ); ?></a>
			<?php endforeach; ?>
		</div>

		<div class="mb-6">
			<p class="text-sm text-gray-500">Showing <?php echo (int) count( $products ); ?> product<?php echo 1 !== count( $products ) ? 's' : ''; ?></p>
		</div>

		<?php if ( $products ) : ?>
			<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
				<?php foreach ( $products as $i => $product ) : ?>
					<div class="veloria-in-y" style="animation-delay: <?php echo esc_attr( (string) min( $i * 0.02, 0.12 ) ); ?>s">
						<?php get_template_part( 'template-parts/product/card-premium', null, array( 'product' => $product, 'eager' => $i < 4 ) ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="text-center py-20">
				<p class="text-gray-500 text-lg mb-4">No products found in this category</p>
				<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="inline-flex items-center px-6 py-3 bg-black text-white text-xs font-bold tracking-wider uppercase hover:bg-[#b59a5c] transition-colors">View All Products</a>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
