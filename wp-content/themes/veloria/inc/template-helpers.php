<?php
/**
 * Template helpers: navigation config, colour swatches, description parsing.
 * Each is a port of the matching React component / lib function so output is
 * identical. Arrays are filterable for child themes.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

use Veloria\Commerce\Catalog\Catalog;

/**
 * Catalog reads refuse partial data and throw; on the storefront a degraded
 * page (empty list) beats a critical error. The failure is logged, never cached.
 *
 * @param mixed $fallback
 * @return mixed
 */
function veloria_try( callable $read, $fallback = array() ) {
	try {
		return $read();
	} catch ( \RuntimeException $e ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( 'Storefront catalog read failed: ' . $e->getMessage(), array( 'source' => 'veloria' ) );
		}
		return $fallback;
	}
}

/**
 * Items of the menu assigned to a theme location (Appearance → Menus), as a
 * label/href/children tree. Null when nothing is assigned, so the built-in
 * defaults apply. Category and page items resolve their URL at runtime, so
 * renaming a slug never breaks the link. Empty product categories are skipped.
 *
 * @return array<int,array<string,mixed>>|null
 */
function veloria_menu_tree( string $location ): ?array {
	$locations = get_nav_menu_locations();
	$items     = empty( $locations[ $location ] ) ? array() : wp_get_nav_menu_items( (int) $locations[ $location ] );
	if ( ! $items ) {
		return null;
	}
	$by_parent = array();
	foreach ( $items as $item ) {
		if ( 'taxonomy' === $item->type && 'product_cat' === $item->object ) {
			$term = get_term( (int) $item->object_id, 'product_cat' );
			if ( ! $term instanceof WP_Term || $term->count < 1 ) {
				continue;
			}
		}
		$by_parent[ (int) $item->menu_item_parent ][] = $item;
	}
	$build = static function ( int $parent ) use ( &$build, $by_parent ): array {
		$out = array();
		foreach ( $by_parent[ $parent ] ?? array() as $item ) {
			$out[] = array(
				'label'    => (string) $item->title,
				'href'     => (string) $item->url,
				'children' => $build( (int) $item->ID ),
			);
		}
		return $out;
	};
	return $build( 0 ) ?: null;
}

/** Shipping promise text that matches the checkout rule (Veloria → Settings). */
function veloria_shipping_promise(): string {
	$cost      = (float) veloria_setting( 'standard_shipping_cost', 0 );
	$threshold = (float) veloria_setting( 'free_shipping_threshold', 0 );
	return $cost <= 0 || $threshold <= 0 ? 'Free shipping on all orders' : 'Free shipping on orders over ₹' . veloria_format_number( $threshold );
}

/**
 * Seller / grievance disclosures entered in Veloria → Settings → Legal.
 * Empty values are dropped, so nothing is shown until the owner fills them in.
 *
 * @return array<string,string>
 */
function veloria_legal(): array {
	$keys = array( 'legal_name', 'legal_address', 'gstin', 'grievance_name', 'grievance_designation', 'grievance_email', 'grievance_phone', 'country_of_origin' );
	$out  = array();
	foreach ( $keys as $key ) {
		$value = trim( (string) veloria_setting( $key, '' ) );
		if ( '' !== $value ) {
			$out[ $key ] = $value;
		}
	}
	return $out;
}

/** Default links pointing at product categories that currently have no products are hidden. */
function veloria_drop_empty_categories( array $links ): array {
	return array_values(
		array_filter(
			$links,
			static function ( array $link ): bool {
				if ( ! preg_match( '#^/product-category/([^/?]+)#', (string) $link['href'], $m ) ) {
					return true;
				}
				$term = get_term_by( 'slug', $m[1], 'product_cat' );
				return $term instanceof WP_Term && $term->count > 0;
			}
		)
	);
}

/**
 * Header navigation. Top-level menu items are links; an item with children
 * becomes a mega menu whose children are column titles and grandchildren the
 * column links.
 *
 * @return array<int,array<string,mixed>> PremiumHeader.tsx NAV_ITEMS
 */
