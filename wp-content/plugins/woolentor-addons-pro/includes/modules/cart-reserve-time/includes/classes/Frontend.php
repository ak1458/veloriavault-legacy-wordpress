<?php
namespace WoolentorPro\Modules\CartReserveTime;
use WooLentorPro\Traits\Singleton;
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
    }

    /**
     * Load Required files
     *
     * @return void
     */
    private function includes(){
        require_once( __DIR__. '/Frontend/Manage_Reserved_Time.php' );
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init(){
        Frontend\Manage_Reserved_Time::instance();
    }

    

}