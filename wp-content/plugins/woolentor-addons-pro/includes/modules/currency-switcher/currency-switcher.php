<?php
namespace WoolentorPro\Modules\CurrencySwitcher;
use WooLentorPro\Traits\ModuleBase;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Currency_Switcher{
    use ModuleBase;

    /**
     * Constructor
     */
    private function __construct() {
        // Define Pro Constants
        $this->define_constants();

        // Load required files
        $this->includes();

        // Initialize classes
        $this->init();
    }

    /**
     * Define Pro Constants
     */
    private function define_constants(){
        define( 'WoolentorPro\Modules\CurrencySwitcher\MODULE_PATH', __DIR__ );
        define( 'WoolentorPro\Modules\CurrencySwitcher\ENABLED', self::$_enabled );
    }

    /**
     * Include required files
     */
    private function includes() {

        // Include helper functions
        if ( file_exists( MODULE_PATH . '/includes/functions.php' ) ) {
            require_once MODULE_PATH . '/includes/functions.php';
        }

        // Include Admin files
        if ( file_exists( MODULE_PATH . '/includes/classes/Admin.php' ) ) {
            require_once( MODULE_PATH . '/includes/classes/Admin.php' );
        }

        // Include Frontend files
        if ( file_exists( MODULE_PATH . '/includes/classes/Frontend.php' ) ) {
            require_once( MODULE_PATH . '/includes/classes/Frontend.php' );
        }
    }

    /**
     * Initialize classes
     */
    private function init() {

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

Currency_Switcher::instance(\Woolentor\Modules\CurrencySwitcher\ENABLED);