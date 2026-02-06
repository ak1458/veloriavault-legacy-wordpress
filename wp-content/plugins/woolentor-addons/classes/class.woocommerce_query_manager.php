<?php
/**
 * WooCommerce Query Manager
 * @author Zenaul Islam <zenaulislam.cse@email.com>
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit();

/**
 * WooCommerce Query Manager Class
 */
class WooLentor_WooCommerce_Query_Manager {

    /**
     * Instance
     * @var null
     */
    private static $_instance = null;

    /**
     * Query args
     * @var array
     */
    private $query_args = [];

    /**
     * Settings
     * @var array
     */
    private $settings = [];

    /**
     * Get Instance
     * @return WooLentor_WooCommerce_Query_Manager
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
        if( ! class_exists('WooCommerce') ) {
            return;
        }
    }

    /**
     * Set settings for query
     * @param array $settings
     * @return $this
     */
    public function set_settings( $settings = [] ) {
        $this->settings = wp_parse_args( $settings, $this->get_default_settings() );
        return $this;
    }

    /**
     * Get settings
     *
     * @return array
     */
    public function get_settings(){
        return $this->settings;
    }

    /**
     * Get default settings
     * @return array
     */
    private function get_default_settings() {
        return [
            'query_type'        => 'products', // products, featured, sale, best_selling, top_rated, etc.
            'query_orderby'     => 'date',
            'query_order'       => 'DESC',
            'posts_per_page'    => 12,
            'paged'             => 1,
            'offset'            => 0,
            'categories'        => [],
            'tags'              => [],
            'attributes'        => [],
            'include_products'  => [],
            'exclude_products'  => [],
            'exclude_out_of_stock' => false,
            'exclude_no_image'  => false,
            'min_price'         => '',
            'max_price'         => '',
            'on_sale_only'      => false,
            'featured_only'     => false,
            'visibility'        => '',
            'stock_status'      => '',
            'product_type'      => '',
            'tax_query'         => [],
            'meta_query'        => [],
            'author'            => '',
            's'                 => '', // Search term
        ];
    }

    /**
     * Build query args
     * @return array
     */
    public function build_query_args() {
        $this->query_args = [
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => false,
            'posts_per_page'      => $this->settings['posts_per_page'],
            'paged'               => $this->get_paged(),
            'orderby'             => $this->settings['query_orderby'],
            'order'               => $this->settings['query_order'],
            'meta_query'          => [],
            'tax_query'           => []
        ];

        // Set offset
        if ( ! empty( $this->settings['offset'] ) && $this->settings['paged'] == 1 ) {
            $this->query_args['offset'] = $this->settings['offset'];
        } elseif ( ! empty( $this->settings['offset'] ) && $this->settings['paged'] > 1 ) {
            $this->query_args['offset'] = $this->settings['offset'] + ( ( $this->settings['paged'] - 1 ) * $this->settings['posts_per_page'] );
        }

        // Apply query type
        $this->apply_query_type();

        // Apply sorting
        $this->apply_sorting();

        // Apply filters
        $this->apply_categories_filter();
        $this->apply_tags_filter();
        $this->apply_attributes_filter();
        $this->apply_price_filter();
        $this->apply_stock_filter();
        $this->apply_visibility_filter();
        $this->apply_product_type_filter();
        $this->apply_include_exclude_filter();
        $this->apply_search_filter();
        $this->apply_author_filter();

        // Apply WooCommerce default queries
        $this->apply_woocommerce_queries();

        // Apply custom tax and meta queries
        $this->apply_custom_queries();

        // Allow filtering
        $this->query_args = apply_filters( 'woolentor_wc_query_manager_args', $this->query_args, $this->settings );

        return $this->query_args;
    }

    /**
     * Get paged number
     * @return int
     */
    private function get_paged() {
        if ( ! empty( $this->settings['paged'] ) ) {
            return $this->settings['paged'];
        }

        if ( get_query_var( 'paged' ) ) {
            return get_query_var( 'paged' );
        } elseif ( get_query_var( 'page' ) ) {
            return get_query_var( 'page' );
        }

        return 1;
    }

