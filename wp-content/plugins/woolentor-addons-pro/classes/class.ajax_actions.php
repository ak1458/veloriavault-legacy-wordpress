<?php
namespace WooLentorPro;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Ajax_Action{

	/**
	 * [$instance]
	 * @var null
	 */
	private static $instance = null;

	/**
	 * [instance]
	 * @return [Ajax_Action]
	 */
    public static function instance(){
        if( is_null( self::$instance ) ){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * [__construct]
     */
    function __construct(){
        // Execute the ajax function under init hook to work it properly,
        // prior to that hook cart quantity update via ajax won't work properly
        add_action( 'init', [ $this, 'load_ajax' ] );
    }

    public function load_ajax(){
        $this->quantity_update_action();
    }

    /**
     * Quantity Update Action
     *
     * @return void
     */
    public function quantity_update_action(){
        add_action( 'wp_ajax_nopriv_woolentor_update_cart_item_quantity', [ $this, 'update_cart_item_quantity' ] );
        add_action( 'wp_ajax_woolentor_update_cart_item_quantity',        [ $this, 'update_cart_item_quantity' ] );
    }

    /**
     * [update_cart_item_quantity] Update cart item quantity
     * @return [JSON]
     */
    public function update_cart_item_quantity(){
        $qty            = absint($_POST['qty']);
        $cart_item_key  = sanitize_key($_POST['cart_item_key']);

        WC()->cart->set_quantity( $cart_item_key, $qty, true );

        $cart_item = WC()->cart->get_cart_item( $cart_item_key );
        $cart_product = $cart_item['data'];

        $product_price  = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $cart_product ), $cart_item, $cart_item_key );
        $subtotal_price = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $cart_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
        $subtotal_price_html = $subtotal_price;

        // Calculate Regular Price
        $sale_price = $cart_product->get_price();
        $regular_price = $cart_product->get_regular_price();
        $regular_price_html = '';
        if( $sale_price !== $regular_price ){
            $regular_price = ( $cart_product->get_regular_price() * $cart_item['quantity'] );
            $regular_price_html = '<del class="woolentor-price-regular">'.wc_price( $regular_price ).'</del>';
        }

        ob_start();
        WC()->cart->calculate_totals();
        woocommerce_cart_totals();
        $total_overview = ob_get_clean();

        $data = [
            'item_details'      => $cart_item,
            'item_price'        => $product_price,
            'item_subtotal_html'=> $regular_price_html . $subtotal_price_html,
            'total_overview'    => $total_overview,
        ];
        wp_send_json( $data );
    }
       
}

\WooLentorPro\Ajax_Action::instance();
