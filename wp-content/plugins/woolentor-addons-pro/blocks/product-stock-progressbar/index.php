<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
$areaClasses = array( $uniqClass );

!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';

$id = $settings['blockUniqId'];

if( $block['is_editor'] ){
	$product_id = woolentor_get_last_product_id();
} else{
	$product_id = get_the_ID();
}

$order_text     = isset( $settings['orderCustomText'] ) ? $settings['orderCustomText'] : 'Ordered:';
$available_text = isset( $settings['availableCustomText'] ) ? $settings['availableCustomText'] : 'Items available:';


echo '<div class="'.esc_attr(implode(' ', $areaClasses )).'">';
	if ( get_post_meta( $product_id, '_manage_stock', true ) == 'yes' ) {

		$total_stock = get_post_meta( $product_id, 'woolentor_total_stock_quantity', true );

		if ( ! $total_stock ) { echo '<div class="stock-management-progressbar">'.__( 'Set the initial stock amount from', 'woolentor-pro' ).' <a href="'.get_edit_post_link( $product_id ).'" target="_blank">'.__( 'here', 'woolentor-pro' ).'</a></div>'; return; }

		$current_stock = round( get_post_meta( $product_id, '_stock', true ) );

		$total_sold = $total_stock > $current_stock ? $total_stock - $current_stock : 0;
		$percentage = $total_sold > 0 ? round( $total_sold / $total_stock * 100 ) : 0;

		if ( $current_stock >= 0 ) {
			echo '<div class="woolentor-stock-progress-bar">';
				echo '<div class="wlstock-info">';
					echo $settings['showOrderCounter'] ? '<div class="wltotal-sold">' . esc_html__( $order_text, 'woolentor-pro' ) . '<span>' . esc_html( $total_sold ) . '</span></div>' : '';
					echo $settings['showAvailableCounter'] ? '<div class="wlcurrent-stock">' . esc_html__( $available_text, 'woolentor-pro' ) . '<span>' . esc_html( $current_stock ) . '</span></div>' : '';
				echo '</div>';
				echo '<div class="wlprogress-area" title="' . esc_html__( 'Sold', 'woolentor-pro' ) . ' ' . esc_attr( $percentage ) . '%">';
					echo '<div class="wlprogress-bar"style="width:' . esc_attr( $percentage ) . '%;"></div>';
				echo '</div>';
			echo '</div>';
		}

	}else{
		if( $block['is_editor'] ){
			echo esc_html__('Stock management is not enabled for this product.','woolentor-pro');
		}
	}
echo '</div>';