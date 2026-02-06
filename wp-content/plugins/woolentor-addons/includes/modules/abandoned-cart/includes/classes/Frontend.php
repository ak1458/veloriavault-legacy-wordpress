<?php
namespace Woolentor\Modules\AbandonedCart;
use WooLentor\Traits\Singleton;
use Woolentor\Modules\AbandonedCart\Frontend\Cart_Recovery;
use Woolentor\Modules\AbandonedCart\Frontend\Unsubscribe_Manager;
use Woolentor\Modules\AbandonedCart\Frontend\Checkout_Data_Manager;

class Frontend {
    use Singleton;

    /**
     * Constructor
     */
    private function __construct() {
        $this->include();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    public function include(){
        require_once MODULE_INCLUDES_PATH . '/classes/Frontend/Cart_Recovery.php';
        require_once MODULE_INCLUDES_PATH . '/classes/Frontend/Unsubscribe_Manager.php';
        require_once MODULE_INCLUDES_PATH . '/classes/Frontend/Checkout_Data_Manager.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        Cart_Recovery::instance();
        Unsubscribe_Manager::instance();
        Checkout_Data_Manager::instance();
    }

}