function veloria_nav_items(): array {
	$tree = veloria_menu_tree( 'primary' );
	if ( null !== $tree ) {
		$nav = array();
		foreach ( $tree as $top ) {
			$entry = array( 'label' => $top['label'], 'href' => $top['children'] ? '#' : $top['href'] );
			if ( $top['children'] ) {
				$loose = array();
				foreach ( $top['children'] as $child ) {
					if ( $child['children'] ) {
						$entry['columns'][] = array( 'title' => $child['label'], 'items' => array_map( static fn( $g ) => array( 'label' => $g['label'], 'href' => $g['href'] ), $child['children'] ) );
					} else {
						$loose[] = array( 'label' => $child['label'], 'href' => $child['href'] );
					}
				}
				if ( $loose ) {
					$entry['columns'][] = array( 'title' => $top['label'], 'items' => $loose );
				}
			}
			$nav[] = $entry;
		}
		return apply_filters( 'veloria_nav_items', $nav );
	}
	return apply_filters( 'veloria_nav_items', veloria_default_nav_items() );
}

/** Built-in header links, also used to seed the editable menu (wp veloria seed-menus). */
function veloria_default_nav_items(): array {
	$by_style = veloria_drop_empty_categories(
		array(
			array( 'label' => 'Tote Bags', 'href' => '/product-category/tote-bag' ),
			array( 'label' => 'Sling Bags', 'href' => '/product-category/sling-bag' ),
			array( 'label' => 'Clutch Bags', 'href' => '/product-category/clutch' ),
			array( 'label' => 'Hand Bag', 'href' => '/product-category/hand-bag' ),
			array( 'label' => 'Crossbody', 'href' => '/product-category/crossbody' ),
			array( 'label' => 'Satchel Bag', 'href' => '/product-category/satchel-bag' ),
		)
	);
	return array(
		array( 'label' => 'SHOP', 'href' => '/shop' ),
		array(
			'label'   => 'COLLECTIONS',
			'href'    => '#',
			'columns' => array(
				array( 'title' => 'By Style', 'items' => $by_style ),
				array(
					'title' => 'Collections',
					'items' => array(
						array( 'label' => 'New Arrivals', 'href' => '/shop?sort=newest' ),
						array( 'label' => 'Best Sellers', 'href' => '/shop?sort=bestsellers' ),
					),
				),
			),
		),
		array( 'label' => 'ABOUT', 'href' => '/about' ),
		array( 'label' => 'CONTACT', 'href' => '/contact-us' ),
	);
}

/** Flat label/href list from a menu location, or null when none is assigned. */
function veloria_menu_links( string $location ): ?array {
	$tree = veloria_menu_tree( $location );
	return null === $tree ? null : array_map( static fn( $i ) => array( 'label' => $i['label'], 'href' => $i['href'] ), $tree );
}

/** @return array<int,array{label:string,href:string}> PremiumFooter.tsx COLLECTIONS */
function veloria_footer_collections(): array {
	return apply_filters( 'veloria_footer_collections', veloria_menu_links( 'footer' ) ?? veloria_drop_empty_categories( veloria_default_footer_collections() ) );
}

function veloria_default_footer_collections(): array {
	return array(
		array( 'label' => 'Tote Bags', 'href' => '/product-category/tote-bag' ),
		array( 'label' => 'Satchel Bags', 'href' => '/product-category/satchel-bag' ),
		array( 'label' => 'Sling Bags', 'href' => '/product-category/sling-bag' ),
		array( 'label' => 'Crossbody', 'href' => '/product-category/crossbody' ),
		array( 'label' => 'Clutch', 'href' => '/product-category/clutch' ),
		array( 'label' => 'Wallet', 'href' => '/product-category/wallet' ),
	);
}

