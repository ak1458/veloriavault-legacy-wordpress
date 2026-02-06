<?php
    global $nm_theme_options;

	// Ubermenu
	if ( function_exists( 'ubermenu' ) ) {
		$ubermenu = true;
		$ubermenu_wrap_open = '<div class="nm-ubermenu-wrap clear">';
		$ubermenu_wrap_close = '</div>';
	} else {
		$ubermenu = false;
		$ubermenu_wrap_open = $ubermenu_wrap_close = '';
	}
    
    // Mobile menu button position
    $mobile_menu_button_inline = ( strpos( $nm_theme_options['header_layout'], 'stacked' ) !== false ) ? true : false;
    $mobile_menu_button_right_menu = apply_filters( 'nm_mobile_menu_button_inline', false );
?>
<div class="nm-header-row nm-row">
    <div class="nm-header-col col-xs-12">
        <?php echo $ubermenu_wrap_open; ?>
        
        <div class="nm-header-left">
            <?php if ( ! $mobile_menu_button_inline ) : ?>
            <nav class="nm-mobile-menu-button-wrapper">
                <ul id="nm-mobile-menu-button-ul" class="nm-menu">
                    <?php nm_header_mobile_menu_button(); ?>
                </ul>
            </nav>
            <?php endif; ?>

            <?php
                // Include header logo
                get_template_part( 'template-parts/header/header', 'logo' );
            ?>
        </div>
        
        <?php if ( $ubermenu ) : ?>
            <?php ubermenu( 'main', array( 'theme_location' => 'main-menu' ) ); ?>
        <?php else : ?>               
        <nav class="nm-main-menu">
            <ul id="nm-main-menu-ul" class="nm-menu">
                <?php if ( $mobile_menu_button_inline ) : ?>
                    <?php nm_header_mobile_menu_button(); ?>
                <?php endif; ?>
                
                <?php
                    wp_nav_menu( array(
                        'theme_location'	=> 'main-menu',
                        'container'       	=> false,
                        'fallback_cb'     	=> false,
                        'walker'            => new NM_Sublevel_Walker,
                        'items_wrap'      	=> '%3$s'
                    ) );
                ?>
            </ul>
        </nav>
        <?php endif; ?>

        <nav class="nm-right-menu">
            <ul id="nm-right-menu-ul" class="nm-menu">
                <?php
                    wp_nav_menu( array(
                        'theme_location'	=> 'right-menu',
                        'container'       	=> false,
                        'fallback_cb'     	=> false,
                        'walker'            => new NM_Sublevel_Walker,
                        'items_wrap'      	=> '%3$s'
                    ) );
                    
                    // Include default links (Login, Cart etc.)
                    get_template_part( 'template-parts/header/header', 'default-links' );
                ?>
                
                <?php if ( $mobile_menu_button_right_menu ) : ?>
                    <?php nm_header_mobile_menu_button(); ?>
                <?php endif; ?>
            </ul>
        </nav>

        <?php echo $ubermenu_wrap_close; ?>
    </div>
</div>