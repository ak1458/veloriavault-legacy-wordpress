<?php
/** Template Name: Register */
defined( 'ABSPATH' ) || exit;
if ( function_exists( 'veloria_elementor_render_page' ) && veloria_elementor_render_page() ) return;
get_header();
get_template_part( 'template-parts/tools/register');
get_footer();
