<?php
/**
 * Homepage (LegacyHomePage.tsx). Data loading mirrors the Next.js page:
 * parent products, five latest reviews, the four hot sellers (with name
 * back-fill if an ID fails), and the "Most loved" tabs.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

use Veloria\Commerce\Catalog\Catalog;

if ( veloria_elementor_render_page() ) { return; }

$catalog  = veloria_catalog();
$products = veloria_try( static fn() => $catalog->parent_products() );
$reviews  = veloria_try( static fn() => $catalog->reviews( 0, 5 ) );

// Hot sellers: ID first, then back-fill by name so the grid always has 4.
$ids       = array_map( 'intval', (array) veloria_setting( 'hot_seller_ids', array() ) );
$backups   = (array) veloria_setting( 'hot_seller_backup_terms', array() );
$by_id     = array();
foreach ( veloria_try( static fn() => $catalog->products_by_ids( $ids ) ) as $p ) {
	$by_id[ $p['id'] ] = $p;
}
$hot_sellers = array();
$seen        = array();
foreach ( $ids as $i => $id ) {
	$p = $by_id[ $id ] ?? Catalog::find( $products, (array) ( $backups[ $i ] ?? array() ) );
	if ( $p && ! isset( $seen[ $p['id'] ] ) ) {
		$seen[ $p['id'] ] = true;
		$hot_sellers[]    = $p;
	}
}
if ( ! $hot_sellers ) {
	$hot_sellers = array_slice( $products, 0, 4 );
}

// Most-loved tabs.
$most_loved = (array) veloria_setting( 'most_loved', array() );
$tabs       = array();
foreach ( (array) ( $most_loved['tabs'] ?? array() ) as $tab ) {
	$list = array_values( array_filter( $products, static fn( $p ) => in_array( $tab['categorySlug'], array_column( $p['categories'], 'slug' ), true ) ) );
	if ( ! empty( $tab['allowedColors'] ) ) {
		$list = array_values(
			array_filter(
				$list,
				static function ( $p ) use ( $tab ) {
					$color = null;
					foreach ( $p['attributes'] as $attr ) {
						if ( 'pa_color' === $attr['slug'] || str_contains( strtolower( $attr['name'] ), 'color' ) ) {
							$color = $attr;
							break;
						}
					}
					if ( ! $color ) {
						return true;
					}
					$hay = array_map( 'strtolower', array_merge( array( (string) $color['option'] ), (array) ( $color['options'] ?? array() ), array_column( (array) ( $color['terms'] ?? array() ), 'name' ) ) );
					foreach ( $tab['allowedColors'] as $allowed ) {
						foreach ( $hay as $value ) {
							if ( str_contains( $value, strtolower( $allowed ) ) ) {
								return true;
							}
						}
					}
					return false;
				}
			)
		);
	}
	if ( ! empty( $tab['productIds'] ) ) {
		$first = array();
		foreach ( $tab['productIds'] as $pid ) {
			foreach ( $list as $p ) {
				if ( $p['id'] === (int) $pid ) {
					$first[] = $p;
				}
			}
		}
		$rest = array_filter( $list, static fn( $p ) => ! in_array( $p['id'], array_map( 'intval', $tab['productIds'] ), true ) );
		$list = array_merge( $first, array_values( $rest ) );
	}
	$list = array_slice( $list, 0, (int) ( $tab['maxItems'] ?? 8 ) );
	if ( $list ) {
		$tabs[] = array(
			'slug'        => $tab['slug'],
			'label'       => $tab['label'],
			'viewAllHref' => $tab['viewAllHref'] ?? '/product-category/' . $tab['slug'],
			'products'    => $list,
		);
	}
}

$spotlight         = (array) veloria_setting( 'spotlight', array() );
$spotlight_product = null;
foreach ( (array) ( $spotlight['search_terms'] ?? array() ) as $terms ) {
	$spotlight_product = Catalog::find( $products, (array) $terms );
	if ( $spotlight_product ) {
		break;
	}
}

get_header();
?>
	<?php get_template_part( 'template-parts/home/hero' ); ?>
	<?php get_template_part( 'template-parts/home/offer-strip' ); ?>
	<?php get_template_part( 'template-parts/home/features' ); ?>
	<?php get_template_part( 'template-parts/home/reviews', null, array( 'reviews' => $reviews ) ); ?>
	<?php get_template_part( 'template-parts/home/spotlight', null, array( 'spotlight' => $spotlight, 'product' => $spotlight_product ) ); ?>
	<?php get_template_part( 'template-parts/home/hot-sellers', null, array( 'products' => $hot_sellers ) ); ?>
	<?php if ( $tabs ) : ?>
		<?php get_template_part( 'template-parts/home/most-loved', null, array( 'config' => $most_loved, 'tabs' => $tabs ) ); ?>
	<?php endif; ?>
	<?php get_template_part( 'template-parts/home/instagram' ); ?>
	<?php get_template_part( 'template-parts/home/policies' ); ?>
<?php
get_footer();
