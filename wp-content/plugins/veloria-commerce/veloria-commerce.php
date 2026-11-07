<?php
/**
 * Plugin Name:       Veloria Commerce
 * Description:       Business rules for Veloria Vault: automatic tier and prepaid discounts, coupon engine, order meta, guest order tools (track, cancel + refund, return, invoice), email OTP, Shiprocket sync, newsletter, chat assistant, SEO routing and security headers. Theme-independent. Developed by <a href="https://arranto.com/en">Arranto</a> and <a href="https://smilefotilo.com">Smile Fotilo</a>.
 * Version:           1.3.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Arranto / Smile Fotilo
 * Author URI:        https://arranto.com/en
 * Text Domain:       veloria-commerce
 * WC requires at least: 8.5
 * WC tested up to:   10.6.1
 *
 * @package Veloria\Commerce
 */

defined( 'ABSPATH' ) || exit;

define( 'VELORIA_COMMERCE_VERSION', '1.3.1' );
define( 'VELORIA_COMMERCE_FILE', __FILE__ );
define( 'VELORIA_COMMERCE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VELORIA_COMMERCE_URL', plugin_dir_url( __FILE__ ) );

/*
 * PSR-4 autoloader for the Veloria\Commerce namespace (src/). No Composer
 * runtime dependency, so updates are a plain zip upload.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'Veloria\\Commerce\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = VELORIA_COMMERCE_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

require_once VELORIA_COMMERCE_DIR . 'src/functions.php';

// Declare compatibility with WooCommerce HPOS (custom order tables).
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			// Checkout is the classic (shortcode-free, template-driven) flow.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
		}
	}
);

register_activation_hook( __FILE__, array( \Veloria\Commerce\Install::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \Veloria\Commerce\Install::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( \Veloria\Commerce\Plugin::class, 'boot' ), 20 );
