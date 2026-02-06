<?php

/* Styles
	=============================================================== */

function nm_child_theme_styles()
{
	// Enqueue child theme styles
	wp_enqueue_style('nm-child-theme', get_stylesheet_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'nm_child_theme_styles', 1000);

/* Hide SKU from product page */
add_filter('wc_product_sku_enabled', '__return_false');

/**
 * Render Swipeable Category Filter HTML (Core Logic)
 */
function veloria_get_swipe_category_filter_html()
{
	// Get all top-level product categories
	$terms = get_terms(array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
	));

	if (empty($terms) || is_wp_error($terms)) {
		return '';
	}

	// Get current object to identify active category
	$current_term_id = 0;
	if (is_product_taxonomy()) {
		$current_term = get_queried_object();
		if ($current_term && isset($current_term->term_id)) {
			$current_term_id = $current_term->term_id;
		}
	}

	ob_start();
?>
	<div class="veloria-swipe-filter-container">
		<ul class="veloria-swipe-filter">
			<?php
			$shop_url = get_permalink(wc_get_page_id('shop'));
			if (is_page('shop-custom')) {
				$shop_url = get_permalink();
			}

			$all_active = ($current_term_id === 0) ? 'active' : '';
			$count_posts = wp_count_posts('product');
			$total_products = $count_posts->publish;
			?>
			<li class="veloria-filter-item">
				<a href="<?php echo esc_url($shop_url); ?>" class="veloria-filter-link <?php echo esc_attr($all_active); ?>">
					<?php esc_html_e('All', 'woocommerce'); ?>
					<span class="count"><?php echo esc_html('(' . $total_products . ')'); ?></span>
				</a>
			</li>
			<?php
			foreach ($terms as $term) {
				$active = ($current_term_id === $term->term_id) ? 'active' : '';
			?>
				<li class="veloria-filter-item">
					<a href="<?php echo esc_url(get_term_link($term)); ?>" class="veloria-filter-link <?php echo esc_attr($active); ?>">
						<?php echo esc_html($term->name); ?>
						<span class="count"><?php echo esc_html('(' . $term->count . ')'); ?></span>
					</a>
				</li>
			<?php
			}
			?>
		</ul>
	</div>
<?php
	return ob_get_clean();
}

add_shortcode('veloria_category_filter', 'veloria_get_swipe_category_filter_html');

add_action('woocommerce_before_shop_loop', 'veloria_render_auto_filter', 25);
function veloria_render_auto_filter()
{
	if (is_shop() || is_product_taxonomy() || is_page('shop-custom')) {
		echo veloria_get_swipe_category_filter_html();
	}
}
