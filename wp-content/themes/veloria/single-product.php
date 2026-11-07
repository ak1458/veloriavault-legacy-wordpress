<?php
/**
 * Product page (src/app/product/[slug]/page.tsx + ProductDetails.tsx).
 *
 * @package Veloria
 * Replaces the WooCommerce template of the same name; reviewed against it.
 * @version 1.6.4
 */

defined( 'ABSPATH' ) || exit;

use Veloria\Commerce\Catalog\Catalog;

if ( function_exists( 'elementor_theme_do_location' ) ) {
	ob_start();
	$builder = veloria_elementor_location( 'single' );
	$content = ob_get_clean();
	if ( $builder ) { get_header(); echo $content; get_footer(); return; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

$catalog = veloria_catalog();
$product = $catalog->product( (int) get_queried_object_id() );
if ( ! $product ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	require __DIR__ . '/404.php';
	return;
}

$category_name = $product['categories'][0]['name'] ?? 'Bags';
$category_slug = $product['categories'][0]['slug'] ?? '';
$category_href = $category_slug ? '/product-category/' . $category_slug : '/shop';
$fallback      = $product['images'][0]['src'] ?? '';

$img = static fn( ?array $i ) => $i ? array( 'id' => $i['id'], 'src' => $i['src'], 'alt' => $i['alt'], 'srcset' => $i['srcset'] ?? '', 'thumbnail' => $i['thumbnail'] ?? $i['src'] ) : null;

// buildVariationOptions(): only variations that carry a colour.
$variations = array();
foreach ( veloria_try( static fn() => $catalog->variations( (int) $product['id'] ) ) as $variation ) {
	$color = Catalog::color_name( $variation );
	if ( '' === $color ) {
		continue;
	}
	$query = Catalog::variation_query_value( $variation ) ?? trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( str_replace( '&', ' and ', $color ) ) ), '-' );
	$variations[] = array(
		'id'            => $variation['id'],
		'color'         => $color,
		'queryValue'    => $query,
		'swatchImage'   => $variation['images'][0]['thumbnail'] ?? $variation['images'][0]['src'] ?? $fallback,
		'leadImage'     => $variation['images'][0]['src'] ?? $fallback,
		'has_own_image' => $variation['has_own_image'],
		'max_qty'       => $variation['max_qty'] ?? 0,
		'name'          => $variation['name'],
		'slug'          => $variation['slug'],
		'href'          => Catalog::relative_link( $variation ),
		'price'         => $variation['price'],
		'regular_price' => $variation['regular_price'],
		'on_sale'       => $variation['on_sale'],
		'stock_status'  => $variation['stock_status'],
		'images'        => array_map( $img, $variation['images'] ),
		'image'         => $img( $variation['images'][0] ?? null ),
	);
}

$default_id = Catalog::default_variation_id( $variations, $fallback );

$config = array(
	'product'           => array(
		'id'            => $product['id'],
		'name'          => $product['name'],
		'slug'          => $product['slug'],
		'href'          => Catalog::relative_link( $product ),
		'price'         => $product['price'],
		'regular_price' => $product['regular_price'],
		'on_sale'       => $product['on_sale'],
		'stock_status'  => $product['stock_status'],
		'category'      => $category_name,
		'images'        => array_map( $img, $product['images'] ),
	),
	'variations'        => $variations,
	'fallbackImage'     => $fallback,
	'defaultVariationId' => $default_id,
);

// First paint (before Alpine): the preselected colour's gallery + price.
$initial         = $default_id ? current( array_filter( $variations, static fn( $v ) => $v['id'] === $default_id ) ) : null;
$initial_gallery = $initial && $initial['images'] ? $initial['images'] : $config['product']['images'];
$initial_price   = (float) ( $initial ? ( $initial['price'] ?: $initial['regular_price'] ) : ( $product['price'] ?: $product['regular_price'] ) );
$initial_regular = (float) ( $initial ? $initial['regular_price'] : $product['regular_price'] );
$initial_on_sale = (bool) ( $initial ? $initial['on_sale'] : $product['on_sale'] ) && $initial_regular > $initial_price;
$tag_list        = get_the_term_list( (int) $product['id'], 'product_tag', '', ', ' );
if ( function_exists( 'WC' ) && WC()->structured_data && ( $wc_product = wc_get_product( (int) $product['id'] ) ) ) {
	// The custom template skips woocommerce_single_product_summary, so emit Product/Offer JSON-LD directly.
	WC()->structured_data->generate_product_data( $wc_product );
}

