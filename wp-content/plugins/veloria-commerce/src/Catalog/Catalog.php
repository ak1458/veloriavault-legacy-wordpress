<?php
/**
 * Catalog read layer: the same WooCommerce Store API the headless site
 * consumed, called in-process with rest_do_request() (no HTTP), mapped to the
 * same product shape as src/lib/woocommerce.ts (mapStoreProduct etc.).
 *
 * Using the Store API means listings, ordering, prices, variation galleries
 * (added by the theme/gallery plugin through Store API filters) and stock
 * flags are byte-for-byte what the headless storefront showed.
 *
 * Results are cached for five minutes (the headless ISR window) and the cache
 * is invalidated whenever a product, stock level, category or review changes.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Catalog;

defined( 'ABSPATH' ) || exit;

final class Catalog {

	private const TTL           = 5 * MINUTE_IN_SECONDS;
	private const VERSION_KEY   = 'veloria_catalog_version';

	private static ?Catalog $instance = null;

	/** @var array<string,mixed> */
	private array $memo = array();
	private array $page_totals = array();

	public static function instance(): Catalog {
		return self::$instance ??= new Catalog();
	}

	public static function register_cache_busting(): void {
		$bump = array( self::class, 'bump' );
		foreach ( array(
			'woocommerce_update_product',
			'woocommerce_new_product',
			'woocommerce_delete_product',
			'woocommerce_trash_product',
			'woocommerce_update_product_variation',
			'woocommerce_product_set_stock',
			'woocommerce_variation_set_stock',
			'woocommerce_product_set_stock_status',
			'woocommerce_variation_set_stock_status',
			'edited_product_cat',
			'created_product_cat',
			'delete_product_cat',
			'wp_update_comment_count',
		) as $hook ) {
			add_action( $hook, $bump );
		}
		add_action( 'transition_comment_status', $bump );
	}

	public static function bump(): void {
		update_option( self::VERSION_KEY, (string) microtime( true ), false );
		self::instance()->memo = array();
		self::instance()->page_totals = array();
	}

	// ── Store API access ────────────────────────────────────────────────

	/**
	 * @param array<string,scalar> $params
	 * @return mixed Decoded response data, or null on error.
	 */
	public function store( string $route, array $params = array(), bool $fresh = false ) {
		$params = array_filter( $params, static fn( $v ) => '' !== $v && null !== $v );
		ksort( $params );
		$key = 'veloria_cat_' . md5( $route . '|' . wp_json_encode( $params ) . '|' . get_option( self::VERSION_KEY, '1' ) );
		$collection = $route . '|' . wp_json_encode( array_diff_key( $params, array( 'page' => true ) ) );

		if ( ! $fresh ) {
			if ( array_key_exists( $key, $this->memo ) ) {
				return $this->memo[ $key ];
			}
			$cached = get_transient( $key );
			if ( false !== $cached ) {
				$total = get_transient( $key . '_pages' );
				if ( false !== $total ) {
					$this->page_totals[ $collection ] = (int) $total;
				}
				return $this->memo[ $key ] = $cached;
			}
		}

		// Pass parameters as strings, like a real query string, so schema validation
		// treats them exactly as it did for the headless site's HTTP requests.
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1' . $route );
		$request->set_query_params( array_map( 'strval', $params ) );
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return null;
		}
		// Normalise to plain arrays (the Store API returns nested stdClass objects),
		// i.e. exactly what the headless site got from json.parse().
		$data = json_decode( (string) wp_json_encode( rest_get_server()->response_to_data( $response, false ) ), true );
		$headers = array_change_key_case( $response->get_headers(), CASE_LOWER );
		if ( isset( $headers['x-wp-totalpages'] ) && is_numeric( $headers['x-wp-totalpages'] ) ) {
			$this->page_totals[ $collection ] = (int) $headers['x-wp-totalpages'];
			set_transient( $key . '_pages', (int) $headers['x-wp-totalpages'], self::TTL );
		}
		set_transient( $key, $data, self::TTL );
		return $this->memo[ $key ] = $data;
	}

	/** Read a complete Store API collection. Never silently return a partial catalog. */
	public function store_all( string $route, array $params = array() ): array {
		$rows = array();
		$seen = array();
		$query = array_filter( array_merge( $params, array( 'per_page' => 100 ) ), static fn( $v ) => '' !== $v && null !== $v );
		unset( $query['page'] );
		ksort( $query );
		$collection = $route . '|' . wp_json_encode( $query );
		for ( $page = 1; $page <= 1000; $page++ ) {
			$data = $this->store( $route, array_merge( $params, array( 'per_page' => 100, 'page' => $page ) ) );
			if ( ! is_array( $data ) || ! array_is_list( $data ) ) {
				throw new \RuntimeException( 'The store catalog could not be loaded completely.' );
			}
			if ( $data ) {
				$fingerprint = hash( 'sha256', (string) wp_json_encode( $data ) );
				if ( isset( $seen[ $fingerprint ] ) ) {
					throw new \RuntimeException( 'The Store API repeated a catalog page.' );
				}
				$seen[ $fingerprint ] = true;
			}
			$rows = array_merge( $rows, $data );
			$total = $this->page_totals[ $collection ] ?? null;
			if ( null !== $total && $page < $total && count( $data ) < 100 ) {
				throw new \RuntimeException( 'The Store API returned an incomplete catalog page.' );
			}
			if ( count( $data ) < 100 || ( null !== $total && $page >= $total ) ) {
				return $rows;
			}
		}
		throw new \RuntimeException( 'Catalog pagination exceeded its safety limit; no partial catalog was returned.' );
	}

	// ── Mapping (port of mapStoreProduct / mapStoreReview) ──────────────

	private static function fallback_slug( string $value ): string {
		$value = strtolower( str_replace( '&', ' and ', $value ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value ) ?? '';
		return trim( $value, '-' );
	}

	private static function minor( ?string $value, int $minor_unit ): string {
		$parsed = (int) ( $value ?? 0 );
		return number_format( $parsed / ( 10 ** $minor_unit ), $minor_unit, '.', '' );
	}

	/** @return array<int,array<string,mixed>> */
	private static function parse_variation( ?string $variation ): array {
		if ( ! $variation ) {
			return array();
		}
		$attributes = array();
		foreach ( preg_split( '/\s*,\s*/', $variation ) ?: array() as $segment ) {
			$parts  = explode( ':', $segment );
			$name   = trim( (string) array_shift( $parts ) );
			$option = trim( implode( ':', $parts ) );
			if ( '' === $name || '' === $option ) {
				continue;
			}
			$attributes[] = array(
				'id'        => 0,
				'name'      => $name,
				'slug'      => 'color' === strtolower( $name ) ? 'pa_color' : self::fallback_slug( $name ),
				'option'    => $option,
				'options'   => array( $option ),
				'variation' => true,
				'visible'   => true,
			);
		}
		return $attributes;
	}

	/**
	 * @param array<string,mixed> $image
	 * @return array<string,mixed>|null
	 */
	private static function map_image( $image, string $fallback_alt = '' ): ?array {
		if ( ! is_array( $image ) || empty( $image['src'] ) ) {
			return null;
		}
		return array(
			'id'     => (int) ( $image['id'] ?? 0 ),
			'src'    => (string) $image['src'],
			'alt'    => (string) ( $image['alt'] ?: $fallback_alt ),
			'name'   => (string) ( $image['name'] ?? '' ),
			'srcset'    => (string) ( $image['srcset'] ?? '' ),
			'sizes'     => (string) ( $image['sizes'] ?? '' ),
			'thumbnail' => (string) ( $image['thumbnail'] ?? '' ) ?: (string) $image['src'],
		);
	}

	/**
	 * @param array<string,mixed> $p Store API product.
	 * @return array<string,mixed>
	 */
	public static function map_product( array $p ): array {
		$name   = (string) ( $p['name'] ?? '' );
		$images = array_values( array_filter( array_map( static fn( $i ) => self::map_image( $i, html_entity_decode( $name ) ), (array) ( $p['images'] ?? array() ) ) ) );
		$minor  = (int) ( $p['prices']['currency_minor_unit'] ?? 2 );

		$var_attrs = self::parse_variation( $p['variation'] ?? null );
		if ( $var_attrs ) {
			$attributes   = $var_attrs;
			$display_name = $name . ' - ' . implode( ' / ', array_column( $var_attrs, 'option' ) );
		} else {
			$display_name = $name;
			$attributes   = array_map(
				static fn( $a ) => array(
					'id'        => (int) ( $a['id'] ?? 0 ),
					'name'      => (string) $a['name'],
					'slug'      => (string) ( $a['taxonomy'] ?: self::fallback_slug( (string) $a['name'] ) ),
					'option'    => '',
					'options'   => array_map( static fn( $t ) => (string) $t['name'], (array) ( $a['terms'] ?? array() ) ),
					'terms'     => array_map(
						static fn( $t ) => array( 'id' => (int) $t['id'], 'name' => (string) $t['name'], 'slug' => (string) $t['slug'] ),
						(array) ( $a['terms'] ?? array() )
					),
					'variation' => (bool) ( $a['has_variations'] ?? false ),
					'visible'   => true,
				),
				(array) ( $p['attributes'] ?? array() )
			);
		}

		$in_stock = ! empty( $p['is_in_stock'] );
		$parent   = (int) ( $p['parent'] ?? 0 );

		return array(
			'id'                => (int) $p['id'],
			'name'              => html_entity_decode( $display_name, ENT_QUOTES ),
			'slug'              => (string) ( $p['slug'] ?? '' ),
			'permalink'         => (string) ( $p['permalink'] ?? '' ),
			'type'              => (string) ( $p['type'] ?? ( $parent ? 'variation' : 'variable' ) ),
			'parent_id'         => $parent,
			'has_own_image'     => $parent ? ( function_exists( 'get_post_meta' ) && (int) get_post_meta( (int) $p['id'], '_thumbnail_id', true ) > 0 ) : ! empty( $images ),
			'description'       => (string) ( $p['description'] ?? '' ),
			'short_description' => (string) ( $p['short_description'] ?? '' ),
			'price'             => self::minor( $p['prices']['price'] ?? '0', $minor ),
			'regular_price'     => self::minor( $p['prices']['regular_price'] ?? '0', $minor ),
			'sale_price'        => self::minor( $p['prices']['sale_price'] ?? '0', $minor ),
			'on_sale'           => (bool) ( $p['on_sale'] ?? false ),
			'image'             => $images[0] ?? null,
			'images'            => $images,
			'categories'        => array_map(
				static fn( $c ) => array(
					'id'    => (int) $c['id'],
					'name'  => html_entity_decode( (string) $c['name'], ENT_QUOTES ),
					'slug'  => (string) $c['slug'],
					'count' => (int) ( $c['count'] ?? 0 ),
				),
				(array) ( $p['categories'] ?? array() )
			),
			'average_rating'    => (string) ( $p['average_rating'] ?? '0' ),
			'rating_count'      => (int) ( $p['review_count'] ?? 0 ),
			'stock_status'      => $in_stock ? ( ! empty( $p['is_on_backorder'] ) ? 'onbackorder' : 'instock' ) : 'outofstock',
			'stock_quantity'    => $p['low_stock_remaining'] ?? null,
			'max_qty'           => (int) ( $p['add_to_cart']['maximum'] ?? 0 ),
			'sku'               => (string) ( $p['sku'] ?? '' ),
			'attributes'        => $attributes,
			'variations'        => (array) ( $p['variations'] ?? array() ),
			'price_html'        => (string) ( $p['price_html'] ?? '' ),
		);
	}

	/**
	 * @param array<string,mixed> $r
	 * @return array<string,mixed>
	 */
	public static function map_review( array $r ): array {
		return array(
			'id'                    => (int) $r['id'],
			'date_created'          => (string) ( $r['date_created'] ?? '' ),
			'formatted_date_created'=> (string) ( $r['formatted_date_created'] ?? '' ),
			'date_created_gmt'      => (string) ( $r['date_created_gmt'] ?? '' ),
			'product_id'            => (int) ( $r['product_id'] ?? 0 ),
			'product_name'          => (string) ( $r['product_name'] ?? '' ),
			'product_permalink'     => (string) ( $r['product_permalink'] ?? '' ),
			'reviewer'              => (string) ( $r['reviewer'] ?: 'Veloria Customer' ),
			'review'                => (string) ( $r['review'] ?? '' ),
			'rating'                => (int) ( $r['rating'] ?? 0 ),
			'verified'              => (bool) ( $r['verified'] ?? false ),
		);
	}

	// ── Queries (same calls as the headless data layer) ─────────────────

	/** @return array<int,array<string,mixed>> */
	public function parent_products( ?string $category_slug = null, string $search = '' ): array {
		$data     = $this->store_all( '/products', array( 'search' => $search ) );
		$products = array();
		foreach ( (array) $data as $p ) {
			if ( ! is_array( $p ) || ! empty( $p['parent'] ) || 'variation' === ( $p['type'] ?? '' ) ) {
				continue;
			}
			$mapped = self::map_product( $p );
			if ( $category_slug && ! in_array( $category_slug, array_column( $mapped['categories'], 'slug' ), true ) ) {
				continue;
			}
			$products[] = $mapped;
		}
		return $products;
	}

	/**
	 * Generic listing (getProducts in the headless lib).
	 *
	 * @param array<string,scalar> $params
	 * @return array<int,array<string,mixed>>
	 */
	public function products( array $params ): array {
		$data = $this->store( '/products', $params + array( 'per_page' => 20, 'page' => 1 ) );
		return array_values( array_map( array( self::class, 'map_product' ), array_filter( (array) $data, 'is_array' ) ) );
	}

	/** @return array<string,mixed>|null */
	public function product( int $id, bool $fresh = false ): ?array {
		$data = $this->store( '/products/' . $id, array(), $fresh );
		return is_array( $data ) && isset( $data['id'] ) ? self::map_product( $data ) : null;
	}

	/**
	 * @param int[] $ids
	 * @return array<int,array<string,mixed>>
	 */
	public function products_by_ids( array $ids ): array {
		return array_values( array_filter( array_map( fn( $id ) => $this->product( (int) $id ), $ids ) ) );
	}

	/** @return array<int,array<string,mixed>> */
	public function variations( int $parent_id ): array {
		// Explicit stock_status bypasses "Hide out of stock items": sold-out colours stay visible (disabled), as on the headless site.
		$data = $this->store_all( '/products', array( 'parent' => $parent_id, 'type' => 'variation', 'stock_status' => 'instock,outofstock,onbackorder', 'orderby' => 'menu_order', 'order' => 'asc' ) );
		$out  = array();
		foreach ( (array) $data as $v ) {
			if ( is_array( $v ) && (int) ( $v['parent'] ?? 0 ) === $parent_id ) {
				$out[] = self::map_product( $v );
			}
		}
		return $out;
	}

	/** Categories that have products (getCategories). @return array<int,array<string,mixed>> */
	public function categories(): array {
		$data = $this->store_all( '/products/categories' );
		$out  = array();
		foreach ( (array) $data as $c ) {
			if ( is_array( $c ) && (int) ( $c['count'] ?? 0 ) > 0 ) {
				$out[] = array(
					'id'          => (int) $c['id'],
					'name'        => html_entity_decode( (string) $c['name'], ENT_QUOTES ),
					'slug'        => (string) $c['slug'],
					'description' => (string) ( $c['description'] ?? '' ),
					'count'       => (int) $c['count'],
				);
			}
		}
		return $out;
	}

	/** @return array<int,array<string,mixed>> */
	public function reviews( int $product_id = 0, int $per_page = 10, bool $all = false ): array {
		$params = array( 'per_page' => $per_page, 'page' => 1 );
		if ( $product_id ) {
			$params['product_id'] = $product_id;
		}
		$data    = $all ? $this->store_all( '/products/reviews', $params ) : $this->store( '/products/reviews', $params );
		$reviews = array_map( array( self::class, 'map_review' ), array_filter( (array) $data, 'is_array' ) );
		if ( $product_id ) {
			$reviews = array_filter( $reviews, static fn( $r ) => $r['product_id'] === $product_id );
		}
		return array_values( $reviews );
	}

	/**
	 * Media attached to a review by "Customer Reviews for WooCommerce".
	 * Read straight from comment meta instead of scraping product HTML.
	 *
	 * @return array<int,array{type:string,url:string,thumbnail:string}>
	 */
	public static function review_media( int $review_id ): array {
		$media = array();
		$seen  = array();
		$add   = static function ( string $type, string $url, string $thumb ) use ( &$media, &$seen ): void {
			if ( '' === $url || isset( $seen[ $url ] ) ) {
				return;
			}
			$seen[ $url ] = true;
			$media[]      = array( 'type' => $type, 'url' => $url, 'thumbnail' => $thumb ?: $url );
		};

		foreach ( (array) get_comment_meta( $review_id, 'ivole_review_image2', false ) as $attachment_id ) {
			$url = wp_get_attachment_url( (int) $attachment_id );
			if ( $url ) {
				$add( 'image', $url, (string) wp_get_attachment_image_url( (int) $attachment_id, 'thumbnail' ) );
			}
		}
		foreach ( (array) get_comment_meta( $review_id, 'ivole_review_image', false ) as $legacy ) {
			if ( is_array( $legacy ) && ! empty( $legacy['url'] ) ) {
				$add( 'image', (string) $legacy['url'], '' );
			}
		}
		foreach ( (array) get_comment_meta( $review_id, 'ivole_review_video2', false ) as $attachment_id ) {
			$url = wp_get_attachment_url( (int) $attachment_id );
			if ( $url ) {
				$add( 'video', $url, $url );
			}
		}
		foreach ( (array) get_comment_meta( $review_id, 'ivole_review_video', false ) as $legacy ) {
			if ( is_array( $legacy ) && ! empty( $legacy['url'] ) ) {
				$add( 'video', (string) $legacy['url'], '' );
			}
		}

		/**
		 * Filter review media (for other review plugins).
		 *
		 * @param array $media
		 * @param int   $review_id
		 */
		return apply_filters( 'veloria_review_media', $media, $review_id );
	}

	/**
	 * Review bundle for the product page (getProductReviewBundle).
	 *
	 * @param array<string,mixed> $product
	 * @return array<string,mixed>
	 */
	public function review_bundle( array $product ): array {
		$reviews = array();
		$media   = array();
		foreach ( $this->reviews( (int) $product['id'], 100, true ) as $review ) {
			$items = array();
			foreach ( self::review_media( $review['id'] ) as $i => $m ) {
				$items[] = $m + array(
					'id'        => $review['id'] . '-' . ( $i + 1 ),
					'reviewId'  => $review['id'],
					'alt'       => 'Customer review media ' . ( $i + 1 ),
					'reviewer'  => $review['reviewer'],
					'createdAt' => $review['date_created'] ?: $review['date_created_gmt'],
				);
			}
			$review['media']      = $items;
			$review['date_label'] = $review['formatted_date_created'] ?: ( $review['date_created'] ? wp_date( 'j F Y', (int) strtotime( $review['date_created'] ) ) : '' );
			$reviews[]            = $review;
			$media                = array_merge( $media, $items );
		}
		usort( $media, static fn( $a, $b ) => strtotime( (string) $b['createdAt'] ) <=> strtotime( (string) $a['createdAt'] ) );

		$count = count( $reviews ) ?: (int) $product['rating_count'];
		if ( $reviews ) {
			$average = round( array_sum( array_column( $reviews, 'rating' ) ) / $count, 2 );
		} else {
			$average = (float) $product['average_rating'];
		}

		$histogram = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 );
		foreach ( $reviews as $review ) {
			$rating = max( 1, min( 5, (int) round( (float) $review['rating'] ) ) );
			$histogram[ $rating ]++;
		}

		return array(
			'reviews'       => $reviews,
			'averageRating' => $average,
			'reviewCount'   => $count,
			'histogram'     => $histogram,
			'media'         => $media,
		);
	}

	// ── Helpers shared by templates ─────────────────────────────────────

	/** Relative link incl. ?attribute_pa_color for variations (getRelativeProductLink). */
	public static function relative_link( array $product ): string {
		$permalink = (string) ( $product['permalink'] ?? '' );
		if ( '' !== $permalink ) {
			$parts = wp_parse_url( $permalink );
			if ( ! empty( $parts['path'] ) ) {
				$path = untrailingslashit( $parts['path'] ) ?: '/';
				return $path . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
			}
		}
		return '/product/' . $product['slug'];
	}

	/** Query value of the colour attribute on a variation (getVariationQueryValue). */
	public static function variation_query_value( array $product ): ?string {
		$permalink = (string) ( $product['permalink'] ?? '' );
		if ( '' !== $permalink ) {
			$query = (string) wp_parse_url( $permalink, PHP_URL_QUERY );
			parse_str( $query, $args );
			if ( ! empty( $args['attribute_pa_color'] ) ) {
				return (string) $args['attribute_pa_color'];
			}
			return null;
		}
		foreach ( (array) $product['attributes'] as $attribute ) {
			if ( 'pa_color' === $attribute['slug'] && ! empty( $attribute['option'] ) ) {
				return self::fallback_slug( (string) $attribute['option'] );
			}
		}
		return null;
	}

	public static function color_name( ?array $product ): string {
		if ( ! $product ) {
			return '';
		}
		$colour  = '';
		$options = array();
		foreach ( (array) $product['attributes'] as $attribute ) {
			$option = (string) ( $attribute['option'] ?? '' );
			if ( '' === $option ) {
				continue;
			}
			$options[] = $option;
			$name      = strtolower( (string) $attribute['name'] );
			if ( 'pa_color' === $attribute['slug'] || str_contains( $name, 'color' ) || str_contains( $name, 'colour' ) ) {
				$colour = $option;
			}
		}
		// Colour-only products keep the colour label; a Size (or Colour + Size)
		// variation gets "Black / Large" so every variation stays selectable.
		if ( count( $options ) > 1 ) {
			return implode( ' / ', $options );
		}
		return '' !== $colour ? $colour : ( $options[0] ?? '' );
	}

	public static function selector_label( array $product ): string {
		$names = array();
		foreach ( (array) $product['attributes'] as $attribute ) {
			if ( ! empty( $attribute['variation'] ) || ! empty( $attribute['option'] ) ) $names[] = $attribute['name'];
		}
		return $names ? implode( ' / ', array_unique( $names ) ) : 'Option';
	}

	/** Prefer a real colour photo, keeping stock ahead of image matching. */
	public static function default_variation_id( array $variations, string $lead_image ): ?int {
		foreach ( array( true, false ) as $own_image ) {
			$candidates = array_filter( $variations, static fn( $v ) => 'outofstock' !== $v['stock_status'] && ( ! $own_image || ! empty( $v['has_own_image'] ) ) );
			foreach ( $candidates as $v ) {
				if ( $lead_image && $v['leadImage'] === $lead_image ) return (int) $v['id'];
			}
			if ( $candidates ) return (int) reset( $candidates )['id'];
		}
		return $variations ? (int) $variations[0]['id'] : null;
	}

	/** Port of findProduct(): first product whose name/slug/permalink contains all terms. */
	public static function find( array $products, array $terms ): ?array {
		foreach ( $products as $product ) {
			$haystack = strtolower( $product['name'] . ' ' . $product['slug'] . ' ' . $product['permalink'] );
			$ok       = true;
			foreach ( $terms as $term ) {
				if ( ! str_contains( $haystack, strtolower( (string) $term ) ) ) {
					$ok = false;
					break;
				}
			}
			if ( $ok && $terms ) {
				return $product;
			}
		}
		return null;
	}
}
