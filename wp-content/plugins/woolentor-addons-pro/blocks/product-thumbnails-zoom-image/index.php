<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
$areaClasses = array( $uniqClass, 'wlpro-product-videothumbnails', 'woolentor-block-product-image-zoom' );

!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';
!empty( $settings['tabThumbnailsPosition'] ) ? $areaClasses[] = 'thumbnails-tab-position-'.$settings['tabThumbnailsPosition'] : '';

global $post;
if( $block['is_editor'] ){
	$product = wc_get_product(woolentor_get_last_product_id());
} else{
	$product = wc_get_product();
}
if ( empty( $product ) ) { return; }
if ( $product && !is_a( $product, 'WC_Product' ) ) {
	$product = wc_get_product( $post->ID );
}

$gallery_images_ids = $product->get_gallery_image_ids() ? $product->get_gallery_image_ids() : array();
if ( $product->get_image_id() ){
	$gallery_images_ids = array( 'wlthumbnails_id' => $product->get_image_id() ) + $gallery_images_ids;
}

// Placeholder image set
if( empty( $gallery_images_ids ) ){
	$gallery_images_ids = array( 'wlthumbnails_id' => get_option( 'woocommerce_placeholder_image', 0 ) );
}

echo '<div class="'.esc_attr(implode(' ', $areaClasses )).'">';

?>
	<div class="wl-thumbnails-image-area">
		<?php 
			echo '<div class="wl-product-details-images">';
				$i = 0;
				foreach ( $gallery_images_ids as $gallery_attachment_id ) {
					$i++;
					if( $i == 1 ){
						echo '<div class="slider-for__item wl_zoom woolentor_image_change" data-src="'.wp_get_attachment_image_url( $gallery_attachment_id, 'full' ).'"><img src="'.wp_get_attachment_image_url( $gallery_attachment_id, 'woocommerce_single' ).'"></div>';
						if( $block['is_editor'] ){
							break;
						}
					}else{
						echo '<div class="slider-for__item wl_zoom" data-src="'.wp_get_attachment_image_url( $gallery_attachment_id, 'full' ).'"><img src="'.wp_get_attachment_image_url( $gallery_attachment_id, 'woocommerce_single' ).'"></div>';
					}
				}
			echo '</div><div class="wl-product-details-thumbs">';
				foreach ( $gallery_images_ids as $gallery_attachment_id ) {
					echo '<div class="sm-image">'.wp_get_attachment_image( $gallery_attachment_id, 'woocommerce_gallery_thumbnail' ).'</div>';
				}
			echo '</div>';
		?>
	</div>
	<script>
		;jQuery(document).ready(function($) {
			'use strict';
			$('.wl-product-details-images').each(function(){
				var $this = $(this);
				var $thumb = $this.siblings('.wl-product-details-thumbs');
				$this.slick({
					arrows: true,
					slidesToShow: 1,
					autoplay: false,
					autoplaySpeed: 5000,
					dots: false,
					infinite: true,
					centerMode: false,
					prevArrow:'<span class="arrow-prv"><i class="fa fa-angle-left"></i></span>',
					nextArrow:'<span class="arrow-next"><i class="fa fa-angle-right"></i></span>',
					centerPadding: 0,
					asNavFor: $thumb,
					rtl: <?php echo is_rtl() ? 'true': 'false' ?>
				});
			});
			$('.wl-product-details-thumbs').each(function(){
				var $this = $(this);
				var $details = $this.siblings('.wl-product-details-images');
				$this.slick({
					arrows: true,
					slidesToShow: <?php echo $settings['thumbnailsSliderItems']; ?>,
					slidesToScroll: 1,
					autoplay: false,
					autoplaySpeed: 5000,
					vertical:false,
					verticalSwiping:true,
					dots: false,
					infinite: true,
					focusOnSelect: true,
					centerMode: false,
					centerPadding: 0,
					prevArrow:'<span class="arrow-prv"><i class="fa fa-angle-left"></i></span>',
					nextArrow:'<span class="arrow-next"><i class="fa fa-angle-right"></i></span>',
					asNavFor: $details,
					rtl: <?php echo is_rtl() ? 'true': 'false' ?>
				});
			}); 
			$('.wl_zoom').zoom();

			var $default_data = {
				src:'',
				srcfull:'',
				srcset:'',
				sizes:'',
				width:'',
				height:'',
			};
			$( '.single_variation_wrap' ).on( 'show_variation', function ( event, variation ) {

				// Get First image data
				if( $default_data.src.length === 0 ){
					$default_data.srcfull = $('.wl-thumbnails-image-area').find('.woolentor_image_change').attr('data-src');
					$default_data.src = $('.wl-thumbnails-image-area').find('.woolentor_image_change img').attr('src');
				}

				$('.wl-thumbnails-image-area').find('.woolentor_image_change').wc_set_variation_attr('data-src',variation.image.full_src);
				$('.wl-thumbnails-image-area').find('.woolentor_image_change .zoomImg').wc_set_variation_attr('src',variation.image.src);

				$('.wl-thumbnails-image-area').find('.woolentor_image_change img').wc_set_variation_attr('src',variation.image.src);

				$('.wl-thumbnails-image-area').find('.wl-product-details-images').slick('slickGoTo', 0);

				// Reset data
				$('.variations').find('.reset_variations').on('click', function(e){
					$('.wl-thumbnails-image-area').find('.woolentor_image_change').wc_set_variation_attr('data-src', $default_data.srcfull );
					$('.wl-thumbnails-image-area').find('.woolentor_image_change .zoomImg').wc_set_variation_attr('src',$default_data.src);
					$('.wl-thumbnails-image-area').find('.woolentor_image_change img').wc_set_variation_attr('src', $default_data.src );

					$('.wl_zoom').zoom();

				});

				$('.wl_zoom').zoom();

			});

		});
	</script>
        
<?php
        
echo '</div>';