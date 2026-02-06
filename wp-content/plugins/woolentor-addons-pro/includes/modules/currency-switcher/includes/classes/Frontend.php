<?php
namespace WoolentorPro\Modules\CurrencySwitcher;
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
        add_action( 'init', [ $this, 'init' ] );
    }

    /**
     * Load Required files
     *
     * @return void
     */
    private function includes(){

        if(file_exists(__DIR__. '/Frontend/Geolocation.php')){
            require_once( __DIR__. '/Frontend/Geolocation.php' );
        }
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init(){
        if(class_exists(__NAMESPACE__.'\Frontend\Geolocation')){
            Frontend\Geolocation::instance();
        }
    }

}