/** @return array<int,array{label:string,href:string}> PremiumFooter.tsx IMPORTANT_LINKS */
function veloria_footer_links(): array {
	return apply_filters( 'veloria_footer_links', veloria_menu_links( 'footer-links' ) ?? veloria_default_footer_links() );
}

function veloria_default_footer_links(): array {
	return apply_filters(
		'veloria_default_footer_links',
		array(
			array( 'label' => 'Track Your Order', 'href' => '/track-order' ),
			array( 'label' => 'Refund & Cancellation', 'href' => '/cancellation-refund' ),
			array( 'label' => 'Privacy Policy', 'href' => '/privacy-policy' ),
			array( 'label' => 'Shipping Policy', 'href' => '/shipping-policy' ),
			array( 'label' => 'Return Policy', 'href' => '/refund-returns' ),
			array( 'label' => 'Warranty Policy', 'href' => '/warranty-policy' ),
			array( 'label' => 'Terms & Conditions', 'href' => '/terms-conditions' ),
		)
	);
}

/** @return array<int,array{slug:string,label:string}> Shop / category tabs. */
function veloria_category_tabs(): array {
	$tabs = array(
		array( 'slug' => 'tote-bag', 'label' => 'Tote' ),
		array( 'slug' => 'satchel-bag', 'label' => 'Satchel' ),
		array( 'slug' => 'sling-bag', 'label' => 'Sling' ),
		array( 'slug' => 'crossbody', 'label' => 'Crossbody' ),
		array( 'slug' => 'clutch', 'label' => 'Clutch' ),
		array( 'slug' => 'wallet', 'label' => 'Wallet' ),
	);
	$tabs = array_values(
		array_filter(
			$tabs,
			static function ( array $tab ): bool {
				$term = get_term_by( 'slug', $tab['slug'], 'product_cat' );
				return $term instanceof WP_Term && $term->count > 0;
			}
		)
	);
	return apply_filters( 'veloria_category_tabs', $tabs );
}

/** Site-relative URL (keeps markup identical to the Next.js links). */
function veloria_url( string $path ): string {
	return str_starts_with( $path, '#' ) || preg_match( '#^https?://#', $path ) ? $path : home_url( $path );
}

