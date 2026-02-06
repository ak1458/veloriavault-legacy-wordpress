<?php
	global $nm_theme_options;
    
    // Copyright text
	$copyright_text = ( isset( $nm_theme_options['footer_bar_text'] ) && strlen( $nm_theme_options['footer_bar_text'] ) > 0 ) ? $nm_theme_options['footer_bar_text'] : '';
	if ( $nm_theme_options['footer_bar_text_cr_year'] ) {
		$copyright_text = sprintf(
            '%1$s&copy;%2$s %3$s%4$s%5$s %6$s',
            '<span class="copy">',
            '</span>',
            '<span class="year">',
            date( 'Y' ),
            '</span>',
            $copyright_text
        );
	}
	
    $column_right_social_icons = ( strpos( $nm_theme_options['footer_bar_content'], 'social' ) !== false ) ? true : false;
    $column_right_copyright = ( strpos( $nm_theme_options['footer_bar_content'], 'copyright' ) !== false ) ? true : false;
    $column_right_text = ( $nm_theme_options['footer_bar_content'] == 'custom' ) ? true : false; 
    
    $has_copyright = ( strlen( $copyright_text ) > 1 ) ? true : false;    
    $has_text = ( strlen( $nm_theme_options['footer_bar_custom_content'] ) > 1 ) ? true : false;

    $column_left_show_copyright = ( ! $column_right_copyright && $has_copyright ) ? true : false;
    $column_left_show_text = ( ! $column_right_text && $has_text ) ? true : false;
    $column_right_show_copyright = ( $column_right_copyright && $has_copyright ) ? true : false;
    $column_right_show_text = ( $column_right_text && $has_text ) ? true : false;
?>
<div class="nm-footer-bar layout-<?php echo esc_attr( $nm_theme_options['footer_bar_layout'] ); ?>">
    <div class="nm-footer-bar-inner">
        <div class="nm-row">
            <div class="nm-footer-bar-left nm-footer-bar-col col-md-8 col-xs-12">
                <div class="nm-footer-bar-col-inner">
                    <?php do_action( 'nm_footer_bar_left_top' ); ?>

                    <?php 
                        if ( isset( $nm_theme_options['footer_bar_logo'] ) && strlen( $nm_theme_options['footer_bar_logo']['url'] ) > 0 ) :

                        $logo_src = ( is_ssl() ) ? str_replace( 'http://', 'https://', $nm_theme_options['footer_bar_logo']['url'] ) : $nm_theme_options['footer_bar_logo']['url'];
                        $logo_alt = get_post_meta( $nm_theme_options['footer_bar_logo']['id'], '_wp_attachment_image_alt', true );
                        $logo_alt = ( $logo_alt ) ? $logo_alt : get_the_title( $nm_theme_options['footer_bar_logo']['id'] );
                    ?>
                    <div class="nm-footer-bar-logo">
                        <img src="<?php echo esc_url( $logo_src ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" />
                    </div>
                    <?php endif; ?>

                    <ul id="nm-footer-bar-menu" class="menu">
                        <?php
                            // Footer menu
                            wp_nav_menu( array(
                                'theme_location'    => 'footer-menu',
                                'container'       	=> false,
                                'fallback_cb'     	=> false,
                                'items_wrap'      	=> '%3$s'
                            ) );
                        ?>
                    </ul>
                    
                    <?php if ( $column_left_show_copyright || $column_left_show_text ) : ?>
                    <div class="nm-footer-bar-text">
                        <?php if ( $column_left_show_copyright ) : ?>
                        <div class="nm-footer-bar-copyright-text"><?php echo wp_kses_post( $copyright_text ); ?></div>
                        <?php endif; ?>

                        <?php if ( $column_left_show_text ) : ?>
                        <div class="nm-footer-bar-custom-text"><?php echo wp_kses_post( do_shortcode( $nm_theme_options['footer_bar_custom_content'] ) ); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php do_action( 'nm_footer_bar_left_bottom' ); ?>
                </div>
            </div>

            <div class="nm-footer-bar-right nm-footer-bar-col col-md-4 col-xs-12">
                <div class="nm-footer-bar-col-inner">
                    <?php do_action( 'nm_footer_bar_right_top' ); ?>

                    <?php if ( $column_right_social_icons ) : ?>
                        <?php echo nm_get_social_profiles( 'nm-footer-bar-social' ); // Args: $wrapper_class ?>
                    <?php endif; ?>
                    
                    <?php if ( $column_right_show_copyright || $column_right_show_text ) : ?>
                    <div class="nm-footer-bar-text">
                        <?php if ( $column_right_show_copyright ) : ?>
                        <div class="nm-footer-bar-copyright-text"><?php echo wp_kses_post( $copyright_text ); ?></div>
                        <?php endif; ?>
                        
                        <?php if ( $column_right_show_text ) : ?>
                        <div class="nm-footer-bar-custom-text"><?php echo wp_kses_post( do_shortcode( $nm_theme_options['footer_bar_custom_content'] ) ); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php do_action( 'nm_footer_bar_right_bottom' ); ?>
                </div>
            </div>
        </div>
    </div>
</div>