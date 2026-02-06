<?php
namespace Woolentor\Modules\QuickCheckout\Frontend;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Shortcode handler class
 */
class Shortcode {
    use Singleton;

    /**
     * [__construct] Class construct
     */
    function __construct() {
        add_shortcode( 'woolentor_quick_checkout_button', [ $this, 'button_shortcode' ] );
    }

    /**
     * [button_shortcode] Button Shortcode callable function
     * @param  [type] $atts 
     * @param  string $content
     * @return [HTML] 
     */
    public function button_shortcode( $atts, $content = '' ){

        global $product;
        $product_id = $product_url = '';
        $is_redirect_single_page = false;
        if ( $product && is_a( $product, 'WC_Product' ) ) {
            $product_id  = $product->get_id();
            $product_url = $product->get_permalink();
            $is_redirect_single_page = ( $product->is_type( 'variable' ) || $product->is_type( 'external' ) ) ? true : false;
        }

        $is_single_product = !empty( $atts['is_single_product'] ) && $atts['is_single_product'] === true;

        /**
         * Get Settings data
         */
        $button_text      = woolentor_get_option_pro('button_text','woolentor_quick_checkout_settings','Buy Now');
        $button_position  = woolentor_get_option_pro( 'button_position', 'woolentor_quick_checkout_settings', 'before_cart_btn' );
        $icon_type        = woolentor_get_option_pro( 'button_icon_type', 'woolentor_quick_checkout_settings', 'default' );
        $icon_position    = woolentor_get_option_pro( 'button_icon_position', 'woolentor_quick_checkout_settings', 'before_text' );
        $checkout_mode    = woolentor_get_option_pro( 'checkout_mode', 'woolentor_quick_checkout_settings', 'popup' );

        $button_icon  = $this->get_icon();
        if( !empty( $button_text ) ){
            $button_text_html = '<span class="woolentor-quick-checkout-btn-text">'.wp_kses_post($button_text).'</span>';
        }else{
            $button_text_html = '';
        }

        $button_class = [
            'woolentor-quick-checkout-button',
            'woolentor-quick-checkout-btn-pos-'.$button_position,
            'woolentor-quick-checkout-btn-icon-'.$icon_type,
            'woolentor-quick-checkout-icon-pos-'.$icon_position,
        ];

        if( $is_single_product ){
            $button_class[] = 'woolentor-quick-checkout-button-single-product';
        }else{
            if( $is_redirect_single_page ){
                $button_class[] = 'woolentor-quick-checkout-redirect-product-page';
            }
        }

        $checkout_url = add_query_arg( ['nonce' => wp_create_nonce('woolentor_quickcheckout')], wc_get_checkout_url() );
        if( $checkout_mode === 'redirect' && !$is_single_product){
            $url_query_args = [ 
                'action'    => 'woolentor_quick_checkout_insert_to_cart',
                'woolentor_quick_checkout' => 'quickcheckout',
                'nonce'     => wp_create_nonce('woolentor_quickcheckout'),
                'product_id'=> $product_id 
            ];
            $checkout_url = add_query_arg( $url_query_args, admin_url( 'admin-ajax.php' ) );
        }

        // Shortcode atts
        $default_atts = [
            'button_url'    => $product_url,
            'product_id'    => $product_id,
            'checkout_url'  => $checkout_url,
            'checkout_mode' => $checkout_mode,
            'button_class'  => implode(' ', $button_class ),
            'button_title'  => $button_text,
            'button_text_icon'=> $button_icon.$button_text_html,
            'template_name'  => 'button',
        ];
        
        $atts = shortcode_atts( $default_atts, $atts, $content );
        return Button_Manager::instance()->button_html( $atts );

    }

    /**
     * [get_icon]
     * @param  string $type
     * @return [HTML]
     */
    public function get_icon(){
        
        $button_text        = woolentor_get_option_pro( 'button_text', 'woolentor_quick_checkout_settings', 'Buy Now' );
        $button_icon_type   = woolentor_get_option_pro( 'button_icon_type', 'woolentor_quick_checkout_settings', 'none' );

        if( $button_icon_type === 'customicon' ){
            $button_icon = woolentor_get_option_pro( 'button_icon','woolentor_quick_checkout_settings', 'sli sli-wallet' );
            return '<span class="woolentor-quick-checkout-btn-icon"><i class="'.esc_attr( $button_icon ).'"></i></span>';
        }elseif( $button_icon_type === 'customimage' ){
            $button_image = woolentor_get_option_pro( 'button_custom_image','woolentor_quick_checkout_settings', '' );
            return '<span class="woolentor-quick-checkout-btn-image"><img src="'.esc_url( $button_image ).'" alt="'.esc_attr( $button_text ).'" /></span>';
        }else{
            $button_icon = '';
        }

        return $button_icon;

    }


}