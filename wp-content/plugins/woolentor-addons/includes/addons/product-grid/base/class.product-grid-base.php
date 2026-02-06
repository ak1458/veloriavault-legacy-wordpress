<?php
/**
 * Base Product Grid Class
 * Handles common functionality for all product grid styles
 *
 * @package WooLentor
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit();

/**
 * WooLentor Product Grid Base Class
 */
class WooLentor_Product_Grid_Base {

    /**
     * Instance
     */
    private static $_instance = null;

    /**
     * Query Manager instance
     */
    protected $query_manager = null;

    /**
     * Available grid styles
     */
    protected $available_styles = [
        'modern'        => 'Modern',
        'minimalist'    => 'Luxury Minimalist',
        'editorial'     => 'Premium Editorial',
        'bold'          => 'Bold & Vibrant',
        'classic'       => 'Classic E-commerce',
        'masonry'       => 'Masonry',
        'metro'         => 'Metro',
        'carousel'      => 'Carousel'
    ];

    /**
     * Available layouts
     */
    protected $available_layouts = [
        'grid'      => 'Grid',
        'list'      => 'List',
        'masonry'   => 'Masonry',
    ];

    /**
     * Get Instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        if ( class_exists( 'WooLentor_WooCommerce_Query_Manager' )) {
            $this->query_manager = WooLentor_WooCommerce_Query_Manager::instance();
        }
    }

    /**
     * Get available styles
     */
    public function get_available_styles() {
        return apply_filters( 'woolentor_product_grid_styles', $this->available_styles );
    }

    /**
     * Get available layouts
     */
    public function get_available_layouts() {
        return apply_filters( 'woolentor_product_grid_layouts', $this->available_layouts );
    }

    /**
     * Get default settings
     */
    public function get_default_settings() {
        return [
            // Style Settings
            'style'                 => 'modern',
            'layout'                => 'grid',

            // Query Settings
            'query_type'            => 'products',
            'posts_per_page'        => 12,
            'orderby'               => 'date',
            'order'                 => 'DESC',
            'categories'            => [],
            'tags'                  => [],
            'exclude_out_of_stock'  => false,
            'exclude_no_image'      => false,

            // Layout Settings
            'columns'               => 4,
            'columns_tablet'        => 3,
            'columns_mobile'        => 1,
            'gap'                   => 30,
            'gap_tablet'            => 20,
            'gap_mobile'            => 15,

            // Display Settings
            'show_image'            => true,
            'image_size'            => 'woocommerce_thumbnail',
            'show_secondary_image'  => false,
            'show_title'            => true,
            'title_tag'             => 'h3',
            'title_length'          => 0,
            'show_price'            => true,
            'show_rating'           => true,
            'show_categories'       => false,
            'show_excerpt'          => false,
            'excerpt_length'        => 20,
            'show_add_to_cart'      => true,
            'show_badges'           => true,
            'show_stock_status'     => false,

            // Action Buttons
            'show_quick_view'       => true,
            'show_wishlist'         => true,
            'show_compare'          => true,

            // Hover Effects
            'hover_animation'       => 'fade-up',
            'image_hover_effect'    => 'zoom',

            // Pagination
            'enable_pagination'     => true,
            'pagination_type'       => 'numbers',
            'load_more_text'        => esc_html__('Load More','woolentor'),
            'load_more_complete_text' => esc_html__( 'No more products', 'woolentor' ),

            // Advanced
            'custom_class'          => '',
            'enable_quick_cart'     => false,

            'grid_id'               => uniqid( 'woolentor-grid-' ),
        ];
    }

