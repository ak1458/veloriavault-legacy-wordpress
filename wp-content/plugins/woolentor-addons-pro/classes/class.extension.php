<?php 
/**
* WooLentor Extension
*/
class WooLentor_Extension{

    private static $instance = null;
    public static function instance(){
        if( is_null( self::$instance ) ){
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct(){

        $this->include_file();
        
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        if( woolentor_get_option_pro( 'single_product_sticky_add_to_cart', 'woolentor_others_tabs', 'off' ) == 'on' ){
            add_action( 'woolentor_footer_render_content', [ $this, 'sticky_single_add_to_cart' ], 15 );
        }

        if( woolentor_get_option_pro( 'redirect_add_to_cart', 'woolentor_others_tabs', 'off' ) == 'on' ){
            add_filter( 'woocommerce_add_to_cart_redirect', [ $this, 'redirect_checkout_add_cart' ], 99, 2 );
        }

    }

    // Scripts enqueue
    public function enqueue_assets(){
        if ( is_product() ){
            wp_enqueue_script( 'woolentor-main' );
        }
    }

    /**
     * [sticky_single_add_to_cart] Single Product Sticky Add to Cart
     * @return [void]
     */
    public function sticky_single_add_to_cart(){
        global $product;
        if ( ! is_product() ) return;
        require( WOOLENTOR_TEMPLATE_PRO .'tmp-sticky_add_to_cart.php' );
    }


    /**
     * [include_file] Nessary File Required
     * @return [void]
     */
    public function include_file(){
        
        // Quick Add to cart
        require( WOOLENTOR_ADDONS_PL_PATH_PRO .'classes/class.quick_add_to_cart.php' );
        
    }

    /**
     * [redirect_checkout_add_cart]
     * @return [url] checkout page url
     */
    public function redirect_checkout_add_cart( $url, $adding_to_cart ) {
        $url = wc_get_checkout_url();
        return $url;
    }

}

WooLentor_Extension::instance();