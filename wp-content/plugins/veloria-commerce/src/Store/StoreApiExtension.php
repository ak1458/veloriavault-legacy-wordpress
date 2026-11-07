<?php
/**
 * Adds storefront data to Store API cart items under `extensions.veloria`:
 * display name ("Aria Tote - Aria Black"), category name and the relative
 * product link with the colour preselected. Uses the official
 * ExtendSchema API, so it survives WooCommerce updates.
 *
 * Also exposes the discount calculation on the cart under
 * `extensions.veloria.calculation`.
 *
 * Store API order placement (POST /wc/store/v1/checkout) is refused: Veloria's
 * coupon-usage, COD/prepaid pricing, total and address checks run only on the
 * classic checkout, and Cart/Checkout Blocks are unsupported.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Store;

use Veloria\Commerce\Pricing\CartPricing;

defined( 'ABSPATH' ) || exit;

final class StoreApiExtension {

	public static function register(): void {
		add_filter( 'rest_pre_dispatch', array( self::class, 'block_store_checkout' ), 10, 3 );
		add_action( 'woocommerce_blocks_loaded', array( self::class, 'extend' ) );
		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			self::extend();
		}
	}

	/**
	 * @param mixed            $result
	 * @param \WP_REST_Server  $server
	 * @param \WP_REST_Request $request
	 * @return mixed
	 */
	public static function block_store_checkout( $result, $server, $request ) {
		if ( null !== $result || ! $request instanceof \WP_REST_Request || 'GET' === $request->get_method() ) {
			return $result;
		}
		if ( ! preg_match( '#^/wc/store(/v\d+)?/checkout(/|$)#', (string) $request->get_route() ) ) {
			return $result;
		}
		if ( apply_filters( 'veloria_allow_store_api_checkout', false, $request ) ) {
			return $result;
		}
		return new \WP_Error( 'veloria_checkout_unsupported', 'Please complete your order on the checkout page.', array( 'status' => 403 ) );
	}

	/**
	 * Batch sub-requests pass a raw WP_Error through rest_post_dispatch; core's
	 * WP_REST_Response-typed connectors filter (priority 10) then fatals. Convert first.
	 *
	 * @param mixed $response
	 * @return mixed
	 */
	public static function batch_error_response( $response ) {
		return is_wp_error( $response ) ? rest_convert_error_to_response( $response ) : $response;
	}

	public static function extend(): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::IDENTIFIER,
				'namespace'       => 'veloria',
				'data_callback'   => array( self::class, 'cart_item_data' ),
				'schema_callback' => static fn() => array(
					'displayName' => array( 'type' => 'string', 'readonly' => true ),
					'category'    => array( 'type' => 'string', 'readonly' => true ),
					'href'        => array( 'type' => 'string', 'readonly' => true ),
					'image'       => array( 'type' => 'string', 'readonly' => true ),
				),
				'schema_type'     => ARRAY_A,
			)
		);

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
				'namespace'       => 'veloria',
				'data_callback'   => static fn() => array( 'calculation' => CartPricing::calculation_for_cart() ),
				'schema_callback' => static fn() => array(
					'calculation' => array( 'type' => 'object', 'readonly' => true ),
				),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * @param array<string,mixed> $cart_item
	 * @return array<string,string>
	 */
	public static function cart_item_data( $cart_item ): array {
		$product = $cart_item['data'] ?? null;
		if ( ! $product instanceof \WC_Product ) {
			return array( 'displayName' => '', 'category' => '', 'href' => '', 'image' => '' );
		}

		$parent_id = $product->get_parent_id();
		$parent    = $parent_id ? wc_get_product( $parent_id ) : $product;
		$name      = $parent ? $parent->get_name() : $product->get_name();
		$href      = '/product/' . ( $parent ? $parent->get_slug() : $product->get_slug() );

		if ( $product instanceof \WC_Product_Variation ) {
			$labels = array();
			$query  = array();
			foreach ( $product->get_variation_attributes( false ) as $taxonomy => $slug ) {
				$term     = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $slug, $taxonomy ) : false;
				$labels[] = $term ? $term->name : $slug;
				$query[ 'attribute_' . $taxonomy ] = $slug;
			}
			if ( $labels ) {
				$name .= ' - ' . implode( ' / ', $labels );
			}
			if ( $query ) {
				$href = add_query_arg( $query, $href );
			}
		}

		$category_ids = $parent ? $parent->get_category_ids() : array();
		$category     = $category_ids ? get_term( (int) $category_ids[0], 'product_cat' ) : null;
		$image_id     = $product->get_image_id() ?: ( $parent ? $parent->get_image_id() : 0 );

		return array(
			'displayName' => html_entity_decode( $name, ENT_QUOTES ),
			'category'    => $category instanceof \WP_Term ? html_entity_decode( $category->name, ENT_QUOTES ) : 'Luxury Bag',
			'href'        => $href,
			'image'       => $image_id ? (string) wp_get_attachment_image_url( (int) $image_id, 'woocommerce_thumbnail' ) : '',
		);
	}
}
