<?php
namespace WoolentorPro\Modules\CartReserveTime;
use WooLentorPro\Traits\ModuleBase;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Cart_Reserve_Time {
    use ModuleBase;

    /**
     * Constructor
     */
    public function __construct(){
        // Define Pro Constants
        $this->define_constants();

        // Include Pro Files
        $this->include_files();

        // Initialize
        $this->init();
    }

    /**
     * Define Pro Constants
     */
    private function define_constants(){
        define( 'WoolentorPro\Modules\CartReserveTime\MODULE_FILE', __FILE__ );
        define( 'WoolentorPro\Modules\CartReserveTime\MODULE_PATH', __DIR__ );
        define( 'WoolentorPro\Modules\CartReserveTime\ENABLED', self::$_enabled );
    }

    /**
     * Include Pro Files
     */
    private function include_files(){
        require_once( MODULE_PATH . '/includes/classes/Admin.php' );
        require_once( MODULE_PATH . '/includes/classes/Frontend.php' );
    }

    /**
     * Initialize Pro Features
     */
    private function init(){

        // For Admin
        if ( $this->is_request( 'admin' ) || $this->is_request( 'rest' ) ) {
            Admin::instance();
        }
        
        if( self::$_enabled ){
            // For Frontend
            if ( $this->is_request( 'frontend' ) ) {
                Frontend::instance();
            }
        }

    }

}

Cart_Reserve_Time::instance(\Woolentor\Modules\CartReserveTime\ENABLED);