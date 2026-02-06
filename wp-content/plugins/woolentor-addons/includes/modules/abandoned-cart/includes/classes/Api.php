<?php
namespace Woolentor\Modules\AbandonedCart;
use WooLentor\Traits\Singleton;

/**
 * REST_API Handler
 */
class Api {
    use Singleton;

    /**
     * [__construct description]
     */
    public function __construct() {
        $this->includes();

        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Include the controller classes
     *
     * @return void
     */
    private function includes() {
        if ( !class_exists( __NAMESPACE__ . '\Api\Cart_Data'  ) ) {
            require_once __DIR__ . '/Api/Cart_Data.php';
        }
        if ( !class_exists( __NAMESPACE__ . '\Api\Email_Template'  ) ) {
            require_once __DIR__ . '/Api/Email_Template.php';
        }
    }

    /**
     * Register the API routes
     *
     * @return void
     */
    public function register_routes() {
        (new Api\Cart_Data())->register_routes();
        (new Api\Email_Template())->register_routes();
    }

}