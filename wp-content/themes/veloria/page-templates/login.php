<?php
/** Template Name: Login */
defined( 'ABSPATH' ) || exit;
if ( function_exists( 'veloria_elementor_render_page' ) && veloria_elementor_render_page() ) return;
get_header();
get_template_part( 'template-parts/tools/login');
get_footer();