    /**
     * Prepare query settings for Query Manager
     */
    public function prepare_query_settings( $settings ) {
        $defaults = $this->get_default_settings();
        $settings = wp_parse_args( $settings, $defaults );

        $query_settings = [
            'query_type'            => $settings['query_type'],
            'query_orderby'         => $settings['orderby'],
            'query_order'           => $settings['order'],
            'posts_per_page'        => $settings['posts_per_page'],
            'categories'            => $settings['categories'],
            'tags'                  => $settings['tags'],
            'exclude_out_of_stock'  => $settings['exclude_out_of_stock'],
            'exclude_no_image'      => $settings['exclude_no_image'],
        ];

        // Handle manual product selection
        if ( ! empty( $settings['include_products'] ) ) {
            $query_settings['include_products'] = $settings['include_products'];
            $query_settings['query_type'] = 'manual';
        }

        // Handle exclusions
        if ( ! empty( $settings['exclude_products'] ) ) {
            $query_settings['exclude_products'] = $settings['exclude_products'];
        }

        // Price range
        if ( ! empty( $settings['min_price'] ) || ! empty( $settings['max_price'] ) ) {
            $query_settings['min_price'] = $settings['min_price'] ?? '';
            $query_settings['max_price'] = $settings['max_price'] ?? '';
        }

        // Handle pagination
        if ( ! empty( $settings['enable_pagination'] ) ) {
            if( isset($settings['paged']) ){
                $paged = $settings['paged'];
            }else{
                $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
            }
            $query_settings['paged'] = $paged;
        }

        // If Enable Product Filter Module
        if ( ! empty( $settings['enable_filters'] ) ) {
            $filter_args = !empty( $settings['filter_arg'] ) ? $settings['filter_arg'] : []; // If Filter arg is pass in settings
            $query_settings = apply_filters( 'woolentor_filterable_shortcode_products_query', $query_settings, $filter_args );
        }

        $query_settings = apply_filters( 'woolentor_product_grid_query_settings', $query_settings, $settings );

        return $query_settings;
    }

    /**
     * Get products using Query Manager
     */
    public function get_products( $settings ) {
        if ( ! $this->query_manager ) {
            // Fallback query if Query Manager is not available
            $query_args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => isset( $settings['posts_per_page'] ) ? $settings['posts_per_page'] : 6,
                'orderby' => isset( $settings['orderby'] ) ? $settings['orderby'] : 'date',
                'order' => isset( $settings['order'] ) ? $settings['order'] : 'DESC',
            ];

            // Add product visibility meta query for WooCommerce
            if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
                $product_visibility_terms = wc_get_product_visibility_term_ids();
                $query_args['tax_query'][] = [
                    'taxonomy' => 'product_visibility',
                    'field'    => 'term_taxonomy_id',
                    'terms'    => $product_visibility_terms['exclude-from-catalog'],
                    'operator' => 'NOT IN',
                ];
            }

