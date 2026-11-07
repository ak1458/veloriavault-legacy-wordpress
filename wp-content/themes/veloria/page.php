<?php
/**
 * Generic page: the same layout as the policy pages.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

if ( veloria_elementor_render_page() ) { return; }

require __DIR__ . '/page-templates/legal.php';