/** Port of src/lib/swatches.ts. @return array<int,array<string,string>> */
function veloria_swatches( array $product ): array {
	$hex_map = array(
		'black'           => '#1a1a1a',
		'white'           => '#fafafa',
		'brown'           => '#6f4e37',
		'chocolate brown' => '#3e2723',
		'camel'           => '#c19a6b',
		'tan'             => '#d2b48c',
		'beige'           => '#d8c9a3',
		'cherry'          => '#9b1c2e',
		'red'             => '#b22222',
		'burgundy'        => '#5d1a1a',
		'maroon'          => '#5d1a1a',
		'green'           => '#4f7942',
		'olive'           => '#6b7a3a',
		'teal green'      => '#1f6f6a',
		'teal'            => '#1f6f6a',
		'yellow'          => '#d4a017',
		'gold'            => '#caa33d',
		'blue'            => '#27496d',
		'navy'            => '#1c2541',
		'pink'            => '#d8a7b1',
		'rose'            => '#c08081',
		'grey'            => '#808080',
		'gray'            => '#808080',
		'silver'          => '#c0c0c0',
		'charry'          => '#9b1c2e',
	);
	$keys = array_keys( $hex_map );
	usort( $keys, static fn( $a, $b ) => strlen( $b ) <=> strlen( $a ) );

	$hex_for = static function ( string $term ) use ( $hex_map, $keys ): string {
		$lower = strtolower( trim( $term ) );
		foreach ( $keys as $key ) {
			if ( $lower === $key || str_ends_with( $lower, ' ' . $key ) || str_contains( $lower, ' ' . $key . ' ' ) ) {
				return $hex_map[ $key ];
			}
		}
		$words = preg_split( '/\s+/', $lower ) ?: array();
		$last  = end( $words );
		return ( $last && isset( $hex_map[ $last ] ) ) ? $hex_map[ $last ] : '#cccccc';
	};

	$label_for = static function ( string $term ) use ( $product ): string {
		$words = array_filter( preg_split( '/\s+/', strtolower( (string) $product['name'] ) ) ?: array(), static fn( $w ) => $w && 'the' !== $w && 'a' !== $w );
		$label = $term;
		foreach ( $words as $word ) {
			$label = (string) preg_replace( '/\b' . preg_quote( $word, '/' ) . '\b/i', '', $label );
		}
		$label = trim( (string) preg_replace( '/\s+/', ' ', $label ) );
		return '' !== $label ? $label : $term;
	};

	$attribute = null;
	foreach ( (array) $product['attributes'] as $attr ) {
		$name = strtolower( (string) $attr['name'] );
		if ( 'pa_color' === $attr['slug'] || str_contains( $name, 'color' ) || str_contains( $name, 'colour' ) ) {
			$attribute = $attr;
			break;
		}
	}
	if ( ! $attribute ) {
		return array();
	}
	$terms = ! empty( $attribute['terms'] ) ? $attribute['terms'] : array_map(
		static fn( $n ) => array( 'name' => $n, 'slug' => trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( $n ) ), '-' ) ),
		(array) ( $attribute['options'] ?? array() )
	);

	return array_map(
		static fn( $t ) => array(
			'name'  => (string) $t['name'],
			'label' => $label_for( (string) $t['name'] ),
			'slug'  => (string) $t['slug'],
			'hex'   => ( ! empty( $t['id'] ) ? sanitize_hex_color( get_term_meta( (int) $t['id'], 'veloria_hex', true ) ) : null ) ?: $hex_for( (string) $t['name'] ),
			'href'  => '/product/' . $product['slug'] . '?attribute_pa_color=' . rawurlencode( (string) $t['slug'] ),
		),
		$terms
	);
}

/**
 * Port of ProductAccordion.tsx parseDescriptionSections().
 *
 * @return array<int,array{title:string,content:string}>
 */
function veloria_description_sections( string $description ): array {
	if ( '' === trim( $description ) ) {
		return array();
	}
	$markers = array(
		array( '/size\s*(?:and|&)\s*material/i', 'Size and Material' ),
		array( '/return\s*(?:and|&)\s*exchange/i', 'Return & Exchange' ),
		array( '/warranty/i', 'Warranty Policy' ),
		array( '/ethic(?:al(?:ly)?|s)\s*(?:made|production)?/i', 'Ethical Production' ),
		array( '/more\s*information/i', 'More Information' ),
	);
	$hidden = array( 'Warranty Policy' );
	$links  = (array) veloria_setting( 'policy_links', array() );

	$strip = static function ( string $html ): string {
		$html = (string) preg_replace( '/<p>\s*<\/p>/i', '', $html );
		$html = (string) preg_replace( '/<br\s*\/?>\s*<br\s*\/?>/i', '<br/>', $html );
		return trim( $html );
	};
	$linkify = static function ( string $html ) use ( $links ): string {
		if ( ! empty( $links['returnExchange'] ) ) {
			$html = (string) preg_replace( '/(return\s*(?:and|&)\s*exchange\s*policy)/i', '<a href="' . esc_url( $links['returnExchange'] ) . '" class="underline hover:text-[#b89a57]">$1</a>', $html );
		}
		if ( ! empty( $links['warranty'] ) ) {
			$html = (string) preg_replace( '/(warranty\s*policy)/i', '<a href="' . esc_url( $links['warranty'] ) . '" class="underline hover:text-[#b89a57]">$1</a>', $html );
		}
		return $html;
	};

	$cleaned   = $strip( $description );
	$sections  = array();
	$remaining = $cleaned;
	$intro     = '';

	foreach ( $markers as $marker ) {
		if ( ! preg_match( $marker[0], $remaining, $m ) ) {
			continue;
		}
		$split = strpos( $remaining, $m[0] );
		if ( $split > 0 && '' === $intro ) {
			$intro = substr( $remaining, 0, $split );
		} elseif ( $split > 0 && $sections ) {
			$sections[ count( $sections ) - 1 ]['content'] = $strip( substr( $remaining, 0, $split ) );
		}
		$remaining = substr( $remaining, $split + strlen( $m[0] ) );

		$next = strlen( $remaining );
		foreach ( $markers as $next_marker ) {
			if ( preg_match( $next_marker[0], $remaining, $nm ) ) {
				$idx = strpos( $remaining, $nm[0] );
				if ( $idx > 0 && $idx < $next ) {
					$next = $idx;
				}
			}
		}
		$sections[] = array( 'title' => $marker[1], 'content' => $strip( substr( $remaining, 0, $next ) ) );
		$remaining  = substr( $remaining, $next );
	}

	$result = array();
	$main   = '' !== $intro ? $intro : ( ! $sections ? $cleaned : '' );
	if ( '' !== trim( $main ) ) {
		$result[] = array( 'title' => 'Description', 'content' => $strip( $main ) );
	}
	foreach ( $sections as $section ) {
		if ( '' !== trim( $section['content'] ) && ! in_array( $section['title'], $hidden, true ) ) {
			$result[] = array( 'title' => $section['title'], 'content' => $linkify( $section['content'] ) );
		}
	}
	if ( ! $result && '' !== trim( $cleaned ) ) {
		$result[] = array( 'title' => 'Description', 'content' => $cleaned );
	}
	return $result;
}

