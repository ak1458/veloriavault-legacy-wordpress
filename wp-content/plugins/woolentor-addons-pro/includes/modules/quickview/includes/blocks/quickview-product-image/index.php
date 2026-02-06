<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = $attributes;

$is_editor = ( isset( $_GET['is_editor_mode'] ) && $_GET['is_editor_mode'] == 'yes' ) ? true : false;

$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
$areaClasses = [ $uniqClass, 'woolentor_block_quickview_image' ];
!empty( $settings['align'] ) ? $areaClasses[] = 'align'.$settings['align'] : '';
!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';

if( $is_editor ){
    $product = wc_get_product( woolentor_get_last_product_id() );
} else{
    global $product;
    $product = wc_get_product();
}
if ( empty( $product ) ) { return; }

$post_thumbnail_id = $product->get_image_id();
if( ! $post_thumbnail_id ){
    $post_thumbnail_id = get_option( 'woocommerce_placeholder_image', 0 );
}
$attachment_ids = $product->get_gallery_image_ids();

$image_attr = [
    'thumbnail_layout' => 'slider',
    'product_data' => $product
];

echo '<div class="'.esc_attr( implode(' ', $areaClasses ) ).'">';
    woolentor_get_template( 'quickview-product-images', $image_attr, true, \Woolentor\Modules\QuickView\TEMPLATE_PATH );
echo '</div>';