    /**
     * Apply query type
     */
    private function apply_query_type() {
        switch ( $this->settings['query_type'] ) {
            case 'featured':
                $this->set_featured_products_query();
                break;

            case 'sale':
            case 'on_sale':
                $this->set_sale_products_query();
                break;

            case 'best_selling':
                $this->set_best_selling_products_query();
                break;

            case 'top_rated':
                $this->set_top_rated_products_query();
                break;

            case 'recent':
            case 'recent_products':
                $this->set_recent_products_query();
                break;

            case 'recently_viewed':
                $this->set_recently_viewed_products_query();
                break;

            case 'upsell':
            case 'upsells':
                $this->set_upsell_products_query();
                break;

            case 'cross_sell':
            case 'cross_sells':
                $this->set_cross_sell_products_query();
                break;

            case 'related':
                $this->set_related_products_query();
                break;

            case 'manual':
                $this->set_manual_products_query();
                break;

            case 'current_query':
                $this->set_current_query();
                break;

            default:
                // Standard products query
                break;
        }
    }

    /**
     * Set featured products query
     */
    private function set_featured_products_query() {
        $this->query_args['tax_query'][] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => 'featured',
            'operator' => 'IN',
        ];
    }

    /**
     * Set sale products query
     */
    private function set_sale_products_query() {
        $product_ids_on_sale = wc_get_product_ids_on_sale();

        if ( ! empty( $product_ids_on_sale ) ) {
            $this->query_args['post__in'] = $product_ids_on_sale;
        } else {
            $this->query_args['post__in'] = [0]; // Force no results
        }
    }

    /**
     * Set best selling products query
     */
    private function set_best_selling_products_query() {
        $this->query_args['meta_key'] = 'total_sales';
        $this->query_args['orderby'] = 'meta_value_num';
        $this->query_args['order'] = 'DESC';
    }

    /**
     * Set top rated products query
     */
    private function set_top_rated_products_query() {
        $this->query_args['meta_key'] = '_wc_average_rating';
        $this->query_args['orderby'] = 'meta_value_num';
        $this->query_args['order'] = 'DESC';
    }

    /**
     * Set recent products query
     */
    private function set_recent_products_query() {
        $this->query_args['orderby'] = 'date';
        $this->query_args['order'] = 'DESC';
    }

    /**
     * Set recently viewed products query
     */
    private function set_recently_viewed_products_query() {
        $viewed_products = woolentor_get_track_user_data();
        $viewed_products = array_reverse( array_filter( array_map( 'absint', $viewed_products ) ) );

        if ( ! empty( $viewed_products ) ) {
            $this->query_args['post__in'] = $viewed_products;
            $this->query_args['orderby'] = 'post__in';
        } else {
            $this->query_args['post__in'] = [0]; // Force no results
        }
    }

    /**
     * Set upsell products query
     */
    private function set_upsell_products_query() {
        global $product;

        if ( ! $product ) {
            $product = wc_get_product( get_the_ID() );
        }

        if ( $product && method_exists( $product, 'get_upsell_ids' ) ) {
            $upsells = $product->get_upsell_ids();

            if ( ! empty( $upsells ) ) {
                $this->query_args['post__in'] = $upsells;
                $this->query_args['orderby'] = 'post__in';
            } else {
                $this->query_args['post__in'] = [0]; // Force no results
            }
        } else {
            $this->query_args['post__in'] = [0]; // Force no results
        }
    }

    /**
     * Set cross sell products query
     */
    private function set_cross_sell_products_query() {
        $cross_sells = [];

        // Get cross-sells from cart
        if ( WC()->cart ) {
            $cross_sells = WC()->cart->get_cross_sells();
        }

        // If empty, try to get from current product
        if ( empty( $cross_sells ) ) {
            global $product;

            if ( ! $product ) {
                $product = wc_get_product( get_the_ID() );
            }

            if ( $product && method_exists( $product, 'get_cross_sell_ids' ) ) {
                $cross_sells = $product->get_cross_sell_ids();
            }
        }

        if ( ! empty( $cross_sells ) ) {
            $this->query_args['post__in'] = $cross_sells;
            $this->query_args['orderby'] = 'post__in';
        } else {
            $this->query_args['post__in'] = [0]; // Force no results
        }
    }

    /**
     * Set related products query
     */
    private function set_related_products_query() {
        global $product;

        if ( ! $product ) {
            $product = wc_get_product( get_the_ID() );
        }

        if ( $product ) {
            $related = wc_get_related_products( $product->get_id(), $this->settings['posts_per_page'] );

            if ( ! empty( $related ) ) {
                $this->query_args['post__in'] = $related;
                $this->query_args['orderby'] = 'post__in';
            } else {
                $this->query_args['post__in'] = [0]; // Force no results
            }
        } else {
            $this->query_args['post__in'] = [0]; // Force no results
        }
    }

    /**
     * Set manual products query
     */
    private function set_manual_products_query() {
        if ( ! empty( $this->settings['include_products'] ) ) {
            $products = is_array( $this->settings['include_products'] ) ? $this->settings['include_products'] : explode( ',', $this->settings['include_products'] );
            $this->query_args['post__in'] = array_map( 'absint', $products );

            if ( $this->settings['query_orderby'] === 'manual' || $this->settings['query_orderby'] === 'post__in' ) {
                $this->query_args['orderby'] = 'post__in';
            }
        } else {
            $this->query_args['post__in'] = [0]; // Force no results
        }
    }

    /**
     * Set current query
     */
    private function set_current_query() {
        global $wp_query;

        if ( $wp_query->is_main_query() && ( is_shop() || is_product_taxonomy() ) ) {
            $this->query_args = array_merge( $this->query_args, $wp_query->query_vars );
            $this->query_args['post_type'] = 'product';
        }
    }

    /**
     * Apply sorting
     */
    private function apply_sorting() {
        $orderby = $this->settings['query_orderby'];
        $order = $this->settings['query_order'];

        // Handle special sorting cases
        switch ( $orderby ) {
            case 'price':
            case 'price-desc':
                $this->query_args['meta_key'] = '_price';
                $this->query_args['orderby'] = 'meta_value_num';
                $this->query_args['order'] = ( $orderby === 'price-desc' ) ? 'DESC' : 'ASC';
                break;

            case 'popularity':
            case 'sales':
                $this->query_args['meta_key'] = 'total_sales';
                $this->query_args['orderby'] = 'meta_value_num';
                break;

            case 'rating':
                $this->query_args['meta_key'] = '_wc_average_rating';
                $this->query_args['orderby'] = 'meta_value_num';
                break;

            case 'menu_order':
                $this->query_args['orderby'] = 'menu_order title';
                break;

            case 'rand':
            case 'random':
                $this->query_args['orderby'] = 'rand';
                break;

            case 'title':
                $this->query_args['orderby'] = 'title';
                break;

            case 'ID':
                $this->query_args['orderby'] = 'ID';
                break;

            case 'modified':
                $this->query_args['orderby'] = 'modified';
                break;

            case 'comment_count':
                $this->query_args['orderby'] = 'comment_count';
                break;

            default:
                // Use WooCommerce catalog ordering
                $ordering_args = WC()->query->get_catalog_ordering_args( $orderby, $order );
                $this->query_args['orderby'] = $ordering_args['orderby'];
                $this->query_args['order'] = $ordering_args['order'];

                if ( ! empty( $ordering_args['meta_key'] ) ) {
                    $this->query_args['meta_key'] = $ordering_args['meta_key'];
                }
                break;
        }
    }

    /**
     * Apply categories filter
     */
    private function apply_categories_filter() {
        if ( ! empty( $this->settings['categories'] ) ) {
            $categories = is_array( $this->settings['categories'] ) ? $this->settings['categories'] : explode( ',', $this->settings['categories'] );

            $tax_query = [
                'taxonomy' => 'product_cat',
                'field'    => is_numeric( $categories[0] ) ? 'term_id' : 'slug',
                'terms'    => $categories,
            ];

            if ( ! empty( $this->settings['category_operator'] ) ) {
                $tax_query['operator'] = $this->settings['category_operator'];
            }

            $this->query_args['tax_query'][] = $tax_query;
        }else{
            // Current Category / taxonomy Page, If not set individual categorie from query settings then apply current taxonomy query
            $termobj = (array)get_queried_object();
            if( !empty($termobj['term_id']) ){
                $tax_query = [
                    [
                        "taxonomy" => $termobj['taxonomy'],
                        "terms" => $termobj['term_id'],
                        "field" => "term_id",
                        "include_children" => true
                    ]
                ];
                $this->query_args['tax_query'][] = $tax_query;
            }
        }
    }

    /**
     * Apply tags filter
     */
    private function apply_tags_filter() {
        if ( ! empty( $this->settings['tags'] ) ) {
            $tags = is_array( $this->settings['tags'] ) ? $this->settings['tags'] : explode( ',', $this->settings['tags'] );

            $tax_query = [
                'taxonomy' => 'product_tag',
                'field'    => is_numeric( $tags[0] ) ? 'term_id' : 'slug',
                'terms'    => $tags,
            ];

            if ( ! empty( $this->settings['tag_operator'] ) ) {
                $tax_query['operator'] = $this->settings['tag_operator'];
            }

            $this->query_args['tax_query'][] = $tax_query;
        }
    }

    /**
     * Apply attributes filter
     */
    private function apply_attributes_filter() {
        if ( ! empty( $this->settings['attributes'] ) && is_array( $this->settings['attributes'] ) ) {
            foreach ( $this->settings['attributes'] as $attribute => $terms ) {
                if ( ! empty( $terms ) ) {
                    $this->query_args['tax_query'][] = [
                        'taxonomy' => $attribute,
                        'field'    => 'slug',
                        'terms'    => is_array( $terms ) ? $terms : explode( ',', $terms ),
                        'operator' => ! empty( $this->settings['attribute_operator'] ) ? $this->settings['attribute_operator'] : 'IN',
                    ];
                }
            }
        }
    }

    /**
     * Apply price filter
     */
    private function apply_price_filter() {
        if ( ! empty( $this->settings['min_price'] ) || ! empty( $this->settings['max_price'] ) ) {
            $min = ! empty( $this->settings['min_price'] ) ? floatval( $this->settings['min_price'] ) : 0;
            $max = ! empty( $this->settings['max_price'] ) ? floatval( $this->settings['max_price'] ) : PHP_FLOAT_MAX;

            $this->query_args['meta_query'][] = [
                'key'     => '_price',
                'value'   => [ $min, $max ],
                'compare' => 'BETWEEN',
                'type'    => 'DECIMAL(10,2)'
            ];
        }
    }

    /**
     * Apply stock filter
     */
    private function apply_stock_filter() {
        // Exclude out of stock
        if ( $this->settings['exclude_out_of_stock'] || $this->settings['stock_status'] === 'instock' ) {
            $this->query_args['meta_query'][] = [
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '='
            ];
        }

        // Only out of stock
        if ( $this->settings['stock_status'] === 'outofstock' ) {
            $this->query_args['meta_query'][] = [
                'key'     => '_stock_status',
                'value'   => 'outofstock',
                'compare' => '='
            ];
        }

        // On backorder
        if ( $this->settings['stock_status'] === 'onbackorder' ) {
            $this->query_args['meta_query'][] = [
                'key'     => '_stock_status',
                'value'   => 'onbackorder',
                'compare' => '='
            ];
        }
    }

    /**
     * Apply visibility filter
     */
    private function apply_visibility_filter() {
        if ( ! empty( $this->settings['visibility'] ) ) {
            $visibility_terms = [];

            switch ( $this->settings['visibility'] ) {
                case 'visible':
                    $visibility_terms = [ 'exclude-from-search', 'exclude-from-catalog' ];
                    $operator = 'NOT IN';
                    break;

                case 'catalog':
                    $visibility_terms = [ 'exclude-from-catalog' ];
                    $operator = 'NOT IN';
                    break;

                case 'search':
                    $visibility_terms = [ 'exclude-from-search' ];
                    $operator = 'NOT IN';
                    break;

                case 'hidden':
                    $visibility_terms = [ 'exclude-from-search', 'exclude-from-catalog' ];
                    $operator = 'IN';
                    break;

                case 'featured':
                    $visibility_terms = [ 'featured' ];
                    $operator = 'IN';
                    break;
            }

            if ( ! empty( $visibility_terms ) ) {
                $this->query_args['tax_query'][] = [
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => $visibility_terms,
                    'operator' => $operator,
                ];
            }
        }
    }

    /**
     * Apply product type filter
     */
    private function apply_product_type_filter() {
        if ( ! empty( $this->settings['product_type'] ) ) {
            $types = is_array( $this->settings['product_type'] ) ? $this->settings['product_type'] : explode( ',', $this->settings['product_type'] );

            $this->query_args['tax_query'][] = [
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => $types,
            ];
        }
    }

    /**
     * Apply include/exclude filter
     */
    private function apply_include_exclude_filter() {
        // Include specific products
        if ( ! empty( $this->settings['include_products'] ) && $this->settings['query_type'] !== 'manual' ) {
            $include = is_array( $this->settings['include_products'] ) ? $this->settings['include_products'] : explode( ',', $this->settings['include_products'] );
            $this->query_args['post__in'] = array_map( 'absint', $include );
        }

        // Exclude specific products
        if ( ! empty( $this->settings['exclude_products'] ) ) {
            $exclude = is_array( $this->settings['exclude_products'] ) ? $this->settings['exclude_products'] : explode( ',', $this->settings['exclude_products'] );
            $this->query_args['post__not_in'] = array_map( 'absint', $exclude );
        }

        // Exclude products without images
        if ( $this->settings['exclude_no_image'] ) {
            $this->query_args['meta_query'][] = [
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS'
            ];
        }
    }

    /**
     * Apply search filter
     */
    private function apply_search_filter() {
        if ( ! empty( $this->settings['s'] ) ) {
            $this->query_args['s'] = sanitize_text_field( $this->settings['s'] );
        }
    }

    /**
     * Apply author filter
     */
    private function apply_author_filter() {
        if ( ! empty( $this->settings['author'] ) ) {
            $this->query_args['author'] = $this->settings['author'];
        }
    }

    /**
     * Apply WooCommerce default queries
     */
    private function apply_woocommerce_queries() {
        // Get WooCommerce meta query
        $wc_meta_query = WC()->query->get_meta_query();
        if ( ! empty( $wc_meta_query ) ) {
            $this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], $wc_meta_query );
        }

        // Get WooCommerce tax query
        $wc_tax_query = WC()->query->get_tax_query();
        if ( ! empty( $wc_tax_query ) ) {
            $this->query_args['tax_query'] = array_merge( $this->query_args['tax_query'], $wc_tax_query );
        }
    }

    /**
     * Apply custom tax and meta queries
     */
    private function apply_custom_queries() {
        // Add custom tax queries
        if ( ! empty( $this->settings['tax_query'] ) && is_array( $this->settings['tax_query'] ) ) {
            $this->query_args['tax_query'] = array_merge( $this->query_args['tax_query'], $this->settings['tax_query'] );
        }

        // Add custom meta queries
        if ( ! empty( $this->settings['meta_query'] ) && is_array( $this->settings['meta_query'] ) ) {
            $this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], $this->settings['meta_query'] );
        }

        // Set relation for multiple queries
        if ( count( $this->query_args['tax_query'] ) > 1 ) {
            $this->query_args['tax_query']['relation'] = ! empty( $this->settings['tax_query_relation'] ) ? $this->settings['tax_query_relation'] : 'AND';
        }

        if ( count( $this->query_args['meta_query'] ) > 1 ) {
            $this->query_args['meta_query']['relation'] = ! empty( $this->settings['meta_query_relation'] ) ? $this->settings['meta_query_relation'] : 'AND';
        }
    }

    /**
     * Get products
     * @return WP_Query
     */
    public function get_products() {
        $query_args = $this->build_query_args();
        return new WP_Query( $query_args );
    }

    /**
     * Get product IDs only
     * @return array
     */
    public function get_product_ids() {
        $query_args = $this->build_query_args();
        $query_args['fields'] = 'ids';

        $query = new WP_Query( $query_args );
        return $query->posts;
    }

    /**
     * Get total pages for pagination
     * @param WP_Query $query
     * @return int
     */
    public function get_total_pages( $query ) {
        return $query->max_num_pages;
    }

    /**
     * Check if has more pages
     * @param WP_Query $query
     * @return bool
     */
    public function has_more_pages( $query ) {
        $current_page = ! empty( $this->settings['paged'] ) ? $this->settings['paged'] : 1;
        return $current_page < $query->max_num_pages;
    }

    /**
     * Reset query
     * @return $this
     */
    public function reset() {
        $this->query_args = [];
        $this->settings = [];
        return $this;
    }
}

// Initialize
WooLentor_WooCommerce_Query_Manager::instance();