/** Card data handed to Alpine for cart/wishlist (same fields as CartItem). */
function veloria_item_payload( array $product ): array {
	$price = (float) ( (float) $product['price'] ?: (float) $product['regular_price'] );
	return array(
		'id'       => (int) $product['id'],
		'name'     => (string) $product['name'],
		'slug'     => (string) $product['slug'],
		'href'     => Catalog::relative_link( $product ),
		'price'    => $price,
		'image'    => (string) ( $product['images'][0]['src'] ?? '' ),
		'category' => (string) ( $product['categories'][0]['name'] ?? 'Luxury Bag' ),
		'type'     => (string) $product['type'],
	);
}

/**
 * Classes that switch with Alpine state but must be right on first paint.
 *
 * Returns the initial classes (to put in the static class attribute) and an
 * object-syntax :class binding. Object syntax lets Alpine remove a class that
 * was rendered server-side, which the string syntax never does.
 *
 * @return array{0:string,1:string} [initial classes, ' :class="{...}"' attribute]
 */
function veloria_toggle( string $expr, string $on, string $off, bool $initially_on ): array {
	$binding = sprintf(
		' :class="{ %s: %s, %s: !(%s) }"',
		"'" . esc_attr( $on ) . "'",
		esc_attr( $expr ),
		"'" . esc_attr( $off ) . "'",
		esc_attr( $expr )
	);
	return array( $initially_on ? $on : $off, $binding );
}

/** JSON for an x-data attribute. */
function veloria_json_attr( $data ): string {
	return esc_attr( (string) wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
}

/** <img> for a catalog image array (src + srcset from the Store API). */
function veloria_catalog_img( ?array $image, string $class, string $sizes = '100vw', bool $eager = false, string $alt = '' ): string {
	$src = $image['src'] ?? veloria_theme_asset( 'images/bag-placeholder.svg' );
	return sprintf(
		'<img src="%s"%s sizes="%s" alt="%s" class="%s" loading="%s" decoding="async"%s>',
		esc_url( $src ),
		! empty( $image['srcset'] ) ? ' srcset="' . esc_attr( $image['srcset'] ) . '"' : '',
		esc_attr( $sizes ),
		esc_attr( '' !== $alt ? $alt : (string) ( $image['alt'] ?? '' ) ),
		esc_attr( $class ),
		$eager ? 'eager' : 'lazy',
		$eager ? ' fetchpriority="high"' : ''
	);
}
