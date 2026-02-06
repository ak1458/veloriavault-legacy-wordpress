<?php
namespace WoolentorPro\Modules\QuickView;
use WooLentorPro\Traits\ModuleBase;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( !trait_exists('\WooLentorPro\Traits\ModuleBase') ) {
    woolentor_include_all_pro(WOOLENTOR_ADDONS_PL_PATH_PRO.'includes/traits');
}

class Quick_View{
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
        define( 'WoolentorPro\Modules\QuickView\MODULE_PATH', __DIR__ );
        define( 'WoolentorPro\Modules\QuickView\WIDGETS_PATH', MODULE_PATH. "/includes/widgets" );
        define( 'WoolentorPro\Modules\QuickView\BLOCKS_PATH', MODULE_PATH. "/includes/blocks" );
    }

    /**
     * Load Required File
     *
     * @return void
     */
    public function include(){
        require_once( MODULE_PATH. "/includes/classes/Frontend.php" );
        require_once( MODULE_PATH. "/includes/classes/Widgets_And_Blocks.php" );
    }

    /**
     * Module Initilize
     *
     * @return void
     */
    public function init(){

        // For Frontend
        if ( $this->is_request( 'frontend' ) ) {
            Frontend::instance();
        }

        // Register Widget and blocks
        if( self::$_enabled ){
            Widgets_And_Blocks::instance();
        }
        

    }


}
Quick_View::instance(true);
