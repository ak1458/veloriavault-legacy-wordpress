<?php
namespace WishSuite;
use WooLentor\Traits\Singleton;
/**
 * Assets handlers class
 */
class Assets {
    use Singleton;

    /**
     * Class constructor
     */
    private function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
    }

    /**
     * All available scripts
     *
     * @return array
     */
    public function get_scripts() {
        return [
            'wishsuite-frontend' => [
                'src'     => WISHSUITE_ASSETS . '/js/frontend.js',
                'version' => WOOLENTOR_VERSION,
                'deps'    => [ 'jquery', 'wc-add-to-cart-variation' ]
            ],
        ];
    }

    /**
     * All available styles
     *
     * @return array
     */
    public function get_styles() {
        return [
            'wishsuite-frontend' => [
                'src'     => WISHSUITE_ASSETS . '/css/frontend.css',
                'version' => WOOLENTOR_VERSION,
            ],
        ];
    }

    /**
     * Register scripts and styles
     *
     * @return void
     */
    public function register_assets() {
        $scripts = $this->get_scripts();
        $styles  = $this->get_styles();

        foreach ( $scripts as $handle => $script ) {
            $deps = isset( $script['deps'] ) ? $script['deps'] : false;
            wp_register_script( $handle, $script['src'], $deps, $script['version'], true );
        }

        foreach ( $styles as $handle => $style ) {
            $deps = isset( $style['deps'] ) ? $style['deps'] : false;
            wp_register_style( $handle, $style['src'], $deps, $style['version'] );
        }

        // Inline CSS
        wp_add_inline_style( 'wishsuite-frontend', $this->inline_style() );
        
        // Frontend Localize data
        $option_data = array(
            'after_added_to_cart' => woolentor_get_option( 'after_added_to_cart', 'wishsuite_table_settings_tabs', 'on' ),
        );
        
        if( is_user_logged_in() &&  woolentor_get_option( 'enable_login_limit', 'wishsuite_general_tabs', 'off' ) === 'on' ){
            $option_data['btn_limit_login_off'] = 'off';
        }else if( !is_user_logged_in() && woolentor_get_option( 'enable_login_limit', 'wishsuite_general_tabs', 'off' ) === 'on' ){
            $option_data['btn_limit_login_off'] = 'on';
        }

        $localize_data = array(
            'ajaxurl'     => admin_url( 'admin-ajax.php' ),
            'wsnonce'     => wp_create_nonce('wishSuite_nonce'),
            'option_data' => $option_data,
        );

        // Admin Localize data
        wp_localize_script( 'wishsuite-frontend', 'WishSuite', $localize_data );

        if( class_exists( '\Elementor\Plugin' ) && ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) ){
            wp_enqueue_style( 'wishsuite-frontend' );
            wp_enqueue_script( 'wishsuite-frontend' );
        }
        
    }

    /**
     * [inline_style]
     * @return [CSS String]
     */
    public function inline_style(){

        $button_custom_css = $table_custom_css = '';

        // Button Custom Style
        if( 'custom' === woolentor_get_option( 'button_style', 'wishsuite_style_settings_tabs', 'default' ) ){

            $btn_padding = wishsuite_dimensions( 'button_custom_padding','wishsuite_style_settings_tabs','padding' );
            $btn_margin  = wishsuite_dimensions( 'button_custom_margin','wishsuite_style_settings_tabs','margin' );
            $btn_border_radius = wishsuite_dimensions( 'button_custom_border_radius','wishsuite_style_settings_tabs','border-radius' );

            $btn_color    = wishsuite_generate_css('button_color','wishsuite_style_settings_tabs','color');
            $btn_bg_color = wishsuite_generate_css('background_color','wishsuite_style_settings_tabs','background-color');

            // Hover
            $btn_hover_color    = wishsuite_generate_css('button_hover_color','wishsuite_style_settings_tabs','color');
            $btn_hover_bg_color = wishsuite_generate_css('hover_background_color','wishsuite_style_settings_tabs','background-color');

            $button_custom_css = "
                .wishsuite-button{
                    {$btn_padding}
                    {$btn_margin}
                    {$btn_color}
                    {$btn_bg_color}
                    {$btn_border_radius}
                }
                .wishsuite-button:hover{
                    {$btn_hover_color}
                    {$btn_hover_bg_color}
                }
            ";
        }

        // Wishlist table style
        if( 'custom' === woolentor_get_option( 'table_style', 'wishsuite_style_settings_tabs', 'default' ) ){

            $heading_color    = wishsuite_generate_css('table_heading_color','wishsuite_style_settings_tabs','color');
            $heading_bg_color = wishsuite_generate_css('table_heading_bg_color','wishsuite_style_settings_tabs','background-color');
            $heading_border_color = wishsuite_generate_css('table_heading_border_color','wishsuite_style_settings_tabs','border-color');

            $border_color = wishsuite_generate_css('table_border_color','wishsuite_style_settings_tabs','border-color');

            // Add To cart Button
            $button_color = wishsuite_generate_css('table_cart_button_color','wishsuite_style_settings_tabs','color');
            $button_bg_color = wishsuite_generate_css('table_cart_button_bg_color','wishsuite_style_settings_tabs','background-color');
            $button_hover_color = wishsuite_generate_css('table_cart_button_hover_color','wishsuite_style_settings_tabs','color');
            $button_hover_bg_color = wishsuite_generate_css('table_cart_button_hover_bg_color','wishsuite_style_settings_tabs','background-color');

            $table_custom_css = "
                .wishsuite-table-content table thead > tr{
                    {$heading_border_color}
                }
                .wishsuite-table-content table thead > tr th{
                    {$heading_color}
                    {$heading_bg_color}
                }
                .wishsuite-table-content table,.wishsuite-table-content table tbody > tr{
                    {$border_color}
                }
            ";

            if( $button_color || $button_bg_color ){
                $table_custom_css .= "
                    .wishsuite-table-content table .wishsuite-addtocart{
                        {$button_color}
                        {$button_bg_color}
                    }
                ";
            }
            if( $button_hover_color || $button_hover_bg_color ){
                $table_custom_css .= "
                    .wishsuite-table-content table .wishsuite-addtocart:hover{
                        {$button_hover_color}
                        {$button_hover_bg_color}
                    }
                ";
            }

        }
        
        return $button_custom_css.$table_custom_css;

    }


}