$review_count = (int) $product['rating_count'];
$rating_value = (int) round( (float) $product['average_rating'] );
$short        = $product['short_description'] ?: ( $product['description'] ? mb_substr( $product['description'], 0, 300 ) . ( mb_strlen( $product['description'] ) > 300 ? '...' : '' ) : '' );

get_header();
?>
<div class="min-h-screen bg-[#fbfaf7] pb-20 lg:pb-0">
<div class="bg-[#fbfaf7] pb-24 lg:pb-14" x-data="productDetails(<?php echo veloria_json_attr( $config ); ?>)">
	<section class="mx-auto max-w-[1320px] px-3 pb-10 pt-5 sm:px-6 lg:px-8 lg:pt-8">
		<div class="grid gap-8 lg:grid-cols-[minmax(0,1.02fr)_minmax(360px,0.98fr)] xl:gap-10">
			<div class="min-w-0">
				<?php get_template_part( 'template-parts/product/gallery', null, array( 'initial' => $initial_gallery ) ); ?>
			</div>

			<div class="min-w-0 lg:sticky lg:top-28 lg:self-start">
				<div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
					<nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-[12px] text-[#6f6658] sm:text-[14px]">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="transition hover:text-[#211a12]">Home</a>
						<span>/</span>
						<a href="<?php echo esc_url( home_url( $category_href ) ); ?>" class="transition hover:text-[#211a12]"><?php echo esc_html( $category_name ); ?></a>
						<span class="hidden sm:inline">/</span>
						<span class="hidden font-medium text-[#211a12] sm:inline"><?php echo esc_html( $product['name'] ); ?></span>
					</nav>
					<div class="flex items-center gap-4 text-[#8d6f3b]">
						<a href="<?php echo esc_url( home_url( $category_href ) ); ?>" aria-label="Back to category" class="transition hover:text-[#211a12]"><?php veloria_icon_e( 'layout-grid', 15 ); ?></a>
						<a href="<?php echo esc_url( home_url( $category_href ) ); ?>" aria-label="View next product" class="transition hover:text-[#211a12]"><?php veloria_icon_e( 'chevron-right', 18 ); ?></a>
					</div>
				</div>

				<h1 class="font-serif text-[1.95rem] leading-none text-[#1f1a13] sm:text-[2.6rem] lg:text-[3.35rem]"><?php echo esc_html( $product['name'] ); ?></h1>

				<?php if ( $review_count > 0 ) : ?>
					<div class="mt-5 flex items-center gap-3">
						<div class="flex items-center gap-0.5 text-[#e0b31c]">
							<?php for ( $i = 0; $i < 5; $i++ ) : ?>
								<?php veloria_icon_e( 'star', 15, $i < $rating_value ? '' : 'text-[#d9cfbf]', array( 'fill' => $i < $rating_value ? 'currentColor' : 'none' ) ); ?>
							<?php endfor; ?>
						</div>
						<span class="text-[15px] text-[#766e62]">(<?php echo (int) $review_count; ?> customer reviews)</span>
					</div>
				<?php endif; ?>

				<div class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
					<span class="text-[1.75rem] font-medium leading-none text-[#856b35] sm:text-[2.2rem] lg:text-[2.45rem]" x-text="'₹' + formatPrice(price)">₹<?php echo esc_html( veloria_format_number( $initial_price, 2 ) ); ?></span>
					<span class="text-[1.05rem] text-[#6f6658] line-through sm:text-[1.25rem]" x-show="showRegular" x-text="'₹' + formatPrice(regularPrice)"<?php echo $initial_on_sale ? '' : ' style="display:none"'; ?>>₹<?php echo esc_html( veloria_format_number( $initial_regular, 2 ) ); ?></span>
				</div>

				<div class="mt-6 max-w-[34rem] text-[15px] leading-[1.85] text-[#5c564d] [&_em]:font-serif [&_p]:m-0]">
					<?php if ( $short ) : ?>
						<div><?php echo wp_kses_post( $short ); ?></div>
					<?php endif; ?>
				</div>

				<?php if ( $variations ) : ?>
					<div class="mt-8">
						<div class="mb-3 flex items-center gap-2 text-[15px]">
							<span class="font-semibold text-[#1f1a13]"><?php echo esc_html( Catalog::selector_label( $product ) ); ?>:</span>
							<span class="text-[#766e62]" x-text="currentColor"><?php echo esc_html( $initial['color'] ?? '' ); ?></span>
						</div>
						<p x-show="unavailableColor" x-cloak class="mb-3 text-[14px] text-[#b25b5b]">That colour is not available. Please choose another.</p>
						<div class="flex flex-wrap items-end gap-3">
							<?php foreach ( $variations as $option ) : ?>
								<?php
								$is_default        = $option['id'] === $default_id;
								$expr              = 'selectedId === ' . (int) $option['id'];
								list( $ring, $rb ) = veloria_toggle( $expr, 'shadow-[0_0_0_1px_#211a12_inset]', 'opacity-70 hover:opacity-100', $is_default );
								list( $bar, $bb )  = veloria_toggle( $expr, 'opacity-100', 'opacity-0 group-hover:opacity-40', $is_default );
								?>
								<?php $sold_out = 'outofstock' === $option['stock_status']; ?>
								<button type="button" @click="selectVariation(variations.find(v => v.id === <?php echo (int) $option['id']; ?>))" class="group flex flex-col items-center gap-2" aria-label="Select <?php echo esc_attr( $option['color'] . ( $sold_out ? ' (sold out)' : '' ) ); ?>" title="<?php echo esc_attr( $option['color'] . ( $sold_out ? ' – sold out' : '' ) ); ?>">
									<span class="relative flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-[#ddd4c7] transition sm:h-10 sm:w-10 <?php echo esc_attr( $ring ); ?>"<?php echo $rb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
										<img src="<?php echo esc_url( $option['swatchImage'] ); ?>" alt="<?php echo esc_attr( $option['color'] ); ?>" class="absolute inset-0 h-full w-full object-cover<?php echo $sold_out ? ' opacity-40 grayscale' : ''; ?>" loading="lazy">
										<?php if ( $sold_out ) : ?>
											<span aria-hidden="true" class="pointer-events-none absolute left-1/2 top-1/2 h-[1.5px] w-[130%] -translate-x-1/2 -translate-y-1/2 -rotate-45 bg-[#8b8376]"></span>
										<?php endif; ?>
									</span>
									<span class="h-[2px] w-6 bg-[#211a12] transition <?php echo esc_attr( $bar ); ?>"<?php echo $bb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></span>
								</button>
							<?php endforeach; ?>
							<button type="button" x-show="selected" @click="clearVariation()" class="pb-1 text-[14px] text-[#6f6658] transition hover:text-[#211a12]">Clear</button>
						</div>
					</div>
				<?php endif; ?>

				<div class="mt-7 flex items-center gap-2 text-[15px]">
					<span :class="inStock ? 'text-[#b7924e]' : 'text-red-500'"><?php veloria_icon_e( 'check', 18, '', array( 'stroke-width' => 2.5 ) ); ?></span>
					<span class="font-medium text-[#1f1a13]" x-text="inStock ? 'In stock' : 'Out of stock'">In stock</span>
				</div>

				<div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-stretch">
					<div class="flex h-[52px] w-full max-w-[180px] items-center border border-[#ded5c9] bg-white sm:w-[132px]">
						<button type="button" @click="quantity = Math.max(1, quantity - 1)" class="flex h-full w-11 items-center justify-center text-[#4e483f] transition hover:bg-[#f6f1e7]" aria-label="Decrease quantity"><?php veloria_icon_e( 'minus', 15 ); ?></button>
						<span class="flex-1 text-center text-[16px] text-[#1f1a13]" x-text="quantity">1</span>
						<button type="button" @click="incrementQty()" :disabled="quantity >= maxQty" class="flex h-full w-11 items-center justify-center text-[#4e483f] transition hover:bg-[#f6f1e7] disabled:cursor-not-allowed disabled:opacity-40" aria-label="Increase quantity"><?php veloria_icon_e( 'plus', 15 ); ?></button>
					</div>
					<button type="button" @click="addToCart()" :disabled="!inStock || needsVariation" class="h-[52px] w-full bg-[#856b35] px-8 text-[14px] font-medium uppercase tracking-[0.02em] text-white transition hover:bg-[#a98a48] disabled:cursor-not-allowed disabled:bg-[#d7c69f] sm:min-w-[190px] sm:w-auto" x-text="needsVariation ? 'Select a colour' : 'Add to cart'">Add to cart</button>
				</div>

				<div class="mt-6 flex flex-wrap items-center gap-x-8 gap-y-3 border-b border-[#e6dfd2] pb-6 text-[15px] text-[#29231b]">
					<button type="button" @click="toggleWishlist()" class="flex items-center gap-2 transition" :class="wishlisted ? 'text-[#b25b5b]' : 'hover:text-[#b25b5b]'">
						<?php veloria_icon_e( 'heart', 16, '', array( ':fill' => "wishlisted ? 'currentColor' : 'none'" ) ); ?>
						<span>Add to wishlist</span>
					</button>
				</div>

				<div class="mt-6 space-y-4 text-[15px] leading-6 text-[#5f584f]">
					<p><span class="font-semibold text-[#1f1a13]">Category:</span> <a href="<?php echo esc_url( home_url( $category_href ) ); ?>" class="transition hover:text-[#b18d48]"><?php echo esc_html( $category_name ); ?></a></p>
					<?php if ( $tag_list && ! is_wp_error( $tag_list ) ) : ?>
						<p><span class="font-semibold text-[#1f1a13]">Tags:</span> <?php echo wp_kses_post( $tag_list ); ?></p>
					<?php endif; ?>
					<div class="flex flex-wrap items-center gap-4 pt-1">
						<span class="font-semibold text-[#1f1a13]">Share:</span>
						<button type="button" @click="copyLink()" class="inline-flex items-center gap-2 text-[#5e564b] transition hover:text-[#b18d48]">
							<?php veloria_icon_e( 'copy', 15 ); ?>
							<span x-text="copied ? 'Link copied' : 'Copy link'">Copy link</span>
						</button>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?php get_template_part( 'template-parts/product/offers' ); ?>
	<div class="mt-8">
		<?php
		// Legal Metrology / E-Commerce Rules: MRP incl. taxes and country of origin on every product.
		$legal   = veloria_legal();
		$mrp     = max( array_merge( array( (float) $product['regular_price'] ), array_map( static fn( $v ) => (float) $v['regular_price'], $variations ) ) );
		$details = array();
		if ( $mrp > 0 ) {
			$details[] = '<strong>MRP:</strong> ₹' . esc_html( veloria_format_number( $mrp, 2 ) ) . ' (incl. of all taxes)';
		}
		if ( isset( $legal['country_of_origin'] ) ) {
			$details[] = '<strong>Country of origin:</strong> ' . esc_html( $legal['country_of_origin'] );
		}
		if ( isset( $legal['legal_name'] ) ) {
			$details[] = '<strong>Sold and marketed by:</strong> ' . esc_html( $legal['legal_name'] . ( isset( $legal['legal_address'] ) ? ', ' . $legal['legal_address'] : '' ) );
		}
		$extra = $details ? array( array( 'title' => 'Legal information', 'content' => '<p>' . implode( '<br>', $details ) . '</p>' ) ) : array();
		get_template_part( 'template-parts/product/accordion', null, array( 'description' => $product['description'], 'extra' => $extra ) );
		?>
	</div>
	<?php get_template_part( 'template-parts/product/trust-badges' ); ?>
	<?php get_template_part( 'template-parts/product/reviews', null, array( 'product' => $product, 'bundle' => veloria_try( static fn() => $catalog->review_bundle( $product ), array( 'reviews' => array(), 'media' => array(), 'reviewCount' => 0, 'averageRating' => 0, 'histogram' => array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 ) ) ) ) ); ?>
	<?php get_template_part( 'template-parts/product/zoom' ); ?>
</div>
</div>
<?php
get_footer();
