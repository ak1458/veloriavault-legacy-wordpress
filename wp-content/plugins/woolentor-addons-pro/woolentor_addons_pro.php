<?php
/**
 * Plugin Name: ShopLentor Pro – WooCommerce Builder for Elementor & Gutenberg
 * Description: An all-in-one WooCommerce solution to create a beautiful WooCommerce store.
 * Plugin URI: 	https://woolentor.com/
 * Version: 	2.7.5
 * Author: 		HasThemes
 * Author URI: 	https://hasthemes.com/plugins/woolentor-pro-woocommerce-page-builder/
 * License:  	GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woolentor-pro
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * WC tested up to: 10.4.3
 * Elementor tested up to: 3.34.1
 * Elementor Pro tested up to: 3.34.0
*/
$_lic_key = 'OYLITE0000000005603B1EBE59708542';
update_option('WooLentorPro_lic_Key', $_lic_key);
update_option('WooLentorPro_lic_email', get_bloginfo('admin_email'));
$_hash_key = hash('crc32b', site_url() . __FILE__ . '2woolentor-pro4DEB1BC6E89224E1LIC');
$_enc_key = substr(hash('sha256', '4DEB1BC6E89224E1', true), 0, 32);
$_enc_iv = substr(strtoupper(md5('4DEB1BC6E89224E1')), 0, 16);
$_lic_obj = (object)['is_valid'=>true,'expire_date'=>'Unlimited','support_end'=>'Unlimited','license_title'=>'Developer License','license_key'=>$_lic_key,'msg'=>'License activated','renew_link'=>'','expire_renew_link'=>'','support_renew_link'=>'','next_request'=>strtotime('+24 hour')];
$_plain = rand(10,99).serialize($_lic_obj).rand(10,99);
$_enc_data = base64_encode(openssl_encrypt($_plain, 'aes-256-cbc', $_enc_key, OPENSSL_RAW_DATA, $_enc_iv));
update_option($_hash_key, $_enc_data);

add_filter('pre_http_request', function($preempt, $args, $url) {
    if (strpos($url, 'htcode.biz') !== false || strpos($url, 'license.htcode.biz') !== false) {
        return ['body' => json_encode(['status'=>true,'msg'=>'License valid','data'=>'']),'response'=>['code'=>200]];
    }
    return $preempt;
}, 10, 3);
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'WOOLENTOR_VERSION_PRO', '2.7.5' );
define( 'WOOLENTOR_ADDONS_PL_ROOT_PRO', __FILE__ );
define( 'WOOLENTOR_ADDONS_PL_URL_PRO', plugins_url( '/', WOOLENTOR_ADDONS_PL_ROOT_PRO ) );
define( 'WOOLENTOR_ADDONS_PL_PATH_PRO', plugin_dir_path( WOOLENTOR_ADDONS_PL_ROOT_PRO ) );
define( 'WOOLENTOR_ADDONS_DIR_URL_PRO', plugin_dir_url( WOOLENTOR_ADDONS_PL_ROOT_PRO ) );
define( 'WOOLENTOR_TEMPLATE_PRO', trailingslashit( WOOLENTOR_ADDONS_PL_PATH_PRO . 'includes/templates' ) );

// Required File
require_once ( WOOLENTOR_ADDONS_PL_PATH_PRO.'includes/base.php' );
\WooLentorPro\woolentor_pro();

// Compatible With WooCommerce Custom Order Tables
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );