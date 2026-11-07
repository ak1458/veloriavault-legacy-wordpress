<?php
/**
 * Coupons are created in WooCommerce → Marketing → Coupons (so expiry, usage
 * limits and usage counts are managed by WooCommerce), but their discount is
 * computed by DiscountEngine so stacking rules and the global cap behave
 * exactly like the headless site.
 *
 * A coupon joins the Veloria engine when "Veloria engine" is ticked on its
 * edit screen. Engine coupons can't be applied through WooCommerce's own
 * coupon field: that would apply them twice.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Pricing;

use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class CouponRepository {

	public const META_ENGINE      = '_veloria_engine';
	public const META_CATEGORY    = '_veloria_category';
	public const META_STACKABLE   = '_veloria_stackable';
	public const META_START       = '_veloria_start_date';
	public const META_MAX         = '_veloria_max_discount';
	public const META_MIN_QTY     = '_veloria_min_qty';
	public const SESSION_KEY      = 'veloria_coupon_codes';

	/** @var array<int,array<string,mixed>>|null */
	private static ?array $cache = null;
	private static bool $checking_native = false;

	public static function register(): void {
		add_action( 'woocommerce_coupon_options', array( self::class, 'render_fields' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( self::class, 'save_fields' ), 10, 2 );
		add_filter( 'woocommerce_coupon_is_valid', array( self::class, 'block_native_apply' ), 10, 2 );
		add_action( 'woocommerce_cart_emptied', array( self::class, 'clear_session' ) );
		add_action( 'admin_notices', array( self::class, 'unsupported_notice' ) );
	}

	/** Existing coupons keep their explicit opt-in; only a new draft starts checked. */
	public static function engine_field_value( int $coupon_id ): string {
		if ( ! $coupon_id || 'auto-draft' === get_post_status( $coupon_id ) ) {
			return 'yes';
		}
		return (string) get_post_meta( $coupon_id, self::META_ENGINE, true );
	}

	public static function unsupported_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'shop_coupon' !== $screen->id || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		global $post;
		if ( ! $post || 'yes' !== get_post_meta( $post->ID, self::META_ENGINE, true ) ) {
			return;
		}
		$reason = self::unsupported_reason( new \WC_Coupon( $post->ID ) );
		if ( '' !== $reason ) {
			printf( '<div class="notice notice-warning"><p><strong>Veloria:</strong> %s This coupon is unavailable in the storefront. Remove unsupported restrictions or choose native coupons in Veloria settings.</p></div>', esc_html( $reason ) );
		}
	}

	/**
	 * Engine coupon definitions in the shape DiscountEngine expects.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function engine_coupons(): array {
		if ( ! self::uses_engine() ) {
			return array();
		}
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'shop_coupon',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 200,
				'meta_key'       => self::META_ENGINE, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'  => true,
			)
		);

		$coupons = array();
		foreach ( $posts as $post ) {
			$wc = new \WC_Coupon( $post->ID );
			$type = $wc->get_discount_type();
			$expires = $wc->get_date_expires();
			$supported = '' === self::unsupported_reason( $wc );

			$coupons[] = array_filter(
				array(
					'id'          => 'wc-' . $post->ID,
					'wcId'        => $post->ID,
					'code'        => strtoupper( $wc->get_code() ),
					'type'        => 'percent' === $type ? 'percentage' : 'fixed_cart',
					'category'    => (string) get_post_meta( $post->ID, self::META_CATEGORY, true ) ?: 'standard',
					'stackable'   => 'yes' === get_post_meta( $post->ID, self::META_STACKABLE, true ),
					'amount'      => (float) $wc->get_amount(),
					'description' => $wc->get_description(),
					'minPurchase' => (float) $wc->get_minimum_amount() ?: null,
					'maxDiscount' => (float) get_post_meta( $post->ID, self::META_MAX, true ) ?: null,
					'usageLimit'  => $wc->get_usage_limit() ?: null,
					'usageCount'  => (int) $wc->get_usage_count(),
					'startDate'   => (string) get_post_meta( $post->ID, self::META_START, true ) ?: null,
					'expiryDate'  => $expires ? wp_date( 'Y-m-d', $expires->getTimestamp(), wp_timezone() ) : null,
					'isActive'    => 'publish' === $post->post_status && $supported,
					'isAutomatic' => false,
					'minQuantity' => (int) get_post_meta( $post->ID, self::META_MIN_QTY, true ) ?: null,
				),
				static fn( $v ) => null !== $v
			);
		}

		/**
		 * Filter the coupon definitions handed to the discount engine.
		 *
		 * @param array<int,array<string,mixed>> $coupons
		 */
		self::$cache = apply_filters( 'veloria_engine_coupons', $coupons );
		return self::$cache;
	}

	public static function has_active_coupons(): bool {
		foreach ( self::engine_coupons() as $coupon ) {
			if ( ! empty( $coupon['isActive'] ) ) {
				return true;
			}
		}
		return false;
	}

	/** @return string[] */
	public static function session_codes(): array {
		if ( ! self::uses_engine() ) {
			return array();
		}
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return array();
		}
		$codes = WC()->session->get( self::SESSION_KEY, array() );
		return is_array( $codes ) ? array_values( array_map( 'strval', $codes ) ) : array();
	}

	/** @param string[] $codes */
	public static function set_session_codes( array $codes ): void {
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( self::SESSION_KEY, array_values( array_unique( array_map( 'strtoupper', $codes ) ) ) );
		}
	}

	public static function clear_session(): void {
		self::set_session_codes( array() );
	}

	/** Record a use of every engine coupon that ended up applied to the order. */
	public static function record_usage( \WC_Order $order, array $applied ): void {
		if ( ! self::uses_engine() ) {
			return;
		}
		$codes = array();
		$existing = array();
		foreach ( $order->get_items( 'coupon' ) as $item ) {
			$existing[] = strtoupper( $item->get_code() );
		}
		foreach ( $applied as $entry ) {
			$code = (string) ( $entry['coupon']['code'] ?? '' );
			$id   = (int) ( $entry['coupon']['wcId'] ?? 0 );
			if ( '' === $code || ! $id ) {
				continue;
			}
			if ( in_array( strtoupper( $code ), $existing, true ) ) {
				continue;
			}
			$coupon = new \WC_Coupon( $id );
			$error = self::validate_native( $coupon );
			if ( '' !== $error ) {
				throw new \Exception( $error );
			}
			$codes[] = $code;
			$item = new \WC_Order_Item_Coupon();
			$item->set_code( $coupon->get_code() );
			// Discounts already exist as negative fee lines. The coupon line is
			// for native reservations/counting; do not apply the money twice.
			$item->set_discount( 0 );
			$item->set_discount_tax( 0 );
			$item->add_meta_data( '_veloria_discount_amount', (float) $entry['discountAmount'], true );
			$order->add_item( $item );
		}
		if ( $codes ) {
			$order->update_meta_data( '_veloria_coupon_codes', implode( ',', $codes ) );
			$cart = WC()->cart;
			$native_codes = $cart->get_applied_coupons();
			if ( $native_codes ) {
				throw new \Exception( 'Remove other WooCommerce coupons before using a Veloria offer.' );
			}
			// Use WooCommerce's own atomic coupon holds, including per-customer
			// limits. The native checkout releases them if order creation fails.
			try {
				$cart->set_applied_coupons( $codes );
				$order->hold_applied_coupons( $order->get_billing_email() );
			} finally {
				$cart->set_applied_coupons( $native_codes );
			}
		}
	}

	/** Cart-wide fee discounts cannot faithfully implement product-scoped rules. */
	public static function unsupported_reason( \WC_Coupon $coupon ): string {
		if ( ! in_array( $coupon->get_discount_type(), array( 'percent', 'fixed_cart' ), true )
			|| $coupon->get_product_ids() || $coupon->get_excluded_product_ids() || $coupon->get_product_categories() || $coupon->get_excluded_product_categories()
			|| $coupon->get_email_restrictions() || $coupon->get_exclude_sale_items() || $coupon->get_individual_use()
			|| $coupon->get_free_shipping() || $coupon->get_limit_usage_to_x_items() ) {
			return 'This coupon uses restrictions the Veloria cart-wide discount engine cannot apply. Please contact support.';
		}
		return '';
	}

	/** Native validity rechecked at checkout, including current usage counts. */
	public static function validate_native( \WC_Coupon $coupon ): string {
		$unsupported = self::unsupported_reason( $coupon );
		if ( '' !== $unsupported ) {
			return $unsupported;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 'Your cart could not be checked. Please refresh checkout.';
		}
		self::$checking_native = true;
		try {
			$valid = ( new \WC_Discounts( WC()->cart ) )->is_coupon_valid( $coupon );
			return is_wp_error( $valid ) ? wp_strip_all_tags( $valid->get_error_message() ) : ( true === $valid ? '' : 'Coupon is no longer available.' );
		} catch ( \Exception $error ) {
			return wp_strip_all_tags( $error->getMessage() );
		} finally {
			self::$checking_native = false;
		}
	}

	public static function validate_session(): array {
		if ( ! self::uses_engine() ) {
			return array();
		}
		$errors = array();
		if ( function_exists( 'WC' ) && WC()->cart && WC()->cart->get_applied_coupons() ) {
			$errors[] = 'Remove native WooCommerce coupons and enter supported offers in the Discounts & Offers box.';
		}
		foreach ( self::session_codes() as $code ) {
			$coupon = new \WC_Coupon( $code );
			if ( ! $coupon->get_id() || 'yes' !== (string) get_post_meta( $coupon->get_id(), self::META_ENGINE, true ) ) {
				$errors[] = 'An applied coupon is no longer available. Please remove it and try again.';
				continue;
			}
			$error = self::validate_native( $coupon );
			if ( '' !== $error ) {
				$errors[] = $error;
			}
		}
		return array_unique( $errors );
	}

	/**
	 * @param bool       $valid
	 * @param \WC_Coupon $coupon
	 */
	public static function block_native_apply( $valid, $coupon ) {
		if ( ! self::uses_engine() || self::$checking_native ) {
			return $valid;
		}
		// Staff editing an order in wp-admin may apply native coupons deliberately.
		if ( is_admin() && current_user_can( 'edit_shop_orders' ) ) {
			return $valid;
		}
		if ( $coupon instanceof \WC_Coupon ) {
			throw new \Exception( esc_html__( 'Enter this code in the Discounts & Offers box at checkout.', 'veloria-commerce' ) );
		}
		return $valid;
	}

	public static function uses_engine(): bool {
		return CartPricing::engine_enabled() && 'native' !== Settings::get( 'coupon_mode', 'veloria' );
	}

	public static function render_fields( int $coupon_id ): void {
		echo '<div class="options_group"><h4 style="padding-left:12px">Veloria engine</h4>';
		woocommerce_wp_checkbox(
			array(
				'id'          => self::META_ENGINE,
				'label'       => 'Veloria engine',
				'description' => 'Apply through the storefront discount engine (stacking rules + 35% cap). Only "Percentage discount" and "Fixed cart discount" types are supported.',
				'value'       => self::engine_field_value( $coupon_id ),
				'cbvalue'     => 'yes',
			)
		);
		woocommerce_wp_select(
			array(
				'id'      => self::META_CATEGORY,
				'label'   => 'Stacking category',
				'value'   => get_post_meta( $coupon_id, self::META_CATEGORY, true ) ?: 'standard',
				'options' => array(
					'standard'   => 'Standard (usually not stackable)',
					'influencer' => 'Influencer / partner',
					'seasonal'   => 'Seasonal (Diwali, Eid…)',
				),
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'          => self::META_STACKABLE,
				'label'       => 'Stackable',
				'description' => 'Can combine with other stackable codes of a different category. Non-stackable codes override all others.',
				'value'       => get_post_meta( $coupon_id, self::META_STACKABLE, true ),
				'cbvalue'     => 'yes',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => self::META_START,
				'label'       => 'Active from',
				'placeholder' => 'YYYY-MM-DD',
				'value'       => get_post_meta( $coupon_id, self::META_START, true ),
				'description' => 'Starts at midnight in the WordPress site timezone. Expiry uses the end of its local calendar day.',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => self::META_MAX,
				'label'             => 'Maximum discount (₹)',
				'type'              => 'number',
				'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
				'value'             => get_post_meta( $coupon_id, self::META_MAX, true ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => self::META_MIN_QTY,
				'label'             => 'Minimum items',
				'type'              => 'number',
				'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
				'value'             => get_post_meta( $coupon_id, self::META_MIN_QTY, true ),
			)
		);
		echo '<p class="form-field" style="color:#666">Every discount is capped at ' . esc_html( (string) Settings::get( 'max_discount_percent' ) ) . '% of the order in total. Product/category/email restrictions, sale exclusions, individual use, item limits and free shipping are not supported by this cart-wide engine; coupons with those options are disabled in the storefront. Global and per-customer usage limits use WooCommerce reservations.</p></div>';
	}

	public static function save_fields( int $coupon_id ): void {
		// Nonce verified by WooCommerce before woocommerce_coupon_options_save fires.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		update_post_meta( $coupon_id, self::META_ENGINE, isset( $_POST[ self::META_ENGINE ] ) ? 'yes' : 'no' );
		update_post_meta( $coupon_id, self::META_STACKABLE, isset( $_POST[ self::META_STACKABLE ] ) ? 'yes' : 'no' );
		$category = sanitize_key( wp_unslash( $_POST[ self::META_CATEGORY ] ?? 'standard' ) );
		update_post_meta( $coupon_id, self::META_CATEGORY, in_array( $category, array( 'standard', 'influencer', 'seasonal' ), true ) ? $category : 'standard' );
		$start = sanitize_text_field( wp_unslash( $_POST[ self::META_START ] ?? '' ) );
		update_post_meta( $coupon_id, self::META_START, preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ? $start : '' );
		update_post_meta( $coupon_id, self::META_MAX, max( 0, (int) ( $_POST[ self::META_MAX ] ?? 0 ) ) ?: '' );
		update_post_meta( $coupon_id, self::META_MIN_QTY, max( 0, (int) ( $_POST[ self::META_MIN_QTY ] ?? 0 ) ) ?: '' );
		// phpcs:enable
		self::$cache = null;
	}
}
