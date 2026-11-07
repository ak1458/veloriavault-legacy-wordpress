<?php
/**
 * Veloria theme bootstrap.
 *
 * Presentation only. Pricing, orders, accounts, SEO routing and every other
 * business rule live in the Veloria Commerce plugin, so this theme can be
 * restyled or replaced without touching how money is calculated.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

define( 'VELORIA_THEME_VERSION', '1.3.1' );
define( 'VELORIA_THEME_DIR', get_template_directory() );
define( 'VELORIA_THEME_URI', get_template_directory_uri() );

require VELORIA_THEME_DIR . '/inc/requirements.php';

if ( ! veloria_requirements_met() ) {
	return;
}

require VELORIA_THEME_DIR . '/inc/setup.php';
require VELORIA_THEME_DIR . '/inc/assets.php';
require VELORIA_THEME_DIR . '/inc/icons.php';
require VELORIA_THEME_DIR . '/inc/template-helpers.php';
require VELORIA_THEME_DIR . '/inc/seo.php';
require VELORIA_THEME_DIR . '/inc/woocommerce.php';
require VELORIA_THEME_DIR . '/inc/native-tools.php';
require VELORIA_THEME_DIR . '/inc/elementor.php';
