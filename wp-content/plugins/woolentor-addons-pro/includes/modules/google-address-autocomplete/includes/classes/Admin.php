<?php
/**
 * Admin handler for Google Address Autocomplete module
 */
namespace Woolentor\Modules\GoogleAddressAutocomplete;
use WooLentorPro\Traits\Singleton;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    use Singleton;

    /**
     * Class constructor
     */
    private function __construct() {
        $this->includes();
        $this->init();
    }

    /**
     * Include required admin files
     */
    private function includes() {
        require_once( __DIR__ . '/Admin/Fields.php' );
    }

    /**
     * Initialize admin components
     */
    public function init() {
        Admin\Fields::instance();
    }
}
