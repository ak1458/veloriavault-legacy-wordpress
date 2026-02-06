<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit();

/**
* Third party
*/
class WooLentorThirdParty{

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
    
    function __construct(){
        $this->woocommerce_german_market();
        $this->theme_compatibility();
    }

    /**
     * WooCommerce German Market
     *
     * @return void
     */
    public function woocommerce_german_market(){
        if( class_exists('Woocommerce_German_Market') ){
            add_action( 'woolentor_universal_after_price', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ) );
            add_action( 'woolentor_addon_after_price', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ) );
        }
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
     * @return void
     */
    public function avada_theme_compatibility(){
        if( !function_exists('woolentor_get_theme_byname') || !woolentor_get_theme_byname('Avada') ){
            return;
        }
        $shopify_is_enable = woolentor_get_option( 'enable','woolentor_shopify_checkout_settings', 'off' ) == 'on';
        if( $shopify_is_enable ){
            global $avada_woocommerce;
            if( is_object( $avada_woocommerce ) ){
                remove_action( 'woocommerce_before_checkout_form', [$avada_woocommerce, 'avada_top_user_container' ], 1 );
                remove_action( 'woocommerce_before_checkout_form', [$avada_woocommerce, 'checkout_coupon_form' ], 10 );
                remove_action( 'woocommerce_before_checkout_form', [$avada_woocommerce, 'before_checkout_form' ], 10 );
                remove_action( 'woocommerce_checkout_after_order_review', [$avada_woocommerce, 'checkout_after_order_review' ], 20 );
                remove_action( 'woocommerce_after_checkout_form', [ $avada_woocommerce, 'after_checkout_form' ] );
            }
        }
    }

    
}

WooLentorThirdParty::instance();