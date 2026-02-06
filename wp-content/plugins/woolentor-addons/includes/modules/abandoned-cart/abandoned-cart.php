<?php
namespace Woolentor\Modules\AbandonedCart;
use WooLentor\Traits\ModuleBase;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Abandoned_Cart{
    use ModuleBase;

    /**
     * Module version
     */
    const VERSION = '1.0.0';

    /**
     * Class Constructor
     */
    public function __construct(){
        // Define Constants
        $this->define_constants();

        // Include Necessary files
        $this->include();

        // Initialize
        $this->init();
    }

    /**
     * Define Required Constants
     *
     * @return void
     */
    public function define_constants(){
        define( 'Woolentor\Modules\AbandonedCart\MODULE_FILE', __FILE__ );
        define( 'Woolentor\Modules\AbandonedCart\MODULE_PATH', __DIR__ );
        define( 'Woolentor\Modules\AbandonedCart\MODULE_INCLUDES_PATH', __DIR__ . '/includes' );
        define( 'Woolentor\Modules\AbandonedCart\MODULE_URL', plugins_url( '', MODULE_FILE ) );
        define( 'Woolentor\Modules\AbandonedCart\MODULE_ASSETS', MODULE_URL . '/assets' );
        define( 'Woolentor\Modules\AbandonedCart\ENABLED', self::$_enabled );
    }

    /**
     * Load Required Files
     *
     * @return void
     */
    public function include(){
        // Core files
        require_once MODULE_INCLUDES_PATH . '/classes/config/Config.php';
        require_once MODULE_INCLUDES_PATH . '/classes/Database/DB_Handler.php';
        require_once MODULE_INCLUDES_PATH . '/classes/Database/DB_Schema.php';
        require_once MODULE_INCLUDES_PATH . '/classes/Dynamic_Scheduler.php';

        // Cart Manager
        require_once MODULE_INCLUDES_PATH . '/classes/Frontend/Cart_Manager.php';

        // Email handling
        require_once MODULE_INCLUDES_PATH . '/classes/Email/Email_Template_System.php';
        require_once MODULE_INCLUDES_PATH . '/classes/Email/Coupon_Manager.php';
        require_once MODULE_INCLUDES_PATH . '/classes/Email/Placeholder_Manager.php';

        // Admin and Frontend
        require_once MODULE_INCLUDES_PATH . '/classes/Admin.php';
        require_once MODULE_INCLUDES_PATH . '/classes/Frontend.php';

        // Api
        require_once MODULE_INCLUDES_PATH . '/classes/Api.php';

        // Initialize Pro Features (if Pro is active)
        if( $this->is_pro() ){
            if( file_exists( WOOLENTOR_ADDONS_PL_PATH_PRO . 'includes/modules/abandoned-cart/abandoned-cart.php' )){
                require_once( WOOLENTOR_ADDONS_PL_PATH_PRO . 'includes/modules/abandoned-cart/abandoned-cart.php' );
            }
        }
    }

    /**
     * Module Initialize
     *
     * @return void
     */
    public function init(){

        // For Admin Dashboard
        if ( $this->is_request( 'admin' ) || $this->is_request( 'rest' ) ) {
            Admin::instance();
            Api::instance();
            $this->maybe_create_tables();
        }

        $this->add_hooks();

        if( self::$_enabled ){

            // For Frontend
            if ( $this->is_request( 'frontend' ) ) {
                Frontend::instance();
            }

            // Initialize components
            Frontend\Cart_Manager::instance();
            Email\Email_Template_System::instance();
            Email\Coupon_Manager::instance();

        }
    }

    /**
     * Add WordPress hooks
     */
    private function add_hooks() {
        // Dynamic Scheduler
        Dynamic_Scheduler::instance();
        if( self::$_enabled && ( !wp_next_scheduled( 'woolentor_abandoned_cart_check' ) || !wp_next_scheduled( 'woolentor_abandoned_cart_cleanup' ) || !wp_next_scheduled( 'woolentor_process_scheduled_emails' ) )){
            Dynamic_Scheduler::instance()->schedule_events();
        }
    }

    /**
     * Maybe create database tables
     */
    private function maybe_create_tables() {
        $db_version = get_option( 'woolentor_abandoned_cart_db_version', '0' );
        
        if ( version_compare( $db_version, self::VERSION, '<' ) ) {
            require_once MODULE_INCLUDES_PATH . '/classes/Database/DB_Schema.php';
            Database\DB_Schema::instance()->create_tables();
            
            // Update database version
            update_option( 'woolentor_abandoned_cart_db_version', self::VERSION );
        }
    }

}