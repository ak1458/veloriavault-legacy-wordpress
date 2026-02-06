<?php
    $badge_image_url = !empty( $badge['badge_image'] ) ? $badge['badge_image'] : "";
    $badge_title     = !empty( $badge['badge_title'] ) ? $badge['badge_title'] : "";
    $badge_postion   = !empty( $badge['badge_position'] ) ? $badge['badge_position'] : "";

    $badge_css = "";
    if($badge_postion === 'custom_position' && woolentor_is_pro()){
        $custom_position = woolentor_css_position( 'badge_custom_position','woolentor_badges_settings','',$badge['badge_custom_position'] );
        $badge_css .= $custom_position;
    }

    $badge_image_size = !empty( $badge['badge_image_size'] ) ? $badge['badge_image_size'] : [];
    $badge_image_size_css = "height: auto; width: 35px;";
    if( !empty( $badge_image_size ) ){
        $badge_image_size_css = 'width: '.($badge_image_size['width'] ? $badge_image_size['width'].$badge_image_size['unit'] : '35px').'; height: '.($badge_image_size['height'] ? $badge_image_size['height'].$badge_image_size['unit'] : 'auto').';';
    }
?>
<?php if( !empty($badge_image_url) ): ?>
    <div class="woolentor-product-badge-area <?php echo esc_attr($classes); ?>" style="<?php echo esc_attr($badge_css); ?>">
        <div class="woolentor-product-badge">
            <img style = "<?php echo esc_attr($badge_image_size_css); ?>" src="<?php echo esc_url( $badge_image_url );?>" alt="<?php echo esc_attr($badge_title); ?>">
        </div>
    </div>
<?php endif; ?>