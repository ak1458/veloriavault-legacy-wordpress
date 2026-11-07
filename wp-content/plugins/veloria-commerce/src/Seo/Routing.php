<?php
/**
 * URL preservation: every URL the headless site served keeps working with the
 * same status and final address.
 *
 *  - /sitemap.xml and /robots.txt identical to the Next.js versions;
 *  - legacy aliases (/my-account, /contact, WordPress page slugs) redirect;
 *  - /images/*, /logo.svg (Next.js public/ assets, used in OG tags) are served
 *    from the theme's public/ folder at the same URLs;
 *  - WordPress-only archives that never existed on the headless site
 *    (feeds, author/date/attachment pages) return 404 / redirect;
 *  - optional 301 of api.veloriavault.com → www (after cutover).
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Seo;

use Veloria\Commerce\Catalog\Catalog;
use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class Routing {

	/** Path => target. Matching ignores a trailing slash. */
	private const REDIRECTS = array(
		'/my-account'                 => '/account',
		'/contact'                    => '/contact-us',
		'/refund_returns'             => '/refund-returns',
		'/cancellation-refund-policy' => '/cancellation-refund',
	);

	public static function register(): void {
		add_action( 'init', array( self::class, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( self::class, 'query_vars' ) );
		add_action( 'parse_request', array( self::class, 'early_routes' ), 1 );
		add_action( 'template_redirect', array( self::class, 'redirects' ), 1 );
		add_action( 'template_redirect', array( self::class, 'serve_sitemap' ), 0 );
		add_filter( 'robots_txt', array( self::class, 'robots' ), 100, 2 );
		add_action( 'update_option_' . Settings::OPTION, array( self::class, 'settings_changed' ), 10, 2 );
		add_action( 'add_option_' . Settings::OPTION, static function (): void { delete_option( 'rewrite_rules' ); } );
		add_action( 'delete_option_' . Settings::OPTION, static function (): void { delete_option( 'rewrite_rules' ); } );

		if ( Settings::get( 'serve_sitemap', true ) ) {
			add_filter( 'wp_sitemaps_enabled', '__return_false' );
		}

		// No feeds on the headless site.
		foreach ( array( 'do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom', 'do_feed_rss2_comments', 'do_feed_atom_comments' ) as $feed ) {
			add_action( $feed, array( self::class, 'not_found' ), 1 );
		}
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}

	public static function add_rewrite_rules(): void {
		if ( Settings::get( 'serve_sitemap', true ) ) {
			add_rewrite_rule( '^sitemap\.xml$', 'index.php?veloria_sitemap=1', 'top' );
		}
	}

	/** Refresh ownership on the next request, after all current-request rewrite hooks ran. */
	public static function settings_changed( $old, $new ): void {
		if ( (bool) ( $old['serve_sitemap'] ?? true ) !== (bool) ( $new['serve_sitemap'] ?? true ) ) {
			delete_option( 'rewrite_rules' );
		}
	}

	/** @param string[] $vars */
	public static function query_vars( $vars ) {
		$vars[] = 'veloria_sitemap';
		return $vars;
	}

	private static function request_path(): string {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return (string) wp_parse_url( $uri, PHP_URL_PATH );
	}

	private static function host(): string {
		return strtolower( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' );
	}

	/** Runs before WordPress resolves the query: static assets and host redirect. */
	public static function early_routes(): void {
		$path = self::request_path();

		// Legacy API host → canonical host (enable after cutover).
		if ( Settings::get( 'redirect_api_host' ) && self::host() === strtolower( (string) Settings::get( 'legacy_api_host' ) ) ) {
			$keep = array( '/wp-json', '/wp-admin', '/wp-login.php', '/wp-cron.php', '/xmlrpc.php' );
			$skip = false;
			foreach ( $keep as $prefix ) {
				if ( str_starts_with( $path, $prefix ) ) {
					$skip = true;
				}
			}
			if ( ! $skip ) {
				$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				wp_redirect( 'https://' . Settings::get( 'canonical_host' ) . $uri, 301, 'Veloria' ); // phpcs:ignore WordPress.Security.SafeRedirect
				exit;
			}
		}

		// Next.js public/ assets at their original URLs.
		if ( preg_match( '#^/(images/.+|logo\.svg|icon\.svg|file\.svg|globe\.svg|window\.svg)$#', $path ) ) {
			self::serve_public_asset( rawurldecode( $path ) );
		}
	}

	private static function serve_public_asset( string $path ): void {
		$base = (string) apply_filters( 'veloria_public_dir', get_stylesheet_directory() . '/public' );
		$file = realpath( $base . $path );
		$root = realpath( $base );
		if ( ! $file || ! $root || ! str_starts_with( $file, $root ) || ! is_file( $file ) ) {
			return;
		}
		$types = array(
			'png'  => 'image/png',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'svg'  => 'image/svg+xml',
			'webp' => 'image/webp',
			'avif' => 'image/avif',
			'gif'  => 'image/gif',
			'ico'  => 'image/x-icon',
		);
		$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		if ( ! isset( $types[ $ext ] ) ) {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: ' . $types[ $ext ] );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'Cache-Control: public, max-age=604800' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	public static function redirects(): void {
		$path = untrailingslashit( self::request_path() ) ?: '/';

		foreach ( self::REDIRECTS as $from => $to ) {
			// Only redirect once the target page exists (pages are renamed by `wp veloria seed-pages`),
			// so a half-configured site never loops or 404s.
			if ( ( $path === $from || str_starts_with( $path, $from . '/' ) ) && get_page_by_path( trim( $to, '/' ) ) && ! get_page_by_path( trim( $from, '/' ) ) ) {
				$target = $to . substr( $path, strlen( $from ) );
				$query  = isset( $_SERVER['QUERY_STRING'] ) && '' !== $_SERVER['QUERY_STRING'] ? '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				wp_safe_redirect( home_url( $target ) . $query, 301 );
				exit;
			}
		}

		if ( '/wp-sitemap.xml' === $path && Settings::get( 'serve_sitemap', true ) ) {
			wp_safe_redirect( home_url( '/sitemap.xml' ), 301 );
			exit;
		}

		// /search?q=… (old or shared links) → the shop's search.
		if ( '/search' === untrailingslashit( $path ) ) {
			$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( '' === $term ? home_url( '/shop' ) : add_query_arg( 'search', rawurlencode( $term ), home_url( '/shop' ) ), 301 );
			exit;
		}

		// WordPress search → the shop's search, as on the headless site.
		if ( is_search() && ! is_admin() ) {
			wp_safe_redirect( add_query_arg( 'search', rawurlencode( get_search_query( false ) ), home_url( '/shop' ) ), 302 );
			exit;
		}

		// Archives the headless site never had.
		if ( is_author() || is_date() ) {
			self::not_found();
		}
		if ( is_attachment() ) {
			$parent = (int) wp_get_post_parent_id( get_queried_object_id() );
			if ( $parent ) {
				wp_safe_redirect( get_permalink( $parent ), 301 );
				exit;
			}
			self::not_found();
		}
	}

	public static function not_found(): void {
		global $wp_query;
		if ( $wp_query ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		nocache_headers();
		if ( doing_action( 'template_redirect' ) ) {
			return;
		}
		include get_query_template( '404' ) ?: '';
		exit;
	}

	/** @return array<int,array{loc:string,changefreq:string,priority:string}> */
	public static function sitemap_entries(): array {
		$base    = home_url();
		$entries = array();
		$static  = array(
			array( '/', 'daily', '1' ),
			array( '/shop', 'daily', '0.9' ),
			array( '/about', 'monthly', '0.7' ),
			array( '/contact-us', 'monthly', '0.7' ),
			array( '/privacy-policy', 'yearly', '0.3' ),
			array( '/terms-conditions', 'yearly', '0.3' ),
			array( '/shipping-policy', 'yearly', '0.3' ),
			array( '/refund-returns', 'yearly', '0.3' ),
			array( '/warranty-policy', 'yearly', '0.3' ),
		);
		foreach ( $static as $row ) {
			$page = '/' === $row[0] ? get_post( (int) get_option( 'page_on_front' ) ) : get_page_by_path( trim( $row[0], '/' ) );
			if ( ! $page || 'publish' !== $page->post_status ) {
				continue;
			}
			$entry = array( 'loc' => '/' === $row[0] ? $base . '/' : $base . $row[0], 'changefreq' => $row[1], 'priority' => $row[2] );
			$modified = get_post_modified_time( 'c', true, $page );
			if ( $modified ) {
				$entry['lastmod'] = $modified;
			}
			$entries[] = $entry;
		}
		$catalog = Catalog::instance();
		foreach ( $catalog->categories() as $category ) {
			$entries[] = array( 'loc' => $base . '/product-category/' . $category['slug'], 'changefreq' => 'weekly', 'priority' => '0.8' );
		}
		foreach ( $catalog->parent_products() as $product ) {
			$entry = array( 'loc' => $base . '/product/' . $product['slug'], 'changefreq' => 'weekly', 'priority' => '0.8' );
			$modified = get_post_modified_time( 'c', true, (int) $product['id'] );
			if ( $modified ) {
				$entry['lastmod'] = $modified;
			}
			$entries[] = $entry;
		}
		return (array) apply_filters( 'veloria_sitemap_entries', $entries );
	}

	public static function serve_sitemap(): void {
		if ( ! get_query_var( 'veloria_sitemap' ) ) {
			return;
		}
		if ( ! Settings::get( 'serve_sitemap', true ) ) {
			self::not_found();
			return;
		}
		// Finish loading before sending a 200 response; an upstream error must not publish a partial map.
		$entries = self::sitemap_entries();
		if ( count( $entries ) > 50000 ) {
			wp_die( 'The sitemap needs to be split into an index before publication.', 'Sitemap unavailable', array( 'response' => 503 ) );
		}
		status_header( 200 );
		header( 'Content-Type: application/xml; charset=utf-8' );
		header( 'Cache-Control: public, max-age=0, s-maxage=3600' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( $entries as $entry ) {
			printf(
				"<url>\n<loc>%s</loc>\n%s<changefreq>%s</changefreq>\n<priority>%s</priority>\n</url>\n",
				esc_url( $entry['loc'] ),
				! empty( $entry['lastmod'] ) ? '<lastmod>' . esc_html( $entry['lastmod'] ) . "</lastmod>\n" : '',
				esc_html( $entry['changefreq'] ),
				esc_html( $entry['priority'] )
			);
		}
		echo '</urlset>';
		exit;
	}

	/** Same rules as src/app/robots.ts. */
	public static function robots( $output, $public ) {
		if ( ! $public || ! Settings::get( 'serve_sitemap', true ) ) {
			return $output;
		}
		$base  = home_url();
		$lines = array(
			'User-Agent: *',
			'Allow: /',
			'Disallow: /api/',
			'Disallow: /checkout',
			'Disallow: /cart',
			'Disallow: /account',
			'Disallow: /my-account',
			'Disallow: /wishlist',
			'Disallow: /*.json$',
			'Disallow: /wp-admin/',
			'Allow: /wp-admin/admin-ajax.php',
			'',
			'Host: ' . $base,
			'Sitemap: ' . $base . '/sitemap.xml',
		);
		return implode( "\n", $lines ) . "\n";
	}
}
