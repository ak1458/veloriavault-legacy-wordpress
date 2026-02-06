<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Plugin Name: YITH WooCommerce Color, Image & Label Variation Swatches Premium
 * Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-color-and-label-variations/
 * Description: <code><strong>YITH WooCommerce Color, Image & Label Variation Swatches</strong></code> allows you to customize the drop-down selection of your variable products and have customers select them directly from your shop pages. A must-have for every e-commerce store. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce shop on <strong>YITH</strong></a>.
 * Version: 3.13.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-woocommerce-color-label-variations
 * Domain Path: /languages/
 * WC requires at least: 10.0
 * WC tested up to: 10.2
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Color and Label Variations Premium
 * @version 3.13.0
 *
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! function_exists( 'yith_wccl_premium_install_woocommerce_admin_notice' ) ) {
	/**
	 * Message if WooCommerce plugin is not installed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function yith_wccl_premium_install_woocommerce_admin_notice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'YITH WooCommerce Color and Label Variations Premium is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-color-label-variations' ); ?></p>
		</div>
		<?php
	}
}


if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

// Free version deactivation if installed __________________.

if ( ! function_exists( 'yith_deactivate_plugins' ) ) {
	require_once 'plugin-fw/yit-deactive-plugin.php';
}
yith_deactivate_plugins( 'YITH_WCCL_FREE_INIT', plugin_basename( __FILE__ ) );

if ( ! defined( 'YITH_WCCL_VERSION' ) ) {
	define( 'YITH_WCCL_VERSION', '3.13.0' );
}

if ( ! defined( 'YITH_WCCL' ) ) {
	define( 'YITH_WCCL', true );
}

if ( ! defined( 'YITH_WCCL_PREMIUM' ) ) {
	define( 'YITH_WCCL_PREMIUM', true );
}

if ( ! defined( 'YITH_WCCL_FILE' ) ) {
	define( 'YITH_WCCL_FILE', __FILE__ );
}

if ( ! defined( 'YITH_WCCL_URL' ) ) {
	define( 'YITH_WCCL_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'YITH_WCCL_DIR' ) ) {
	define( 'YITH_WCCL_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YITH_WCCL_TEMPLATE_PATH' ) ) {
	define( 'YITH_WCCL_TEMPLATE_PATH', YITH_WCCL_DIR . 'templates' );
}
if ( ! defined( 'YITH_WCCL_VIEW_PATH' ) ) {
    define( 'YITH_WCCL_VIEW_PATH', YITH_WCCL_DIR . 'views' );
}

if ( ! defined( 'YITH_WCCL_ASSETS_URL' ) ) {
	define( 'YITH_WCCL_ASSETS_URL', YITH_WCCL_URL . 'assets' );
}

if ( ! defined( 'YITH_WCCL_INIT' ) ) {
	define( 'YITH_WCCL_INIT', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YITH_WCCL_SLUG' ) ) {
	define( 'YITH_WCCL_SLUG', 'yith-woocommerce-color-label-variations' );
}

if ( ! defined( 'YITH_WCCL_SECRET_KEY' ) ) {
	define( 'YITH_WCCL_SECRET_KEY', '' );
}

if ( ! defined( 'YITH_WCCL_DB_VERSION' ) ) {
	define( 'YITH_WCCL_DB_VERSION', '1.0.0' );
}

// Plugin Framework Loader.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php';
}

if( !class_exists('YITH_WCCL_WC_Cartitem_Endpoint') ) {
    include_once YITH_WCCL_DIR . 'includes/wc-blocks/src/class-yith-wccl-wc-item-endpoint-register.php';
    YITH_WCCL_WC_Cartitem_Endpoint();
}

/**
 * Activate plugin.
 *
 * @since 1.0.0
 * @return void
 */
function yith_wccl_activation_process() {
	if ( ! function_exists( 'yith_wccl_activation' ) ) {
		require_once 'includes/function.yith-wccl-activation.php';
	}

	yith_wccl_activation();
}

register_activation_hook( __FILE__, 'yith_wccl_activation_process' );

if ( ! function_exists( 'yith_plugin_onboarding_registration_hook' ) ) {
	include_once 'plugin-upgrade/functions-yith-licence.php';
}
register_activation_hook( __FILE__, 'yith_plugin_onboarding_registration_hook' );

/**
 * Install.
 *
 * @since 1.0.0
 * @return void
 */
function yith_wccl_premium_install() {

	if ( ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'yith_wccl_premium_install_woocommerce_admin_notice' );
	} else {

        if ( function_exists( 'yith_plugin_fw_load_plugin_textdomain' ) ) {
            yith_plugin_fw_load_plugin_textdomain( 'yith-woocommerce-color-label-variations', basename( dirname( __FILE__ ) ) . '/languages' );
        }

		// Load required classes and functions.
		require_once 'includes/function.yith-wccl.php';
		require_once 'includes/class.yith-wccl.php';

		// Let's start the game!
		YITH_WCCL();
	}

	// check for update table.
	if ( function_exists( 'yith_wccl_update_db_check' ) ) {
		yith_wccl_update_db_check();
	}
}

add_action( 'plugins_loaded', 'yith_wccl_premium_install', 11 );
