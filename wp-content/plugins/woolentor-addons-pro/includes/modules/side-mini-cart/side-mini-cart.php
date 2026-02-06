<?php
namespace Woolentor\Modules\SideMiniCart;
use WooLentorPro\Traits\ModuleBase;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Side_Mini_Cart{
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

        // Block Assets
        add_action( 'enqueue_block_editor_assets', [ $this, 'block_editor_assets' ] );

    }

    /**
     * Defined Required Constants
     *
     * @return void
     */
    public function define_constants(){
        define( 'Woolentor\Modules\SideMiniCart\MODULE_FILE', __FILE__ );
        define( 'Woolentor\Modules\SideMiniCart\MODULE_PATH', __DIR__ );
        define( 'Woolentor\Modules\SideMiniCart\WIDGETS_PATH', MODULE_PATH. "/includes/widgets" );
        define( 'Woolentor\Modules\SideMiniCart\BLOCKS_PATH', MODULE_PATH. "/includes/blocks" );
        define( 'Woolentor\Modules\SideMiniCart\MODULE_URL', plugins_url( '', MODULE_FILE ) );
        define( 'Woolentor\Modules\SideMiniCart\MODULE_ASSETS', MODULE_URL . '/assets' );
        define( 'Woolentor\Modules\SideMiniCart\ENABLED', self::$_enabled );
    }

    /**
     * Load Required File
     *
     * @return void
     */
    public function include(){
        require_once( MODULE_PATH. "/includes/classes/Admin.php" );
        require_once( MODULE_PATH. "/includes/classes/Frontend.php" );
        require_once( MODULE_PATH. "/includes/classes/Widgets_And_Blocks.php" );
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

        // For Frontend
        if ( self::$_enabled && $this->is_request( 'frontend' ) ) {
            Frontend::instance();
        }

        // Register Widget and blocks
        if( self::$_enabled ){
            Widgets_And_Blocks::instance();
        }

    }

    /**
	 * Block editor assets.
	 */
	public function block_editor_assets() {
        wp_enqueue_style('woolentor-mini-cart', MODULE_ASSETS . '/css/frontend.css', [], WOOLENTOR_VERSION_PRO );
    }

}

/**
 * Returns the instance.
 * @todo Need to delete in future
 */
function woolentor_Side_Mini_Cart( $enabled = true ) {
    return Side_Mini_Cart::instance( $enabled );
}