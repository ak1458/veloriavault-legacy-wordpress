<?php
/**
 * Store settings with code-level defaults.
 *
 * Every value that was hard-coded in the Next.js storefront lives here with the
 * live value as its default, so a fresh install behaves exactly like the
 * headless site. Saved values (option `veloria_commerce_settings`) are merged
 * over the defaults key by key, which keeps upgrades safe: a new setting added
 * in a later version simply falls back to its default.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION = 'veloria_commerce_settings';
	private const CANCEL_POLICY_VERSION = 2;

	/** @var array<string,mixed>|null */
	private static ?array $cache = null;

	/**
	 * Defaults mirror the live headless storefront (src/types/coupon.ts,
	 * src/config/*.ts) as of commit 02a2eea.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			// ── Pricing (src/types/coupon.ts, src/lib/coupon-calculator.ts) ──
			'pricing_mode'            => 'veloria',
			'coupon_mode'             => 'veloria',
			'tier_rules'              => array(
				array( 'min_qty' => 1, 'percent' => 10 ),
				array( 'min_qty' => 2, 'percent' => 15 ),
			),
			'prepaid_percent'         => 5,
			'max_discount_percent'    => 35,
			'cod_fee'                 => 149,
			'free_shipping_threshold' => 0,
			'standard_shipping_cost'  => 0,
			'cod_gateway'             => 'cod',
			'prepaid_gateway_ids'     => array( 'razorpay' ),
			'lucky_draw_enabled'      => false,
			'override_shipping'       => true,

			// ── Offer copy (src/config/offers.ts) ──
			'announcement_messages'   => array(
				'Buy 1 Get 10% Off',
				'Buy 2 Get 15% Off',
				'Extra 5% Off on Prepaid Orders',
			),
			'offer_tier_labels'       => array( 'Buy 1 bag', 'Buy 2 or more' ),
			'prepaid_offer_text'      => 'Extra 5% off on prepaid orders',

			// ── Product page (src/config/product-page.ts) ──
			'product_promo'           => array(
				'eyebrow' => 'Special offer',
				'items'   => array(
					array(
						'title'       => 'Buy 1 - Enjoy 10% Off',
						'description' => 'Automatically applied to one bag.',
					),
					array(
						'title'       => 'Buy 2 - Get 15% Off',
						'description' => 'The best automatic bag offer in the current checkout flow.',
					),
					array(
						'title'       => 'Extra 5% Off On Prepaid',
						'description' => 'Works automatically with UPI, credit/debit cards, and net banking.',
					),
				),
			),
			'policy_links'            => array(
				'returnExchange' => '/refund-returns/',
				'warranty'       => '/warranty-policy/',
			),
			'trust_badges'            => array(
				array( 'icon' => 'leather', 'title' => 'Genuine Full-Grain', 'subtitle' => 'Leather' ),
				array( 'icon' => 'craft', 'title' => 'Handcrafted in India', 'subtitle' => '' ),
				array( 'icon' => 'secure', 'title' => 'Secured Checkout', 'subtitle' => '' ),
				array( 'icon' => 'ethical', 'title' => 'Responsibly Produced', 'subtitle' => '' ),
				array( 'icon' => 'packaging', 'title' => 'Premium Plastic Free', 'subtitle' => 'Packaging' ),
				array( 'icon' => 'returns', 'title' => '7 Days From Delivery', 'subtitle' => 'Return to Original Payment' ),
			),

			// ── Homepage (src/config/hot-sellers.ts, most-loved.ts, LegacyHomePage.tsx) ──
			'hot_seller_label'        => 'Hot Seller',
			'hot_seller_title'        => 'Structured designs that carry essentials and confidence.',
			'hot_seller_ids'          => array( 3459, 3137, 3692, 3734 ),
			'hot_seller_backup_terms' => array( array( 'freya' ), array( 'amara' ), array( 'vanya' ), array( 'vivian' ) ),
			'most_loved'              => array(
				'eyebrow'     => 'Most Loved Styles',
				'title'       => 'Choose the silhouette that fits her rhythm.',
				'label_color' => '#856b35',
				'tabs'        => array(
					array(
						'slug'          => 'tote-bag',
						'label'         => 'Tote',
						'categorySlug'  => 'tote-bag',
						'allowedColors' => array( 'black', 'brown', 'burgundy', 'tan' ),
						'productIds'    => array( 3003, 3459, 3137 ),
						'maxItems'      => 8,
						'viewAllHref'   => '/product-category/tote-bag',
					),
					array(
						'slug'          => 'satchel-bag',
						'label'         => 'Satchel',
						'categorySlug'  => 'satchel-bag',
						'allowedColors' => array( 'black', 'tan', 'cherry', 'brown' ),
						'productIds'    => array(),
						'maxItems'      => 8,
						'viewAllHref'   => '/product-category/satchel-bag',
					),
					array(
						'slug'          => 'sling-bag',
						'label'         => 'Sling',
						'categorySlug'  => 'sling-bag',
						'allowedColors' => array( 'black', 'tan', 'chocolate brown', 'burgundy' ),
						'productIds'    => array( 3692, 3734 ),
						'maxItems'      => 8,
						'viewAllHref'   => '/product-category/sling-bag',
					),
					array(
						'slug'          => 'crossbody',
						'label'         => 'Crossbody',
						'categorySlug'  => 'crossbody',
						'allowedColors' => array( 'black', 'tan', 'brown', 'teal green' ),
						'productIds'    => array(),
						'maxItems'      => 8,
						'viewAllHref'   => '/product-category/crossbody',
					),
				),
			),
			'spotlight'               => array(
				'eyebrow'      => 'Designed For Everyday Elegance',
				'title'        => 'Donna Tote',
				'image'        => '/wp-content/uploads/2026/01/Bag-16-4-scaled.jpg',
				'search_terms' => array( array( 'donna', 'chocolate brown' ), array( 'donna' ) ),
				'paragraphs'   => array(
					'Donna is designed to be that effortless companion you reach for without thinking. It moves through errands, coffee runs, casual Fridays, and easy evenings out with the same quiet confidence.',
					'Structured enough for work, relaxed enough for everyday wear, and finished in premium leather that ages beautifully.',
				),
			),
			'instagram_live'          => false,

			// Brand and homepage presentation (defaults preserve the existing design).
			'footer_tagline'          => 'Timeless leather goods for the modern minimalist. Handcrafted with precision and designed to last a lifetime.',
			'instagram_url'           => 'https://www.instagram.com/veloriavault/',
			'facebook_url'            => 'https://www.facebook.com/p/Veloria-Vault-61568488662553/',
			'hero_slides'             => array(
				array( 'image' => '/images/hero/Ultra_realistic_luxury_202603301304.png', 'alt' => 'Premium leather handbag collection' ),
				array( 'image' => '/images/hero/Ultra_realistic_luxury_202603301304 (1).png', 'alt' => 'Luxury tote bag with model' ),
				array( 'image' => '/images/hero/Ultra_realistic_luxury_202603301309.png', 'alt' => 'Black leather bag with gold chain' ),
				array( 'image' => '/images/hero/Ultra_realistic_luxury_202603301309 (1).png', 'alt' => 'Close-up of Veloria Vault logo' ),
			),
			'home_features'           => array(
				array( 'title' => 'Genuine Leather', 'description' => 'Carefully selected natural hides with durability, flexibility, and a refined finish.', 'icon' => 'leather' ),
				array( 'title' => 'Designed With Intent', 'description' => 'Every curve, cut, and contour is shaped with purpose so beauty never fights function.', 'icon' => 'design' ),
				array( 'title' => 'Hand Crafted In India', 'description' => 'Built to move through daily life while holding shape, softness, and structure.', 'icon' => 'hammer' ),
			),
			'home_policies'           => array(
				array( 'title' => 'Hassle-Free Replacements', 'description' => 'Contact support for easy replacements.', 'icon' => 'refresh-cw' ),
				array( 'title' => 'Free Shipping', 'description' => '{shipping}.', 'icon' => 'truck' ),
				array( 'title' => 'Secure Checkout', 'description' => 'Protected payment flow and verified transactions.', 'icon' => 'shield-check' ),
			),

			// ── Contact / support ──
			'support_email'           => 'care@veloriavault.com',
			'support_phone'           => '+91-7376326666',
			'newsletter_inbox'        => '',

			// ── Legal disclosures (blank until the owner supplies them; nothing invented) ──
			'legal_name'              => '',
			'legal_address'           => '',
			'gstin'                   => '',
			'grievance_name'          => '',
			'grievance_designation'   => '',
			'grievance_email'         => '',
			'grievance_phone'         => '',
			'country_of_origin'       => 'India',

			// ── Orders (src/lib/orders.ts) ──
			'cancel_window_hours'     => 48,
			'cancel_policy_version'   => self::CANCEL_POLICY_VERSION,
			'return_window_days'      => 7,

			// ── Integrations ──
			'shiprocket_mode'         => 'off', // off | custom. "custom" = port of lib/shiprocket.ts.
			'shiprocket_pickup'       => 'Primary',
			'shiprocket_channel_id'   => '',
			// Store URL the official Shiprocket channel was registered with (sent as the
			// webhook source). Empty once the channel's store URL is updated to www.
			'shiprocket_webhook_source' => 'https://api.veloriavault.com/',

			// ── Domain, SEO, security ──
			'canonical_host'          => 'www.veloriavault.com',
			'legacy_api_host'         => 'api.veloriavault.com',
			'redirect_api_host'       => false,
			'security_headers'        => true,
			'csp_mode'                => 'report', // off | report | enforce. Enforce only once reports are clean.
			'csp_extra_sources'       => '',
			'block_suspicious'        => true,
			'hsts_subdomains'         => false, // Adds includeSubDomains. Hard to undo.
			'hsts_preload'            => false, // Adds preload (needs hsts_subdomains). Effectively irreversible.
			'seo_enabled'             => true,
			'serve_sitemap'           => true,
		);
	}

	/** @return array<string,mixed> */
	public static function all(): array {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
			// The owner-approved 48-hour policy replaces the old saved 24-hour
			// default on read. Loading an upgrade never writes store options.
			if ( ! is_array( $saved ) || (int) ( $saved['cancel_policy_version'] ?? 0 ) < self::CANCEL_POLICY_VERSION ) {
				self::$cache['cancel_window_hours'] = 48;
				self::$cache['cancel_policy_version'] = self::CANCEL_POLICY_VERSION;
			}
		}
		return self::$cache;
	}

	/**
	 * @param mixed $fallback Returned when the key is unknown.
	 * @return mixed
	 */
	public static function get( string $key, $fallback = null ) {
		$all = self::all();
		$value = array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
		$value = self::resolve( $key, $value );
		/**
		 * Filter a single Veloria setting at read time.
		 *
		 * @param mixed  $value
		 * @param string $key
		 */
		return apply_filters( 'veloria_setting', $value, $key );
	}

	/**
	 * Read-time guards for values that point into a specific database or host
	 * (the defaults carry live product IDs, an uploads path and root-relative
	 * links). On another install they degrade gracefully instead of breaking.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private static function resolve( string $key, $value ) {
		if ( ! function_exists( 'get_post_status' ) || ! is_array( $value ) ) {
			return $value;
		}
		switch ( $key ) {
			case 'hot_seller_ids':
				// Missing products become 0 so each slot keeps its position and the
				// theme falls back to the matching hot_seller_backup_terms entry.
				return array_map( static fn( $id ) => self::published_product( $id ) ? (int) $id : 0, $value );
			case 'most_loved':
				if ( isset( $value['tabs'] ) && is_array( $value['tabs'] ) ) {
					foreach ( $value['tabs'] as $i => $tab ) {
						if ( is_array( $tab ) && isset( $tab['productIds'] ) && is_array( $tab['productIds'] ) ) {
							$value['tabs'][ $i ]['productIds'] = array_values( array_filter( $tab['productIds'], array( self::class, 'published_product' ) ) );
						}
					}
				}
				return $value;
			case 'spotlight':
				// An uploads path that does not exist here is dropped; the theme then
				// uses its bundled fallback image.
				$image = (string) ( $value['image'] ?? '' );
				if ( str_starts_with( $image, '/wp-content/uploads/' ) && defined( 'WP_CONTENT_DIR' ) ) {
					$file = WP_CONTENT_DIR . '/uploads/' . substr( $image, strlen( '/wp-content/uploads/' ) );
					if ( str_contains( $image, '..' ) || ! is_file( $file ) ) {
						$value['image'] = '';
					}
				}
				return $value;
			case 'policy_links':
				// Root-relative links are resolved against this install's home URL.
				foreach ( $value as $name => $link ) {
					if ( is_string( $link ) && str_starts_with( $link, '/' ) && ! str_starts_with( $link, '//' ) ) {
						$value[ $name ] = home_url( $link );
					}
				}
				return $value;
		}
		return $value;
	}

	/** @param mixed $id */
	public static function published_product( $id ): bool {
		static $seen = array();
		$id = absint( $id );
		if ( $id <= 0 ) {
			return false;
		}
		if ( ! isset( $seen[ $id ] ) ) {
			$seen[ $id ] = 'product' === get_post_type( $id ) && 'publish' === get_post_status( $id );
		}
		return $seen[ $id ];
	}

	/** @param array<string,mixed> $values */
	public static function save( array $values ): void {
		if ( array_key_exists( 'cancel_window_hours', $values ) ) {
			$values['cancel_policy_version'] = self::CANCEL_POLICY_VERSION;
		}
		$saved = get_option( self::OPTION, array() );
		$saved = array_merge( is_array( $saved ) ? $saved : array(), $values );
		update_option( self::OPTION, $saved, true );
		self::$cache = null;
	}

	public static function flush(): void {
		self::$cache = null;
	}

	/**
	 * Secrets come from wp-config.php constants first (never stored in the DB),
	 * then from the option, then from the Razorpay plugin settings.
	 */
	public static function secret( string $constant, string $option_key = '' ): string {
		if ( defined( $constant ) && is_string( constant( $constant ) ) ) {
			return trim( (string) constant( $constant ) );
		}
		if ( '' !== $option_key ) {
			$value = self::get( $option_key, '' );
			return is_string( $value ) ? trim( $value ) : '';
		}
		return '';
	}

	public static function support_email(): string {
		$email = (string) self::get( 'support_email', 'care@veloriavault.com' );
		return is_email( $email ) ? $email : (string) get_option( 'admin_email' );
	}

	public static function newsletter_inbox(): string {
		$inbox = (string) self::get( 'newsletter_inbox', '' );
		return is_email( $inbox ) ? $inbox : self::support_email();
	}
}
