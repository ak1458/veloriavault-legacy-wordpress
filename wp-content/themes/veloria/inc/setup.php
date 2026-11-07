<?php
/**
 * Theme supports and WordPress output clean-up.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_theme_support( 'responsive-embeds' );
		load_theme_textdomain( 'veloria', VELORIA_THEME_DIR . '/languages' );
	}
);

// Keep block/global styles for merchant-authored content and plugin widgets.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
add_filter( 'show_admin_bar', static fn( $show ) => $show && current_user_can( 'edit_posts' ) );

// Favicon from the Next.js app (src/app/icon.svg).
add_action(
	'wp_head',
	static function (): void {
		if ( ! has_site_icon() ) {
			printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( veloria_theme_asset( 'icon.svg' ) ) );
		}
	},
	2
);

// A Site Icon whose upload file is gone (live attachment 84) would 404; use the theme icon instead.
add_filter(
	'get_site_icon_url',
	static function ( $url ) {
		$id = (int) get_option( 'site_icon' );
		if ( $id && $url ) {
			$file = get_attached_file( $id );
			if ( ! $file || ! file_exists( $file ) ) {
				return veloria_theme_asset( 'icon.svg' );
			}
		}
		return $url;
	}
);

// Email header logo: Gmail/Outlook do not render SVG and the live upload is missing; use the theme PNG.
add_filter(
	'option_woocommerce_email_header_image',
	static function ( $url ) {
		$url     = (string) $url;
		$uploads = wp_get_upload_dir();
		$local   = '' !== $url && str_starts_with( $url, $uploads['baseurl'] ) ? $uploads['basedir'] . substr( $url, strlen( $uploads['baseurl'] ) ) : null;
		if ( '' === $url || preg_match( '/\.svg(\?|$)/i', $url ) || ( null !== $local && ! file_exists( $local ) ) ) {
			// Cron/CLI sends have no HTTPS context; keep the site's own scheme in emails.
			return set_url_scheme( veloria_theme_asset( 'email-logo.png' ), wp_parse_url( (string) get_option( 'home' ), PHP_URL_SCHEME ) );
		}
		return $url;
	}
);

/**
 * Body classes the headless layout put on <body>.
 *
 * @param string[] $classes
 * @return string[]
 */
add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'antialiased';
		$classes[] = 'bg-[#faf8f5]';
		$classes[] = 'text-gray-900';
		return $classes;
	}
);
