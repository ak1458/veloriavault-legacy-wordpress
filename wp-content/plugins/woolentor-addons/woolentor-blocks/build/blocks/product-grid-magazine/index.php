<?php
/**
 * Product Grid Magazine Block - Server-side Rendering
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

// Add badge style and position classes
if( !empty($settings['badge_style'])){
	$areaClasses[] = 'woolentor-badge-style-' . $settings['badge_style'];
}
if( !empty($settings['badge_position'])){
	$areaClasses[] = 'woolentor-badge-pos-' . $settings['badge_position'];
}

// Add layout class
if( !empty($settings['layout'])){
	$areaClasses[] = 'woolentor-layout-' . $settings['layout'];
}

// Add switcher tab style class
if( !empty($settings['switcher_tab_style'])){
	$areaClasses[] = 'woolentor-switcher-style-' . $settings['switcher_tab_style'];
}
if( !empty($settings['content_align'])){
	$areaClasses[] = 'woolentor-content-align-' . $settings['content_align'];
}

// Prepare grid settings following Elementor widget pattern
$grid_settings = [
	// Core settings
	'style'                 => 'magazine',
	'widget_name'           => 'woolentor-product-grid-magazine',

	// Layout settings
	'layout'                => ! empty( $settings['layout'] ) ? $settings['layout'] : 'grid',

	// Grid ID for AJAX
	'grid_id'               => $uniqClass,
	'blockUniqId'           => $settings['blockUniqId'],
];

$merge_settings = wp_parse_args( $grid_settings, $settings );

// Apply filter for customization (same as Elementor widget)
$grid_settings = apply_filters( 'woolentor_product_grid_magazine_block_settings', $merge_settings, $settings );

// Start output buffering
ob_start();
	echo '<div class="' . esc_attr( implode( ' ', $areaClasses ) ) . '">';
		$product_grid_base->render( $grid_settings );
	echo '</div>';
// Output the buffered content
echo ob_get_clean();
