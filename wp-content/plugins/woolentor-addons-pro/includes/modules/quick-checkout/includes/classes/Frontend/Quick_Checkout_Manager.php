<?php
namespace Woolentor\Modules\QuickCheckout\Frontend;
use WooLentorPro\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Quick_Checkout_Manager {
   use Singleton;

    public function __construct(){
        add_action( 'woolentor_footer_render_content', [ $this, 'quick_checkout_popup_wrapper' ], 10 );
        add_action( 'init', [ $this, 'init' ] );

        // Insert To Cart
        add_action( 'wp_ajax_woolentor_quick_checkout_insert_to_cart', [ $this, 'insert_to_cart' ] );
		add_action( 'wp_ajax_nopriv_woolentor_quick_checkout_insert_to_cart', [ $this, 'insert_to_cart' ] );

    }

    // HTML Wrapper for Qucick Checkout
    public function quick_checkout_popup_wrapper(){
        woolentor_get_template( 'quick-checkout-modal-wrap', null, true, \Woolentor\Modules\QuickCheckout\TEMPLATE_PATH );
    }

    // Init
    public function init(){
        if( $this->is_fire_quick_checkout_request() ){
            if('yes' === get_option( 'woocommerce_cart_redirect_after_add' )){
				add_filter('woocommerce_add_to_cart_redirect', [$this, 'redirect_checkout_add_cart'], 99, 2);
			}
            add_filter( 'woocommerce_add_to_cart_validation', [$this, 'clear_item_from_cart_before_add_to_cart'], 20, 3 );
			add_filter( 'wc_add_to_cart_message_html', [$this, 'remove_cart_message_after_added'] );
        }
    }

    /**
     * [is_fire_quick_checkout_request] If checkout action is fire
     * @return boolean
     */
    public function is_fire_quick_checkout_request() {

        if ( !isset( $_GET['nonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'woolentor_quickcheckout' ) ) {
            return false;
        }else{
            if( isset( $_GET['woolentor_quick_checkout'] ) && $_GET['woolentor_quick_checkout'] == 'quickcheckout' ){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * Redirect checkout page
     * @param mixed $url
     * @param mixed $adding_to_cart
     * @return string
     */
    public function redirect_checkout_add_cart( $url, $adding_to_cart ) {
        $url = wc_get_checkout_url();
        return $url;
    }

    /**
     * [clear_item_from_cart_before_add_to_cart]
     * @return [bool]
     */
    public function clear_item_from_cart_before_add_to_cart( $passed, $product_id, $quantity ) {
		if( !\WC()->cart->is_empty() ){
			\WC()->cart->empty_cart();
		}
		return $passed;
	}

    /**
     * [remove_cart_message_after_added]
     * @return string
     */
    public function remove_cart_message_after_added() {
		return '';
	}

    /**
     * [insert_to_cart] Insert to cart if redirect to checkout page.
     * @return void
     */
    public function insert_to_cart(){

        if ( !$this->is_fire_quick_checkout_request() ){
            $errormessage = [
                'message'  => __('Security Verification Failed !','woolentor')
            ];
            wp_send_json_error( $errormessage );
        }else{
            if ( isset( $_GET['product_id'] ) ) {
                $product_id  = absint( $_GET['product_id'] );
                $get_product = wc_get_product( $product_id );

                if( !\WC()->cart->is_empty() ){
                    \WC()->cart->empty_cart();
                }

                if ( $get_product && is_a( $get_product, 'WC_Product' ) ) {

                    if ( $get_product->is_type( 'simple' ) ) {
						
						$adding_status = \WC()->cart->add_to_cart( $product_id );

						if ( $adding_status ) {
							$redirect_url = wc_get_checkout_url();
						} else {
							$redirect_url = wp_get_referer();
							if ( empty( $redirect_url ) ) {
								$redirect_url = site_url();
							}
						}
					} elseif ( $get_product->is_type('external') ) {
						$redirect_url = $get_product->get_product_url();
					} else {
						$redirect_url = $get_product->get_slug();
					}

                    $message = [
                        'message' => __('Successfully added !','woolentor'),
                        'url'     => $redirect_url
                    ];
                    wp_send_json_success( $message );


                }
            }
        }
        wp_die();

    }
    

}