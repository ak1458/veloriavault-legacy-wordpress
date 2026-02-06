<?php
/**
 *	NM: Shop - Results bar/button
 */

defined( 'ABSPATH' ) || exit;

global $nm_theme_options;

$has_results = false;
$results_bar_class = '';
$results_bar_buttons = array();

/* Search */
if ( ! empty( $_REQUEST['s'] ) ) { // Is search query set and not empty?
    $has_results = true;
    $results_bar_class .= ' is-search';
    $results_bar_buttons['search_taxonomy'] = array(
        'parent_class'  => 'nm-shop-search-reset',
        'id'            => 'nm-shop-search-taxonomy-reset',
        'title'         => sprintf( esc_html__( 'Search results: &ldquo;%s&rdquo;', 'woocommerce' ), esc_html( $_REQUEST['s'] ) ),
    );
}

/* Category */
if ( is_product_category() ) {    
    $show_active_category = apply_filters( 'nm_shop_show_active_category', true );
    
    if ( $show_active_category ) {
        $has_results = true;
        $results_bar_buttons['search_taxonomy'] = array(
            'parent_class'  => 'nm-shop-taxonomy-reset nm-shop-category-reset',
            'id'            => 'nm-shop-search-taxonomy-reset',
        );
        $current_term = $GLOBALS['wp_query']->get_queried_object();

        $results_bar_class .= ' is-category';
        $results_bar_buttons['search_taxonomy']['title'] = '<span>' . esc_html__( 'Category', 'woocommerce' ) . ': </span>' . esc_html( $current_term->name );
    }
}

/* Tag */
if ( is_product_tag() ) {
    $has_results = true;
    $results_bar_buttons['search_taxonomy'] = array(
        'parent_class'  => 'nm-shop-taxonomy-reset nm-shop-tag-reset',
        'id'            => 'nm-shop-search-taxonomy-reset',
    );
    $current_term = $GLOBALS['wp_query']->get_queried_object();

    $results_bar_class .= ' is-tag';
    $results_bar_buttons['search_taxonomy']['title'] = '<span>' . esc_html__( 'Tag', 'woocommerce' ) . ': </span>'  . esc_html( $current_term->name );
}

/* Brand */
if ( is_tax( 'product_brand' ) ) {
    $has_results = true;
    $results_bar_buttons['search_taxonomy'] = array(
        'parent_class'  => 'nm-shop-taxonomy-reset nm-shop-brand-reset',
        'id'            => 'nm-shop-search-taxonomy-reset',
    );
    $current_term = $GLOBALS['wp_query']->get_queried_object();
    
    $results_bar_class .= ' is-brand';
    $results_bar_buttons['search_taxonomy']['title'] = '<span>' . esc_html__( 'Brand', 'woocommerce' ) . ': </span>'  . esc_html( $current_term->name );
}

/* Filters */
$show_active_filters = apply_filters( 'nm_shop_show_active_filters', true );
$active_filters = '';
if ( $show_active_filters ) {
    $active_filters = nm_get_active_filters();
    if ( $active_filters ) {
        $has_results = true;
        $results_bar_class .= ' has-filters has-individual-filters';
        $results_bar_buttons['active_filters'] = array(
            'parent_class'  => 'nm-shop-active-filters',
            'id'            => 'nm-shop-active-filters',
            'html'          => $active_filters,
        );
        $results_bar_buttons['filters'] = array(
            'parent_class'  => 'nm-shop-filters-reset',
            'id'            => 'nm-shop-filters-reset',
            'title'         => esc_html__( 'Clear filters', 'woocommerce' ),
        );
    }
} else {
    $filters_count = nm_get_active_filters_count();
    if ( $filters_count ) {
        $has_results = true;
        $results_bar_class = ' has-filters';
        $results_bar_buttons['filters'] = array(
            'parent_class'  => 'nm-shop-filters-reset',
            'id'            => 'nm-shop-filters-reset',
            'title'         => sprintf( esc_html__( 'Filters active %s(%s)%s', 'nm-framework' ), '<span>', $filters_count, '</span>' )
        );
    }
}

if ( $has_results ) :
?>

<div class="nm-shop-results-bar <?php echo esc_attr( $results_bar_class ); ?>">
    <ul>
        <?php
            $shop_url_escaped = esc_url( get_permalink( wc_get_page_id( 'shop' ) ) );
            
            foreach ( $results_bar_buttons as $button ) {
                if ( $button['id'] == 'nm-shop-active-filters' ) {
                    echo $button['html'];
                } else {
                    printf( '<li class="%1$s"><a href="%2$s" id="%3$s" class="nm-shop-reset-button" data-shop-url="%4$s">%5$s</a></li>',
                        $button['parent_class'],
                        '#',
                        $button['id'],
                        $shop_url_escaped,
                        $button['title']
                    );
                }
            }
        ?>
    </ul>
</div>

<?php endif; ?>
