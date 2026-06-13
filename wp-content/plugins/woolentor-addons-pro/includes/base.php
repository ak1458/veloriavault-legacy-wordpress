<?php

namespace WooLentorPro;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin Base Class
 */
final class Base {

    /**
     * [$_instance]
     * @var null
     */
    private static $_instance = null;

    /**
     * [instance] Initializes a singleton instance
     * @return [Base]
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * [__construct] Class construcotr
     */
    private function __construct() {
        if ( ! function_exists('is_plugin_active')){ include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); }
        // Installer
        if( is_admin() ){
            require( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.installer.php' );
        }

        add_action( 'init', [ $this, 'i18n' ] );
        add_action( 'plugins_loaded', [ $this, 'init' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'delete_transient_key' ] );
        
        // Register Plugin Active Hook
        register_activation_hook( WOOLENTOR_ADDONS_PL_ROOT_PRO, [ $this, 'plugin_activate_hook'] );
        if( empty( get_option('woolentorpro_version', '') ) ){
            $this->plugin_activate_hook();
        }

        // Plugin Deactive Hook
        register_deactivation_hook( WOOLENTOR_ADDONS_PL_ROOT_PRO, [ $this, 'plugin_deactive_hook'] );

    }

    /**
     * [i18n] Load Text Domain
     * @return [void]
     */
    public function i18n() {
        load_plugin_textdomain( 'woolentor-pro', false, dirname( plugin_basename( WOOLENTOR_ADDONS_PL_ROOT_PRO ) ) . '/languages/' );
    }

    /**
     * [init] Plugins Loaded Init Hook
     * @return [void]
     */
    public function init() {

        // Check WooLentor Free version
        if( !is_plugin_active('woolentor-addons/woolentor_addons_elementor.php') ){
            add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
            return;
        }

        // Check WooCommerce
        if ( !did_action( 'woocommerce_loaded' ) ) {
            return;
        }

        // Include File
        $this->include_files();

    }

