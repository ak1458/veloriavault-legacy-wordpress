<?php
/**
 * Google Address Autocomplete Module
 *
 * Provides Google Places API address autocomplete functionality
 * for WooCommerce checkout billing and shipping address fields.
 */
namespace Woolentor\Modules\GoogleAddressAutocomplete;
use WooLentorPro\Traits\ModuleBase;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Google_Address_Autocomplete {
    use ModuleBase;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->include();
        $this->init();
    }

    /**
     * Define module constants
     */
    private function define_constants() {
        define( 'Woolentor\Modules\GoogleAddressAutocomplete\MODULE_FILE', __FILE__ );
        define( 'Woolentor\Modules\GoogleAddressAutocomplete\MODULE_PATH', __DIR__ );
        define( 'Woolentor\Modules\GoogleAddressAutocomplete\MODULE_URL', plugins_url( '', MODULE_FILE ) );
        define( 'Woolentor\Modules\GoogleAddressAutocomplete\MODULE_ASSETS', MODULE_URL . '/assets' );
        define( 'Woolentor\Modules\GoogleAddressAutocomplete\ENABLED', self::$_enabled );
    }

    /**
     * Include required files
     */
    private function include() {
        require_once( MODULE_PATH . '/includes/classes/Admin.php' );
        require_once( MODULE_PATH . '/includes/classes/Frontend.php' );
    }

    /**
     * Initialize module components
     */
    private function init() {
        // Initialize admin on admin/REST requests
        if ( $this->is_request( 'admin' ) || $this->is_request( 'rest' ) ) {
            Admin::instance();
        }

        // Initialize frontend only if module is enabled
        if ( self::$_enabled ) {
            if ( $this->is_request( 'frontend' ) ) {
                Frontend::instance();
            }
        }
    }
}
