<?php
/**
 * Front-end assets. One compiled stylesheet (Tailwind v4) and one script
 * bundle (Alpine.js + GSAP + storefront modules), built by `npm run build`
 * into assets/dist and committed, so deployments need no Node.js.
 * Files are versioned by modification time for cache busting.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

function veloria_asset_version( string $relative ): string {
	$path = VELORIA_THEME_DIR . '/' . $relative;
	return file_exists( $path ) ? (string) filemtime( $path ) : VELORIA_THEME_VERSION;
}

/**
 * Return a safe, existing file path below a known root.
 *
 * @param string $root Root directory that must contain the file.
 * @param string $relative Relative path below the root.
 */
function veloria_safe_relative_file( string $root, string $relative ): ?string {
	$relative = ltrim( str_replace( '\\', '/', $relative ), '/' );
	if ( '' === $relative || str_contains( $relative, "\0" ) ) {
		return null;
	}

	$parts = explode( '/', $relative );
	foreach ( $parts as $part ) {
		if ( '' === $part || '.' === $part || '..' === $part ) {
			return null;
		}
	}

	$root_real = realpath( $root );
	$file_real = realpath( $root . '/' . implode( '/', $parts ) );
	if ( false === $root_real || false === $file_real || ! is_file( $file_real ) ) {
		return null;
	}

	$prefix = rtrim( $root_real, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
	if ( ! str_starts_with( $file_real, $prefix ) ) {
		return null;
	}

	return implode( '/', $parts );
}

/** Encode a safe relative path while preserving URL path separators. */
function veloria_encode_asset_path( string $relative ): string {
	return str_replace( '%2F', '/', rawurlencode( $relative ) );
}

/**
 * Resolve a file shipped in the theme's public asset tree.
 *
 * @param string $relative Relative path below public/.
 * @param string $fallback Relative fallback below public/.
 */
function veloria_theme_asset( string $relative, string $fallback = 'images/bag-placeholder.svg' ): string {
	$resolved = veloria_safe_relative_file( VELORIA_THEME_DIR . '/public', $relative );
	if ( null !== $resolved ) {
		return VELORIA_THEME_URI . '/public/' . veloria_encode_asset_path( $resolved );
	}

	$resolved_fallback = veloria_safe_relative_file( VELORIA_THEME_DIR . '/public', $fallback );
	if ( null !== $resolved_fallback ) {
		return VELORIA_THEME_URI . '/public/' . veloria_encode_asset_path( $resolved_fallback );
	}

	return VELORIA_THEME_URI . '/public/images/bag-placeholder.svg';
}

/**
 * Convert a local or same-origin upload URL to a safe relative upload path.
 *
 * @return string|null Null means the value is not a local upload URL.
 */
function veloria_upload_relative( string $value ): ?string {
	$path = $value;
	if ( preg_match( '#^https?://#i', $value ) ) {
		$parsed = parse_url( $value );
		$home   = parse_url( home_url( '/' ) );
		if ( ! is_array( $parsed ) || ! is_array( $home ) || empty( $parsed['host'] ) || empty( $home['host'] ) ) {
			return null;
		}
		$parsed_port = (int) ( $parsed['port'] ?? ( 'https' === strtolower( (string) ( $parsed['scheme'] ?? '' ) ) ? 443 : 80 ) );
		$home_port   = (int) ( $home['port'] ?? ( 'https' === strtolower( (string) ( $home['scheme'] ?? '' ) ) ? 443 : 80 ) );
		if ( strtolower( (string) $parsed['host'] ) !== strtolower( (string) $home['host'] ) || $parsed_port !== $home_port ) {
			return null;
		}
		$path = (string) ( $parsed['path'] ?? '' );
	}

	if ( ! str_starts_with( $path, '/wp-content/uploads/' ) ) {
		return null;
	}

	return ltrim( substr( $path, strlen( '/wp-content/uploads/' ) ), '/' );
}

/**
 * Resolve a configured image from an absolute URL, a local WordPress upload,
 * or the theme's public asset tree. Missing values use a bundled fallback.
 *
 * @param string $value Image URL or site-relative path.
 * @param string $fallback Relative fallback below public/.
 */
function veloria_asset_url( string $value, string $fallback = 'images/bag-placeholder.svg' ): string {
	$value = trim( $value );
	$upload_relative = veloria_upload_relative( $value );
	if ( null !== $upload_relative ) {
		$resolved_upload = veloria_safe_relative_file( WP_CONTENT_DIR . '/uploads', $upload_relative );
		if ( null !== $resolved_upload ) {
			return content_url( '/uploads/' . veloria_encode_asset_path( $resolved_upload ) );
		}
		return veloria_theme_asset( $fallback );
	}

	if ( preg_match( '#^https?://#i', $value ) ) {
		return $value;
	}

	$relative = preg_replace( '#^/(?:public/)?#', '', $value );
	return veloria_theme_asset( $relative ?: $fallback, $fallback );
}

/**
 * Like veloria_asset_url(), but returns a copy sized for display instead of the
 * multi-megabyte original: an upload resolves to the attachment's registered
 * image size, a theme photo to its pre-built "<name>-<width>.webp" sibling.
 *
 * @param string $size Registered image size, e.g. 'medium_large' (768) or '1536x1536'.
 */
function veloria_sized_asset_url( string $value, string $size = 'large', string $fallback = 'images/bag-placeholder.svg' ): string {
	$url = veloria_asset_url( $value, $fallback );
	if ( str_starts_with( $url, content_url( '/uploads/' ) ) ) {
		$id    = attachment_url_to_postid( $url );
		$sized = $id ? wp_get_attachment_image_url( $id, $size ) : false;
		return $sized ?: $url;
	}
	$relative = ltrim( (string) preg_replace( '#^/(?:public/)?#', '', trim( $value ) ), '/' );
	if ( preg_match( '#^(.+)\.(?:png|jpe?g)$#i', $relative, $match ) ) {
		$width = in_array( $size, array( 'thumbnail', 'medium', 'medium_large', 'woocommerce_thumbnail' ), true ) ? 900 : 1920;
		if ( null !== veloria_safe_relative_file( VELORIA_THEME_DIR . '/public', $match[1] . '-' . $width . '.webp' ) ) {
			return veloria_theme_asset( $match[1] . '-' . $width . '.webp', $fallback );
		}
	}
	return $url;
}

/**
 * srcset for a theme photo that has pre-built "<name>-900.webp" / "<name>-1920.webp"
 * siblings; empty when they are missing (the caller keeps its plain src).
 */
function veloria_theme_srcset( string $relative ): string {
	if ( ! preg_match( '#^(.+)\.(?:png|jpe?g)$#i', ltrim( $relative, '/' ), $match ) ) {
		return '';
	}
	$set = array();
	foreach ( array( 900, 1920 ) as $width ) {
		if ( null === veloria_safe_relative_file( VELORIA_THEME_DIR . '/public', "{$match[1]}-{$width}.webp" ) ) {
			return '';
		}
		$set[] = veloria_theme_asset( "{$match[1]}-{$width}.webp" ) . " {$width}w";
	}
	return implode( ', ', $set );
}

/**
 * Drop render-blocking extension assets on pages that never use them.
 * Cart, checkout, account and shop/product pages keep everything (native
 * forms, fragments, gateways, the WooCommerce gallery used by Elementor widgets).
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_woocommerce' ) ) {
			return;
		}
		if ( ! is_product() ) {
			// The theme gallery has its own zoom; PhotoSwipe only serves the native product gallery.
			wp_dequeue_style( 'photoswipe' );
			wp_dequeue_style( 'photoswipe-default-skin' );
			wp_dequeue_script( 'wc-photoswipe' );
			wp_dequeue_script( 'wc-photoswipe-ui-default' );
			// Customer Reviews styles only its product-page widgets.
			wp_dequeue_style( 'cr-frontend-css' );
		}
		// Content pages (home, about, contact, policies) render no WooCommerce markup.
		$content_page = is_front_page() || is_page( array( 'about', 'contact-us', 'privacy-policy', 'terms-conditions', 'shipping-policy', 'refund-returns', 'cancellation-refund', 'warranty-policy' ) );
		if ( $content_page ) {
			wp_dequeue_style( 'woocommerce-general' );
			wp_dequeue_style( 'woocommerce-layout' );
			wp_dequeue_style( 'woocommerce-smallscreen' );
		}
		// Shared WooCommerce helpers need not block the first paint; WordPress
		// keeps a script blocking automatically when inline code depends on it.
		foreach ( array( 'wc-add-to-cart', 'woocommerce', 'wc-js-cookie', 'wc-jquery-blockui' ) as $handle ) {
			wp_script_add_data( $handle, 'strategy', 'defer' );
		}
	},
	100
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		// Keep WooCommerce and extension assets: native forms and Elementor widgets
		// rely on their country fields, checkout fragments and gateway scripts.

		wp_enqueue_style( 'veloria-app', VELORIA_THEME_URI . '/assets/dist/app.css', array(), veloria_asset_version( 'assets/dist/app.css' ) );
		wp_enqueue_script( 'veloria-app', VELORIA_THEME_URI . '/assets/dist/app.js', array( 'jquery' ), veloria_asset_version( 'assets/dist/app.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );

		$prepaid_gateway = 'razorpay';
		if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
			foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
				if ( veloria_setting( 'cod_gateway', 'cod' ) !== $id ) {
					$prepaid_gateway = $id;
					break;
				}
			}
		}

		wp_localize_script(
			'veloria-app',
			'veloriaConfig',
			array(
				'home'           => home_url( '/' ),
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'restUrl'        => esc_url_raw( rest_url( 'veloria/v1/' ) ),
				'storeUrl'       => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
				'checkoutAjax'   => WC_AJAX::get_endpoint( 'checkout' ),
				'checkoutNonce'  => wp_create_nonce( 'woocommerce-process_checkout' ),
				'prepaidGateway' => $prepaid_gateway,
				'codGateway'     => (string) veloria_setting( 'cod_gateway', 'cod' ),
				'codFee'         => (float) veloria_setting( 'cod_fee', 149 ),
				'announcements'  => array_values( (array) veloria_setting( 'announcement_messages', array() ) ),
				'urls'           => array(
					'shop'     => home_url( '/shop' ),
					'cart'     => home_url( '/cart' ),
					'checkout' => home_url( '/checkout' ),
					'account'  => home_url( '/account' ),
					'login'    => home_url( '/login' ),
					'track'    => home_url( '/track-order' ),
				),
				'placeholder'    => veloria_theme_asset( 'images/bag-placeholder.svg' ),
			)
		);
	},
	20
);

// Self-hosted fonts: preload the faces used above the fold (next/font did the same).
add_action(
	'wp_head',
	static function (): void {
		foreach ( array( 'lato-latin-400-normal.woff2', 'playfair-display-latin-wght-normal.woff2' ) as $font ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( VELORIA_THEME_URI . '/assets/fonts/' . $font ) );
		}
	},
	1
);
