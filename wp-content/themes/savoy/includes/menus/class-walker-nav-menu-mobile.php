<?php
    /*
     * Menus class: Add custom mobile menu markup
     * 
     * The extended "Walker_Nav_Menu" class is placed in: "../wp-includes/class-walker-nav-menu.php"
     */
    class NM_Sublevel_Walker_Mobile extends Walker_Nav_Menu {
        /* Starts the list before the elements are added */
        function start_lvl( &$output, $depth = 0, $args = array() ) {
            $indent = str_repeat( "\t", $depth );
            
            if ( $depth == 0 ) {
                global $nm_mobile_menu_back_button_title;
                
                $header = '<div class="nm-mobile-sub-menu-header"><a class="nm-mobile-sub-menu-back-button"><i class="nm-font-chevron-thin-up"></i>' . $nm_mobile_menu_back_button_title . '</a></div>';
                
                $output .= "\n$indent<div class='sub-menu'>" . $header . "<ul class='nm-mobile-sub-menu-ul'>\n";
            } else {
                $output .= "\n$indent<ul class='sub-menu'>\n";
            }
        }
        
        /* Ends the list of after the elements are added */
        function end_lvl( &$output, $depth = 0, $args = array() ) {
            $indent = str_repeat( "\t", $depth );
            
            if ( $depth == 0 ) {
                $output .= "$indent</ul></div>\n";
            } else {
                $output .= "$indent</ul>\n";
            }
        }
    }
