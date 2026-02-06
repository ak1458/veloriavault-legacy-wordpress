<?php
/**
 * Product Grid Template - Editorial Grid & List Style
 * Support for both grid and list view layouts
 *
 * @var array $settings Widget/Block settings
 * @var WP_Query $products WooCommerce products query
 * @var string $grid_classes Grid container CSS classes
 * @var string $grid_id Unique grid container ID
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure we have products to display
if ( ! $products || ! $products->have_posts() ) {
    echo '<div class="woolentor-no-products">' . esc_html__( 'No products found.', 'woolentor' ) . '</div>';
    return;
}

// Extract settings with defaults
$settings = wp_parse_args( $settings, [
    'columns' => 3,
    'layout' => 'grid',
    'show_image' => true,
    'show_title' => true,
    'show_price' => true,
    'show_rating' => true,
    'show_categories' => true,
    'show_add_to_cart' => true,
    'show_sale_badge' => true,
    'show_new_badge' => true,
    'new_badge_days' => 7,
    'show_trending_badge' => false,
    'badge_style' => 'solid',
    'badge_position' => 'top-left',
    'show_quick_actions' => true,
    'show_grid_description' => true,
    'grid_description_length' => 15,
    'show_list_description' => true,
    'list_description_length' => 30,
    'show_grid_stock_indicator' => true,
    'show_list_stock_indicator' => true,
    'stock_text_in_stock' => 'In Stock',
    'stock_text_out_of_stock' => 'Out of Stock',
    'grid_image_aspect_ratio' => '3-4',
    'list_image_aspect_ratio' => '3-4',
    'image_size' => 'woocommerce_thumbnail',
    'show_secondary_image' => false,
] );

// Prepare grid classes
$grid_classes = isset( $settings['grid_classes'] ) ? $settings['grid_classes'] : '';
$grid_id = isset( $settings['grid_id'] ) ? $settings['grid_id'] : uniqid( 'woolentor-grid-' );

// Add column class if not already included
if ( strpos( $grid_classes, 'woolentor-columns-' ) === false ) {
    $grid_classes .= ' woolentor-columns-' . $settings['columns'];
}

// Get layout mode and settings
$layout_mode = $settings['layout'];
$show_image = $settings['show_image'];
$show_title = $settings['show_title'];
$show_price = $settings['show_price'];
$show_rating = $settings['show_rating'];
$show_categories = $settings['show_categories'];
$show_add_to_cart = $settings['show_add_to_cart'];
$show_sale_badge = $settings['show_sale_badge'];
$sale_badge_text = $settings['sale_badge_text'];
$show_new_badge = $settings['show_new_badge'];
$new_badge_text = $settings['new_badge_text'];
$show_trending_badge = $settings['show_trending_badge'];
$trending_badge_text = $settings['trending_badge_text'];
$show_quick_actions = $settings['show_quick_actions'];
$show_grid_description = $settings['show_grid_description'];
$show_list_description = $settings['show_list_description'];
$grid_description_length = absint($settings['grid_description_length']);
$list_description_length = absint($settings['list_description_length']);
$show_grid_stock_indicator = $settings['show_grid_stock_indicator'];
$show_list_stock_indicator = $settings['show_list_stock_indicator'];
$stock_text_in_stock = $settings['stock_text_in_stock'];
$stock_text_out_of_stock = $settings['stock_text_out_of_stock'];
$grid_image_aspect_ratio = $settings['grid_image_aspect_ratio'];
$list_image_aspect_ratio = $settings['list_image_aspect_ratio'];
$show_view_details = $settings['show_view_details'];
$view_details_text = $settings['view_details_text'];
$show_secondary_image = $settings['show_secondary_image'];
$show_quick_view = $settings['show_quick_view'];
$show_badges = $settings['show_badges'];
$show_wishlist = ($settings['show_wishlist'] && true === woolentor_has_wishlist_plugin());
$show_compare = ($settings['show_compare'] && true === woolentor_exist_compare_plugin());

// Image size
$image_size = $settings['image_size'];

// Pagination settings
$enable_pagination = isset( $settings['enable_pagination'] ) && $settings['enable_pagination'];
$pagination_type = isset( $settings['pagination_type'] ) ? $settings['pagination_type'] : 'numbers';

// Title Tag
$title_html_tag = woolentor_validate_html_tag( $settings['title_tag'] );

// Layout and card Class Calculate
if ( $layout_mode === 'grid' ) {
    $layout_mode_class = 'grid';
    $card_classes = array( 'woolentor-product-card', 'woolentor-editorial-grid-card' );
} elseif($layout_mode === 'list') {
    $layout_mode_class = 'list';
    $card_classes = array( 'woolentor-product-card', 'woolentor-editorial-list-card' );
} else {
    $layout_mode_class = $settings['default_view_mode'] === 'grid' ? 'grid' : 'list';
    $card_classes = $settings['default_view_mode'] === 'grid' ? array( 'woolentor-product-card', 'woolentor-editorial-grid-card' ) : array( 'woolentor-product-card', 'woolentor-editorial-list-card' );
}

// Aspect ratio class
$grid_aspect_ratio_class = 'grid-aspect-' . $grid_image_aspect_ratio;
$list_aspect_ratio_class = 'list-aspect-' . $list_image_aspect_ratio;
?>

<?php if(!$only_items): ?>
<div class="woolentor-product-grid-editorial woolentor-layout-<?php echo esc_attr( $layout_mode_class ); ?> <?php echo esc_attr( $grid_classes ); ?> <?php echo esc_attr( $grid_aspect_ratio_class ); ?> <?php echo esc_attr( $list_aspect_ratio_class ); ?>" id="<?php echo esc_attr( $grid_id ); ?>">
<?php endif; ?>

    <?php while ( $products->have_posts() ) : $products->the_post(); ?>
        <?php
        global $product;

        // Skip if not a valid product
        if ( ! is_a( $product, 'WC_Product' ) ) {
            continue;
        }

        // Product data
        $product_id = $product->get_id();
        $product_title = $product->get_name();
        $product_permalink = $product->get_permalink();
        $product_price = $product->get_price_html();
        $product_categories = get_the_terms( $product_id, 'product_cat' );
        $is_on_sale = $product->is_on_sale();
        $is_in_stock = $product->is_in_stock();
        $product_gallery_image_ids = $product->get_gallery_image_ids() ? $product->get_gallery_image_ids() : array();

        // Calculate discount percentage if on sale
        $discount_percentage = '';
        if ( $is_on_sale && $product->get_regular_price() ) {
            $regular_price = (float) $product->get_regular_price();
            $sale_price = (float) $product->get_sale_price();
            if ( $regular_price > 0 ) {
                $discount = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
                $discount_percentage = '-' . $discount . '%';
            }
        }

        // Check if product is new
        $is_new = false;
        if ( $show_new_badge ) {
            $product_date = get_the_date( 'U', $product_id );
            $days_since_published = ( time() - $product_date ) / ( 60 * 60 * 24 );
            $is_new = $days_since_published <= absint( $settings['new_badge_days'] );
        }

        // Check if product is trending (featured)
        $is_trending = $show_trending_badge && $product->is_featured();

        // Product description
        $product_grid_description = '';
        $product_list_description = '';
        if ( $show_grid_description || $show_list_description ) {
            $description = $product->get_short_description() ?: $product->get_description();
            if ( $description ) {
                $product_content = strip_tags( $description );
                $count_length = strlen($product_content);
                $product_grid_description = $grid_description_length === 0 ? wp_trim_words( $product_content, $count_length ) : wp_trim_words( $product_content, $grid_description_length );
                $product_list_description = $list_description_length === 0 ? wp_trim_words( $product_content, $count_length ) : wp_trim_words( $product_content, $list_description_length );
            }
        }

        // Card status classes
        $status_classes = $card_classes;
        if ( ! $is_in_stock ) {
            $status_classes[] = 'woolentor-out-of-stock';
        }
        if ( $is_on_sale ) {
            $status_classes[] = 'woolentor-on-sale';
        }
        ?>

        <div class="product woolentor-product-item" data-product-id="<?php echo esc_attr( $product_id ); ?>">
            <div class="<?php echo esc_attr( implode( ' ', $status_classes ) ); ?>">

                <!-- GRID VIEW CONTENT -->
                <div class="woolentor-grid-view-content">

                    <?php if ( $show_image ) : ?>
                        <div class="woolentor-product-image">
                            <a href="<?php echo esc_url( $product_permalink ); ?>" title="<?php echo esc_attr( $product_title ); ?>">
                                <?php
                                    echo $product->get_image( $image_size, array(
                                        'class' => 'woolentor-product-img',
                                        'alt' => $product->get_slug(),
                                        'loading' => 'lazy'
                                    ) );
                                ?>
                            </a>
                            <?php
                                if( $show_secondary_image ){
                                    woolentor_product_secondary_image($product_gallery_image_ids, $image_size);
                                }
                            ?>

                            <!-- Product Overlay -->
                            <div class="woolentor-product-overlay">
                                <?php if ( $show_view_details ) : ?>
                                    <a class="woolentor-view-detail" href="<?php echo esc_url( $product_permalink ); ?>" title="<?php echo esc_attr( $product_title ); ?>">
                                        <?php echo esc_html( $view_details_text ); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ( $show_quick_actions ) : ?>
                                    <div class="woolentor-quick-actions">
                                        <?php
                                            // Add to Cart Button
                                            if ( $show_add_to_cart ) {
                                                $cart_icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>';

                                                $button_classes = array(
                                                    'woolentor-quick-action',
                                                    'woolentor-cart-action',
                                                    'button',
                                                    'product_type_' . $product->get_type(),
                                                    $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
                                                    $product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
                                                );

                                                $button_url = $product->add_to_cart_url();

                                                printf(
                                                    '<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="%s" data-tooltip="%s" rel="nofollow">%s</a>',
                                                    esc_url( $button_url ),
                                                    esc_attr( $product_id ),
                                                    esc_attr( $product->get_sku() ),
                                                    esc_attr( implode( ' ', array_filter( $button_classes ) ) ),
                                                    esc_attr__( 'Add to Cart', 'woolentor' ),
                                                    $cart_icon
                                                );
                                            }
                                        ?>

                                        <?php
                                            // Wishlist button using WooLentor's global function
                                            if ( function_exists( 'woolentor_add_to_wishlist_button' ) && $show_wishlist ) {
                                                // Define custom icons for the wishlist button
                                                $normal_icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>';
                                                $added_icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>';

                                                // Output the wishlist button with custom classes wrapped
                                                echo '<div class="woolentor-quick-action woolentor-wishlist-action" data-tooltip="' . esc_attr__( 'Add to Wishlist', 'woolentor' ) . '">';
                                                    echo woolentor_add_to_wishlist_button( $normal_icon, $added_icon, 'yes' );
                                                echo '</div>';
                                            }
                                        ?>

                                        <?php if( $show_quick_view ): ?>
                                            <button class="woolentor-quick-action woolentor-quickview-btn woolentorquickview" data-tooltip="<?php echo esc_attr__( 'Quick View', 'woolentor' );?>" data-product_id="<?php the_ID();?>" title="<?php echo esc_attr__( 'Quick View', 'woolentor' ); ?>" <?php echo wc_implode_html_attributes( ['aria-label'=>$product->get_title()] ); ?>>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>

                                        <?php
                                            if( $show_compare && (function_exists('woolentor_compare_button') && true === woolentor_exist_compare_plugin()) ){
                                                echo '<div class="woolentor-quick-action woolentor-compare-action" data-tooltip="' . esc_attr__( 'Add to Compare', 'woolentor' ) . '">';
                                                woolentor_compare_button(
                                                    array(
                                                        'style'=>2,
                                                        'btn_text'=>'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
                                                        'btn_added_txt'=>'<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'
                                                    )
                                                );
                                                echo '</div>';
                                            }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php
                            if( $show_badges ){
                                // Check if Product Badges module is enabled and has badges
                                $show_template_badges = true;
                                $module_badges_html = '';

                                if ( defined( 'Woolentor\Modules\Badges\ENABLED' ) && \Woolentor\Modules\Badges\ENABLED ) {
                                    // Module is enabled, check if it has badges for this product
                                    if ( class_exists( '\Woolentor\Modules\Badges\Frontend\Badge_Manager' ) ) {
                                        $module_badges_html = \Woolentor\Modules\Badges\Frontend\Badge_Manager::instance()->product_badges();
                                        if ( ! empty( $module_badges_html ) ) {
                                            // Display module badges
                                            $show_template_badges = false;
                                        }
                                    }
                                }

                                // Show template badges only if module badges aren't shown
                                if ( $show_template_badges ) : ?>
                                    <!-- Badges -->
                                    <div class="woolentor-badges">
                                        <?php if ( $show_sale_badge && $is_on_sale ) : ?>
                                            <span class="woolentor-badge woolentor-sale-badge">
                                                <?php echo esc_html( $sale_badge_text ); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ( $show_new_badge && $is_new ) : ?>
                                            <span class="woolentor-badge woolentor-new-badge">
                                                <?php echo esc_html( $new_badge_text ); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ( $is_trending ) : ?>
                                            <span class="woolentor-badge woolentor-trending-badge">
                                                <?php echo esc_html( $trending_badge_text ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif;
                            } ?>
                        </div>
                    <?php endif; ?>

                    <div class="woolentor-product-content">
                        <?php if ( $show_categories && $product_categories && ! is_wp_error( $product_categories ) ) : ?>
                            <div class="woolentor-product-categories">
                                <?php
                                $first_category = array_slice( $product_categories, 0, 1 );
                                foreach ( $first_category as $category ) : ?>
                                    <span class="woolentor-product-category">
                                        <?php echo esc_html( $category->name ); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php
                            do_action( 'woolentor_product_addon_before_title' );
                            if ( $show_title ){
                                echo sprintf( "<%s class='woolentor-product-title'><a href='%s' title='%s'>%s</a></%s>", $title_html_tag, esc_url( $product_permalink ), esc_attr( $product_title ), wp_kses_post( $product_title ), $title_html_tag );
                            }
                            do_action( 'woolentor_product_addon_after_title' );
                        ?>

                        <?php if ( $show_grid_description && $product_grid_description ) : ?>
                            <div class="woolentor-product-description">
                                <p><?php echo wp_kses_post( $product_grid_description ); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ( $show_rating && $product->get_average_rating() ) : ?>
                            <div class="woolentor-product-rating">
                                <?php echo '<div class="woolentor-product-stars">'.woolentor_wc_product_rating_generate($product).'</div>'; ?>
                                <span class="woolentor-review-count">(<?php echo esc_html( $product->get_review_count() ); ?>)</span>
                            </div>
                        <?php endif; ?>

                        <?php if ( $show_price ) : do_action( 'woolentor_product_addon_before_price' ); ?>
                            <div class="woolentor-product-price">
                                <?php echo wp_kses_post( $product_price ); ?>
                            </div>
                        <?php do_action( 'woolentor_product_addon_after_price' ); endif; ?>

                        <?php if ( $show_grid_stock_indicator ) : ?>
                            <div class="woolentor-stock-status <?php echo $is_in_stock ? 'in-stock' : 'out-of-stock'; ?>">
                                <span class="stock-dot"></span>
                                <span class="stock-text">
                                    <?php echo $is_in_stock ? esc_html( $stock_text_in_stock ) : esc_html( $stock_text_out_of_stock ); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- END GRID VIEW CONTENT -->

                <!-- LIST VIEW CONTENT -->
                <div class="woolentor-list-view-content">

                    <?php if ( $show_image ) : ?>
                        <div class="woolentor-product-image">
                            <a href="<?php echo esc_url( $product_permalink ); ?>" title="<?php echo esc_attr( $product_title ); ?>">
                                <?php
                                    echo $product->get_image( $image_size, array(
                                        'class' => 'woolentor-product-img',
                                        'alt' => $product->get_slug(),
                                        'loading' => 'lazy'
                                    ) );
                                ?>
                            </a>
                            <?php
                                if( $show_secondary_image ){
                                    woolentor_product_secondary_image($product_gallery_image_ids, $image_size);
                                }
                            ?>

                            <!-- List View Product Overlay -->
                            <div class="woolentor-product-overlay woolentor-list-overlay">
                                <?php if ( $show_quick_actions ) : ?>
                                    <div class="woolentor-quick-actions">
                                        <?php
                                            // Wishlist button using WooLentor's global function
                                            if ( function_exists( 'woolentor_add_to_wishlist_button' ) && $show_wishlist ) {
                                                // Define custom icons for the wishlist button
                                                $normal_icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>';
                                                $added_icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>';

                                                // Output the wishlist button with custom classes wrapped
                                                echo '<div class="woolentor-quick-action woolentor-wishlist-action" data-tooltip="' . esc_attr__( 'Add to Wishlist', 'woolentor' ) . '">';
                                                    echo woolentor_add_to_wishlist_button( $normal_icon, $added_icon, 'yes' );
                                                echo '</div>';
                                            }
                                        ?>

                                        <?php
                                            if( $show_compare && (function_exists('woolentor_compare_button') && true === woolentor_exist_compare_plugin()) ){
                                                echo '<div class="woolentor-quick-action woolentor-compare-action" data-tooltip="' . esc_attr__( 'Add to Compare', 'woolentor' ) . '">';
                                                woolentor_compare_button(
                                                    array(
                                                        'style'=>2,
                                                        'btn_text'=>'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
                                                        'btn_added_txt'=>'<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'
                                                    )
                                                );
                                                echo '</div>';
                                            }
                                        ?>

                                        <?php if( $show_quick_view ): ?>
                                            <button class="woolentor-quick-action woolentorquickview" data-product_id="<?php the_ID();?>" data-tooltip="<?php echo esc_attr__( 'Quick View', 'woolentor' ); ?>" <?php echo wc_implode_html_attributes( ['aria-label'=>$product->get_title()] ); ?>>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php
                            if( $show_badges ){
                                // Check if Product Badges module is enabled and has badges (List View)
                                $show_template_badges_list = true;
                                $module_badges_html_list = '';

                                if ( defined( 'Woolentor\Modules\Badges\ENABLED' ) && \Woolentor\Modules\Badges\ENABLED ) {
                                    // Module is enabled, check if it has badges for this product
                                    if ( class_exists( '\Woolentor\Modules\Badges\Frontend\Badge_Manager' ) ) {
                                        $module_badges_html_list = \Woolentor\Modules\Badges\Frontend\Badge_Manager::instance()->product_badges();
                                        if ( ! empty( $module_badges_html_list ) ) {
                                            $show_template_badges_list = false;
                                        }
                                    }
                                }

                                // Show template badges only if module badges aren't shown
                                if ( $show_template_badges_list ) : ?>
                                    <!-- Badges -->
                                    <div class="woolentor-badges">
                                        <?php if ( $show_sale_badge && $is_on_sale ) : ?>
                                            <span class="woolentor-badge woolentor-sale-badge">
                                                <?php echo esc_html( $sale_badge_text ); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ( $show_new_badge && $is_new ) : ?>
                                            <span class="woolentor-badge woolentor-new-badge">
                                                <?php echo esc_html( $new_badge_text ); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ( $is_trending ) : ?>
                                            <span class="woolentor-badge woolentor-trending-badge">
                                                <?php echo esc_html( $trending_badge_text ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif;
                            } ?>
                        </div>
                    <?php endif; ?>

                    <div class="woolentor-product-content">
                        <div class="woolentor-content-header">
                            <?php if ( $show_categories && $product_categories && ! is_wp_error( $product_categories ) ) : ?>
                                <div class="woolentor-product-categories">
                                    <?php
                                    $first_category = array_slice( $product_categories, 0, 1 );
                                    foreach ( $first_category as $category ) : ?>
                                        <span class="woolentor-product-category">
                                            <?php echo esc_html( $category->name ); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                                do_action( 'woolentor_product_addon_before_title' );
                                if ( $show_title ){
                                    echo sprintf( "<%s class='woolentor-product-title'><a href='%s' title='%s'>%s</a></%s>", $title_html_tag, esc_url( $product_permalink ), esc_attr( $product_title ), wp_kses_post( $product_title ), $title_html_tag );
                                }
                                do_action( 'woolentor_product_addon_after_title' );
                            ?>
                        </div>

                        <?php if ( $show_list_description && $product_list_description ) : ?>
                            <div class="woolentor-product-description">
                                <p><?php echo wp_kses_post( $product_list_description ); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ( $show_rating && $product->get_average_rating() ) : ?>
                            <div class="woolentor-product-rating">
                                <?php echo '<div class="woolentor-product-stars">'.woolentor_wc_product_rating_generate($product).'</div>'; ?>
                                <span class="woolentor-review-count">(<?php echo esc_html( $product->get_review_count() ); ?> reviews)</span>
                            </div>
                        <?php endif; ?>

                        <div class="woolentor-content-footer">
                            <?php if ( $show_price ) : do_action( 'woolentor_product_addon_before_price' ); ?>
                                <div class="woolentor-product-price">
                                    <?php echo wp_kses_post( $product_price ); ?>
                                    <?php if ( $discount_percentage ) : ?>
                                        <span class="woolentor-discount-percentage"><?php echo esc_html( $discount_percentage ); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php do_action( 'woolentor_product_addon_after_price' ); endif; ?>

                            <?php if ( $show_list_stock_indicator ) : ?>
                                <div class="woolentor-stock-status <?php echo $is_in_stock ? 'in-stock' : 'out-of-stock'; ?>">
                                    <span class="stock-dot"></span>
                                    <span class="stock-text">
                                        <?php echo $is_in_stock ? esc_html( $stock_text_in_stock ) : esc_html( $stock_text_out_of_stock ); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if ( $show_add_to_cart ) : ?>
                                <div class="woolentor-product-actions">
                                    <?php
                                        $button_classes = array(
                                            'woolentor-cart-btn',
                                            'button',
                                            'product_type_' . $product->get_type(),
                                            $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
                                            $product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
                                        );

                                        $button_text = $product->add_to_cart_text();
                                        $button_url = $product->add_to_cart_url();

                                        printf(
                                            '<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="%s" title="%s" rel="nofollow">%s</a>',
                                            esc_url( $button_url ),
                                            esc_attr( $product_id ),
                                            esc_attr( $product->get_sku() ),
                                            esc_attr( implode( ' ', array_filter( $button_classes ) ) ),
                                            esc_attr( $button_text ),
                                            esc_html( $button_text )
                                        );
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- END LIST VIEW CONTENT -->

            </div>
        </div>

    <?php endwhile; ?>

<?php if(!$only_items){ echo '</div>'; }
// Reset global post data
wp_reset_postdata();
?>
