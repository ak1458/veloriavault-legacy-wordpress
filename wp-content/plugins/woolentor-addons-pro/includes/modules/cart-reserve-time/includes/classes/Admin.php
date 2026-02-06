<?php
namespace WoolentorPro\Modules\CartReserveTime;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Admin handlers class
 */
class Admin {
    use Singleton;
    
    /**
     * Initialize the class
     */
    private function __construct() {
        $this->includes();
        $this->init();
    }

    /**
     * Load Required files
     *
     * @return void
     */
    private function includes(){
        require_once(__DIR__.'/Admin/Fields.php');
        if( ENABLED ){
            require_once(__DIR__.'/Admin/Cart_Reserve_Meta_Boxes.php');
        }
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init(){
        $enable_per_product = woolentor_get_option( 'enable_per_product', 'woolentor_cart_reserve_timer_settings', 'off' );
        add_action('current_screen', function ($screen) use($enable_per_product) {
            if ( $screen->post_type === 'product' ) {
                if( ENABLED && $enable_per_product == 'on'){
                    Admin\Coupon_Meta_Boxes::instance();
                }
            }
        });
    }

}