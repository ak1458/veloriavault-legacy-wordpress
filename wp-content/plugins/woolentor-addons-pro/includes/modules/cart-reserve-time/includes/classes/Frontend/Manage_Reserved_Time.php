<?php
namespace WoolentorPro\Modules\CartReserveTime\Frontend;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Manage_Reserved_Time {
    use Singleton;

    /**
     * Class __construct
     */
    public function __construct(){
        add_filter( 'woolentor_cart_reserve_notice_html', [ $this, 'modify_notice_html' ], 10, 2 );
        add_filter( 'woolentor_cart_reserve_time', [ $this, 'get_product_specific_time' ], 10, 2 );
        add_action( 'woolentor_cart_reserve_expired', [ $this, 'handle_expiration' ] );
    }

    /**
     * Modify Notice HTML
     */
    public function modify_notice_html( $html, $data ){
        $style = woolentor_get_option( 'notice_style', 'woolentor_cart_reserve_timer_settings', 'style-1' );
        $bg_color = woolentor_get_option( 'background_color', 'woolentor_cart_reserve_timer_settings', '#f7f6f7' );
        $text_color = woolentor_get_option( 'text_color', 'woolentor_cart_reserve_timer_settings', '#515151' );
        $timer_color = woolentor_get_option( 'timer_color', 'woolentor_cart_reserve_timer_settings', '#ff6b6b' );
        $content_align = woolentor_get_option( 'content_align', 'woolentor_cart_reserve_timer_settings', 'left' );

        $html = str_replace( 'class="woolentor-cart-reserve-notice"', 'class="woolentor-cart-reserve-notice woolentor-notice-' . esc_attr( $style ) . '"', $html );
        
        $custom_style = "
            <style>
                .woolentor-cart-reserve-notice {
                    background-color: {$bg_color};
                    color: {$text_color};
                    text-align: {$content_align};
                }
                .woolentor-timer {
                    color: {$timer_color};
                }
            </style>
        ";

        return $custom_style . $html;
    }

    /**
     * Get cart reserve time
     */
    private function get_reserve_time( $product_ids ) {
        $individual_product_time = 0;

        $specific_timer = [
            'custom' => 0,
            'category' => 0
        ];

        if(is_array($product_ids) && !empty($product_ids)){

            foreach ($product_ids as $key => $product_id ) {
               // Check if product has custom timer enabled
                $custom_timer_enabled = get_post_meta( $product_id, '_woolentor_enable_custom_timer', true );
                if( $custom_timer_enabled === 'yes' ) {
                    $custom_duration = get_post_meta( $product_id, '_woolentor_custom_timer_duration', true );
                    if( !empty( $custom_duration ) ) {
                        // If multiple products have custom timer, use the longest duration
                        $individual_product_time = max( $individual_product_time, absint( $custom_duration ) );
                    }
                }

                $product_categories = woolentor_get_option( 'product_categories', 'woolentor_cart_reserve_timer_settings', [] );
                if( !empty( $product_categories ) ){
                    $product_cats = wp_get_post_terms( $product_id, 'product_cat', ['fields' => 'ids'] );
                    $intersect = array_intersect( $product_cats, $product_categories );
                }else{
					$intersect = [];
				}


            }
            $specific_timer['custom'] = $individual_product_time;
            $specific_timer['category'] = $intersect;

            return $specific_timer;

        }else{
            return $specific_timer;
        }

    }


    /**
     * Get Product Specific Time
     */
    public function get_product_specific_time( $time, $product_ids ){
        $enable_per_product = woolentor_get_option( 'enable_per_product', 'woolentor_cart_reserve_timer_settings', 'off' );
        
        if( $enable_per_product === 'on' ){

            $custom_duration = $this->get_reserve_time( $product_ids );

            // Custom Duration If added in product Screen
            if( $custom_duration['custom'] > 0 ){
                return $custom_duration['custom'];
            }

            if( !empty($custom_duration['category']) ){
                return $time; // Use default time for selected categories
            }else{
                return false; // Don't show timer for non-selected categories
            }

        }

        return $time;
    }

    /**
     * Handle Expiration
     */
    public function handle_expiration(){
        $expiration_action = woolentor_get_option( 'expire_action', 'woolentor_cart_reserve_timer_settings', 'hide' );

        switch( $expiration_action ){
            case 'redirect':
                $redirect_url = woolentor_get_option( 'redirect_url', 'woolentor_cart_reserve_timer_settings', '' );
                if( !empty( $redirect_url ) ){
                    wp_safe_redirect( $redirect_url );
                    exit;
                }
                break;

            case 'coupon':
                $coupon_code = woolentor_get_option( 'coupon_code', 'woolentor_cart_reserve_timer_settings', '' );
                if( !empty( $coupon_code ) && !WC()->cart->has_discount( $coupon_code ) ){
                    WC()->cart->apply_coupon( $coupon_code );
                    wc_add_notice( sprintf( 
                        __( 'Coupon code "%s" has been applied to your cart.', 'woolentor' ), 
                        $coupon_code 
                    ) );
                }
                break;
        }

    }

}