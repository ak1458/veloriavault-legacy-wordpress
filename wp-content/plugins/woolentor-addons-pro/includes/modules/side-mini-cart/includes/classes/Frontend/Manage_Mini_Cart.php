<?php
namespace Woolentor\Modules\SideMiniCart\Frontend;
use WooLentorPro\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Manage_Mini_Cart {
    use Singleton;

    public function __construct(){
        add_action( 'woolentor_footer_render_content', [ $this, 'mini_cart' ] );
        add_action( 'woolentor_cart_content', [ $this, 'get_cart_item' ] );
        add_filter( 'woocommerce_add_to_cart_fragments', [ $this,'wc_add_to_cart_fragment' ], 10, 1 );
    }

    /**
     * Mini Cart Template
     * @return void
     */
    public function mini_cart(){
        require( WOOLENTOR_TEMPLATE_PRO .'tmp-mini_cart.php' );
    }

    /**
     * [get_cart_item] Render fragment cart item
     * @return [html]
     */
    public function get_cart_item(){

        $args = [];
        ob_start();
        $mini_cart_tmp_id = method_exists( 'Woolentor_Template_Manager', 'get_template_id' ) ? \Woolentor_Template_Manager::instance()->get_template_id( 'mini_cart_layout', 'woolentor_get_option_pro' ) : '0';
        if( !empty( $mini_cart_tmp_id ) ){
            echo method_exists('Woolentor_Manage_WC_Template','render_build_content') ? \Woolentor_Manage_WC_Template::render_build_content( $mini_cart_tmp_id, true ) : '';
        }else{
            wc_get_template( 'tmp-mini_cart_content.php', $args, '', WOOLENTOR_TEMPLATE_PRO );
        }
        echo ob_get_clean();

    }

    /**
     * Cart Item HTML Return For fragment.
     */
    public function cart_item_html(){
        ob_start();
        $this->get_cart_item();
        return ob_get_clean();
    }

    /**
     * [wc_add_to_cart_fragment] add to cart freagment callable
     * @param  [type] $fragments
     * @return [type] $fragments
     */
    public function wc_add_to_cart_fragment( $fragments ){

        $item_count = WC()->cart->get_cart_contents_count();
        $cart_item  = $this->cart_item_html();

        // Cart Item
        $fragments['div.woolentor_cart_content_container'] = '<div class="woolentor_cart_content_container">'.$cart_item.'</div>';

        //Cart Counter
        $fragments['span.woolentor_mini_cart_counter'] = '<span class="woolentor_mini_cart_counter">'.$item_count.'</span>';

        return $fragments;
    }
    

}