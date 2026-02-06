<?php
namespace WoolentorPro\Modules\AdvancedCoupon;
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
            require_once(__DIR__.'/Admin/Coupon_Meta_Boxes.php');
        }
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init(){
        add_action('current_screen', function ($screen) {
            if ( $screen->post_type === 'shop_coupon' ) {
                if( ENABLED ){
                    Admin\Coupon_Meta_Boxes::instance();
                }
            }
        });
    }

}