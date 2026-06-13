<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit();

/**
* Third party
*/
class WooLentorProThirdParty{

    /**
     * [$_instance]
     * @var null
     */
    private static $_instance = null;

    /**
     * Checkout page template ID
     * @var null
     */
    private static $checkout_page_id = null;

    /**
     * My account page template ID
     * @var null
     */
    private static $my_account_page_id = null;

    /**
     * Cart page template ID
     * @var null
     */
    private static $cart_page_id = null;

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
    
    function __construct(){
        $checkout_page_id = method_exists( 'Woolentor_Template_Manager', 'get_template_id' ) ? Woolentor_Template_Manager::instance()->get_template_id( 'productcheckoutpage', 'woolentor_get_option_pro' ) : '0';
        if( !empty( $checkout_page_id ) && class_exists('WC_Checkout_Add_Ons_Loader') ){
            add_filter( 'wc_checkout_add_ons_position', [ $this,'change_add_ons_pos' ], 10, 1 );
        }

        self::$checkout_page_id = $checkout_page_id;

        // Plugin Loaded Hook
        add_action( 'plugins_loaded', [$this, 'after_plugin_loaded'], 99 );

        // Theme Compatibility
        $this->theme_compatibility();

    }

    /**
     * Theme Compatibility
     * @return void
     */
    public function theme_compatibility(){
        add_action( 'wp', [ $this, 'woocommerce_theme_compatibility' ], 99 );
    }

    /**
     * WooCommerce Theme Compatibility
     * @return void
     */
    public function woocommerce_theme_compatibility(){
        // Avada Theme
        $this->avada_theme_compatibility();
    }

    /**
     * Avada Theme Compatibility
     * Remove Avada's checkout hooks that conflict with WooLentor Builder
     * @return void
     */
    public function avada_theme_compatibility(){
        self::$my_account_page_id = Woolentor_Woo_Custom_Template_Layout_Pro::instance()->my_account_page_manage();
        self::$cart_page_id = Woolentor_Woo_Custom_Template_Layout_Pro::instance()->get_template_id( 'productcartpage' );

        // Check if Avada theme is active
        if( !function_exists('woolentor_get_theme_byname') || !woolentor_get_theme_byname('Avada') ){
            return;
        }

        global $avada_woocommerce;
        if( is_object( $avada_woocommerce ) ){

            // Checkout Page
            if( !empty( self::$checkout_page_id ) ){
                remove_action( 'woocommerce_before_checkout_form', [$avada_woocommerce, 'avada_top_user_container' ], 1 );
                remove_action( 'woocommerce_before_checkout_form', [$avada_woocommerce, 'checkout_coupon_form' ], 10 );
                remove_action( 'woocommerce_before_checkout_form', [$avada_woocommerce, 'before_checkout_form' ], 10 );
                remove_action( 'woocommerce_checkout_after_order_review', [$avada_woocommerce, 'checkout_after_order_review' ], 20 );
                remove_action( 'woocommerce_after_checkout_form', [ $avada_woocommerce, 'after_checkout_form' ] );
            }

            // Cart Page
            if( !empty( self::$cart_page_id ) ){
                remove_action( 'woocommerce_before_cart_table', [ $avada_woocommerce, 'before_cart_table' ], 20 );
                remove_action( 'woocommerce_after_cart_table', [ $avada_woocommerce, 'after_cart_table' ], 20 );
            }

            // My Account Page
            if( !empty( self::$my_account_page_id ) ){
                remove_action( 'woocommerce_before_account_navigation', [ $avada_woocommerce, 'avada_top_user_container' ], 10 );
            }
        }
    }

    /**
     * [change_add_ons_pos] Support woocommerce-checkout-add-ons
     * @param  [string] $position
     * @return [string]
     */
    public function change_add_ons_pos( $position ){
        if( 'woocommerce_checkout_billing' === $position ){
            $position = 'woocommerce_after_checkout_billing_form';
        }elseif( 'woocommerce_checkout_before_customer_details' === $position ){
            $position = 'woocommerce_before_checkout_billing_form';
        }elseif( 'woocommerce_checkout_after_customer_details' === $position ){
            $position = 'woolentor_before_checkout_order';
        }
        return $position;
    }

