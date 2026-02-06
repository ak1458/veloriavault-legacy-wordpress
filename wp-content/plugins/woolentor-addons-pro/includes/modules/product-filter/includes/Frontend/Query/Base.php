<?php
/**
 * Base.
 */

namespace WLPF\Frontend\Query;

/**
 * Class.
 */
class Base {

	/**
     * Constructor.
     */
    public function __construct() {
        add_action( 'woocommerce_product_query', array( $this, 'product_query' ), 100 );
    }

    /**
     * Product query.
     */
    public function product_query( $query ) {
        $filter_args = \WLPF\Frontend\Selected::get_data();

        if ( empty( $filter_args ) ) {
            return;
        }

        $this->fixed_args( $query, $filter_args );
        $this->taxonomy_args( $query, $filter_args );
        $this->attribute_args( $query, $filter_args );
        $this->author_args( $query, $filter_args );
        $this->sorting_args( $query, $filter_args );
        $this->prices_args( $query, $filter_args );
        $this->search_args( $query, $filter_args );
        $this->page_args( $query, $filter_args );
    }

    /**
     * Fixed args.
     */
    protected function fixed_args( $query, $filter_args ) {
        $fixed_filter = ( isset( $filter_args['fixed_filter'] ) ? wlpf_cast( $filter_args['fixed_filter'], 'array' ) : array() );

        if ( ! empty( $fixed_filter ) ) {
            $search = ( isset( $fixed_filter['search'] ) ? wlpf_cast( $fixed_filter['search'], 'text' ) : '' );
            $sorting = ( isset( $fixed_filter['sorting'] ) ? wlpf_cast( $fixed_filter['sorting'], 'key' ) : '' );
            $taxonomy = ( isset( $fixed_filter['taxonomy'] ) ? wlpf_cast( $fixed_filter['taxonomy'], 'key' ) : '' );
            $taxonomy_term = ( isset( $fixed_filter['taxonomy_term'] ) ? wlpf_cast( $fixed_filter['taxonomy_term'], 'key' ) : '' );

            if ( ! isset( $filter_args['search'] ) && ( 0 < strlen( $search ) ) ) {
                $query->set( 's', $search );
            }

            if ( ! isset( $filter_args['sorting'] ) && ( 0 < strlen( $sorting ) ) ) {
                $ordering_args = wlpf_get_ordering_args( $sorting );

                $orderby = $ordering_args['orderby'];
                $order = $ordering_args['order'];
                $meta_key = $ordering_args['meta_key'];

                if ( ! empty( $orderby ) && ! empty( $order ) ) {
                    $query->set( 'orderby', $orderby );
                    $query->set( 'order', $order );
                    $query->set( 'meta_key', $meta_key );
                }
            }

            if ( ! empty( $taxonomy ) && ! empty( $taxonomy_term ) ) {
                $query->set( $taxonomy, $taxonomy_term );
                $query->set( 'taxonomy', $taxonomy );
                $query->set( 'term', $taxonomy_term );
            }
        }
    }

    /**
     * Taxonomy args.
     * @todo: This is a old function need to be removed.
     */
    protected function taxonomy_args_old( $query, $filter_args ) {
        $tax_query = $query->get( 'tax_query' );
        $tax_query = wlpf_cast( $tax_query, 'array', false );

        $tax_filter = ( isset( $filter_args['tax_filter'] ) ? wlpf_cast( $filter_args['tax_filter'], 'array' ) : array() );

        if ( ! empty( $tax_filter ) ) {
            $tax_query = array_merge( $tax_query, array_values( $tax_filter ) );

            $query->set( 'tax_query', $tax_query );
        }
    }

    /**
     * Taxonomy args.
     */
    protected function taxonomy_args( $query, $filter_args ) {
        $tax_query = $query->get( 'tax_query' );
        $tax_query = wlpf_cast( $tax_query, 'array', false );

        $tax_filter = ( isset( $filter_args['tax_filter'] ) ? wlpf_cast( $filter_args['tax_filter'], 'array' ) : array() );

        if ( ! empty( $tax_filter ) ) {
            
            // Remove existing taxonomy queries from tax_query to avoid conflicts
            foreach ( $tax_filter as $filter ) {
                if ( isset( $filter['taxonomy'] ) ) {
                    if ( isset( $filter['terms'] ) ) {
                        $query->set( $filter['taxonomy'], '' );
                    }
                }
            }

            $tax_query = array_merge( $tax_query, array_values( $tax_filter ) );

            $query->set( 'tax_query', $tax_query );
        }
    }

