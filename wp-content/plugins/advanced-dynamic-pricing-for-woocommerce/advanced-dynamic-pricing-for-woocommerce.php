<?php
/**
 * Plugin Name: Advanced Dynamic Pricing and Discount Rules for WooCommerce
 * Plugin URI:
 * Description: Manage WooCommerce discounts
 * Version: 4.11.0
 * Author: AlgolPlus
 * Author URI: https://algolplus.com/
 * WC requires at least: 3.6
 * WC tested up to: 10.4
 *
 * Text Domain: advanced-dynamic-pricing-for-woocommerce
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

//Stop if another version is active!
if (defined('WC_ADP_PLUGIN_FILE')) {
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php echo esc_html__('Please, ', 'advanced-dynamic-pricing-for-woocommerce') .'<a href="plugins.php">'
            .esc_html__('deactivate', 'advanced-dynamic-pricing-for-woocommerce').'</a>'
            .esc_html__(' Free version of Advanced Dynamic Pricing For WooCommerce!', 'advanced-dynamic-pricing-for-woocommerce');
            ?></p>
        </div>
        <?php
    });

    return;
}

//main constants
define('WC_ADP_PLUGIN_FILE', basename(__FILE__));
define('WC_ADP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WC_ADP_PLUGIN_URL', plugins_url('', __FILE__));
define('WC_ADP_MIN_PHP_VERSION', '7.0.0');
define('WC_ADP_MIN_WC_VERSION', '3.6');
define('WC_ADP_VERSION', '4.11.0');
define('WC_ADP_WC_TIPTIP_SINCE_VERSION', '10.3');


include_once "AutoLoader.php";
include_once "Factory.php";

\ADP\AutoLoader::register();

\ADP\Factory::get("PluginActions", __FILE__)->register();
\ADP\Factory::get("Loader");

if ( ! function_exists('adp_functions')) {
    function adp_functions()
    {
        return \ADP\BaseVersion\Includes\Functions::getInstance();
    }
}

if ( ! class_exists('WDP_Functions')) {
    include_once "BaseVersion/Includes/Legacy/class-wdp-functions.php";
}

if ( ! class_exists('WDP_Importer')) {
    include_once "BaseVersion/Includes/Legacy/class-wdp-importer.php";
}

if ( ! function_exists('adp_context')) {
    function adp_context(): ADP\BaseVersion\Includes\Context
    {
        static $context;

        if (!$context) {
            $context = \ADP\BaseVersion\Includes\Context::builder()->buildDefault();
            $context = apply_filters("adp_context_created", $context);
        }

        return $context;
    }
}
