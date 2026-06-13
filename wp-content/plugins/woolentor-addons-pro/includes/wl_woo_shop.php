<?php
/*
*  Woolentor Pro Manage WooCommerce Builder Page.
*/
class Woolentor_Woo_Custom_Template_Layout_Pro{

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function __construct(){

        add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'woolentor_init_cart' ) );
        add_action('init', array( $this, 'init' ) );
        
    }

    public function init(){

        // Fix for WooCommerce conditional functions when using custom templates
        add_filter( 'woocommerce_is_checkout', array( $this, 'fix_is_checkout' ), 999 );
        add_filter( 'woocommerce_is_cart', array( $this, 'fix_is_cart' ), 999 );
        
        // Cart
        add_action( 'woolentor_cart_content_build', array( $this, 'woolentor_cart_content' ) );
        add_action( 'woolentor_cartempty_content_build', array( $this, 'woolentor_emptycart_content' ) );
        
        // Checkout
        add_action( 'woolentor_checkout_content', array( $this, 'woolentor_checkout_content' ) );
        add_action( 'woolentor_checkout_top_content', array( $this, 'woolentor_checkout_top_content' ) );

        // Thank you Page
        add_action( 'woolentor_thankyou_content', array( $this, 'woolentor_thankyou_content' ) );

        // MyAccount
        add_action( 'woolentor_woocommerce_account_content', array( $this, 'woolentor_account_content' ) );
        add_action( 'woolentor_woocommerce_account_content_form_login', array( $this, 'woolentor_account_login_content' ) );

        // Quick View Content
        add_action( 'woolentor_quick_view_content', array( $this, 'woolentor_quick_view_content' ) );

        add_filter( 'template_include', array( $this, 'change_page_template' ), 999);
    }

    /**
     * Fix is_checkout() when using Woolentor checkout template
     *
     * When Woolentor overrides the checkout template, the global $post changes
     * causing is_checkout() to return false. This breaks compatibility with
     * payment plugins like PayPal Payments, Stripe, etc.
     *
     * @param bool $is_checkout Current is_checkout value
     * @return bool
     */
    public function fix_is_checkout( $is_checkout ) {
        // If already true, no need to modify
        if ( $is_checkout ) {
            return $is_checkout;
        }

        // Only apply fix if Woolentor checkout template is active
        $wl_checkout_page_id = $this->get_template_id( 'productcheckoutpage' );
        if ( empty( $wl_checkout_page_id ) ) {
            return $is_checkout;
        }

        // Verify WooCommerce checkout page exists
        $checkout_page_id = wc_get_page_id( 'checkout' );
        if ( ! $checkout_page_id || $checkout_page_id < 1 ) {
            return $is_checkout;
        }

        // Check if we're on the actual WooCommerce checkout page
        if ( is_page( $checkout_page_id ) ) {
            return true;
        }

        return $is_checkout;
    }

    /**
     * Fix is_cart() when using Woolentor cart template
     *
     * When Woolentor overrides the cart template, the global $post changes
     * causing is_cart() to return false. This breaks compatibility with
     * payment plugins like PayPal Payments, Stripe, etc.
     *
     * @param bool $is_cart Current is_cart value
     * @return bool
     */
    public function fix_is_cart( $is_cart ) {
        // If already true, no need to modify
        if ( $is_cart ) {
            return $is_cart;
        }

        // Only apply fix if Woolentor cart template is active
        $wl_cart_page_id = $this->get_template_id( 'productcartpage' );
        $wl_emptycart_page_id = $this->get_template_id( 'productemptycartpage' );

        if ( empty( $wl_cart_page_id ) && empty( $wl_emptycart_page_id ) ) {
            return $is_cart;
        }

        // Verify WooCommerce cart page exists
        $cart_page_id = wc_get_page_id( 'cart' );
        if ( ! $cart_page_id || $cart_page_id < 1 ) {
            return $is_cart;
        }

        // Check if we're on the actual WooCommerce cart page
        if ( is_page( $cart_page_id ) ) {
            return true;
        }

        return $is_cart;
    }

    /**
     *  Include WC fontend.
     */
    public function woolentor_init_cart() {
        $has_cart = is_a( WC()->cart, 'WC_Cart' );
        if ( ! $has_cart ) {
            $session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
            WC()->session = new $session_class();
            WC()->session->init();
            WC()->cart = new \WC_Cart();
            WC()->customer = new \WC_Customer( get_current_user_id(), true );
        }
    }

    /**
     * Get Template ID
     */
    public function get_template_id( $field_key = ''){
        $wltemplateid = method_exists( 'Woolentor_Manage_WC_Template', 'get_template_id' ) ? Woolentor_Manage_WC_Template::instance()->get_template_id( $field_key ) : '0';
        return $wltemplateid;
    }

    // Manage Template part
    public function get_template_part( $slug, $template_id ){
        if( empty( $template_id ) ){
            return;
        }
        $template = '';
        if( $slug === 'cart-empty'){
            $wlemptycart_page_id = $this->get_template_id( 'productemptycartpage' );
            if( !empty( $wlemptycart_page_id ) ) {
                $template = WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/cart-empty-elementor.php';
            }
        }
        elseif( $slug === 'cart' ){
            $wlcart_page_id = $this->get_template_id( 'productcartpage' );
            if( !empty( $wlcart_page_id ) ) {
                $template = WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/cart-elementor.php';
                $this->shipping_calculate();
                do_action( 'woocommerce_check_cart_items' );
            }
        }elseif( $slug === 'checkout' ){
            $wlcheckout_page_id = $this->get_template_id( 'productcheckoutpage' );
            if( !empty( $wlcheckout_page_id ) ) {
                $template = WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/form-checkout.php';
            }
        }elseif( $slug === 'thankyou' ){
            $wlthankyou_page_id = $this->get_template_id( 'productthankyoupage' );
            if( !empty( $wlthankyou_page_id ) ) {
                $template = WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/thankyou.php';
            }
        }elseif( $slug === 'myaccount' ){
            $wlmyaccount_page_id = $this->my_account_page_manage();
            if( !empty( $wlmyaccount_page_id ) ) {
                $template = WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/my-account.php';
            }
        }elseif( $slug === 'myaccount/form-login' ){
            $wlmyaccount_login_page_id = $this->get_template_id( 'productmyaccountloginpage' );
            if( !empty( $wlmyaccount_login_page_id ) ) {
                $template = WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/form-login.php';
            }
        }

        return $template;
    }

    public function woolentor_emptycart_content(){
        $elementor_page_id = $this->get_template_id( 'productemptycartpage' );
        if( !empty( $elementor_page_id ) ){
            if( class_exists('\WooLentorBlocks\Manage_Styles') && function_exists('wc_notice_count') && wc_notice_count() > 0 ){
                \WooLentorBlocks\Manage_Styles::instance()->generate_inline_css( $elementor_page_id );
            }
            echo $this->build_page_content( $elementor_page_id );
        }
    }

    public function woolentor_cart_content(){
        $elementor_page_id = $this->get_template_id( 'productcartpage' );
        if( !empty( $elementor_page_id ) ){
            echo $this->build_page_content( $elementor_page_id );
        }
    }

    public function woolentor_checkout_content(){
        $elementor_page_id = $this->get_template_id( 'productcheckoutpage' );
        if( !empty( $elementor_page_id ) ){
            echo $this->build_page_content( $elementor_page_id );
        }else{ the_content(); }
    }

    public function woolentor_checkout_top_content(){
        $elementor_page_id = $this->get_template_id( 'productcheckouttoppage' );
        if( !empty( $elementor_page_id ) ){
            echo $this->build_page_content( $elementor_page_id );
        }
    }

    public function woolentor_thankyou_content(){
        $elementor_page_id = $this->get_template_id( 'productthankyoupage' );
        if( !empty( $elementor_page_id ) ){
            echo $this->build_page_content( $elementor_page_id );
        }else{ the_content(); }
    }

    public function woolentor_account_content(){
        $elementor_page_id = $this->my_account_page_manage();
        if ( !empty($elementor_page_id) ){
            echo $this->build_page_content( $elementor_page_id );
        }else{ the_content(); }
    }

    public function woolentor_account_login_content(){
        $elementor_page_id = $this->get_template_id( 'productmyaccountloginpage' );
        if ( !empty($elementor_page_id) ){
            echo $this->build_page_content( $elementor_page_id );
        }else{ the_content(); }
    }

    public function woolentor_quick_view_content(){
        $elementor_page_id = $this->get_template_id( 'productquickview' );
        if( !empty( $elementor_page_id ) ){
            echo $this->build_page_content( $elementor_page_id );
        }
    }

    /**
     * Page Tempalte
     */
    public function get_page_template_path( $template_part, $template_id ) {

        $template = 0;

        if( Woolentor_Template_Manager::instance()->edit_with_gutenberg( $template_id ) ) {

            $page_template_slug = get_post_meta( $template_id, '_wp_page_template', true );

            $page_template_slug = ( in_array( $page_template_slug, ['elementor_header_footer', 'elementor_canvas'] ) ? 'woolentor_fullwidth' : $page_template_slug );
            $template = !empty( $page_template_slug ) ? WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/page/'.$page_template_slug.'.php' : $template;

			// if( empty( $page_template_slug ) ) {
			// 	$template = WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/page/woolentor-default.php';
			// }

            if( empty( $page_template_slug ) ) {
				$template = WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/page/default.php';
			}

            add_action('woolentor/builder/content', function () use ( $template_id, $template_part ) {
                include_once ( $this->get_template_part( $template_part, $template_id ) );
            });

            return $template;
        }

        if( woolentor_is_elementor_active_pro() ){

            // The code snippet originates in Elementor, specifically in /elementor/modules/page-templates/module.php at line 82.
            $document        = \Elementor\Plugin::$instance->documents->get_doc_for_frontend($template_id);
            $template_module = \Elementor\Plugin::$instance->modules_manager->get_modules('page-templates');

            if( $document && $document::get_property('support_wp_page_templates') ) {
                $page_template = $document->get_meta('_wp_page_template');
                $page_template = ( in_array( $page_template, ['elementor_header_footer', 'elementor_canvas'] ) ? $page_template : '');

                $template_path = $template_module->get_template_path( $page_template );

                if( 'elementor_theme' !== $page_template && !$template_path && $document->is_built_with_elementor() ) {
                    $kit_default_template = \Elementor\Plugin::$instance->kits_manager->get_current_settings('default_page_template');
                    $template_path        = $template_module->get_template_path( $kit_default_template );
                }

                if( $template_path ) {
                    $template = $template_path;
                }
            }


            if( $template_path ) {
                $template_module->set_print_callback(function () use ( $template_id, $template_part ){
                    include_once ( $this->get_template_part( $template_part, $template_id ) );
                });
            } else{
                if( !$this->is_elementor_editor_mode() ){
                    $template = WOOLENTOR_ADDONS_PL_PATH_PRO . 'wl-woo-templates/page/default.php';
                    add_action('woolentor/builder/content', function () use ( $template_id, $template_part ) {
                        include_once ( $this->get_template_part( $template_part, $template_id ) );
                    });
                }
            }

        }

        return $template;
    }

    /**
     * Template Change
     *
     * @param [type] $template
     * @return void
     */
    public function change_page_template( $template ){

        $template_id = 0;
        $template_part = 0;

        if ( class_exists( 'WooCommerce' ) ) {
            if( is_cart() ){
                $empty_cart_page_id = $this->get_template_id( 'productemptycartpage' );
                if ( WC()->cart->is_empty() && !empty( $empty_cart_page_id ) ) {
                    $template_id = $empty_cart_page_id;
                    $template_part = 'cart-empty';
                }else{
                    $cart_page_id = $this->get_template_id( 'productcartpage' );
                    if( !empty( $cart_page_id ) ){
                        $template_id = $cart_page_id;
                        $template_part = 'cart';
                    }
                }
            }elseif ( (is_checkout() && !is_wc_endpoint_url('order-received') && !is_checkout_pay_page()) || (isset($_REQUEST['wc-ajax']) &&  $_REQUEST['wc-ajax'] == 'update_order_review') ){
                $wl_checkout_page_id = $this->get_template_id( 'productcheckoutpage' );
                if( !empty($wl_checkout_page_id) ){
                    $template_id = $wl_checkout_page_id;
                    $template_part = 'checkout';
                }
            }elseif( is_checkout() && is_wc_endpoint_url('order-received') ){
                $wl_thankyou_page_id = $this->get_template_id( 'productthankyoupage' );
                if( !empty( $wl_thankyou_page_id ) ){
                    $template_id = $wl_thankyou_page_id;
                    $template_part = 'thankyou';
                }

            }elseif ( is_account_page() ){
                $wl_myaccount_page_id = $this->my_account_page_manage();
                if( !empty( $wl_myaccount_page_id )){
                    $template_id = $wl_myaccount_page_id;
                    $template_part = 'myaccount';
                }else{
                    if( !is_user_logged_in() ){
                        $elementor_page_id = $this->get_template_id( 'productmyaccountloginpage' );
                        if( !empty( $elementor_page_id )){
                            $template_id = $elementor_page_id;
                            $template_part = 'myaccount/form-login';
                        }
                    }
                } 
            }

        }

        if( !empty( $template_id ) ){
            $template_path = $this->get_page_template_path( $template_part, $template_id );
            if ( $template_path && !$this->is_elementor_editor_mode()) {
                $template = $template_path;
            }
            add_filter('woolentor_builder_template_id',function( $build_template_id ) use( $template_id ){
                $build_template_id = $template_id;
                return $build_template_id;
            });
            add_filter('woolentor_builder_template_width',function( $template_width ) use( $template_id ){
                $template_width = $this->get_template_width( $template_id );
                return $template_width;
            });
        }
        
        return $template;
    }

    // Check Elementor Editor mode
    public function is_elementor_editor_mode(){
        if( isset( $_GET['action'] ) && $_GET['action'] == 'elementor' ){
            return true;
        }else{
            if( isset( $_GET['elementor-preview'] ) && $_GET['elementor-preview'] !== ''){
                return true;
            }
            return false;
        }
    }

    // Get Template width
    public function get_template_width( $template_id ){
        if( ! Woolentor_Template_Manager::instance()->edit_with_gutenberg( $template_id ) ){
            return '';
        }
        $get_width = get_post_meta( $template_id, '_woolentor_container_width', true );
		return $get_width ? $get_width : '';
    }

    // Build page content
    public function build_page_content( $id ){

        $output = '';
        $document = class_exists('\Elementor\Plugin') ? Elementor\Plugin::instance()->documents->get( $id ) : false;

        if( $document && $document->is_built_with_elementor() ){
            // Check If empty cart page
            $elementor_page_id = $this->get_template_id( 'productemptycartpage' );
            if( !empty( $elementor_page_id ) && is_cart() ){
                $output = Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id, true );
            }else{
                $output = Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id );
            }
        }else{
            $content = get_the_content( null, false, $id );

            if ( has_blocks( $content ) ) {
                if( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'woolentor_quickview' ){
                    \WooLentorBlocks\Manage_Styles::instance()->generate_inline_css( $id );
                }
                $blocks = parse_blocks( $content );
                foreach ( $blocks as $block ) {
                    $output .= do_shortcode( render_block( $block ) );
                }
            }else{
                $content = apply_filters( 'the_content', $content );
                $content = str_replace(']]>', ']]&gt;', $content );
                return $content;
            }

        }
        return $output;

    }

    // Manage My Accont Custom template
    public function my_account_page_manage(){
        global $wp;

        $request = explode( '/', $wp->request );

        // $account_page_slugs = [
        //     'orders',
        //     'downloads',
        //     'edit-address',
        //     'edit-account',
        //     'reset-password',
        //     'lost-password'
        // ];

        $lost_password = get_option( 'woocommerce_myaccount_lost_password_endpoint', 'lost-password' );
        $order_endpoint = get_option( 'woocommerce_myaccount_orders_endpoint', 'orders' );
        $downloads_endpoint = get_option( 'woocommerce_myaccount_downloads_endpoint', 'downloads' );
        $edit_account_endpoint = get_option( 'woocommerce_myaccount_edit_account_endpoint', 'edit-account' );
        $edit_address_endpoint = get_option( 'woocommerce_myaccount_edit_address_endpoint', 'edit-address' );

        $account_page_slugs = [
            'reset-password' => 'reset-password',
            'lost-password'  => $lost_password,
            'orders'         => $order_endpoint,
            'downloads'      => $downloads_endpoint,
            'edit-account'   => $edit_account_endpoint,
            'edit-address'   => $edit_address_endpoint,
        ];

        $page_slug = '';
        if( ( end( $request ) === basename( get_permalink(wc_get_page_id( 'myaccount' )) )) && is_user_logged_in() ){
            $page_slug = 'dashboard';
        }else if( in_array( end( $request ), $account_page_slugs ) ){
            if( ! empty( $_GET['show-reset-form'] ) ){
                if ( isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) && 0 < strpos( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ], ':' ) ) {
                    list( $rp_id, $rp_key ) = array_map( 'wc_clean', explode( ':', wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ), 2 ) ); // @codingStandardsIgnoreLine
                    $userdata  = get_userdata( absint( $rp_id ) );
                    $rp_login  = $userdata ? $userdata->user_login : '';
                    $user      = check_password_reset_key( $rp_key, $rp_login );
                    if ( ! is_wp_error( $user ) ) {
                        $page_slug = 'reset-password';
                    }else{
                        // $page_slug = end( $request );
                        $page_slug = array_search( end( $request ), $account_page_slugs );
                    }
                }
            }else{
                // $page_slug = end( $request );
                $page_slug = array_search( end( $request ), $account_page_slugs );
            }
        }else{
            if( is_user_logged_in() ){
                $page_slug = 'productmyaccountpage';
            }
        }

        $template_id = $this->get_template_id( $page_slug );

        if( $page_slug == 'reset-password' ){
            return $template_id;
        }else if( in_array( $page_slug, ['orders','downloads','edit-address','edit-account']) && !is_user_logged_in()){
            $template_id = "";
        }
        if( empty( $template_id ) && is_user_logged_in() ){
            $template_id = $this->get_template_id( 'productmyaccountpage' );
        }

        return $template_id;

    }

    /**
     * Shipping calculate
     *
     * @return void
     */
    public function shipping_calculate() {

        $nonce_key =  !empty( $_REQUEST['woocommerce-shipping-calculator-nonce'] ) ? 'woocommerce-shipping-calculator-nonce' : '_wpnonce';

        // Update Shipping. Nonce check uses new value and old value (woocommerce-cart). @todo remove in 4.0.
        if ( ! empty( $_POST['calc_shipping'] ) && !empty( $_REQUEST[$nonce_key] ) && 
        ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[$nonce_key] ) ), 'woocommerce-shipping-calculator' ) || 
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[$nonce_key] ) ), 'woocommerce-cart' ) ) ) {
            try {
                WC()->shipping()->reset_shipping();
    
                $address = array();
    
                $address['country']  = isset( $_POST['calc_shipping_country'] ) ? map_deep( wp_unslash( $_POST['calc_shipping_country'] ), 'sanitize_text_field' ) : '';
                $address['state']    = isset( $_POST['calc_shipping_state'] ) ? map_deep( wp_unslash( $_POST['calc_shipping_state'] ), 'sanitize_text_field' ) : '';
                $address['postcode'] = isset( $_POST['calc_shipping_postcode'] ) ? map_deep( wp_unslash( $_POST['calc_shipping_postcode'] ), 'sanitize_text_field' ) : '';
                $address['city']     = isset( $_POST['calc_shipping_city'] ) ? map_deep( wp_unslash( $_POST['calc_shipping_city'] ), 'sanitize_text_field' ) : '';
    
                $address = apply_filters( 'woocommerce_cart_calculate_shipping_address', $address );
    
                if ( $address['postcode'] && ! WC_Validation::is_postcode( $address['postcode'], $address['country'] ) ) {
                    throw new Exception( __( 'Please enter a valid postcode / ZIP.', 'woolentor-pro' ) );
                } elseif ( $address['postcode'] ) {
                    $address['postcode'] = wc_format_postcode( $address['postcode'], $address['country'] );
                }
    
                if ( $address['country'] ) {
                    if ( ! WC()->customer->get_billing_first_name() ) {
                        WC()->customer->set_billing_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
                    }
                    WC()->customer->set_shipping_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
                } else {
                    WC()->customer->set_billing_address_to_base();
                    WC()->customer->set_shipping_address_to_base();
                }
    
                WC()->customer->set_calculated_shipping( true );
                WC()->customer->save();
    
                wc_add_notice( __( 'Shipping costs updated.', 'woolentor-pro' ), 'notice' );
    
                do_action( 'woocommerce_calculated_shipping' );
    
            } catch ( Exception $e ) {
                if ( ! empty( $e ) ) {
                    wc_add_notice( $e->getMessage(), 'error' );
                }
            }

            // Also calc totals before we check items so subtotals etc are up to date.
            WC()->cart->calculate_totals();
        }

    }

}

Woolentor_Woo_Custom_Template_Layout_Pro::instance();