    /**
     * Taxonomy args new test.
     * @todo: This is a test function need to be removed.
     */
    protected function taxonomy_args_new_test( $query, $filter_args ) {
        $tax_query = $query->get( 'tax_query' );
        $tax_query = wlpf_cast( $tax_query, 'array', false );
    
        $tax_filter = ( isset( $filter_args['tax_filter'] ) ? wlpf_cast( $filter_args['tax_filter'], 'array' ) : array() );
    
        if ( ! empty( $tax_filter ) ) {
            // Check if we're filtering by product categories
            // $has_product_cat_filter = false;
            // $filtered_categories = array();
            
            foreach ( $tax_filter as $filter ) {
                if ( isset( $filter['taxonomy'] ) ) {
                    // $has_product_cat_filter = true;
                    if ( isset( $filter['terms'] ) ) {
                        // $filtered_categories = array_merge( $filtered_categories, (array) $filter['terms'] );
                        $query->set( $filter['taxonomy'], '' );
                    }
                }
            }
            
            // If we have product category filters, clear ALL conflicting category parameters
            // if ( $has_product_cat_filter ) {
                // Clear the main category parameters that conflict with our filter
                // $query->set( 'product_cat', '' );
                // $query->set( 'taxonomy', '' );
                // $query->set( 'term', '' );
                // $query->set( 'term_id', '' );
                
                // Remove existing product_cat queries from tax_query to avoid conflicts
                // if ( ! empty( $tax_query ) ) {
                //     $tax_query = array_filter( $tax_query, function( $query_item ) {
                //         return ! ( isset( $query_item['taxonomy'] ) && $query_item['taxonomy'] === 'product_cat' );
                //     });
                    
                //     // Reset array keys
                //     $tax_query = array_values( $tax_query );
                // }
            // }
            
            // Add the filter queries
            $tax_query = array_merge( $tax_query, array_values( $tax_filter ) );
    
            // Set proper relation
            // if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
            //     $tax_query['relation'] = 'AND';
            // }
    
            $query->set( 'tax_query', $tax_query );
        }
    }

    /**
     * Attribute args.
     */
    protected function attribute_args( $query, $filter_args ) {
        $tax_query = $query->get( 'tax_query' );
        $tax_query = wlpf_cast( $tax_query, 'array', false );

        $attr_filter = ( isset( $filter_args['attr_filter'] ) ? wlpf_cast( $filter_args['attr_filter'], 'array' ) : array() );

        if ( ! empty( $attr_filter ) ) {
            $tax_query = array_merge( $tax_query, array_values( $attr_filter ) );

            $query->set( 'tax_query', $tax_query );
        }
    }

    /**
     * Author args.
     */
    protected function author_args( $query, $filter_args ) {
        $author = ( isset( $filter_args['author'] ) ? wlpf_cast( $filter_args['author'], 'array' ) : array() );
        $terms = ( isset( $author['terms'] ) ? wlpf_cast( $author['terms'], 'array' ) : array() );

        if ( ! empty( $terms ) ) {
            $query->set( 'author__in', $terms );
        }
    }

    /**
     * Sorting args.
     */
    protected function sorting_args( $query, $filter_args ) {
        $sorting = ( isset( $filter_args['sorting'] ) ? wlpf_cast( $filter_args['sorting'], 'array' ) : array() );
        $term = ( isset( $sorting['term'] ) ? wlpf_cast( $sorting['term'], 'key' ) : '' );

        $ordering_args = wlpf_get_ordering_args( $term );

        $orderby = $ordering_args['orderby'];
        $order = $ordering_args['order'];
        $meta_key = $ordering_args['meta_key'];

        if ( ! empty( $orderby ) && ! empty( $order ) ) {
            $query->set( 'orderby', $orderby );
            $query->set( 'order', $order );
            $query->set( 'meta_key', $meta_key );
        }
    }

    /**
     * Prices args.
     */
    protected function prices_args( $query, $filter_args ) {
        $meta_query = $query->get( 'meta_query' );
        $meta_query = wlpf_cast( $meta_query, 'array', false );

        $prices = ( isset( $filter_args['prices'] ) ? wlpf_cast( $filter_args['prices'], 'array' ) : array() );

        if ( ! empty( $prices ) ) {
            $min_price = ( isset( $prices['min'] ) ? wlpf_cast( $prices['min'], 'absint' ) : 0 );
            $max_price = ( isset( $prices['max'] ) ? wlpf_cast( $prices['max'], 'absint' ) : $min_price );

            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $min_price,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );

            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $max_price,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );

            $query->set( 'meta_query', $meta_query );
        }
    }

    /**
     * Search args.
     */
    protected function search_args( $query, $filter_args ) {
        $search = ( isset( $filter_args['search'] ) ? wlpf_cast( $filter_args['search'], 'text' ) : '' );

        if ( 0 < strlen( $search ) ) {
            $query->set( 's', $search );
        }
    }

    /**
     * Page args.
     */
    protected function page_args( $query, $filter_args ) {
        $page = ( isset( $filter_args['page'] ) ? wlpf_cast( $filter_args['page'], 'absint' ) : null );

        if ( null !== $page ) {
            $query->set( 'paged', $page );
        }
    }

}