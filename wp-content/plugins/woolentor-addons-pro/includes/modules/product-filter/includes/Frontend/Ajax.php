<?php
/**
 * Ajax.
 */

namespace WLPF\Frontend;

/**
 * Class.
 */
class Ajax {

	/**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_ajax_wlpf_ajax_filter', array( $this, 'filter' ) );
        add_action( 'wp_ajax_nopriv_wlpf_ajax_filter', array( $this, 'filter' ) );
    }

    /**
     * Filter.
     */
    public function filter() {
        $response = array();

        $nonce    = ( isset( $_POST['nonce'] ) ? wlpf_cast( $_POST['nonce'], 'text' ) : '' );
        $addon    = ( isset( $_POST['addon'] ) ? wlpf_cast( $_POST['addon'], 'key' ) : '' );
        $settings = ( isset( $_POST['settings'] ) ? wlpf_cast( $_POST['settings'], 'jsonarray' ) : array() );
        $filters  = ( isset( $_POST['filters'] ) ? wlpf_cast( $_POST['filters'], 'array' ) : array() );
        $termobj = ( isset( $_POST['termobj'] ) ? wlpf_cast( $_POST['termobj'], 'array' ) : array() );

        $filterable = true;

        if ( ! wp_verify_nonce( $nonce, 'wlpf-ajax-nonce' ) ) {
            wp_send_json( $response );
        }

        $is_tax_filter = array_key_exists('tax_filter', $filters);

        // Current Category Page, If not set Advanced Filter module query then apply current taxonomy query
        if( $termobj['term_id'] && !$is_tax_filter){
            $tax_query = [
                [
                    "taxonomy" => $termobj['taxonomy'],
                    "terms" => $termobj['term_id'],
                    "field" => "term_id",
                    "include_children" => true
                ]
            ];
            $filters['tax_filter'] = $tax_query;
        }
        

        switch ( $addon ) {
            case 'wl-product-grid':
            case 'wl-product-expanding-grid':
            case 'woolentor-custom-product-archive':
                $type = ( isset( $settings['product_type'] ) ? wlpf_cast( $settings['product_type'], 'key' ) : '' );
                $type = wlpf_cast_product_type( $type );

                $shortcode = new \WooLentor_WC_Shortcode_Products( $settings, $type, $filterable, $filters );
                $content = $shortcode->get_content( $settings['product_layout'] );
                break;

            case 'woolentor-product-grid-modern':
            case 'woolentor-product-grid-luxury':
            case 'woolentor-product-grid-editorial':
            case 'woolentor-product-grid-magazine':
                // Load dependencies
                if ( ! class_exists( '\WooLentor_Product_Grid_Base' ) ) {
                    require_once WOOLENTOR_ADDONS_PL_PATH . 'includes/addons/product-grid/base/class.product-grid-base.php';
                }

                $product_grid_base = new \WooLentor_Product_Grid_Base();

                $settings['filter_arg'] = $filters;
                if( !empty($filters['page']) ){
                    $settings['paged'] = $filters['page'];
                }

                ob_start();
                $product_grid_base->render_items( $settings );
                $html = ob_get_clean();

                $content = $html;
                break;

            case 'woolentor-product-archive-addons':
                $type = ( isset( $settings['product_type'] ) ? wlpf_cast( $settings['product_type'], 'key' ) : '' );
                $type = wlpf_cast_product_type( $type );
                $settings = wlpf_cast_product_archive_addons_settings( $settings );

                $shortcode = new \WLPF\Frontend\Products( $settings, $type, $filters );
                $content = $shortcode->get_content();
                break;

            default:
                $type = 'products';
                $type = wlpf_cast_product_type( $type );
                $settings = wlpf_cast_default_archive_settings();

                $shortcode = new \WLPF\Frontend\Products( $settings, $type, $filters );
                $content = $shortcode->get_content();
                break;
        }

        if ( strip_tags( trim( $content ) ) ) {
            $response['content'] = $content;
        } else {
            $response['content'] = woolentor_pro_products_not_found_content();
        }

        wp_send_json( $response );
    }

}