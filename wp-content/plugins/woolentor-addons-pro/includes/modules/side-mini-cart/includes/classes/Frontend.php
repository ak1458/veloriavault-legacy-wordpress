<?php
namespace Woolentor\Modules\SideMiniCart;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Frontend handlers class
 */
class Frontend {
    use Singleton;
    
    /**
     * Initialize the class
     */
    private function __construct() {
        $this->includes();
        $this->init();
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Load Required files
     *
     * @return void
     */
    private function includes(){
        require_once( __DIR__. '/Frontend/Manage_Mini_Cart.php' );
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init(){
        Frontend\Manage_Mini_Cart::instance();
    }

    /**
     * Enqueue Scripts
     *
     * @return void
     */
    public function enqueue_scripts(){
        wp_enqueue_style('woolentor-mini-cart', MODULE_ASSETS . '/css/frontend.css', [], WOOLENTOR_VERSION_PRO );
        wp_enqueue_script('woolentor-mini-cart', MODULE_ASSETS . '/js/frontend.js', ['jquery'], WOOLENTOR_VERSION_PRO, true );

        if( isset( $_POST['add-to-cart'] ) ){ $added_to_cart = true; }else{ $added_to_cart = false;}
        $hide_mini_cart = woolentor_get_option_pro( 'empty_mini_cart_hide', 'woolentor_others_tabs', 'off' );

        $localize_data = [
            'addedToCart'  => $added_to_cart,
            'hideMiniCart' => $hide_mini_cart == 'on' ? true : false,
        ];
        wp_localize_script( 'woolentor-mini-cart', 'woolentorMiniCart', $localize_data );


        // Inline CSS
        wp_add_inline_style( 'woolentor-mini-cart', $this->inline_style() );

    }

    /**
     * [inline_style]
     * @return [string]
     */
    public function inline_style(){

        $icon_color     = woolentor_generate_css_pro('mini_cart_icon_color','woolentor_others_tabs','color');
        $icon_bg        = woolentor_generate_css_pro('mini_cart_icon_bg_color','woolentor_others_tabs','background-color');
        $icon_border    = woolentor_generate_css_pro('mini_cart_icon_border_color','woolentor_others_tabs','border-color');

        $counter_color      = woolentor_generate_css_pro('mini_cart_counter_color','woolentor_others_tabs','color');
        $counter_bg_color   = woolentor_generate_css_pro('mini_cart_counter_bg_color','woolentor_others_tabs','background-color');

        $button_color      = woolentor_generate_css_pro('mini_cart_buttons_color','woolentor_others_tabs','color');
        $button_bg_color   = woolentor_generate_css_pro('mini_cart_buttons_bg_color','woolentor_others_tabs','background-color');

        $button_hover_color     = woolentor_generate_css_pro('mini_cart_buttons_hover_color','woolentor_others_tabs','color');
        $button_hover_bg_color  = woolentor_generate_css_pro('mini_cart_buttons_hover_bg_color','woolentor_others_tabs','background-color');

        $custom_css = "
            .woolentor_mini_cart_icon_area{
                {$icon_color}
                {$icon_bg}
                {$icon_border}
            }
            .woolentor_mini_cart_counter{
                {$counter_color}
                {$counter_bg_color}
            }
            .woolentor_button_area a.button{
                {$button_color}
                {$button_bg_color}
            }
            .woolentor_button_area a.button:hover{
                {$button_hover_color}
            }
            .woolentor_button_area a::before{
                {$button_hover_bg_color}
            }
        ";

        return $custom_css;

    }


}