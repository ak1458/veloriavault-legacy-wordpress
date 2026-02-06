<?php
/**
 * Product Grid Luxury Block - Server-side Rendering
 * Uses same query system as Elementor widget for consistency
 *
 * @package WooLentorBlocks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure WooCommerce is active
if ( ! function_exists( 'WC' ) ) {
	echo '<p>' . esc_html__( 'WooCommerce is required for this block.', 'woolentor' ) . '</p>';
	return;
}

// Load Product Grid Base if not already loaded
if ( ! class_exists( 'WooLentor_Product_Grid_Base' ) ) {
	require_once WOOLENTOR_ADDONS_PL_PATH . 'includes/addons/product-grid/base/class.product-grid-base.php';
}

// Get Product Grid Base instance
$product_grid_base = WooLentor_Product_Grid_Base::instance();

// Block unique class
$uniqClass = 'woolentorblock-' . $settings['blockUniqId'];
$areaClasses = array( $uniqClass );

// Add custom className if provided
if ( ! empty( $settings['className'] ) ) {
	$areaClasses[] = esc_attr( $settings['className'] );
}

// Add responsive column classes
$columns = isset( $settings['columns'] ) ? $settings['columns'] : array();
if ( ! empty( $columns['desktop'] ) ) {
	$areaClasses[] = 'woolentor-columns-' . $columns['desktop'];
}
if ( ! empty( $columns['tablet'] ) ) {
	$areaClasses[] = 'woolentor-columns-tablet-' . $columns['tablet'];
}
if ( ! empty( $columns['mobile'] ) ) {
	$areaClasses[] = 'woolentor-columns-mobile-' . $columns['mobile'];
}

if( !empty($settings['badge_style'])){
	$areaClasses[] = 'woolentor-badge-style-' . $settings['badge_style'];
}
if( !empty($settings['badge_position'])){
	$areaClasses[] = 'woolentor-badge-pos-' . $settings['badge_position'];
}
if( !empty($settings['card_hover_effect'])){
	$areaClasses[] = 'woolentor-card-hover-' . $settings['card_hover_effect'];
}
if( !empty($settings['image_hover_effect'])){
	$areaClasses[] = 'woolentor-image-hover-' . $settings['image_hover_effect'];
}
if( !empty($settings['image_aspect_ratio'])){
	$areaClasses[] = 'woolentor-image-ratio-' . $settings['image_aspect_ratio'];
}
if( !empty($settings['content_align'])){
	$areaClasses[] = 'woolentor-content-align-' . $settings['content_align'];
}

// Prepare grid settings following Elementor widget pattern
$grid_settings = [
	// Core settings
	'style'                 => 'luxury',
	'widget_name'           => 'woolentor-product-grid-luxury',

	// Layout settings
	'layout'                => 'grid', // Luxury only has grid layout
	'columns'               => ! empty( $columns['desktop'] ) ? absint( $columns['desktop'] ) : 3,

	// Grid ID for AJAX
	'grid_id'               => $uniqClass,
	'blockUniqId'           => $settings['blockUniqId'],
];

$merge_settings = wp_parse_args( $grid_settings, $settings );

// Apply filter for customization (same as Elementor widget)
$grid_settings = apply_filters( 'woolentor_product_grid_luxury_block_settings', $merge_settings, $settings );

// Start output buffering
ob_start();
	echo '<div class="' . esc_attr( implode( ' ', $areaClasses ) ) . '">';
		$product_grid_base->render( $grid_settings );
	echo '</div>';
// Output the buffered content
echo ob_get_clean();
