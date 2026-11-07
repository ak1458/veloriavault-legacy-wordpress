<?php
/**
 * Configuration checks shown to shop managers. Each one guards a known way
 * the storefront could silently diverge from the headless behaviour.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Admin;

use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class Notices {

	public static function register(): void {
		add_action( 'admin_notices', array( self::class, 'render' ) );
		add_action( 'admin_notices', array( self::class, 'variation_images' ) );
	}

	public static function variation_images(): void {
		$screen = get_current_screen();
		global $post;
		if ( ! $screen || 'product' !== $screen->id || ! $post || ! current_user_can( 'edit_post', $post->ID ) ) return;
		$product = wc_get_product( $post->ID );
		if ( ! $product || ! $product->is_type( 'variable' ) ) return;
		$missing = array();
		foreach ( $product->get_children() as $id ) {
			if ( ! (int) get_post_meta( $id, '_thumbnail_id', true ) ) $missing[] = '#' . $id;
		}
		if ( $missing ) printf( '<div class="notice notice-warning"><p><strong>Veloria:</strong> Variations %s have no own image and use the parent photo. Add a photo to each variation so customers can identify the option.</p></div>', esc_html( implode( ', ', $missing ) ) );
	}

	/** @return array<int,array{level:string,message:string}> */
	public static function problems(): array {
		$problems = array();
		foreach ( self::offer_copy_problems( Settings::all() ) as $message ) {
			$problems[] = array( 'level' => 'warning', 'message' => $message );
		}

		if ( 'native' !== Settings::get( 'pricing_mode' ) && is_plugin_active_safe( 'advanced-dynamic-pricing-for-woocommerce/advanced-dynamic-pricing-for-woocommerce.php' ) ) {
			$problems[] = array( 'level' => 'error', 'message' => 'Advanced Dynamic Pricing is active. Its rules apply on top of the Veloria tier and prepaid discounts in the cart, so customers could get double discounts. Export its rules, then deactivate it.' );
		}
		if ( in_array( 'razorpay', (array) Settings::get( 'prepaid_gateway_ids', array( 'razorpay' ) ), true ) && ! is_plugin_active_safe( 'woo-razorpay/woo-razorpay.php' ) ) {
			$problems[] = array( 'level' => 'error', 'message' => 'Razorpay is selected for online payment offers but its plugin is not active. Activate it or select your actual online gateways in Veloria settings.' );
		}
		$structure = (string) get_option( 'permalink_structure' );
		if ( '' === $structure || str_ends_with( $structure, '/' ) ) {
			$problems[] = array( 'level' => 'warning', 'message' => 'Permalinks end with a slash (or are "Plain"). Live URLs have no trailing slash: set a custom structure of /%postname% under Settings → Permalinks.' );
		}
		$account = (int) get_option( 'woocommerce_myaccount_page_id' );
		if ( $account && 'account' !== get_post_field( 'post_name', $account ) ) {
			$problems[] = array( 'level' => 'warning', 'message' => 'The My account page slug should be "account" to match live URLs. Run: wp veloria seed-pages' );
		}
		$official_shiprocket = \Veloria\Commerce\Shipping\ShiprocketSync::official_sync_active();
		if ( 'off' === Settings::get( 'shiprocket_mode' ) && ! $official_shiprocket ) {
			$problems[] = array( 'level' => 'warning', 'message' => 'No Shiprocket sync is running: Veloria sync is off and the official Shiprocket plugin is not active. Activate the official plugin, or switch Veloria → Shiprocket sync to "Veloria sync".' );
		} elseif ( 'custom' === Settings::get( 'shiprocket_mode' ) && $official_shiprocket ) {
			$problems[] = array( 'level' => 'error', 'message' => 'Veloria sync and the official Shiprocket plugin are both active. Veloria sync stays paused to avoid duplicate shipments; set Veloria → Shiprocket sync to "Off".' );
		}
		if ( function_exists( 'wc_get_shipping_method_count' ) && 0 === wc_get_shipping_method_count( true ) ) {
			$problems[] = array( 'level' => 'warning', 'message' => 'No shipping method is set up, so WooCommerce skips shipping and orders carry no "Standard Shipping" line (the headless checkout always added one). Add any method, e.g. Free shipping, to a zone covering India; Veloria replaces its rate with Standard Shipping automatically.' );
		}
		// Product-level "Manage stock" at 0 on a variable product marks it sold out even when its colours have stock.
		$hidden = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 20,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array( 'key' => '_manage_stock', 'value' => 'yes' ),
					array( 'key' => '_stock_status', 'value' => 'outofstock' ),
				),
			)
		);
		$names = array();
		foreach ( $hidden as $id ) {
			$product = wc_get_product( $id );
			if ( $product && $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $child ) {
					$variation = wc_get_product( $child );
					if ( $variation && $variation->is_in_stock() ) {
						$names[] = $product->get_name();
						break;
					}
				}
			}
		}
		if ( $names ) {
			$problems[] = array( 'level' => 'error', 'message' => 'Hidden from the shop as out of stock although colours are in stock: ' . implode( ', ', $names ) . '. Edit the product → Inventory and untick "Manage stock?" at product level; each colour keeps its own stock.' );
		}
		// Tax-inclusive GST (live setup) keeps cart totals equal to the engine; exclusive prices would add tax on top.
		if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) && 'yes' !== get_option( 'woocommerce_prices_include_tax' ) ) {
			$problems[] = array( 'level' => 'warning', 'message' => 'WooCommerce tax calculation is on with prices entered exclusive of tax, so tax is added on top of the Veloria discount engine total. Enter prices inclusive of tax (WooCommerce → Settings → Tax).' );
		}

		return $problems;
	}

	/** Warn on stale advertised percentages without rewriting merchant copy. */
	public static function offer_copy_problems( array $settings ): array {
		$tiers = (array) $settings['tier_rules'];
		usort( $tiers, static fn( $a, $b ) => $a['min_qty'] <=> $b['min_qty'] );
		$allowed = array_map( static fn( $tier ) => (float) $tier['percent'], $tiers );
		$allowed[] = (float) $settings['prepaid_percent'];
		$groups = array(
			'Announcement bar' => (array) $settings['announcement_messages'],
			'Tier labels' => (array) $settings['offer_tier_labels'],
			'Prepaid offer text' => array( $settings['prepaid_offer_text'] ),
			'Product page offers' => array_map( static fn( $item ) => ( $item['title'] ?? '' ) . ' ' . ( $item['description'] ?? '' ), (array) ( $settings['product_promo']['items'] ?? array() ) ),
		);
		$warnings = array();
		foreach ( $groups as $label => $texts ) {
			foreach ( $texts as $text ) {
				preg_match_all( '/(\d+(?:\.\d+)?)\s*%/', (string) $text, $matches );
				$valid = 'Prepaid offer text' === $label ? array( (float) $settings['prepaid_percent'] ) : $allowed;
				if ( preg_match( '/prepaid|online/i', $text ) ) {
					$valid = array( (float) $settings['prepaid_percent'] );
				} elseif ( preg_match( '/buy\s+(\d+)/i', $text, $quantity ) ) {
					$percent = 0.0;
					foreach ( $tiers as $tier ) if ( (int) $quantity[1] >= (int) $tier['min_qty'] ) $percent = (float) $tier['percent'];
					$valid = array( $percent );
				}
				foreach ( $matches[1] as $percent ) {
					if ( ! in_array( (float) $percent, $valid, true ) ) {
						$warnings[] = $label . ' advertises ' . $percent . '%, which differs from the current tier/prepaid rules. Update Offer copy in Veloria settings.';
					}
				}
			}
		}
		return array_values( array_unique( $warnings ) );
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && ! in_array( $screen->id, array( 'dashboard', 'plugins', 'woocommerce_page_veloria-settings', 'woocommerce_page_wc-settings' ), true ) ) {
			return;
		}
		foreach ( self::problems() as $problem ) {
			printf( '<div class="notice notice-%s"><p><strong>Veloria:</strong> %s</p></div>', esc_attr( $problem['level'] ), esc_html( $problem['message'] ) );
		}
	}
}

/** is_plugin_active() is only loaded in admin; this works everywhere. */
function is_plugin_active_safe( string $plugin ): bool {
	$active = (array) get_option( 'active_plugins', array() );
	if ( in_array( $plugin, $active, true ) ) {
		return true;
	}
	if ( is_multisite() ) {
		$network = (array) get_site_option( 'active_sitewide_plugins', array() );
		return isset( $network[ $plugin ] );
	}
	return false;
}
