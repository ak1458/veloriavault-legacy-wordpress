<?php
namespace WoolentorPro\Modules\Smart_Cross_Sell_Popup;
use WooLentorPro\Traits\ModuleBase;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Smart_Cross_Sell_Popup {
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
        define( 'WoolentorPro\Modules\Smart_Cross_Sell_Popup\MODULE_FILE', __FILE__ );
        define( 'WoolentorPro\Modules\Smart_Cross_Sell_Popup\MODULE_PATH', __DIR__ );
        define( 'WoolentorPro\Modules\Smart_Cross_Sell_Popup\ENABLED', self::$_enabled );
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

Smart_Cross_Sell_Popup::instance(\Woolentor\Modules\Smart_Cross_Sell_Popup\ENABLED);