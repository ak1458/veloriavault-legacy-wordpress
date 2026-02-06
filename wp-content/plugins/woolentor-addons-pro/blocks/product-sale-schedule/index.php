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

// Countdown Custom Label
$data_customlavel = [];
$data_customlavel['daytxt'] = ! empty( $settings['customLabelDays'] ) ? esc_html($settings['customLabelDays']) : esc_html__('Days','woolentor');
$data_customlavel['hourtxt'] = ! empty( $settings['customLabelHours'] ) ? esc_html($settings['customLabelHours']) : esc_html__('Hours','woolentor');
$data_customlavel['minutestxt'] = ! empty( $settings['customLabelMinutes'] ) ? esc_html($settings['customLabelMinutes']) : esc_html__('Min','woolentor');
$data_customlavel['secondstxt'] = ! empty( $settings['customLabelSeconds'] ) ? esc_html($settings['customLabelSeconds']) : esc_html__('Sec','woolentor');


echo '<div class="'.esc_attr(implode(' ', $areaClasses )).'">';

	$offer_start_date_timestamp = get_post_meta( $product_id, '_sale_price_dates_from', true );
	$offer_end_date_timestamp = get_post_meta( $product_id, '_sale_price_dates_to', true );
	$offer_end_date = $offer_end_date_timestamp ? date_i18n( 'Y/m/d', $offer_end_date_timestamp ) : '';

	if ( $offer_end_date == '' && $block['is_editor']) {
		echo '<div class="ht-single-product-countdown">'.__( 'Do not set sale schedule time', 'woolentor-pro' ).'</div>';
	}else{
		if( $offer_end_date != '' ):
			if( $offer_start_date_timestamp && $offer_end_date_timestamp && current_time( 'timestamp' ) > $offer_start_date_timestamp && current_time( 'timestamp' ) < $offer_end_date_timestamp
			): 
		?>
			<div class="ht-single-product-countdown ht-product-countdown-wrap">
				<div class="ht-product-countdown" data-countdown="<?php echo esc_attr( $offer_end_date ); ?>" data-customlavel='<?php echo wp_json_encode( $data_customlavel ) ?>'></div>
			</div>
		<?php endif; endif;
	}

echo '</div>';