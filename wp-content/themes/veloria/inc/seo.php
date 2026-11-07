<?php
/**
 * <head> SEO tags, reproducing the Next.js metadata output page by page
 * (captured from the live site on 18 Oct 2026), including its inheritance
 * rules: pages without their own Open Graph block inherit the root one, and
 * Twitter tags always come from the root.
 *
 * Disabled automatically when Yoast SEO or Rank Math is active, or when
 * Veloria → "Theme SEO tags" is switched off.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

use Veloria\Commerce\Catalog\Catalog;

function veloria_seo_enabled(): bool {
	return (bool) veloria_setting( 'seo_enabled', true ) && ! defined( 'WPSEO_VERSION' ) && ! class_exists( 'RankMath' );
}

/**
 * Keep local copies, staging copies and search-disabled sites out of search indexes.
 * "staging" alone is not trusted: the live install reports it because it began as
 * a staging clone. Staging copies set VELORIA_NOINDEX or "Discourage search engines".
 */
function veloria_noindex_environment(): bool {
	return in_array( wp_get_environment_type(), array( 'local', 'development' ), true )
		|| ( defined( 'VELORIA_NOINDEX' ) && VELORIA_NOINDEX )
		|| '0' === (string) get_option( 'blog_public' );
}

add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		if ( veloria_noindex_environment() ) {
			unset( $robots['index'], $robots['follow'] );
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	},
	99
);

add_action(
	'send_headers',
	static function (): void {
		if ( veloria_noindex_environment() && ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}
	}
);

const VELORIA_SITE_NAME     = 'Veloria Vault';
const VELORIA_DEFAULT_TITLE = 'Veloria Vault | Luxury Leather Handbags';
const VELORIA_DEFAULT_DESC  = 'Timeless leather goods for the modern minimalist. Handcrafted genuine leather handbags, totes, satchels and clutches.';
const VELORIA_OG_DESC       = 'Timeless leather goods for the modern minimalist.';

function veloria_strip_html( string $html ): string {
	return trim( (string) preg_replace( '/\s+/', ' ', (string) preg_replace( '/<[^>]*>/', ' ', $html ) ) );
}

/** Absolute URL on the canonical origin without trailing slash ("/" → origin). */
function veloria_abs( string $path ): string {
	$origin = untrailingslashit( home_url() );
	return '/' === $path ? $origin : $origin . $path;
}

/** Root Open Graph block from src/app/layout.tsx. */
function veloria_root_og(): array {
	return array(
		'og:title'        => VELORIA_DEFAULT_TITLE,
		'og:description'  => VELORIA_OG_DESC,
		'og:url'          => veloria_abs( '/' ),
		'og:site_name'    => VELORIA_SITE_NAME,
		'og:locale'       => 'en_IN',
		'og:image'        => veloria_theme_asset( 'images/og-image.jpg' ),
		'og:image:width'  => '1200',
		'og:image:height' => '630',
		'og:image:alt'    => 'Veloria Vault - Luxury Leather Handbags',
		'og:type'         => 'website',
	);
}

/**
 * Resolve the metadata for the current request.
 *
 * @return array{title:string,description:string,canonical:?string,og:array<string,string>}
 */
