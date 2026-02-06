<?php
namespace Woolentor\Modules\AbandonedCart;
use WooLentor\Traits\Singleton;

class Admin {
    use Singleton;

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        Admin\Fields::instance();
        // Enqueue assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Load Required files
     *
     * @return void
     */
    private function includes(){
        require_once( __DIR__. '/Admin/Fields.php' );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts( $hook ) {
        wp_enqueue_editor();
    }


}