<?php
/**
 * Plugin Name: ShopLentor – WooCommerce Builder for Elementor & Gutenberg
 * Description: An all-in-one WooCommerce solution to create a beautiful WooCommerce store.
 * Plugin URI:  https://woolentor.com/
 * Version:     3.3.1
 * Author:      HasThemes
 * Author URI:  https://hasthemes.com/plugins/woolentor-pro/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woolentor
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * WC tested up to: 10.4.3
 * Elementor tested up to: 3.34.1
 * Elementor Pro tested up to: 3.34.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


define( 'WOOLENTOR_VERSION', '3.3.1' );
define( 'WOOLENTOR_ADDONS_PL_ROOT', __FILE__ );
define( 'WOOLENTOR_ADDONS_PL_URL', plugins_url( '/', WOOLENTOR_ADDONS_PL_ROOT ) );
define( 'WOOLENTOR_ADDONS_PL_PATH', plugin_dir_path( WOOLENTOR_ADDONS_PL_ROOT ) );
define( 'WOOLENTOR_ADDONS_DIR_URL', plugin_dir_url( WOOLENTOR_ADDONS_PL_ROOT ) );
define( 'WOOLENTOR_PLUGIN_BASE', plugin_basename( WOOLENTOR_ADDONS_PL_ROOT ) );
define( 'WOOLENTOR_TEMPLATE', trailingslashit( WOOLENTOR_ADDONS_PL_PATH . 'includes/templates' ) );

// Required File
require_once ( WOOLENTOR_ADDONS_PL_PATH.'includes/base.php' );
\WooLentor\woolentor();

/**
 * Gutenbarge Blocks
 */
require_once ( WOOLENTOR_ADDONS_PL_PATH.'woolentor-blocks/woolentor-blocks.php' );

// Compatible With WooCommerce Custom Order Tables
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );