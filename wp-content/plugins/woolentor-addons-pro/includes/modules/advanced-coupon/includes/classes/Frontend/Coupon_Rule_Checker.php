<?php
namespace WoolentorPro\Modules\AdvancedCoupon\Frontend;
use WooLentorPro\Traits\Singleton;
use Woolentor\Modules\AdvancedCoupon\Functions;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Coupon_Rule_Checker {
    use Singleton;

    public function __construct(){
        add_filter('woocommerce_coupon_is_valid', [$this,'woocommerce_coupon_is_valid'], 10, 2);
        if( woolentor_get_option_pro('url_coupon','woolentor_advanced_coupon_settings', 'off') == 'on'){
            add_action( 'template_redirect', [$this, 'manage_url_coupon'] );
        }
    }

    /**
     * Check Coupon Validility
     * @param mixed $is_valid
     * @param mixed $coupon
     * @return mixed
     */
    public function woocommerce_coupon_is_valid($is_valid, $coupon) {
        $coupon_id = $coupon->get_id();

        // Payment Method
        if( $is_valid ){
            $is_valid = $this->checked_payment_method($coupon_id, $is_valid);
        }
    
        return $is_valid;
    }

    /**
     * Checked Payment method
     * @param mixed $coupon_id
     * @param mixed $is_valid
     * @throws \Exception
     * @return mixed
     */
    public function checked_payment_method($coupon_id, $is_valid){

        $payment_method_ids     = Functions::get_multiple_meta_date($coupon_id, 'woolentor_payment_method_ids');
        $payment_restrict_type  = Functions::get_meta_data( $coupon_id , 'woolentor_payment_restrict_type');
        $payment_err_msg        = Functions::get_meta_data($coupon_id, 'woolentor_payment_error_message');
        $payment_err_msg        = !empty($payment_err_msg) ? $payment_err_msg : 'This coupon is not applicable with your selected payment method.';

        // If the payment method is empty, return the current validity status.
        if ( empty($payment_method_ids) ) {
            return $is_valid;
        }

        if (!empty($payment_method_ids)) {
            $woocommerce    = WC();
            $chosen_method  = isset( $woocommerce->session->chosen_payment_method ) ? $woocommerce->session->chosen_payment_method : '';

            // Determine the validity condition based on the restrict type
            $is_method_restricted = in_array($chosen_method, $payment_method_ids);
            $validity_condition = ($payment_restrict_type === 'allowed') ? !$is_method_restricted : $is_method_restricted;

            // Set $is_valid to false and throw an exception if the condition is not met
            if ($validity_condition) {
                $is_valid = false;
                throw new \Exception(esc_html($payment_err_msg), 109);
            }
        }

        return $is_valid;
    }

    /**
     * Manage URL Coupon
     * @return void
     */
    public function manage_url_coupon() {
        global $wp_query;

        if ( !$wp_query->is_main_query()) {
            return;
        }

        $post_type = isset( $wp_query->query['post_type'] ) ? $wp_query->query['post_type'] : 'post';

        if ( $post_type !== 'shop_coupon' ) {
            return;
        }

        $coupon_slug = isset( $wp_query->query['name'] ) ? sanitize_title( $wp_query->query['name'] ) : '';
        $coupon_id   = Functions::get_coupon_id_by_slug( $coupon_slug );
        $enable_url  = Functions::get_meta_data( $coupon_id,'woolentor_coupon_url_enable', 'no' ) === 'yes' ? 'yes' : 'no';
        $redirect_url = Functions::get_meta_data( $coupon_id,'woolentor_coupon_url_redirect_url', wc_get_cart_url() );
        $redirect_url = !empty($redirect_url) ? $redirect_url : wc_get_cart_url();

        if( $enable_url === 'no' ){
            $error_msg = esc_html__( 'URL coupon is not enabled.', 'woolentor' );
            wc_add_notice( $error_msg, 'error' );
            wp_redirect( $redirect_url );
            exit();
        }else{
            $coupon = new \WC_Coupon( $coupon_id );

            // Initialize the cart session.
            \WC()->session->set_customer_session_cookie( true );

            // Do not proceed if the coupon is invalid
            if ( !$coupon->get_id() ) {
                $error_msg = esc_html__( 'The coupon code is invalid.', 'woolentor' );
                wc_add_notice( $error_msg, 'error' );
                wp_redirect( $redirect_url );
                exit();
            }

            // Success Message
            $succes_msg = Functions::get_meta_data( $coupon_id,"woolentor_coupon_url_success_message",esc_html__( 'The coupon was applied successfully.', 'woolentor' ));
            $succes_msg = !empty($succes_msg) ? $succes_msg : esc_html__( 'The coupon was applied successfully.', 'woolentor' );
            add_filter( 'woocommerce_coupon_message', function($msg, $msg_code) use( $succes_msg )  { return $succes_msg; },10, 2);

            // Apply The coupon.
            $is_apply = \WC()->cart->apply_coupon( $coupon->get_code() );
            if( !$is_apply ){
                wp_redirect( $redirect_url );
                exit();
            }else{
                // Clear all notices when redirecting to an external URL.
                if ( strpos( $redirect_url, home_url() ) === false ) {
                    wc_clear_notices();
                }

                wp_redirect( $redirect_url );
                exit();
            }

        }
        
    }
    

}