    /**
     * [admin_notice_missing_main_plugin] Admin Notice If WooLentor Free Version Deactive | Not install
     * @return [void]
     */
    public function admin_notice_missing_main_plugin() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
        $woolentor = 'woolentor-addons/woolentor_addons_elementor.php';
        if( $this->is_plugins_active( $woolentor ) ) {
            if( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }
            $activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $woolentor . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $woolentor );
            $message = sprintf( __( '%1$sShopLentor Addons Pro%2$s requires ShopLentor plugin to be active. Please activate ShopLentor to continue.', 'woolentor-pro' ), '<strong>', '</strong>' );
            $button_text = esc_html__( 'Activate ShopLentor', 'woolentor-pro' );
        } else {
            if( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }
            $activation_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woolentor-addons' ), 'install-plugin_woolentor-addons' );
            $message = sprintf( __( ' %1$sShopLentor Addons Pro %2$s requires %1$s"ShopLentor Addons"%2$s plugin to be installed and activated. Please install ShopLentor to continue.', 'woolentor-pro' ), '<strong>', '</strong>' );
            $button_text = esc_html__( 'Install ShopLentor', 'woolentor-pro' );
        }
        $button = '<p><a href="' . $activation_url . '" class="button-primary">' . $button_text . '</a></p>';
        printf( '<div class="error"><p>%1$s</p>%2$s</div>', $message, $button );

    }

    /**
     * [is_plugins_active] Check Plugin installation status
     * @param  [string]  $pl_file_path plugin location
     * @return boolean  True | False
     */
    public function is_plugins_active( $pl_file_path = NULL ){
        $installed_plugins_list = get_plugins();
        return isset( $installed_plugins_list[$pl_file_path] );
    }

    /**
     * [plugin_activate_hook] Plugin Activation Hook
     * @return [void]
     */
    public function plugin_activate_hook() {
        if( class_exists('\WooLentorPro\Installer') ){
            \WooLentorPro\Installer::instance();
        }
    }

    /**
     * [plugin_redirect_option_page] After Install plugin then redirect setting page
     * @return [void]
     */
    public function plugin_redirect_option_page() {
        if ( get_option( 'woolentor_do_activation_redirect', FALSE ) ) {
            delete_option('woolentor_do_activation_redirect');
            if( !isset( $_GET['activate-multi'] ) ){
                wp_redirect( admin_url("admin.php?page=woolentor-pro") );
            }
        }
    }

    /**
     * [plugin_deactive_hook] Plugin Deactivation Hook
     * @return [void]
     */
    public function plugin_deactive_hook(){
        delete_transient( 'woolentor_template_info' );
        delete_transient( 'woolentor_gutenberg_template_info' );
        delete_transient( 'woolentor_gutenberg_patterns_info' );

        // Clear file-based cache
        if( class_exists('\Woolentor_Template_Library_Manager') && method_exists('\Woolentor_Template_Library_Manager', 'clear_cache') ){
            \Woolentor_Template_Library_Manager::clear_cache();
        }

        delete_metadata( 'user', null, 'woolentor_dismissed_lic_notice', null, true );
    }

    /**
     * [delete_transient_key]
     * @param  [string] $hook
     * @return [void]
     */
    public function delete_transient_key( $hook ){
        if( $hook === 'shoplentor_page_woolentor_templates' || $hook === 'shoplentor_page_woolentor' ){
            if ( get_option( 'woolentor_do_activation_library_cache', FALSE ) ) {
                // Clear old transient (backward compatibility)
                delete_transient( 'woolentor_template_info' );

                // Clear file-based cache
                if( class_exists('\Woolentor_Template_Library_Manager') && method_exists('\Woolentor_Template_Library_Manager', 'clear_cache') ){
                    \Woolentor_Template_Library_Manager::clear_cache( 'template' );
                }

                delete_option('woolentor_do_activation_library_cache');
            }
        }
    }

    /**
     * [include_files] Required Necessary file
     * @return [void]
     */
    public function include_files(){

        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'includes/helper-function.php' );
        woolentor_include_all_pro(WOOLENTOR_ADDONS_PL_PATH_PRO.'includes/traits');
        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.ajax_actions.php' );
        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.assest_management.php' );
        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.widgets_control.php' );
        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.my_account.php' );
        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.wc-shortcode-products.php' );
        // Block Manager
        if ( class_exists( 'WooLentorBlocks' ) ){
            require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'blocks/blocks.php' );
        }
        // For Checkout page
        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.checkout_page.php' );
        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.checkout_field_manager.php' );

        // Admin Setting file
        if( is_admin() || $this->is_rest_api_request() ){
            require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'includes/licence/WooLentorPro.php' );
        }
        if( is_admin() ){
            require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'includes/custom-metabox.php' );
        }

        // Builder File
        if( woolentor_get_option_pro( 'enablecustomlayout', 'woolentor_woo_template_tabs', 'on' ) == 'on' ){
            require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'includes/wl_woo_shop.php' );
            // For Cart page
            require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.cart_page.php' );
            require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.third_party.php' );
        }
        
        // WooLentor Extension
        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO.'classes/class.extension.php' );

        // Modules Manager
        require_once( WOOLENTOR_ADDONS_PL_PATH_PRO. 'includes/modules/class.module-manager.php' );

    }

    /**
     * Check if the current request is a REST API request.
     *
     * @return bool True if the current request is a REST API request, false otherwise.
     */
    private function is_rest_api_request() {
        if ( empty( $_SERVER['REQUEST_URI'] ) ) {
            return false;
        }

        $rest_prefix         = trailingslashit( rest_get_url_prefix() );
        $is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        return $is_rest_api_request;
    }


}

/**
 * Initializes the main plugin
 */
function woolentor_pro() {
    return Base::instance();
}