function veloria_seo_meta(): array {
	$meta = array(
		'title'       => VELORIA_DEFAULT_TITLE,
		'description' => VELORIA_DEFAULT_DESC,
		'canonical'   => null,
		'og'          => veloria_root_og(),
	);
	$page_meta = static function ( string $title, string $description, string $path, array $og ) use ( &$meta ): void {
		$meta['title']       = $title . ' | ' . VELORIA_SITE_NAME;
		$meta['description'] = $description;
		$meta['canonical']   = veloria_abs( $path );
		$meta['og']          = $og;
	};

	if ( is_front_page() ) {
		$meta['canonical'] = veloria_abs( '/' );
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$page_meta(
			'Shop',
			"Browse Veloria Vault's collection of luxury leather handbags, totes, satchels, and accessories.",
			'/shop',
			array(
				'og:title'        => 'Shop | Veloria Vault',
				'og:description'  => 'Browse our collection of luxury leather handbags, totes, satchels, and accessories.',
				'og:url'          => veloria_abs( '/shop' ),
				'og:image'        => veloria_theme_asset( 'images/covers/shop.png' ),
				'og:image:width'  => '1200',
				'og:image:height' => '630',
				'og:image:alt'    => 'Shop Veloria Vault',
			)
		);
	} elseif ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		$name = $term instanceof WP_Term ? html_entity_decode( $term->name, ENT_QUOTES ) : '';
		$desc = $term instanceof WP_Term ? veloria_strip_html( (string) $term->description ) : '';
		$desc = '' !== $desc ? $desc : 'Shop handcrafted genuine leather ' . strtolower( $name ) . ' at Veloria Vault.';
		$path = '/product-category/' . ( $term instanceof WP_Term ? $term->slug : '' );
		$page_meta(
			$name,
			$desc,
			$path,
			array(
				'og:title'       => $name . ' | Veloria Vault',
				'og:description' => $desc,
				'og:url'         => veloria_abs( $path ),
			)
		);
	} elseif ( is_singular( 'product' ) ) {
		$product = Catalog::instance()->product( (int) get_queried_object_id() );
		if ( $product ) {
			$desc = veloria_strip_html( $product['short_description'] ?: $product['description'] );
			$path = '/product/' . $product['slug'];
			$og   = array(
				'og:title'       => html_entity_decode( $product['name'], ENT_QUOTES ),
				'og:description' => $desc,
				'og:url'         => veloria_abs( $path ),
			);
			if ( ! empty( $product['images'][0]['src'] ) ) {
				$og['og:image'] = $product['images'][0]['src'];
			}
			$page_meta( html_entity_decode( $product['name'], ENT_QUOTES ), $desc, $path, $og );
		}
	} elseif ( is_page() ) {
		$template = get_page_template_slug();
		$slug     = (string) get_post_field( 'post_name', get_queried_object_id() );
		if ( 'page-templates/about.php' === $template ) {
			$desc = 'Learn about Veloria Vault - timeless leather goods for the modern minimalist.';
			$page_meta(
				'About Us',
				$desc,
				'/about',
				array(
					'og:title'        => 'About Us | Veloria Vault',
					'og:description'  => $desc,
					'og:url'          => veloria_abs( '/about' ),
					'og:image'        => veloria_theme_asset( 'images/covers/about.png' ),
					'og:image:width'  => '1200',
					'og:image:height' => '630',
					'og:image:alt'    => 'About Veloria Vault',
				)
			);
		} elseif ( 'page-templates/contact.php' === $template ) {
			$page_meta(
				'Contact Us',
				'Get in touch with Veloria Vault for any questions, support, or inquiries about our luxury leather handbags.',
				'/contact-us',
				array(
					'og:title'        => 'Contact Us | Veloria Vault',
					'og:description'  => 'Get in touch with Veloria Vault for any questions, support, or inquiries.',
					'og:url'          => veloria_abs( '/contact-us' ),
					'og:image'        => veloria_theme_asset( 'images/covers/contact us.png' ),
					'og:image:width'  => '1200',
					'og:image:height' => '630',
					'og:image:alt'    => 'Contact Veloria Vault',
				)
			);
		} elseif ( 'page-templates/legal.php' === $template ) {
			// Title + description only; Open Graph inherited from the root.
			$legal = array(
				'cancellation-refund' => array( 'Cancellation & Refund Policy', 'Learn about our cancellation and refund policies.' ),
				'privacy-policy'      => array( 'Privacy Policy', 'Learn how we collect, use, and safeguard your personal information.' ),
				'refund-returns'      => array( 'Refund and Returns Policy', 'Read our comprehensive refund and returns policy.' ),
				'shipping-policy'     => array( 'Shipping Policy', 'Learn about our shipping procedures, timelines, and delivery terms.' ),
				'terms-conditions'    => array( 'Terms & Conditions', 'Read the terms and conditions governing the use of our website.' ),
				'warranty-policy'     => array( 'Warranty Policy', 'Learn about our Lifetime Service Warranty and what it covers.' ),
			);
			$title = $legal[ $slug ][0] ?? html_entity_decode( get_the_title(), ENT_QUOTES );
			$desc  = $legal[ $slug ][1] ?? ( has_excerpt() ? get_the_excerpt() : VELORIA_DEFAULT_DESC );
			$meta['title']       = $title . ' | ' . VELORIA_SITE_NAME;
			$meta['description'] = $desc;
		}
		// Cart, checkout, account, wishlist, login, register, track-order keep root
		// metadata with no canonical; only the title says which page it is.
		$utility = array(
			'cart'        => 'Shopping Cart',
			'checkout'    => 'Checkout',
			'account'     => 'My Account',
			'my-account'  => 'My Account',
			'wishlist'    => 'My Wishlist',
			'login'       => 'Sign In',
			'register'    => 'Create Account',
			'track-order' => 'Track Your Order',
		);
		if ( isset( $utility[ $slug ] ) ) {
			$meta['title'] = $utility[ $slug ] . ' | ' . VELORIA_SITE_NAME;
		}
	} elseif ( is_404() ) {
		$meta['title'] = 'Page Not Found | ' . VELORIA_SITE_NAME;
	}

	return apply_filters( 'veloria_seo_meta', $meta );
}

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		return veloria_seo_enabled() ? veloria_seo_meta()['title'] : $title;
	},
	20
);

