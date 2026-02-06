<?php
namespace WoolentorPro\Modules\StoreVacation;
use WooLentorPro\Traits\Singleton;
use \Woolentor\Modules\StoreVacation\Frontend as FrontendBase;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Frontend handlers class
 */
class Frontend {
    use Singleton;
    
    /**
     * Initialize the class
     */
    private function __construct() {
        $this->includes();
        $this->init();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Load Required files
     *
     * @return void
     */
    private function includes(){
        require_once( __DIR__. '/Frontend/Manage_Notice.php' );
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init(){
        Frontend\Manage_Notice::instance();
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts(){
        if(!FrontendBase::is_vacation_active()){
            return;
        }

        wp_enqueue_style(
            'woolentor-vacation',
            MODULE_ASSETS . '/vacation.css',
            [],
            WOOLENTOR_VERSION
        );

        wp_enqueue_script(
            'woolentor-vacation',
            MODULE_ASSETS . '/vacation.js',
            ['jquery'],
            WOOLENTOR_VERSION,
            true
        );
    }

    

}