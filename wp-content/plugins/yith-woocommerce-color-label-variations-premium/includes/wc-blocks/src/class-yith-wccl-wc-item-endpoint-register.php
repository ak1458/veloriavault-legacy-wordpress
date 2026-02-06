<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Main WooCommerce cart item endpoint
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 * @version 3.0.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;


if ( ! class_exists( 'YITH_WCCL_WC_Cartitem_Endpoint' ) ) {
	/**
	 * YITH WooCommerce Color and Label Variations Premium Block Class
	 *
	 * @since 3.0.0
	 */
	class YITH_WCCL_WC_Cartitem_Endpoint
    {

        /**
         * Single instance of the class
         *
         * @since 3.0.0
         * @var YITH_WCCL_WC_Blocks
         */
        protected static $instance;

        /**
         * Returns single instance of the class
         *
         * @return YITH_WCCL_WC_Blocks
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
            add_action( 'woocommerce_blocks_loaded', array( $this, 'register_endpoint' ) );
        }

        /**
         * Register the endpoint
         *
         * @return void
         */
        public function register_endpoint() {
            woocommerce_store_api_register_endpoint_data(
                array(
                    'endpoint'        => CartItemSchema::IDENTIFIER,
                    'namespace'       => 'yith_wccl_wc_cart_item_manager',
                    'data_callback'   => array( $this, 'extend_cart_item_data' ),
                    'schema_callback' => array( $this, 'extend_cart_item_schema' ),
                    'schema_type'     => ARRAY_A,
                )
            );
        }

        /**
         * Return the data
         *
         * @return array
         */
        public function extend_cart_item_data( $cart_item ) {
            $data = array();
            if ( isset( $cart_item['key'] ) ) {
                $data['edit_link']     = YITH_WCCL_Frontend::get_instance()->display_edit_product_link( $cart_item, $cart_item['key'], true ) ?? '';
            }
            return $data;
        }

        /**
         * Return the schema
         *
         * @return array
         */
        public function extend_cart_item_schema( ) {
            return array(
                'edit_link'      => array(
                    'description' => __( 'Button for editing the attributes on the Cart page.', 'yith-woocommerce-color-label-variations' ),
                    'type'        => array( 'string', 'null' ),
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
            );
        }
    }
}

/**
 * Unique access to instance of YITH_WCCL_WC_Blocks class
 *
 * @return YITH_WCCL_WC_Cartitem_Endpoint
 * @since 3.0.0
 */
function YITH_WCCL_WC_Cartitem_Endpoint()
{ // phpcs:ignore WordPress.NamingConventions
    return YITH_WCCL_WC_Cartitem_Endpoint::get_instance();
}