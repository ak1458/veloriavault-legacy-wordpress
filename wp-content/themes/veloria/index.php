<?php
/**
 * Fallback template. The storefront has no blog, so anything that reaches
 * here is treated as not found (the headless site returned 404 too).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_404() ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
require __DIR__ . '/404.php';
