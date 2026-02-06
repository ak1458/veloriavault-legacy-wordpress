<?php
/**
 *	NM - WooCommerce Product Quick View
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Include link
 */
function nm_quickview_include_link() {
    global $nm_theme_options;
    
    if ( $nm_theme_options['product_quickview'] && $nm_theme_options['product_quickview_link'] ) {
        $link_label = ( isset( $nm_theme_options['product_quickview_link_label'] ) && strlen( $nm_theme_options['product_quickview_link_label'] ) > 0 ) ? $nm_theme_options['product_quickview_link_label'] : __( 'Show more', 'nm-framework' );
        
        echo apply_filters( 'nm_product_quickview_link', '<a href="' . esc_url( get_permalink() ) . '" class="nm-quickview-btn">' . esc_html( $link_label ) . '</a>', $link_label );
    }
}
add_action( 'woocommerce_after_shop_loop_item', 'nm_quickview_include_link', 14 );

/**
 *	AJAX: Load product
 */
function nm_ajax_load_product() {
    global $post, $product;
    
    $product_id = intval( $_POST['product_id'] );
    $product    = wc_get_product( $product_id );
    $output     = '';
    
    if ( $product ) {
        $is_published           = ( $product->get_status() === 'publish' );
        $is_catalog_visible     = ( $product->get_catalog_visibility() !== 'hidden' );
        $is_password_protected  = post_password_required( $product->get_id() );
        
        // Make sure product is published, visible and not password protected
        if ( $is_published && $is_catalog_visible && ! $is_password_protected ) {
            $post = $product->post;
            setup_postdata( $post );

            ob_start();
                wc_get_template_part( 'quickview/content', 'quickview' );
            $output = ob_get_clean();

            wp_reset_postdata();
        }
    }

    echo $output; // Escaped
    
    exit;
}
// Note: Keep default AJAX actions in case WooCommerce endpoint URL is unavailable
add_action( 'wp_ajax_nm_ajax_load_product' , 'nm_ajax_load_product' );
add_action( 'wp_ajax_nopriv_nm_ajax_load_product', 'nm_ajax_load_product' );
// Register WooCommerce Ajax endpoint (available since 2.4)
add_action( 'wc_ajax_nm_ajax_load_product', 'nm_ajax_load_product' );
