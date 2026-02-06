<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
$areaClasses = array( $uniqClass );

!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';

global $product, $post;
if ( empty( $product ) ) { return; }
if ( $product && !is_a( $product, 'WC_Product' ) ) {
	$product = wc_get_product( $post->ID );
}

$next_icon = !empty( $settings['nextIcon'] ) ? '<i class="'.$settings['nextIcon'].'"></i>' : '<i class="fa fa-long-arrow-right"></i>';
$prev_icon = !empty( $settings['previousIcon'] ) ? '<i class="'.$settings['previousIcon'].'"></i>' : '<i class="fa fa-long-arrow-left"></i>';

echo '<div class="'.esc_attr(implode(' ', $areaClasses )).'">';

	if('yes' === $settings['inSameCategory']){
		$previous = get_adjacent_post( true, '', true, 'product_cat' );
		$next     = get_adjacent_post( true, '', false, 'product_cat' );
	}else{
		$previous = get_adjacent_post( false, '', true );
		$next     = get_adjacent_post( false, '', false );
	}

	if ( $previous) {
		$previous_post = wc_get_product( $previous->ID );
		if ( $previous_post && $previous_post->is_visible() ) {
			$previous_product = $previous_post->get_permalink();
		}else{
			$previous_product = woolentor_get_previous_next_product(true);
		}
	}else{
		$previous_product = '';
	}

	if($next){
		$next_post = wc_get_product( $next->ID );
		if ( $next_post && $next_post->is_visible() ) {
			$next_product = $next_post->get_permalink();
		}else{
			$next_product = woolentor_get_previous_next_product();
		}
	}else{
		$next_product = '';
	}

	?>
		<div class="wl-single-product-navigation">
			<?php if($next_product): ?>
				<a href="<?php echo esc_url( $next_product ); ?>"><?php echo $prev_icon; ?></a>
			<?php endif; ?>
			<?php if($previous_product): ?>
				<a href="<?php echo esc_url( $previous_product ); ?>"><?php echo $next_icon; ?></a>
			<?php endif; ?>
		</div>
	<?php
	
echo '</div>';