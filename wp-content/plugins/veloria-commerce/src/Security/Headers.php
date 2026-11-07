<?php
/**
 * Security headers, blocked paths and request filtering (port of
 * src/middleware.ts, next.config.ts headers() and vercel.json headers).
 *
 * Only storefront requests are filtered. wp-admin, wp-login, cron, admin-ajax
 * and the customizer are left alone so administration never breaks.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Security;

use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class Headers {

	private const BLOCKED_PATHS = array(
		'/.env', '/.env.local', '/.env.production', '/.env.development', '/config.js', '/config.json',
		'/package.json', '/package-lock.json', '/yarn.lock', '/.git', '/.gitignore', '/Dockerfile', '/docker-compose.yml',
	);

	private const SUSPICIOUS = array(
		'/<script/i',
		'/javascript:/i',
		'/\bon(click|load|error|mouseover|focus|blur|submit|change|keyup|keydown)=/i',
		'/select.*from/i',
		'/union.*select/i',
		'/drop.*table/i',
		'/insert.*into/i',
		'/delete.*from/i',
	);

	private const BAD_BOTS = array( 'sqlmap', 'nikto', 'nessus', 'nmap', 'masscan', 'zgrab', 'gobuster', 'dirbuster', 'wfuzz', 'burp', 'metasploit', 'acunetix', 'netsparker' );

	public static function register(): void {
		add_action( 'init', array( self::class, 'filter_request' ), 1 );
		add_action( 'send_headers', array( self::class, 'send' ) );
		add_action( 'template_redirect', array( self::class, 'private_pages' ), 1 );
		add_filter( 'rest_post_dispatch', array( self::class, 'rest_no_store' ), 10, 3 );
	}

	public static function private_pages(): void {
		$private = is_user_logged_in() || ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) || is_page( array( 'track-order', 'login', 'register' ) );
		if ( ! $private ) return;
		if ( ! defined( 'DONOTCACHEPAGE' ) ) define( 'DONOTCACHEPAGE', true );
		nocache_headers();
		header( 'Cache-Control: private, no-store, max-age=0' );
	}

	private static function is_storefront_request(): bool {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}
		$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return ! str_starts_with( $path, '/wp-login.php' ) && ! str_starts_with( $path, '/wp-admin' );
	}

	/**
	 * Anonymous /wp/v2/users lists login slugs (e.g. the admin), aiding password
	 * guessing. Logged-in editors (Elementor, block editor) keep the endpoint.
	 *
	 * @param array<string,mixed> $endpoints
	 * @return array<string,mixed>
	 */
	public static function hide_users_endpoints( $endpoints ) {
		if ( is_array( $endpoints ) && ! is_user_logged_in() ) {
			unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		}
		return $endpoints;
	}

	public static function filter_request(): void {
		if ( ! self::is_storefront_request() ) {
			return;
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

		foreach ( self::BLOCKED_PATHS as $blocked ) {
			if ( str_starts_with( $path, $blocked ) ) {
				self::deny( 404, 'Not Found' );
			}
		}

		if ( ! Settings::get( 'block_suspicious', true ) || str_starts_with( $path, '/wp-json/' ) ) {
			return;
		}

		$decoded = rawurldecode( $uri );
		foreach ( self::SUSPICIOUS as $pattern ) {
			if ( preg_match( $pattern, $decoded ) ) {
				self::deny( 400, 'Bad Request' );
			}
		}

		$agent = strtolower( isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( self::BAD_BOTS as $bot ) {
			if ( str_contains( $agent, $bot ) ) {
				self::deny( 403, 'Forbidden' );
			}
		}
	}

	private static function deny( int $status, string $body ): void {
		status_header( $status );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html( $body );
		exit;
	}

	public static function send(): void {
		if ( ! Settings::get( 'security_headers', true ) || ! self::is_storefront_request() || is_customize_preview() ) {
			return;
		}
		// Elementor's editor loads the page in a same-origin iframe. Only an
		// authenticated editor of this exact document may use that preview.
		$preview_id = isset( $_GET['elementor-preview'] ) ? absint( $_GET['elementor-preview'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$editor_preview = $preview_id > 0 && is_user_logged_in() && current_user_can( 'edit_post', $preview_id );
		header( 'X-Frame-Options: ' . ( $editor_preview ? 'SAMEORIGIN' : 'DENY' ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-XSS-Protection: 1; mode=block' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()' );
		if ( is_ssl() ) {
			// includeSubDomains / preload are hard to undo, so they are opt-in.
			$hsts = 'max-age=31536000';
			if ( Settings::get( 'hsts_subdomains', false ) ) {
				$hsts .= '; includeSubDomains';
				if ( Settings::get( 'hsts_preload', false ) ) {
					$hsts .= '; preload';
				}
			}
			header( 'Strict-Transport-Security: ' . $hsts );
		}

		// Logged-in users get the CSP too (they are the most valuable XSS
		// targets), except in editor / post previews, which load builder code.
		$mode = (string) Settings::get( 'csp_mode', 'report' );
		if ( 'off' !== $mode && ! $editor_preview && ! self::is_preview_request() ) {
			$name = 'enforce' === $mode ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';
			header( $name . ': ' . self::csp() );
		}
	}

	/**
	 * Post, page-builder and customizer previews. send_headers runs before the
	 * main query, so is_preview() is not available yet; read the request.
	 */
	private static function is_preview_request(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! is_user_logged_in() ) {
			return false;
		}
		foreach ( array( 'preview', 'preview_id', 'elementor-preview', 'elementor_library', 'customize_changeset_uuid', 'customize_messenger_channel' ) as $param ) {
			if ( isset( $_GET[ $param ] ) ) {
				return true;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return false;
	}

	/**
	 * Scheme://host[:port] of the site, derived from home_url() / site_url()
	 * so the policy follows the install (staging, a new domain) automatically.
	 *
	 * @return string[]
	 */
	private static function own_origins(): array {
		$origins = array();
		foreach ( array( home_url( '/' ), site_url( '/' ) ) as $url ) {
			$parts = wp_parse_url( $url );
			if ( empty( $parts['host'] ) ) {
				continue;
			}
			$origin    = ( $parts['scheme'] ?? 'https' ) . '://' . strtolower( $parts['host'] ) . ( isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '' );
			$origins[] = $origin;
		}
		return array_values( array_unique( $origins ) );
	}

	/**
	 * Keeps only CSP host-source tokens (e.g. https://cdn.example.com,
	 * *.example.com, wss://ws.example.com:443/path). Keywords, quotes, scheme-only
	 * sources and directive separators are dropped, so the setting cannot add
	 * or weaken directives.
	 */
	public static function sanitize_sources( string $raw ): string {
		$label  = '[a-z0-9](?:[a-z0-9-]*[a-z0-9])?';
		$re     = '#^(?:(?:https?|wss?)://)?(?:\*\.)?' . $label . '(?:\.' . $label . ')+(?::(?:\d{1,5}|\*))?(?:/[a-z0-9._~%/-]*)?$#i';
		$tokens = preg_split( '/[\s,]+/', $raw ) ?: array();
		$clean  = array();
		foreach ( $tokens as $token ) {
			if ( '' !== $token && preg_match( $re, $token ) ) {
				$clean[] = $token;
			}
		}
		return implode( ' ', array_unique( $clean ) );
	}

	/**
	 * Same policy as next.config.ts, widened for the third parties an
	 * Elementor/WooCommerce storefront commonly embeds (YouTube, Vimeo, Google
	 * Maps, reCAPTCHA, GA/GTM, Meta pixel, Razorpay), plus the site's own
	 * origins and any extra sources from settings. Elementor's own assets are
	 * served from the site (own origin) and Google Fonts.
	 */
	public static function csp(): string {
		$extra = self::sanitize_sources( (string) Settings::get( 'csp_extra_sources', '' ) );
		$extra = '' !== $extra ? ' ' . $extra : '';
		$own   = implode( ' ', self::own_origins() );

		$razorpay  = 'https://checkout.razorpay.com https://api.razorpay.com https://lumberjack.razorpay.com https://*.razorpay.com';
		$google    = 'https://www.google.com https://www.gstatic.com https://www.recaptcha.net';
		$analytics = 'https://www.googletagmanager.com https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com';
		$meta      = 'https://connect.facebook.net https://www.facebook.com';
		$maps      = 'https://maps.googleapis.com https://maps.gstatic.com';
		$video     = 'https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com';

		$policy = array(
			"default-src 'self'",
			"script-src 'self' 'unsafe-inline' 'unsafe-eval' {$own} {$razorpay} {$google} {$analytics} {$meta} {$maps} {$video}" . $extra,
			"style-src 'self' 'unsafe-inline' {$own} https://fonts.googleapis.com https://www.gstatic.com" . $extra,
			"img-src 'self' {$own} https: blob: data: https://i.ytimg.com https://*.vimeocdn.com" . $extra,
			"font-src 'self' {$own} data: https://fonts.gstatic.com" . $extra,
			"connect-src 'self' {$own} {$razorpay} {$google} {$analytics} {$meta} {$maps}" . $extra,
			"media-src 'self' {$own} blob: https://*.vimeocdn.com" . $extra,
			"frame-src 'self' {$own} {$razorpay} {$video} https://www.google.com https://maps.google.com https://www.recaptcha.net https://www.googletagmanager.com https://www.facebook.com" . $extra,
			"frame-ancestors 'none'",
			"base-uri 'self'",
			// The Meta pixel posts events to facebook.com/tr through a hidden form.
			"form-action 'self' {$own} https://*.razorpay.com https://www.facebook.com" . $extra,
		);
		return (string) apply_filters( 'veloria_csp', implode( '; ', $policy ) );
	}

	/**
	 * vercel.json: every /api route except media is no-store, so customer data
	 * is never cached by a CDN.
	 *
	 * @param \WP_HTTP_Response $response
	 */
	public static function rest_no_store( $response, $server, $request ) {
		if ( $response instanceof \WP_HTTP_Response && $request instanceof \WP_REST_Request && str_starts_with( $request->get_route(), '/veloria/' ) ) {
			$response->header( 'Cache-Control', 'no-store, max-age=0' );
		}
		return $response;
	}
}