    /**
     * After Plugin Loaded
     * @return void
     */
    public function after_plugin_loaded(){

        // Email Field Hide issue for woocommerce-payments plugin
        if( !empty( self::$checkout_page_id ) && class_exists('WC_Payments') ){
            $woopay_util = new \WCPay\WooPay\WooPay_Utilities();
            if ( $woopay_util->should_enable_woopay( WC_Payments::get_gateway() ) ) {
                add_action( 'woocommerce_before_checkout_billing_form', [ 'WC_Payments', 'woopay_fields_before_billing_details' ], -50 );
            }
        }

    }

    /**
     * [woof_filter]
     * @param  [array] $args query argument
     * @return [array] query argument
     */
    public function support_filter( $args ){

        $queries =[];
        $new_queries = [];
        parse_str( $_SERVER['QUERY_STRING' ], $queries );
        foreach ( $queries as $key => $querie ) {
            $new_queries[] = $key;
        }

        if ( isset( $_GET['swoof'] ) || isset( $_GET['wlfilter'] ) ) {

            if( isset( $_GET['product_cat'] ) ){
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => explode( ',', $_GET['product_cat'] ),
                    'include_children' => true
                );
            }

            if( isset( $_GET['product_tag'] ) ){
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_tag',
                    'field'    => 'slug',
                    'terms'    => explode( ',', $_GET['product_tag'] ),
                    'include_children' => true
                );
            }

            if( isset( $_GET['product_visibility'] ) ){
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => explode( ',', $_GET['product_visibility'] ),
                    'operator' => ( $_GET['product_visibility'] === 'exclude-from-catalog' ? 'NOT IN' : 'IN' ),
                );
            }

            // Filter By Attribute
            if( isset( $new_queries[1] ) && !in_array( $new_queries[1], [ 'wlsort', 'wlorder_by', 'min_price', 'max_price' ] ) ){
                $attr_pre_str = substr( $new_queries[1], 0, 6 );
                if( 'filter' === $attr_pre_str ){
                    $taxonomy = str_replace('filter', 'pa', $new_queries[1] );
                }else{
                    $taxonomy = $new_queries[1];
                }
                if( isset( $_GET[$new_queries[1] ] ) ){
                    $args['tax_query'][] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'name',
                        'terms' => explode( ',', $_GET[$new_queries[1]] ),
                    );
                }
            }

            // WooLentor Filter
            if( isset( $_GET['wlorder_by'] ) ){
                if( in_array( $_GET['wlorder_by'], [ '_price', 'total_sales', '_wc_average_rating' ] ) ) {
                    $args['meta_key']   = $_GET['wlorder_by'];
                    $args['orderby']    = 'meta_value_num';
                }else if( $_GET['wlorder_by'] === 'featured' ){
                    $args['tax_query'][] = array(
                        'taxonomy' => 'product_visibility',
                        'field'    => 'name',
                        'terms'    => explode( ',', $_GET['wlorder_by'] ),
                        'operator' => ( $_GET['wlorder_by'] === 'exclude-from-catalog' ? 'NOT IN' : 'IN' ),
                    );
                }else{
                    $args['orderby'] = $_GET['wlorder_by'];
                }
            }
            if( isset( $_GET['wlsort'] ) ){
                $args['order'] = $_GET['wlsort'];
            }

        }

        // WooCommerce Default Filter
        if( isset( $new_queries[0] ) ){
            $attr_pre_str = substr( $new_queries[0], 0, 6 );
            if( 'filter' === $attr_pre_str ){
                $taxonomy = str_replace('filter', 'pa', $new_queries[0] );
                if( isset( $_GET[$new_queries[0] ] ) ){
                    $args['tax_query'][] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'name',
                        'terms' => explode( ',', $_GET[$new_queries[0]] ),
                    );
                }
            }
        }

        return $args;

    }

    
}

WooLentorProThirdParty::instance();