<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
$areaClasses = array( $uniqClass );

!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';

$id = $settings['blockUniqId'];

$product = wc_get_product();
if( empty( $product ) ){
	$product = wc_get_product( woolentor_get_last_product_id() );
}
if ( empty( $product ) ) {return; }

$product_title  = get_the_title();
$product_url    = get_permalink();
$product_img    = wp_get_attachment_url( get_post_thumbnail_id() );

echo '<div class="'.esc_attr(implode(' ', $areaClasses )).'">';
	if( 'style-1' == $settings['layout']){

		$facebook_url   = 'https://www.facebook.com/sharer/sharer.php?u=' . $product_url;
		$twitter_url    = 'http://twitter.com/intent/tweet?status=' . rawurlencode( $product_title ) . '+' . $product_url;
		$pinterest_url  = 'http://pinterest.com/pin/create/bookmarklet/?media=' . $product_img . '&url=' . $product_url . '&is_video=false&description=' . rawurlencode( $product_title );
		$gplus_url      = 'https://plus.google.com/share?url='. $product_url;
		$reddit_url     = 'https://reddit.com/submit?url={'.  $product_url .'}&title={'. $product_title .'}';

		?>
			<div class="woolentor_product_social_share <?php echo $settings['layout']; ?>">
				<?php
					if( !empty( $settings['socialShareTitle'] ) ){
						echo '<h2>'.esc_html( $settings['socialShareTitle'] ).'</h2>';
					}
				?>
				<ul>
					<li><a href="<?php echo esc_url( $facebook_url ); ?>" target="_blank"><i class="fa fa-facebook-f"></i></a></li>
					<li><a href="<?php echo esc_url( $gplus_url ); ?>" target="_blank"><i class="fa fa-google-plus"></i></a></li>
					<li><a href="<?php echo esc_url( $pinterest_url ); ?>" target="_blank"><i class="fa fa-pinterest-p"></i></a></li>
					<li><a href="<?php echo esc_url( $twitter_url ); ?>" target="_blank"><i class="fa fa-twitter"></i></a></li>
					<li><a href="<?php echo esc_url( $reddit_url ); ?>" target="_blank"><i class="fa fa-reddit-alien"></i></a></li>
				</ul>
			</div>
		<?php
	}else{
		$shocial_share = [
			'Facebook' =>[
				'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . $product_url,
				'icon'=> 'fa fa-facebook-f'
			],
			'Twitter' => [
				'url' => 'http://twitter.com/intent/tweet?status=' . rawurlencode( $product_title ) . '+' . $product_url,
				'icon'=> 'fa fa-twitter'
			],
			'Pinterest' => [
				'url' => 'http://pinterest.com/pin/create/bookmarklet/?media=' . $product_img . '&url=' . $product_url . '&is_video=false&description=' . rawurlencode( $product_title ),
				'icon'=> 'fa fa-pinterest-p'
			], 
			'Google+' => [
				'url' => 'https://plus.google.com/share?url='. $product_url,
				'icon'=> 'fa fa-google-plus'
			],
			'Reddit' => [
				'url' => 'https://reddit.com/submit?url={'. $product_url .'}&title={'. $product_title .'}',
				'icon'=> 'fa fa-reddit-alien'
			],
			'Linkedin' => [
				'url' => 'http://www.linkedin.com/shareArticle?url='. esc_url($product_url) .'&amp;title='. $product_title,
				'icon'=> 'fa fa-linkedin-in'
			],
			'Skype' => [
				'url' => 'https://web.skype.com/share?url='.urlencode( esc_url($product_url) ),
				'icon'=> 'fa fa-skype'
			],
			'WhatsApp' => [
				'url' => 'https://api.whatsapp.com/send?text='.$product_title.' '.urlencode( esc_url($product_url) ),
				'icon'=> 'fa fa-whatsapp'
			],
			'instagram' => [
				'url' => 'https://www.instagram.com/%s',
				'icon'=> 'fa fa-instagram'
			]
		];

		?>
			<div class="woolentor_product_social_share <?php echo $settings['layout']; ?>">
				<?php
					if( !empty( $settings['socialShareTitle'] ) ){
						echo '<h2>'.esc_html( $settings['socialShareTitle'] ).'</h2>';
					}
				?>
				<ul>
					<?php
						if( isset( $settings['socialMediaList'] ) ){
							foreach($settings['socialMediaList'] as $share_item_key => $share_item):
								$share_item_info = $shocial_share[$share_item['socialMediaName']];
								if( $share_item['socialMediaName'] === 'instagram'){
									$share_item_info['url'] = sprintf( $share_item_info['url'], ($share_item['instagramUsername'] ? $share_item['instagramUsername'] : ''));
								}
								$itemUniqId = $id.$share_item_key;
						?>
							<li class="woolentor-repeater-item-<?php echo esc_attr($itemUniqId); ?>">
								<a href="<?php echo esc_url( $share_item_info['url'] ); ?>" target="_blank">
									<i class="<?php echo esc_attr($share_item_info['icon']); ?>"></i>
									<?php if(!empty($share_item['socialMediaText'])): ?>
										<span><?php echo $share_item['socialMediaText'];  ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php
							// Individual Style
							if (!empty($share_item['itemColor'])) {
								$styleIndividual_Item .= ".woolentor_product_social_share .woolentor-repeater-item-$itemUniqId a{
									color: {$share_item['itemColor']};
								}";
							}
							if (!empty($share_item['itemBGColor'])) {
								$styleIndividual_Item .= ".woolentor_product_social_share .woolentor-repeater-item-$itemUniqId a{
									background-color: {$share_item['itemBGColor']};
								}";
							}
							if (!empty($share_item['itemHoverColor'])) {
								$styleIndividual_Item .= ".woolentor_product_social_share .woolentor-repeater-item-$itemUniqId a:hover{
									color: {$share_item['itemHoverColor']};
								}";
							}
							if (!empty($share_item['itemHoverBGColor'])) {
								$styleIndividual_Item .= ".woolentor_product_social_share .woolentor-repeater-item-$itemUniqId a:hover{
									background-color: {$share_item['itemHoverBGColor']};
								}";
							}
							
							endforeach;
						}
					?>
				</ul>
			</div>
		<?php
		if( !empty( $styleIndividual_Item ) ){
			echo '<style type="text/css">'.$styleIndividual_Item.'</style>';
		}
	}
echo '</div>';