            return new WP_Query( $query_args );
        }

        $query_settings = $this->prepare_query_settings( $settings );

        // Set Query Manager settings
        $this->query_manager->set_settings( $query_settings );

        return $this->query_manager->get_products();
    }

    /**
     * Get wrapper classes
     */
    public function get_wrapper_classes( $settings ) {
        $classes = [
            'woolentor-product-grid',
            'woolentor-style-' . $settings['style'],
            'woolentor-layout-' . $settings['layout']
        ];

        if ( ($settings['pagination_type'] == 'load_more') || ($settings['pagination_type'] == 'infinite') ) {
            $classes[] = 'woolentor-ajax-enabled';
        }

        if ( ! empty( $settings['enable_filters'] ) ) {
            $classes[] = 'wl-filterable-products-wrap';
        }

        if ( ! empty( $settings['hover_animation'] ) ) {
            $classes[] = 'woolentor-hover-' . $settings['hover_animation'];
        }

        if ( ! empty( $settings['custom_class'] ) ) {
            $classes[] = $settings['custom_class'];
        }

        if ( ! empty( $settings['same_height_grid'] ) ) {
            $classes[] = 'woolentor-same-height-grid';
        }

        return apply_filters( 'woolentor_product_grid_wrapper_classes', $classes, $settings );
    }

    /**
     * Get product classes
     */
    public function get_product_classes( $product, $settings ) {
        $classes = [
            'woolentor-product-item',
            'product',
            'type-product',
            'status-' . $product->get_status(),
            'product-type-' . $product->get_type(),
        ];

        if ( $product->is_featured() ) {
            $classes[] = 'featured';
        }

        if ( $product->is_on_sale() ) {
            $classes[] = 'sale';
        }

        if ( ! $product->is_in_stock() ) {
            $classes[] = 'outofstock';
        }

        return apply_filters( 'woolentor_product_grid_item_classes', $classes, $product, $settings );
    }

    /**
     * Allow Style / Template Type
     *
     * @return array
     */
    private function get_allowed_styles() : array {
        return [
            'modern',
            'luxury',
            'editorial',
            'magazine',
        ];
    }

    /**
     * Get Template Path
     *
     * @param string $style
     * @return string
     */
    private function get_template_path( string $style ) : string {
        $base_dir     = wp_normalize_path( WOOLENTOR_ADDONS_PL_PATH . 'templates/product-grid/' );
        $candidate    = wp_normalize_path( $base_dir . $style . '.php' );
        $real_base    = wp_normalize_path( realpath( $base_dir ) );
        $real_target  = wp_normalize_path( realpath( $candidate ) );
    
        // If file doesn’t exist or resolves outside base, fall back
        if ( ! $real_target || strpos( $real_target, $real_base ) !== 0 || ! is_file( $real_target ) ) {
            // fall back to default safely
            $fallback = wp_normalize_path( $base_dir . 'modern.php' );
            return is_file( $fallback ) ? $fallback : ''; // empty -> handle gracefully
        }
    
        return apply_filters( 'woolentor_product_grid_template_path', $real_target, $style );
    }

    /**
     * Load template
     */
    public function load_template( $style, $layout, $products, $settings, $only_items = false ) {

        $style = isset( $style ) ? sanitize_key( $style ) : 'modern';
        if ( ! in_array( $style, $this->get_allowed_styles(), true ) ) {
            $style = 'modern';
        }

        $template_path = $this->get_template_path( $style );

        if ( file_exists( $template_path ) ) {
            // Make sure WooCommerce functions are available
            if ( ! function_exists( 'woocommerce_get_product_thumbnail' ) ) {
                return;
            }
            include $template_path;
        }
    }

    /**
     * Render product grid
     */
    public function render( $settings ) {

        $settings = wp_parse_args( $settings, $this->get_default_settings() );

        // Get wrapper classes
        $wrapper_classes = $this->get_wrapper_classes( $settings );

        // Start output
        echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '" data-wl-widget-name="'.esc_attr($settings['widget_name']).'" data-wl-widget-settings="' . esc_attr( htmlspecialchars(wp_json_encode( $settings )) ) . '">';

            // Render products wrapper
            echo '<div class="woolentor-products-wrapper '.($settings['enable_filters'] ? 'wl-filterable-products-content' : '').' ">';
                $this->render_items( $settings );
            echo '</div>'; // End products wrapper

        echo '</div>'; // End main wrapper

        // Reset post data
        wp_reset_postdata();
    }

    /**
     * Prepare only Item List Without Wrapper
     *
     * @param [Array] $settings
     * @param boolean $only_items
     * @return void
     */
    public function render_items( $settings, $only_items = false ){

        // If directly call for get Items then prepare Settings again.
        if( $only_items === true ){
            $settings = wp_parse_args( $settings, $this->get_default_settings() );
        }

        // Dropdown Filter
        $order_by = isset( $_GET['orderby'] ) ? $_GET['orderby'] : $settings['orderby'];
        $settings['orderby'] = $order_by;

        // Get products
        $products = $this->get_products( $settings );

        // Render filters if enabled
        if ( $settings['layout'] === 'grid_list_tab' && !$only_items ) {
            $this->render_filters( $settings, $products );
        }
        
        if ( $products && $products->have_posts() ) {

            // Load template
            $this->load_template( $settings['style'], $settings['layout'], $products, $settings, $only_items );

            // Render pagination
            if ( ! empty( $settings['enable_pagination'] ) && !$only_items ) {
                $this->render_pagination( $products, $settings );
            }
        } else {
            $this->render_no_products_found( $settings );
        }

    }

    /**
     * Render filters
     */
    public function render_filters( $settings, $products ) {

        $found_product = $products->found_posts;
        $total_pages = $products->max_num_pages;
        $current_page = isset( $settings['paged'] ) ? $settings['paged'] : max( 1, get_query_var( 'paged' ) );

        $show_from = ($settings['posts_per_page'] * $current_page) - $settings['posts_per_page'];
        $show_from = $show_from === 0 ? 1 : $show_from;
        $show_to = $settings['posts_per_page'] * $current_page;
        $show_to = $show_to > $found_product ? $found_product : $show_to;

        $order_by = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'menu_order';

        $result_count_formate = 'Showing <strong>%1$s-%2$s</strong> of <strong>%3$s</strong> products';

        $switcher_tab_style = isset( $settings['switcher_tab_style'] ) ? $settings['switcher_tab_style'] : 'one';
        $default_view = isset( $settings['default_view_mode'] ) ? $settings['default_view_mode'] : 'grid';
        $grid_active_class = ( $default_view === 'grid' ) ? 'woolentor-active' : '';
        $list_active_class = ( $default_view === 'list' ) ? 'woolentor-active' : '';
        $switcher_tab_grid_text = isset( $settings['switcher_tab_grid_text'] ) ? $settings['switcher_tab_grid_text'] : 'Grid View';
        $switcher_tab_list_text = isset( $settings['switcher_tab_list_text'] ) ? $settings['switcher_tab_list_text'] : 'Editorial View';
        ?>
        <div class="woolentor-product-filters woolentor-style-<?php echo esc_attr( $switcher_tab_style ); ?>">

            <?php if( $switcher_tab_style === 'two'): ?>
            <div class="woolentor-layout-switcher-area">
                <div class="woolentor-layout-switcher">
                    <button class="woolentor-layout-btn <?php echo esc_attr( $grid_active_class ); ?>" data-layout="grid" aria-label="<?php esc_attr_e( 'Grid view', 'woolentor' ); ?>">
                        <?php echo esc_html( $switcher_tab_grid_text ); ?>
                    </button>
                    <button class="woolentor-layout-btn <?php echo esc_attr( $list_active_class ); ?>" data-layout="list" aria-label="<?php esc_attr_e( 'List view', 'woolentor' ); ?>">
                        <?php echo esc_html( $switcher_tab_list_text ); ?>
                    </button>
                </div>
            </div>
            <?php else: ?>
                <div class="woolentor-filter-row">

                    <div class="woolentor-results-info">
                        <?php echo sprintf( $result_count_formate, $show_from, $show_to, $found_product); ?>
                    </div>

                    <div class="woolentor-view-controls">
                        <!-- Sorting -->
                        <div class="woolentor-filter-item woolentor-filter-sort">
                            <?php woolentor_product_shorting($order_by); ?>
                        </div>

                        <!-- Layout Switcher -->
                        <div class="woolentor-filter-item woolentor-layout-switcher">
                            <button class="woolentor-layout-btn <?php echo esc_attr( $grid_active_class ); ?>" data-layout="grid" aria-label="<?php esc_attr_e( 'Grid view', 'woolentor' ); ?>">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                            </button>
                            <button class="woolentor-layout-btn <?php echo esc_attr( $list_active_class ); ?>" data-layout="list" aria-label="<?php esc_attr_e( 'List view', 'woolentor' ); ?>">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render pagination
     */
    public function render_pagination( $products, $settings ) {

        $query_settings = $this->query_manager->get_settings();
        $total_pages = $products->max_num_pages;
        $current_page = isset( $query_settings['paged'] ) ? $query_settings['paged'] : max( 1, get_query_var( 'paged' ) );

        if ( $total_pages <= 1 ) {
            return;
        }

        echo '<div class="woolentor-pagination woolentor-pagination-' . esc_attr( $settings['pagination_type'] ) . '">';

        switch ( $settings['pagination_type'] ) {
            case 'numbers':
                echo wp_kses_post(paginate_links( [
                    'current' => $current_page,
                    'total' => $total_pages,
                    'type' => 'list',
                    'prev_text' => '<i class="sli sli-arrow-left"></i>',
                    'next_text' => '<i class="sli sli-arrow-right"></i>',
                ] ));

                break;

            case 'load_more':
                if ( $current_page < $total_pages ) {
                    echo '<button class="woolentor-ajax-loader"><span class="spinner"></span></button>';
                    echo '<button class="woolentor-load-more-btn" data-complete-loadtxt="'.esc_attr( $settings['load_more_complete_text'] ).'" data-grid-id="'.$settings['grid_id'].'" data-page="' . ( $current_page + 1 ) . '" data-max-pages="' . $total_pages . '">';
                    echo esc_html( $settings['load_more_text'] );
                    echo '</button>';
                }else{
                    echo '<button class="woolentor-load-more-btn" data-complete-loadtxt="'.esc_attr( $settings['load_more_complete_text'] ).'" data-grid-id="'.$settings['grid_id'].'" data-page="' . ( $current_page + 1 ) . '" data-max-pages="' . $total_pages . '" disabled="true">';
                    echo esc_html( $settings['load_more_complete_text'] );
                    echo '</button>';
                }
                break;

            case 'infinite':
                echo '<div class="woolentor-infinite-scroll" data-grid-id="'.$settings['grid_id'].'" data-page="' . ( $current_page + 1 ) . '" data-max-pages="' . $total_pages . '">';
                echo '<button class="woolentor-ajax-loader"><span class="spinner"></span></button>';
                echo '</div>';
                break;
        }

        echo '</div>';
    }

    /**
     * Render no products found message
     */
    public function render_no_products_found( $settings ) {
        ?>
        <div class="woolentor-no-products">
            <p><?php esc_html_e( 'No products were found matching your selection.', 'woolentor' ); ?></p>
        </div>
        <?php
    }
}

// Initialize
WooLentor_Product_Grid_Base::instance();