add_action(
	'wp',
	static function (): void {
		if ( veloria_seo_enabled() ) {
			remove_action( 'wp_head', 'rel_canonical' );
			remove_action( 'wp_head', 'wp_robots', 1 );
		}
	}
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! veloria_seo_enabled() ) {
			return;
		}
		$meta = veloria_seo_meta();
		printf( '<meta name="description" content="%s"/>' . "\n", esc_attr( $meta['description'] ) );
		if ( veloria_noindex_environment() ) {
			echo '<meta name="robots" content="noindex, nofollow"/>' . "\n";
			echo '<meta name="googlebot" content="noindex, nofollow"/>' . "\n";
		} else {
			echo '<meta name="robots" content="index, follow"/>' . "\n";
			echo '<meta name="googlebot" content="index, follow, max-video-preview:-1, max-image-preview:large, max-snippet:-1"/>' . "\n";
		}
		if ( $meta['canonical'] ) {
			printf( '<link rel="canonical" href="%s"/>' . "\n", esc_url( $meta['canonical'] ) );
		}
		foreach ( $meta['og'] as $property => $content ) {
			printf( '<meta property="%s" content="%s"/>' . "\n", esc_attr( $property ), esc_attr( (string) $content ) );
		}
		echo '<meta name="twitter:card" content="summary_large_image"/>' . "\n";
		printf( '<meta name="twitter:title" content="%s"/>' . "\n", esc_attr( VELORIA_DEFAULT_TITLE ) );
		printf( '<meta name="twitter:description" content="%s"/>' . "\n", esc_attr( VELORIA_OG_DESC ) );
		printf( '<meta name="twitter:image" content="%s"/>' . "\n", esc_url( veloria_theme_asset( 'images/og-image.jpg' ) ) );

		$organization = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'Organization',
			'name'         => VELORIA_SITE_NAME,
			'url'          => veloria_abs( '/' ),
			'logo'         => veloria_theme_asset( 'logo.svg' ),
			'description'  => VELORIA_DEFAULT_DESC,
			'sameAs'       => array_values( array_filter( array( veloria_setting( 'instagram_url' ), veloria_setting( 'facebook_url' ) ) ) ),
			'contactPoint' => array(
				'@type'       => 'ContactPoint',
				'telephone'   => (string) veloria_setting( 'support_phone', '+91-7376326666' ),
				'contactType' => 'customer service',
				'email'       => (string) veloria_setting( 'support_email', 'care@veloriavault.com' ),
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	},
	3
);
