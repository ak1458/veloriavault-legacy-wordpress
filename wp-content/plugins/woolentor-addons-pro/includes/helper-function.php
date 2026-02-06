<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit();

/**
* Options return
*/
function woolentor_get_option_pro( $option, $section, $default = '' ){
    $options = get_option( $section );
    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }
    return $default;
}

function woolentor_get_option_text( $option, $section, $default = '' ){
    $options = get_option( $section );
    if ( isset( $options[$option] ) ) {
        if( !empty($options[$option]) ){
            return $options[$option];
        }
        return $default;
    }
    return $default;
}

/**
 * Has Elementor action
 *
 * @return [void]
 */
function woolentor_is_elementor_active_pro() {
    return did_action('elementor/loaded');
}

/**
 * [woolentor_content_print_pro] Content Print
 * @param mixed $content
 * @return void
 */
function woolentor_print_content_pro($content) {
    echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * All PHP file include from folder
 * @param mixed $folder_name
 * @return void
 */
function woolentor_include_all_pro($folder_name){
    foreach (glob("{$folder_name}/*.php") as $filename){
        include $filename;
    }
}

/**
 * Single String Translate
 * @param mixed $name String identifier
 * @param mixed $value Default value
 * @param string $context Translation context/group (must match registration context)
 * @return mixed Translated string or original value
 */
function woolentor_translator_pro( $name, $value, $context = 'ShopLentor' ){
    if( method_exists('\WooLentor\MultiLanguage\Languages','translator') ) {
        return \WooLentor\MultiLanguage\Languages::translator( $name, $value, $context );
    }
    return $value;
}


/**
* [woolentor_generate_css_pro]
* @param  [string] $key      
* @param  [string] $tab      
* @param  [string] $css_attr 
* @return [type]  
*/
function woolentor_generate_css_pro( $key, $tab, $css_attr, $unit = '', $important = '' ){
    $field_value = !empty( woolentor_get_option_pro( $key, $tab ) ) ? woolentor_get_option_pro( $key, $tab ) : '';

    if( !empty( $field_value ) ){
        $css_attr .= ":{$field_value}{$unit}";
        return $css_attr."{$important};";
    }else{
        return false;
    }

}

/**
 * [woolentor_dimensions_pro]
 * @param  [string] $key
 * @param  [string] $tab
 * @return [String | Bool]
 */
function woolentor_dimensions_pro( $key, $tab, $css_attr, $important = '' ){
    $dimensions = !empty( woolentor_get_option_pro( $key, $tab ) ) ? woolentor_get_option_pro( $key, $tab ) : array();
    if( !empty( $dimensions['top'] ) || !empty( $dimensions['right'] ) || !empty( $dimensions['bottom'] ) || !empty( $dimensions['left'] ) ){

        $unit   = ( empty( $dimensions['unit'] ) ? 'px' : $dimensions['unit'] );
        $top    = ( !empty( $dimensions['top'] ) ? $dimensions['top'] : 0 );
        $right  = ( !empty( $dimensions['right'] ) ? $dimensions['right'] : 0 );
        $bottom = ( !empty( $dimensions['bottom'] ) ? $dimensions['bottom'] : 0 );
        $left   = ( !empty( $dimensions['left'] ) ? $dimensions['left'] : 0 );

        $css_attr .= ":{$top}{$unit} {$right}{$unit} {$bottom}{$unit} {$left}{$unit}";
        return $css_attr."{$important};";
        
    }else{
        return false;
    }
}

/**
* Woocommerce Product last order id return
*/
function woolentor_get_last_order_id(){

    if( function_exists('wc_get_orders') ){
        $orders = wc_get_orders( array(
            'limit' => 1,  // Limit the query to one order
            'orderby' => 'date',  // Order by date
            'order' => 'DESC',    // Sort in descending order (latest first)
        ));

        // Check if there are any orders
        if ( !empty( $orders ) ) {
            $latest_order = reset( $orders ); // Get the first (latest) order in the array
            return $latest_order->get_id(); // Get the order ID
        }
    }

    return 0;
}

/**
 * [woolentor_pro_template_endpoint]
 * @return [url]
 */
// function woolentor_pro_template_endpoint(){
//     return 'https://library.shoplentor.com/wp-json/woolentor/v1promnmnsdc/templates';
// }
function woolentor_pro_template_endpoint(){
    return [
        'primary' => 'https://library.shoplentor.com/wp-json/woolentor/v1promnmnsdc/templates',
        'backup'  => 'https://library-backup.shoplentor.com/wp-json/woolentor/v1promnmnsdc/templates'
    ];
}

/**
 * [woolentor_pro_template_url]
 * @return [url]
 */
// function woolentor_pro_template_url(){
//     return 'https://library.shoplentor.com/wp-json/woolentor/v1/templates/%s';
// }
function woolentor_pro_template_url(){
    return [
        'primary' => 'https://library.shoplentor.com/wp-json/woolentor/v1promnmnsdc/templates',
        'backup'  => 'https://library-backup.shoplentor.com/wp-json/woolentor/v1promnmnsdc/templates'
    ];
}

/**
* Add Inline CSS.
*/
function woolentor_styles_inline() {

    $containerwid = get_option( 'elementor_container_width', '1140' );
    if( $containerwid == 0 ){ $containerwid = '1140'; }

    $emptycartcss = $checkoutpagecss = $noticewrap = '';
    
    if ( class_exists( 'WooCommerce' ) ) {
        if ( is_cart() && WC()->cart->is_empty() ) {
            $emptycartcss = "
                .woolentor-page-template .woocommerce{
                    margin: 0 auto;
                    width: {$containerwid}px;
                }
            ";
        }
        if( is_checkout() ){
            $checkoutpagecss = "
               .woolentor-woocommerce-checkout .woocommerce-NoticeGroup, .woocommerce-error{
                    margin: 0 auto;
                    width: {$containerwid}px;
                } 
            ";
        }
    }

    $noticewrap = "
        .woocommerce-notices-wrapper{
            margin: 0 auto;
            width: {$containerwid}px;
        }
    ";

    $custom_css = "
        $emptycartcss
        $checkoutpagecss
        $noticewrap
        ";
    wp_add_inline_style( 'woolentor-widgets-pro', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'woolentor_styles_inline' );


if( class_exists('WooCommerce') ){
    /**
     * [woolentor_stock_status]
     */
    function woolentor_stock_status_pro( $order_text, $available_text, $product_id ){

        if ( get_post_meta( $product_id, '_manage_stock', true ) == 'yes' ) {

            $total_stock = get_post_meta( $product_id, 'woolentor_total_stock_quantity', true );

            if ( ! $total_stock ) { echo '<div class="stock-management-progressbar">'.__('Do not set stock amount for progress bar','woolentor-pro').'</div>'; return; }

            $current_stock = round( get_post_meta( $product_id, '_stock', true ) );

            $total_sold = $total_stock > $current_stock ? $total_stock - $current_stock : 0;
            $percentage = $total_sold > 0 ? round( $total_sold / $total_stock * 100 ) : 0;

            if ( $current_stock >= 0 ) {
                echo '<div class="woolentor-stock-progress-bar">';
                    echo '<div class="wlstock-info">';
                        echo '<div class="wltotal-sold">' . __( $order_text, 'woolentor-pro' ) . '<span>' . esc_html( $total_sold ) . '</span></div>';
                        echo '<div class="wlcurrent-stock">' . __( $available_text, 'woolentor-pro' ) . '<span>' . esc_html( $current_stock ) . '</span></div>';
                    echo '</div>';
                    echo '<div class="wlprogress-area" title="' . __( 'Sold', 'woolentor-pro' ) . ' ' . esc_attr( $percentage ) . '%">';
                        echo '<div class="wlprogress-bar"style="width:' . esc_attr( $percentage ) . '%;"></div>';
                    echo '</div>';
                echo '</div>';
            }

        }

    }

    function Woolentor_Control_Sale_Badge( $settings, $id ){
        $product = wc_get_product( $id );

        if( $product->is_on_sale() && $product->is_in_stock() ){

            $discount = '';
            $regurlar_price = get_post_meta( $id, '_regular_price', true);
            $sale_price  = get_post_meta( $id, '_sale_price', true);
            $currency = get_woocommerce_currency_symbol();

            if( $product->is_type('variable') && $product->is_on_sale() ) {
                $regurlar_price = $product->get_variation_regular_price(); // Min regular price
                $sale_price     = $product->get_variation_price(); // Min Sale price
            }

            $sale_badge_after = isset( $settings['product_after_badge_percent'] )?$settings['product_after_badge_percent']:'';
            $sale_badge_before = isset( $settings['product_before_badge_percent'] )?$settings['product_before_badge_percent']:'';

            if( $settings['product_sale_badge_type'] === 'custom' ){
                if( $product->is_on_sale() ){
                    echo '<span class="ht-product-label ht-product-label-right">'. $settings['product_sale_badge_custom'].'</span>';
                }
            }elseif ($settings['product_sale_badge_type'] === 'dis_percent' ) {
                if($regurlar_price && $sale_price ){
                    $price = ( $regurlar_price-$sale_price )/$regurlar_price;
                    $discount = round($price *100);
                    $discount =  '<span class="ht-product-label ht-product-label-right">'.$sale_badge_before.' '. $discount . __( '%', 'woolentor-pro' ) .' '. $sale_badge_after.'</span>';
                }
                echo wp_kses_post( $discount );
            }elseif ($settings['product_sale_badge_type'] === 'dis_price' ) {
                if($regurlar_price && $sale_price ){
                    $price = ( $regurlar_price - $sale_price );
                    $discount = $price;
                    $discount =  '<span class="ht-product-label ht-product-label-right">'.$sale_badge_before.' '. $discount . $currency .' '.$sale_badge_after.'</span>';
                }
                echo wp_kses_post( $discount );
            }else{
                woolentor_sale_flash( 'default', true, $settings['product_outofstock_badge_custom'] );
            }
        }else{
            woolentor_sale_flash( 'default', true, $settings['product_outofstock_badge_custom'] );
        }

    }

    /**
     * [woolentor_pro_locate_template]
     * @param  [string] $tmp_name Template name
     * @return [Template path]
     */
    function woolentor_pro_locate_template( $destination, $tmp_name ) {
        $woo_tmp_base = WC()->template_path();

        $woo_tmp_path     = $woo_tmp_base . $tmp_name; //active theme directory/woocommerce/
        $theme_tmp_path   = '/' . $tmp_name; //active theme root directory
        $plugin_tmp_path  = WOOLENTOR_TEMPLATE_PRO . $destination .'/'. $tmp_name;

        $located = locate_template( [ $woo_tmp_path, $theme_tmp_path ] );

        if ( ! $located && file_exists( $plugin_tmp_path ) ) {
            return apply_filters( 'woolentor_addons_locate_template', $plugin_tmp_path, $tmp_name );
        }

        return apply_filters( 'woolentor_addons_locate_template', $located, $tmp_name );
    }

    /**
     * [woolentor_pro_get_template]
     * @param  [string]  $tmp_name Template name
     * @param  [array]  $args template argument array
     * @param  boolean $echo
     * @return [void]
     */
    function woolentor_pro_get_template( $destination, $tmp_name, $args = null, $echo = true ) {
        $located = woolentor_pro_locate_template( $destination, $tmp_name );

        if ( $args && is_array( $args ) ) {
            extract( $args );
        }

        if ( $echo !== true ) { ob_start(); }

        // include file located.
        include( $located );

        if ( $echo !== true ) { return ob_get_clean(); }

    }

    /**
     * [woolentor_pro_get_taxonomies]
     * @return [array] texonomies list
     */
    function woolentor_pro_get_taxonomies( $object = 'product', $skip_terms = false ) {
        $all_taxonomies = get_object_taxonomies( $object );
        $taxonomies_list = [];
        $taxonomies_list['none'] = esc_html__( 'Select', 'woolentor-pro' );
        foreach ( $all_taxonomies as $taxonomy_data ) {
            $taxonomy = get_taxonomy( $taxonomy_data );

            if( $skip_terms === true ){
                if( ($taxonomy->show_ui) && ( 'pa_' !== substr( $taxonomy_data, 0, 3 ) ) ) {
                    $taxonomies_list[ $taxonomy_data ] = $taxonomy->label;
                }
            }else{
                if( $taxonomy->show_ui ) {
                    $taxonomies_list[ $taxonomy_data ] = $taxonomy->label;
                }
            }
            
        }
        return $taxonomies_list;
    }

    // For Cart Page
    /**
     * Show Category
     */
    function woolentor_cart_page_categories( $cart_item, $cart_item_key ){
        $product_item = $cart_item['data'];
        // make sure to get parent product if variation
        if ( $product_item->is_type( 'variation' ) ) {
            $product_item = wc_get_product( $product_item->get_parent_id() );
        }
        $cat_ids = $product_item->get_category_ids();
        // if product has categories, concatenate cart item name with them
        echo  ( $cat_ids ? '<div class="woolentor-cart-cats">' . wc_get_product_category_list( $product_item->get_id(), ', ',
                '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $cat_ids ), 'woolentor-pro') . ' ',
            '</span></div>'
        ) : '' ) ;
    }

    /**
     * Show Product stock
     */
    function woolentor_cart_page_stock( $cart_item, $cart_item_key ){
        $product = $cart_item['data'];
        if ( $product->backorders_require_notification() && $product->is_on_backorder( $cart_item['quantity'] ) ) {
            return;
        }
        echo  ( wc_get_stock_html( $product ) ? '<div class="woolentor-cart-stock">' . wc_get_stock_html( $product ) . '</div>' : '' ) ;
    }

    /**
     * Get Avaiable payment method
     */
    function woolentor_get_payment_method(){

        $generate_payment_gateways = [];
        $get_avaiable_gateways = WC()->payment_gateways->get_available_payment_gateways();
        foreach ( $get_avaiable_gateways as $key => $gateway ) {
            $generate_payment_gateways[$key] = $gateway->title;
        }
        return $generate_payment_gateways;

    }

    /**
     * Get Previous and Next product if not visible
     */
    function woolentor_get_previous_next_product($previous = false){
        global $post;

        $args = array(
            'limit'      => 2,
            'visibility' => 'catalog',
            'exclude'    =>  array( $post->ID ),
            'orderby'    => 'date',
            'status'     => 'publish',
        );

        if ( ! $previous ) {
            $args['order'] = 'ASC';
        }
     
        $terms = get_the_terms( $post->ID, 'product_cat' );

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $args['category'] = wp_list_pluck( $terms, 'slug' );
        }

        $products = wc_get_products( $args );

        if ( ! empty( $products ) && count( $products ) >= 2 ) {
            return $products[0];
        }

        return false;   

    }

    /**
     * Product Hover Image For Universal all addon and any addon if need.
     *
     * @return void
     */
    function woolentor_product_hover_image( $gallery_images_ids, $image_size ) {

		$hover_image_id = '';
        if( !empty( $gallery_images_ids[0] ) ){
            $hover_image_id = $gallery_images_ids[0];
        }

        if ( $hover_image_id != '' ) :
            $img = wp_get_attachment_image( $hover_image_id, $image_size );
            ?>
                <div class="ht-product-hover-img">
                    <a href="<?php echo esc_url( get_permalink() ); ?>">
                        <?php echo wp_kses_post($img); ?>
                    </a>
                </div>
            <?php
        endif;

    }

    
}

/*
 * Products not found content.
 */
function woolentor_pro_products_not_found_content(){
    return '<div class="products-not-found"><p class="woocommerce-info">' . esc_html__( 'No products were found matching your selection.','woolentor-pro' ) . '</p></div>';
}

/**
 * It scan the elementor page given by id
 * Look for each sections and find the widget by it's name and get the widget settings.
 * 
 * page_id The ID of the page you want to get the settings from.
 * widget_name String The slug of the widget you want to get the settings for.
 * 
 * An array of settings for the widget.
 */
function woolentor_pro_get_settings_by_widget_name( $page_id, $widget_name ){
    $elementor_data = get_post_meta( $page_id, '_elementor_data', true );

    if ( is_array( $elementor_data ) ) {
        $elementor_data_arr = $elementor_data;
    } else {
        $elementor_data_arr = (array) json_decode( $elementor_data, true );
    }

    $settings = array();

    foreach ( $elementor_data_arr as $section ) {
        $widget = woolentor_pro_get_widget_from_section( $section, $widget_name );

        if ( is_array( $widget ) ) {
            $settings['settings'] = $widget['settings'];
            break;
        }
    }

    return $settings;
}

// Recursively searches through the section elements to find the widget by its name.
function woolentor_pro_get_widget_from_section( $section, $widget_name ) {
    if ( isset( $section['widgetType'] ) && $section['widgetType'] == $widget_name ) {
        return $section;
    }

    if ( isset( $section['elements'] ) && is_array( $section['elements'] ) ) {
        foreach ( $section['elements'] as $element ) {
            $widget = woolentor_pro_get_widget_from_section( $element, $widget_name );

            if ( $widget !== null ) {
                return $widget;
            }
        }
    }

    return null;
}

/**
 * This function fetches the post content and checks if it has blocks.
 * If blocks are present, it parses them and calls the find_block_attributes function to search for the desired block name.
 * 
 * @param [type] $post_id
 * @param [type] $block_name
 */
function woolentor_pro_get_block_settings_by_block_name( $post_id, $block_name ) {
    $post_content = get_post( $post_id )->post_content;

    if ( has_blocks( $post_content ) ) {
        $blocks = parse_blocks( $post_content );
        return woolentor_pro_find_block_attributes( $blocks, $block_name );
    }

    return null;
}

// Recursively searches through the block attributes to find the block by its name.
function woolentor_pro_find_block_attributes( $blocks, $block_name ) {
    foreach ( $blocks as $block ) {
        if ( isset( $block['blockName'] ) && $block['blockName'] == $block_name ) {
            return $block['attrs'];
        }

        if ( isset( $block['innerBlocks'] ) ) {
            $attrs = woolentor_pro_find_block_attributes( $block['innerBlocks'], $block_name );
            if ( $attrs !== null ) {
                return $attrs;
            }
        }
    }

    return null;
}