<?php
namespace Woolentor\Modules\Order_Bump;
use WooLentorPro\Traits\ModuleBase;

// If this file is accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main class
 *
 * @since 1.0.0
 */
class Order_Bump {
    use ModuleBase;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct(){
        $this->define_constants();
        $this->includes();
        $this->init();
    }

    /**
     * Define the required constants.
     *
     * @since 1.0.0
     */
    private function define_constants(){
        define('Woolentor\Modules\Order_Bump\MODULE_FILE', __FILE__);
        define('Woolentor\Modules\Order_Bump\MODULE_PATH', __DIR__);
        define('Woolentor\Modules\Order_Bump\MODULE_URL', plugins_url('', MODULE_FILE));
        define('Woolentor\Modules\Order_Bump\MODULE_ASSETS', MODULE_URL . '/assets');
        define('Woolentor\Modules\Order_Bump\WIDGETS_PATH', MODULE_PATH . "/includes/widgets");
        define('Woolentor\Modules\Order_Bump\BLOCKS_PATH', MODULE_PATH . "/includes/blocks");
    }

    /**
     * Include required core files.
     *
     * @since 1.0.0
     */
    public function includes(){
        require_once WOOLENTOR_ADDONS_PL_PATH_PRO . 'includes/modules/email-automation/libs/wloptf/wloptf.php';
        require_once MODULE_PATH . '/includes/class-helper.php';
        require_once MODULE_PATH . '/includes/class-ajax-actions.php';
        require_once MODULE_PATH . '/includes/class-manage-rules.php';

        // Admin
        require_once MODULE_PATH . '/includes/Admin/class-admin.php';
        require_once MODULE_PATH . '/includes/Admin/class-metaboxes.php';
        require_once MODULE_PATH . '/includes/Admin/class-order-bumps-list-table.php';
        require_once MODULE_PATH . '/includes/Admin/class-customize-cpt.php';

        // Frontend
        require_once MODULE_PATH . '/includes/Frontend/class-frontend.php';
        require_once MODULE_PATH . '/includes/Widgets_And_Blocks.php';
    }

    /**
     * Initialize the plugin.
     */
    public function init(){

        if( $this->is_request('ajax') ){
            Ajax_Actions::instance();
        }

        if ( self::$_enabled && $this->is_request('frontend') ) {
            Frontend::instance();
        }

        if ($this->is_request('admin')) {
            Admin::instance();
        }

        if (class_exists('\Woolentor\Modules\Order_Bump\Widgets_And_Blocks')) {
            Widgets_And_Blocks::instance();
        }

    }
}

/**
 * Returns the main instance of Order Bump Module.
 */
function order_bump($enabled){ // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
    return Order_Bump::instance($enabled);
}

// Kick-off the module
order_bump(true);