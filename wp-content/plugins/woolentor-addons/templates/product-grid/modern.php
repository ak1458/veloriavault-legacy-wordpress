<?php
/**
 * Product Grid Template - Modern Grid & List Style
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
    'show_image' => 'yes',
    'show_title' => 'yes',
    'show_price' => 'yes',
    'show_rating' => true,
    'show_add_to_cart' => true,
    'show_sale_badge' => true,
    'show_new_badge' => 'yes',
    'new_badge_days' => 7,
    'show_trending_badge' => 'no',
    'badge_style' => 'gradient',
    'badge_position' => 'top-left',
    'show_quick_actions' => 'yes',
    'show_description' => 'yes',
    'description_length' => 120,
    'show_product_features' => 'yes',
    'show_stock_status' => 'yes',
    'show_quantity_selector' => 'yes',
    'image_size' => 'woocommerce_thumbnail',
    'content_length' => 15,
    'enable_ajax' => 'no',
    'ajax_button_text' => '',
] );

// Prepare grid classes
$grid_classes = isset( $settings['grid_classes'] ) ? $settings['grid_classes'] : '';
$grid_id = isset( $settings['grid_id'] ) ? $settings['grid_id'] : uniqid( 'woolentor-grid-' );

// Add column class if not already included
if ( strpos( $grid_classes, 'woolentor-columns-' ) === false ) {
    $grid_classes .= ' woolentor-columns-' . $settings['columns'];
}

// Get layout mode and settings (boolean settings should be properly converted by the widget class)
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
$show_product_features = $settings['show_product_features'];
$show_stock_status = $settings['show_stock_status'];
$show_quantity_selector = $settings['show_quantity_selector'];
$show_secondary_image = $settings['show_secondary_image'];
$show_grid_description = $settings['show_grid_description'];
$show_list_description = $settings['show_list_description'];
$grid_description_length = absint($settings['grid_description_length']);
$list_description_length = absint($settings['list_description_length']);
$show_quick_view = $settings['show_quick_view'];
$show_badges = $settings['show_badges'];
$show_wishlist = ($settings['show_wishlist'] && true === woolentor_has_wishlist_plugin());
$show_compare = ($settings['show_compare'] && true === woolentor_exist_compare_plugin());

// Image size
$image_size = $settings['image_size'];

// Content length
$content_length = absint( $settings['content_length'] );

// Pagination settings
$enable_pagination = isset( $settings['enable_pagination'] ) && $settings['enable_pagination'];
$pagination_type = isset( $settings['pagination_type'] ) ? $settings['pagination_type'] : 'numbers';

// AJAX settings (for load more and infinite scroll)
$enable_ajax = $enable_pagination && in_array( $pagination_type, ['load_more', 'infinite'] );
$ajax_button_text = ! empty( $settings['ajax_button_text'] ) ? $settings['ajax_button_text'] : esc_html__( 'Load More', 'woolentor' );

// Title Tag
$title_html_tag = woolentor_validate_html_tag( $settings['title_tag'] );

// Layout and card Class Calulate
if ( $layout_mode === 'grid' ) {
    $layout_mode_clase = 'grid';
    $card_classes = array( 'woolentor-product-card', 'woolentor-grid-card' );
} elseif($layout_mode === 'list') {
    $layout_mode_clase = 'list';
    $card_classes = array( 'woolentor-product-card', 'woolentor-list-card' );
}else{
    $layout_mode_clase = $settings['default_view_mode'] === 'grid' ?  'grid' : 'list';
    $card_classes = $settings['default_view_mode'] === 'grid' ?  array( 'woolentor-product-card', 'woolentor-grid-card' ) : array( 'woolentor-product-card', 'woolentor-list-card' );
}

?>

<?php if(!$only_items): ?>
<div class="woolentor-product-grid-modern woolentor-layout-<?php echo esc_attr( $layout_mode_clase ); ?> <?php echo esc_attr( $grid_classes ); ?>" id="<?php echo esc_attr( $grid_id ); ?>">
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

        // Product attributes for features
        $product_features = array();
        if ( $show_product_features && in_array( $layout_mode, ['list', 'grid_list_tab'] ) ) {
            $attributes = $product->get_attributes();
            foreach ( $attributes as $attribute ) {
                if ( $attribute->get_visible() ) {
                    $product_features[] = wc_attribute_label( $attribute->get_name() );
                    if ( count( $product_features ) >= 3 ) break;
                }
            }
        }

        // Card classes based on layout
        if ( ! $is_in_stock ) {
            $card_classes['woolentor-out-of-stock'] = 'woolentor-out-of-stock';
        }else{
            $card_classes['woolentor-out-of-stock'] = '';
        }
        if ( $is_on_sale ) {
            $card_classes['woolentor-on-sale'] = 'woolentor-on-sale';
        }else{
            $card_classes['woolentor-on-sale'] = '';
        }

        ?>

        <div class="product woolentor-product-item" data-product-id="<?php echo esc_attr( $product_id ); ?>">
            <div class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>">

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
                                <?php endif; } ?>

                            <?php if ( $show_quick_actions ) : ?>
                                <div class="woolentor-quick-actions">
                                    <?php
                                        // Wishlist button using WooLentor's global function
                                        if ( function_exists( 'woolentor_add_to_wishlist_button' ) && $show_wishlist ) {
                                            // Define custom icons for the wishlist button
                                            $normal_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                            $added_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';

                                            // Output the wishlist button with custom classes wrapped
                                            echo '<div class="woolentor-quick-action woolentor-wishlist-btn">';
                                                echo woolentor_add_to_wishlist_button( $normal_icon, $added_icon, 'yes' );
                                            echo '</div>';
                                        }
                                    ?>

                                    <?php if( $show_quick_view ): ?>
                                        <button class="woolentor-quick-action woolentor-quickview-btn woolentorquickview" data-product_id="<?php the_ID();?>" title="<?php echo esc_attr__( 'Quick View', 'woolentor' ); ?>" <?php echo wc_implode_html_attributes( ['aria-label'=>$product->get_title()] ); ?>>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>

                                    <?php 
                                        if( $show_compare && (function_exists('woolentor_compare_button') && true === woolentor_exist_compare_plugin()) ){
                                            echo '<div class="woolentor-quick-action">';
                                            woolentor_compare_button(
                                                array(
                                                    'style'=>2,
                                                    'btn_text'=>'<i class="sli sli-refresh"></i>',
                                                    'btn_added_txt'=>'<i class="sli sli-check"></i>'
                                                )
                                            );
                                            echo '</div>';
                                        }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="woolentor-product-content">
                        <?php if ( $show_categories && $product_categories && ! is_wp_error( $product_categories ) ) : ?>
                            <div class="woolentor-product-categories">
                                <?php foreach ( array_slice( $product_categories, 0, 2 ) as $category ) : ?>
                                    <a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="woolentor-product-category">
                                        <?php echo esc_html( $category->name ); ?>
                                    </a>
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
                                <?php if ( $discount_percentage ) : ?>
                                    <span class="woolentor-discount-percentage"><?php echo esc_html( $discount_percentage ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php do_action( 'woolentor_product_addon_after_price' ); endif; ?>

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
                                        '<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="%s" title="%s" rel="nofollow"><svg class="cart-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>%s</a>',
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
                                            // Module badges are automatically added to the product image via filter
                                            // Don't echo here to avoid duplicates - badges are already attached to image
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
                                <?php endif; } ?>

                            <?php if ( $show_quick_actions ) : ?>
                                <div class="woolentor-quick-actions">
                                    <?php
                                        // Wishlist button using WooLentor's global function
                                        if ( function_exists( 'woolentor_add_to_wishlist_button' ) && $show_wishlist ) {
                                            // Define custom icons for the wishlist button
                                            $normal_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                            $added_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';

                                            // Output the wishlist button with custom classes wrapped
                                            echo '<div class="woolentor-quick-action woolentor-wishlist-btn">';
                                            echo woolentor_add_to_wishlist_button( $normal_icon, $added_icon, 'yes' );
                                            echo '</div>';
                                        }
                                    ?>

                                    <?php if( $show_quick_view ): ?>
                                        <button class="woolentor-quick-action woolentor-quickview-btn woolentorquickview" data-product_id="<?php the_ID();?>" title="<?php echo esc_attr__( 'Quick View', 'woolentor' ); ?>" <?php echo wc_implode_html_attributes( ['aria-label'=>$product->get_title()] ); ?>>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>

                                    <?php 
                                        if( $show_compare && (function_exists('woolentor_compare_button') && true === woolentor_exist_compare_plugin()) ){
                                            echo '<div class="woolentor-quick-action">';
                                            woolentor_compare_button(
                                                array(
                                                    'style'=>2,
                                                    'btn_text'=>'<i class="sli sli-refresh"></i>',
                                                    'btn_added_txt'=>'<i class="sli sli-check"></i>'
                                                )
                                            );
                                            echo '</div>';
                                        }
                                    ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endif; ?>

                    <div class="woolentor-product-content">
                        <div class="woolentor-content-header">
                            <?php if ( $show_categories && $product_categories && ! is_wp_error( $product_categories ) ) : ?>
                                <div class="woolentor-product-categories">
                                    <?php foreach ( array_slice( $product_categories, 0, 3 ) as $category ) : ?>
                                        <a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="woolentor-product-category">
                                            <?php echo esc_html( $category->name ); ?>
                                        </a>
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

                        <?php if ( $product_features ) : ?>
                            <div class="woolentor-product-features">
                                <?php foreach ( $product_features as $feature ) : ?>
                                    <span class="woolentor-feature">
                                        <?php echo esc_html( $feature ); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $show_rating && $product->get_average_rating() ) : ?>
                            <div class="woolentor-product-rating">
                                <?php echo '<div class="woolentor-product-stars">'.woolentor_wc_product_rating_generate($product).'</div>'; ?>
                                <div class="rating-info">
                                    <span class="rating-number"><?php echo esc_html($product->get_average_rating()); ?></span>
                                    <span class="review-count"><?php echo '('.esc_html($product->get_review_count()).' reviews)';?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="woolentor-content-footer">
                            <div class="woolentor-price-stock">
                                <?php if ( $show_price ) : do_action( 'woolentor_product_addon_before_price' ); ?>
                                    <div class="woolentor-product-price">
                                        <?php echo wp_kses_post( $product_price ); ?>
                                        <?php if ( $discount_percentage ) : ?>
                                            <span class="woolentor-discount-percentage"><?php echo esc_html( $discount_percentage ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php do_action( 'woolentor_product_addon_after_price' ); endif; ?>
                            </div>

                            <?php if ( $show_add_to_cart || $show_quantity_selector ) : ?>
                                <div class="woolentor-product-actions">

                                    <?php if ( $show_stock_status ) : ?>
                                        <div class="woolentor-stock-status">
                                            <?php if ( $is_in_stock ) : ?>
                                                <span class="woolentor-in-stock">
                                                    <span class="woolentor-stock-dot"></span>
                                                    <?php echo esc_html__( 'In Stock', 'woolentor' ); ?>
                                                    <?php
                                                    $stock_qty = $product->get_stock_quantity();
                                                    if ( $stock_qty && $stock_qty <= 5 ) {
                                                        echo ' <span class="woolentor-low-stock">(' . sprintf( esc_html__( 'Only %d left', 'woolentor' ), $stock_qty ) . ')</span>';
                                                    }
                                                    ?>
                                                </span>
                                            <?php else : ?>
                                                <span class="woolentor-out-of-stock">
                                                    <span class="woolentor-stock-dot"></span>
                                                    <?php echo esc_html__( 'Out of Stock', 'woolentor' ); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $show_quantity_selector && $product->is_sold_individually() === false && $is_in_stock ) : ?>
                                        <div class="woolentor-quantity-selector">
                                            <button type="button" class="woolentor-qty-btn woolentor-qty-minus">-</button>
                                            <input type="number" class="woolentor-qty-input" value="1" min="1" />
                                            <button type="button" class="woolentor-qty-btn woolentor-qty-plus">+</button>
                                        </div>
                                    <?php endif; ?>

                                    <?php
                                        if ( $show_add_to_cart ) {
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
                                                '<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="%s" title="%s" rel="nofollow"><svg class="cart-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>%s</a>',
                                                esc_url( $button_url ),
                                                esc_attr( $product_id ),
                                                esc_attr( $product->get_sku() ),
                                                esc_attr( implode( ' ', array_filter( $button_classes ) ) ),
                                                esc_attr( $button_text ),
                                                esc_html( $button_text )
                                            );
                                        }
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