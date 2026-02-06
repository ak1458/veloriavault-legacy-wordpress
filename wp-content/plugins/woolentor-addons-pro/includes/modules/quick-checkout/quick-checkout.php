<?php
namespace Woolentor\Modules\QuickCheckout;
use WooLentorPro\Traits\ModuleBase;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Quick_Checkout{
    use ModuleBase;

    /**
     * Class Constructor
     */
    public function __construct(){

        // Definded Constants
        $this->define_constants();

        // Include Nessary file
        $this->include();

        // initialize
        $this->init();

    }

    /**
     * Defined Required Constants
     *
     * @return void
     */
    public function define_constants(){
        define( 'Woolentor\Modules\QuickCheckout\MODULE_FILE', __FILE__ );
        define( 'Woolentor\Modules\QuickCheckout\MODULE_PATH', __DIR__ );
        define( 'Woolentor\Modules\QuickCheckout\TEMPLATE_PATH', MODULE_PATH. "/includes/templates/" );
        define( 'Woolentor\Modules\QuickCheckout\MODULE_URL', plugins_url( '', MODULE_FILE ) );
        define( 'Woolentor\Modules\QuickCheckout\MODULE_ASSETS', MODULE_URL . '/assets' );
        define( 'Woolentor\Modules\QuickCheckout\ENABLED', self::$_enabled );
    }

    /**
     * Load Required File
     *
     * @return void
     */
    public function include(){
        require_once( MODULE_PATH. "/includes/classes/Admin.php" );
        require_once( MODULE_PATH. "/includes/classes/Frontend.php" );
    }

    /**
     * Module Initilize
     *
     * @return void
     */
    public function init(){
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

/**
 * Returns the instance.
 * @todo Need to delete in future
 */
function woolentor_QuickCheckout( $enabled = true ) {
    return Quick_Checkout::instance( $enabled );
}