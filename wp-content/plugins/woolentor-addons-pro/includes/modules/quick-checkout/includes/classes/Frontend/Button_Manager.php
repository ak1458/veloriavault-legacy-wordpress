<?php
namespace Woolentor\Modules\QuickCheckout\Frontend;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Manage Button class
 */
class Button_Manager {
    use Singleton;
    
    /**
     * Class construct
     */
    private function __construct() {
        add_action( 'init', [ $this, 'button_manager' ] );
    }

    /**
     * [button_manager] Button Manager
     * @return [void]
     */
    public function button_manager(){

        $button_position  = woolentor_get_option( 'button_position', 'woolentor_quick_checkout_settings', 'before_cart_btn' );       
        
        // Shop Button Position
        if( $button_position !== 'use_shortcode' && $this->is_enable() == 'on' ){
            switch ( $button_position ) {
                case 'before_cart_btn':
                    add_action( 'woocommerce_after_shop_loop_item', [ $this, 'button_print' ], 7 );
                    break;

                case 'top_thumbnail':
                    add_action( 'woocommerce_before_shop_loop_item', [ $this, 'button_print' ], 5 );
                    break;
                
                default:
                    add_action( 'woocommerce_after_shop_loop_item', [ $this, 'button_print' ], 20 );
                    break;
            }
        }

        // Button For Single Product page
        add_action('woocommerce_before_add_to_cart_button', [ $this, 'button_print_for_single_product' ], 10);


    }

    /**
     * [button_print]
     * @return [void]
     */
    public function button_print(){
        echo woolentor_do_shortcode( 'woolentor_quick_checkout_button' );
    }

    /**
     * [button_html] Button HTML
     * @param  [type] $atts template attr
     * @return [HTML]
     */
    public function button_html( $atts ) {
        $button_attr = apply_filters( 'woolentor_quick_checkout_button_arg', $atts );
        return woolentor_get_template( 'quick-checkout-'.$button_attr['template_name'], $button_attr, false, \Woolentor\Modules\QuickCheckout\TEMPLATE_PATH );
    }

    /**
     * [button_print_for_single_product] Button For Single Product page
     * @return [string]
     */
    public function button_print_for_single_product(){
        echo woolentor_do_shortcode( 'woolentor_quick_checkout_button', ['is_single_product' => true] );
    }

    /**
     * [is_enable] Check quick checkout enable
     * @return [bool]
     */
    public function is_enable(){

        $enable_module = woolentor_get_option( 'enable', 'woolentor_quick_checkout_settings', 'off' ) === 'on';
        $enable_button_shop_page = woolentor_get_option( 'enable_on_shop_archive', 'woolentor_quick_checkout_settings', 'on' ) === 'on';

        if( $enable_module && $enable_button_shop_page ){
            $show_button = 'on';
        }else{
            $show_button = 'off';
        }

        return $show_button;

    }


}