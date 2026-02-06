<?php
/**
 * Product Grid Template - Luxury Editorial Style
 * Premium editorial design with sophisticated layout
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
    'show_new_badge' => 'yes',
    'new_badge_days' => 7,
    'show_trending_badge' => 'no',
    'badge_style' => 'solid',
    'badge_position' => 'top-left',
    'show_quick_actions' => true,
    'show_subtitle' => true,
    'subtitle_length' => 5,
    'show_category_badge' => true,
    'show_discount_offer_badge' => true,
    'show_view_details' => true,
    'view_details_text' => esc_html__( 'View Details', 'woolentor' ),
    'add_to_cart_text' => '',
    'image_aspect_ratio' => '4-5',
    'image_size' => 'woocommerce_thumbnail',
] );

// Prepare grid classes
$grid_classes = isset( $settings['grid_classes'] ) ? $settings['grid_classes'] : '';
$grid_id = isset( $settings['grid_id'] ) ? $settings['grid_id'] : uniqid( 'woolentor-grid-' );

// Add aspect ratio class
$aspect_ratio_class = 'woolentor-ratio-' . $settings['image_aspect_ratio'];

// Add column class if not already included
if ( strpos( $grid_classes, 'woolentor-columns-' ) === false ) {
    $grid_classes .= ' woolentor-columns-' . $settings['columns'];
}

// Get settings
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
$show_subtitle = $settings['show_subtitle'];
$subtitle_length = absint($settings['subtitle_length']);
$show_category_badge = $settings['show_category_badge'];
$show_discount_offer_badge = $settings['show_discount_offer_badge'];
$show_view_details = $settings['show_view_details'];
$view_details_text = $settings['view_details_text'];
$add_to_cart_text = $settings['add_to_cart_text'];
$show_secondary_image = $settings['show_secondary_image'];
$show_badges = $settings['show_badges'];
$show_quick_view = $settings['show_quick_view'];
$show_wishlist = ($settings['show_wishlist'] && true === woolentor_has_wishlist_plugin());
$show_compare = ($settings['show_compare'] && true === woolentor_exist_compare_plugin());

// Image size
$image_size = $settings['image_size'];

// Title Tag
$title_html_tag = woolentor_validate_html_tag( $settings['title_tag'] );

// Pagination settings
$enable_pagination = isset( $settings['enable_pagination'] ) && $settings['enable_pagination'];
$pagination_type = isset( $settings['pagination_type'] ) ? $settings['pagination_type'] : 'numbers';

?>

<?php if(!$only_items): ?>
<div class="woolentor-product-grid-luxury <?php echo esc_attr( $aspect_ratio_class ); ?> <?php echo esc_attr( $grid_classes ); ?>" id="<?php echo esc_attr( $grid_id ); ?>">
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

        // Product subtitle (short description)
        $product_subtitle = '';
        if ( $show_subtitle ) {
            $description = $product->get_short_description() ?: $product->get_description();
            if ( $description ) {
                $product_content = strip_tags( $description );
                $product_subtitle = wp_trim_words( $product_content, $subtitle_length );
            }
        }

        // Card classes
        $card_classes = array( 'woolentor-product-card', 'woolentor-luxury-card' );
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
                                    <?php if ( $show_category_badge && $product_categories && ! is_wp_error( $product_categories ) ) : ?>
                                        <span class="woolentor-badge woolentor-category-badge">
                                            <?php echo esc_html( $product_categories[0]->name ); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ( $show_sale_badge && $is_on_sale ) : ?>
                                        <span class="woolentor-badge woolentor-sale-badge">
                                            <?php echo esc_html( $sale_badge_text ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ( $is_trending ) : ?>
                                        <span class="woolentor-badge woolentor-trending-badge">
                                            <?php echo esc_html( $trending_badge_text ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <!-- Show Only For Mobile Device if set two column Start -->
                                    <?php if ( $show_discount_offer_badge && $is_on_sale && $discount_percentage ) : ?>
                                        <div class="woolentor-sale-indicator show-on-mobile-two-column" style="display:none;">
                                            <?php echo esc_html( $discount_percentage ); ?>
                                        </div>
                                    <?php elseif ( $show_new_badge && $is_new ) : ?>
                                        <div class="woolentor-new-badge-indicator show-on-mobile-two-column" style="display:none;">
                                            <?php echo esc_html( $new_badge_text ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <!-- Show Only For Mobile Device if set two column end -->

                                </div>

                                <?php if ( $show_discount_offer_badge && $is_on_sale && $discount_percentage ) : ?>
                                    <div class="woolentor-sale-indicator">
                                        <?php echo esc_html( $discount_percentage ); ?>
                                    </div>
                                <?php elseif ( $show_new_badge && $is_new ) : ?>
                                    <div class="woolentor-new-badge-indicator">
                                        <?php echo esc_html( $new_badge_text ); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; } ?>

                        <?php if ( $show_quick_actions ) : ?>
                            <div class="woolentor-quick-actions">
                                <?php
                                    // Wishlist button using WooLentor's global function
                                    if ( function_exists( 'woolentor_add_to_wishlist_button' ) && $show_wishlist ) {
                                        // Define custom icons for the wishlist button
                                        $normal_icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                        $added_icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';

                                        // Output the wishlist button with custom classes wrapped
                                        echo '<div class="woolentor-quick-action woolentor-wishlist-btn">';
                                            echo woolentor_add_to_wishlist_button( $normal_icon, $added_icon, 'yes' );
                                        echo '</div>';
                                    }
                                ?>

                                <?php if( $show_quick_view ): ?>
                                    <button class="woolentor-quick-action woolentor-quickview-btn woolentorquickview" data-product_id="<?php the_ID();?>" title="<?php echo esc_attr__( 'Quick View', 'woolentor' ); ?>" <?php echo wc_implode_html_attributes( ['aria-label'=>$product->get_title()] ); ?>>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
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
                        <div class="woolentor-product-category-badge">
                            <?php echo esc_html( $product_categories[0]->name ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                        do_action( 'woolentor_product_addon_before_title' );
                        if ( $show_title ){
                            echo sprintf( "<%s class='woolentor-product-title'><a href='%s' title='%s'>%s</a></%s>", $title_html_tag, esc_url( $product_permalink ), esc_attr( $product_title ), wp_kses_post( $product_title ), $title_html_tag );
                        }
                        do_action( 'woolentor_product_addon_after_title' );
                    ?>

                    <?php if ( $show_subtitle && $product_subtitle ) : ?>
                        <div class="woolentor-product-subtitle">
                            <?php echo wp_kses_post( $product_subtitle ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_rating && $product->get_average_rating() ) : ?>
                        <div class="woolentor-product-rating">
                            <?php echo '<div class="woolentor-product-stars">'.woolentor_wc_product_rating_generate($product).'</div>'; ?>
                            <span class="woolentor-rating-text"><?php echo esc_html( number_format($product->get_average_rating(), 1) ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_price ) : do_action( 'woolentor_product_addon_before_price' ); ?>
                        <div class="woolentor-product-price">
                            <?php echo wp_kses_post( $product_price ); ?>
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

                                $button_url = $product->add_to_cart_url();
                                $button_text = empty( $add_to_cart_text ) ? $product->add_to_cart_text() : $add_to_cart_text;

                                printf(
                                    '<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="%s" title="%s" rel="nofollow">%s<span class="woolentor-cart-arrow">→</span></a>',
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

                    <?php if ( $show_view_details ) : ?>
                        <a href="<?php echo esc_url( $product_permalink ); ?>" class="woolentor-view-details">
                            <?php echo esc_html( $view_details_text ); ?>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    <?php endwhile; ?>

<?php if(!$only_items){ echo '</div>'; }
// Reset global post data
wp_reset_postdata();
?>
