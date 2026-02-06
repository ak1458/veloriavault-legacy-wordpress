<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Main WooCommerce Endpoint class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 * @version 3.0.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.


if ( ! class_exists( 'YITH_WCCL_WC_Endpoints' ) ) {
	/**
	 * YITH WooCommerce Color and Label Variations Premium Block Class
	 *
	 * @since 3.0.0
	 */
	class YITH_WCCL_WC_Endpoints
    {

        /**
         * Single instance of the class
         *
         * @since 3.0.0
         * @var YITH_WCCL_WC_Endpoints
         */
        protected static $instance;

        /**
         * Returns single instance of the class
         *
         * @return YITH_WCCL_WC_Endpoints
         * @since 3.0.0
         */
        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function __construct() {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
        }

        /**
         * Enqueue styles and scripts
         *
         * @return void
         */
        public function enqueue_scripts() {
            global $post;

            if ( has_block( 'woocommerce/cart', $post ) ) {
                $deps = include YITH_WCCL_DIR . 'assets/js/build/wc-blocks/edit-link/index.asset.php';

                wp_enqueue_script(
                    'yith-wccl-edit-link',
                    YITH_WCCL_ASSETS_URL . '/js/build/wc-blocks/edit-link/index.js',
                    $deps['dependencies'],
                    $deps['version'],
                    true
                );
            }
        }


    }
}

/**
 * Unique access to instance of YITH_WCCL_WC_Blocks class
 *
 * @return YITH_WCCL_WC_Endpoints
 * @since 3.0.0
 */
function YITH_WCCL_WC_Endpoints()
{ // phpcs:ignore WordPress.NamingConventions
    return YITH_WCCL_WC_Endpoints::get_instance();
}