<?php
/**
 * WP-CLI tools for setting up and verifying the storefront.
 *
 *   wp veloria check                     Preflight: pages, permalinks, plugins, settings.
 *   wp veloria seed-pages [--apply] [--content-dir=<dir>]
 *                                        Create/rename pages to the live URLs (dry run by default).
 *   wp veloria seed-menus [--apply]      Editable header/footer menus from the current links (dry run by default).
 *   wp veloria parity --vectors=<file>   Compare the discount engine with TypeScript vectors.
 *   wp veloria reconcile-razorpay --from=<date> [--to=<date>]
 *                                        List captured Razorpay payments with no WooCommerce order (read-only).
 *   wp veloria newsletter-import <csv>   Import subscribers recovered from the inbox.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Cli;

use Veloria\Commerce\Admin\Notices;
use Veloria\Commerce\Newsletter\Newsletter;
use Veloria\Commerce\Payments\Razorpay;
use Veloria\Commerce\Pricing\DiscountEngine;
use Veloria\Commerce\Install;

defined( 'ABSPATH' ) || exit;

final class Commands {

	/**
	 * Pages the storefront needs: slug => [title, template, legacy slugs, WooCommerce option].
	 */
	private const PAGES = array(
		'home'                => array( 'Home', '', array(), '' ),
		'shop'                => array( 'Shop', '', array(), 'woocommerce_shop_page_id' ),
		'cart'                => array( 'Cart', '', array(), 'woocommerce_cart_page_id' ),
		'checkout'            => array( 'Checkout', '', array(), 'woocommerce_checkout_page_id' ),
		'account'             => array( 'My Account', '', array( 'my-account' ), 'woocommerce_myaccount_page_id' ),
		'track-order'         => array( 'Track Your Order', 'page-templates/track-order.php', array(), '' ),
		'wishlist'            => array( 'My Wishlist', 'page-templates/wishlist.php', array(), '' ),
		'login'               => array( 'Login', 'page-templates/login.php', array(), '' ),
		'register'            => array( 'Create Account', 'page-templates/register.php', array(), '' ),
		'about'               => array( 'About Us', 'page-templates/about.php', array(), '' ),
		'contact-us'          => array( 'Contact Us', 'page-templates/contact.php', array(), '' ),
		'privacy-policy'      => array( 'Privacy Policy', 'page-templates/legal.php', array(), '' ),
		'terms-conditions'    => array( 'Terms & Conditions', 'page-templates/legal.php', array(), '' ),
		'shipping-policy'     => array( 'Shipping Policy', 'page-templates/legal.php', array(), '' ),
		'refund-returns'      => array( 'Refund and Returns Policy', 'page-templates/legal.php', array( 'refund_returns' ), '' ),
		'cancellation-refund' => array( 'Cancellation & Refund Policy', 'page-templates/legal.php', array( 'cancellation-refund-policy' ), '' ),
		'warranty-policy'     => array( 'Warranty Policy', 'page-templates/legal.php', array(), '' ),
	);

	private const SHORTCODES = array(
		'woocommerce_cart_page_id'      => '[woocommerce_cart]',
		'woocommerce_checkout_page_id'  => '[woocommerce_checkout]',
		'woocommerce_myaccount_page_id' => '[woocommerce_my_account]',
	);

	/**
	 * Preflight checks.
	 */
	public function check(): void {
		$failed = 0;
		$problem = static function ( string $message ) use ( &$failed ): void {
			\WP_CLI::warning( $message );
			$failed++;
		};
		foreach ( Notices::problems() as $notice ) {
			$problem( $notice['message'] );
		}
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_orders' ) ) {
			\WP_CLI::error( 'WooCommerce must be active before checking the store.' );
		}
		foreach ( \Veloria\Commerce\Admin\HealthChecks::problems() as $message ) {
			$problem( $message );
		}
		if ( 'veloria' !== get_template() ) {
			$problem( 'The active theme must be Veloria (or a child of Veloria).' );
		}
		foreach ( Install::schema_problems() as $message ) {
			$problem( $message );
		}
		if ( get_option( 'veloria_commerce_upgrade_error' ) ) {
			$problem( 'A Veloria schema/maintenance error is recorded. Re-activate locally after resolving it.' );
		}
		foreach ( self::PAGES as $slug => $def ) {
			$page = get_page_by_path( $slug );
			if ( ! $page ) {
				\WP_CLI::warning( "Missing page /{$slug}" );
				$failed++;
				continue;
			}
			if ( 'publish' !== $page->post_status ) {
				$problem( "/{$slug} must be published." );
			}
			if ( $def[3] && (int) get_option( $def[3] ) !== (int) $page->ID ) {
				$problem( "{$def[3]} must point to /{$slug}." );
			}
			$builder = function_exists( 'get_post_meta' ) && 'yes' === get_post_meta( $page->ID, '_veloria_elementor_enabled', true );
			if ( ! $builder && isset( self::SHORTCODES[ $def[3] ] ) && trim( $page->post_content ) !== self::SHORTCODES[ $def[3] ] ) {
				$problem( "/{$slug} must use the classic WooCommerce shortcode." );
			}
			if ( ! $builder && 'page-templates/legal.php' === $def[1] ) {
				$file = VELORIA_COMMERCE_DIR . 'content/policies/' . $slug . '.html';
				$content = is_readable( $file ) ? file_get_contents( $file ) : false;
				if ( false === $content || '' === trim( $content ) || trim( $page->post_content ) !== trim( $content ) ) {
					$problem( "/{$slug} differs from the approved bundled policy content; review seed-pages dry run." );
				}
			}
			if ( ! $builder && $def[1] && get_page_template_slug( $page ) !== $def[1] ) {
				\WP_CLI::warning( "/{$slug} should use template {$def[1]}" );
				$failed++;
			}
		}
		if ( 'page' !== get_option( 'show_on_front' ) ) {
			\WP_CLI::warning( 'Front page should be a static page ("home").' );
			$failed++;
		}
		$home = get_page_by_path( 'home' );
		if ( ! $home || (int) get_option( 'page_on_front' ) !== (int) $home->ID ) {
			$problem( 'page_on_front must point to the published /home page.' );
		}
		$permalinks = (array) get_option( 'woocommerce_permalinks', array() );
		if ( '/%postname%' !== get_option( 'permalink_structure' ) || '/product' !== ( $permalinks['product_base'] ?? '' ) || 'product-category' !== ( $permalinks['category_base'] ?? '' ) ) {
			$problem( 'Permalink bases must match /%postname%, /product and product-category.' );
		}
		if ( 'INR' !== get_option( 'woocommerce_currency' ) || ! str_starts_with( (string) get_option( 'woocommerce_default_country' ), 'IN' ) ) {
			$problem( 'The storefront requires INR and an India store location.' );
		}
		if ( $failed ) {
			\WP_CLI::error( "{$failed} problem(s) found. This check does not verify backups, webhooks or provider delivery." );
		}
		\WP_CLI::success( 'Local configuration checks passed. Backups, providers, cache and browser journeys need separate evidence.' );
	}

	/**
	 * Create or rename storefront pages. Dry run unless --apply is given.
	 *
	 * ## OPTIONS
	 *
	 * [--apply]
	 * : Make the changes.
	 *
	 * [--content-dir=<dir>]
	 * : Approved <slug>.html policy files. Defaults to the bundled policies; every file must exist and contain text.
	 *
	 * [--overwrite-content]
	 * : Kept for compatibility. --apply now replaces differing policies after a recoverable original backup.
	 *
	 * @subcommand seed-pages
	 * @param array<int,string>    $args
	 * @param array<string,string> $assoc
	 */
	public function seed_pages( array $args, array $assoc ): void {
		$apply       = isset( $assoc['apply'] );
		$content_dir = isset( $assoc['content-dir'] ) ? rtrim( $assoc['content-dir'], '/\\' ) : VELORIA_COMMERCE_DIR . 'content/policies';
		$log         = static fn( string $m ) => \WP_CLI::log( ( $apply ? '' : '[dry run] ' ) . $m );
		$policies    = array();
		// Seeding renames slugs and replaces page content that a headless frontend may
		// still read from this database. Only allow it once the Veloria theme serves
		// the site. The environment type is not trusted: the live install reports
		// "staging" because it began as a staging clone.
		if ( $apply && ! \Veloria\Commerce\Plugin::storefront_active() ) {
			\WP_CLI::error( 'Refusing --apply while the Veloria theme is not active. Activate the theme first (on staging, or during the production cutover). Nothing was seeded.' );
		}
		// Validate all inputs before the first mutation, so a missing file cannot leave a half-seeded store.
		foreach ( self::PAGES as $slug => $definition ) {
			if ( 'page-templates/legal.php' !== $definition[1] ) {
				continue;
			}
			$file = $content_dir . '/' . $slug . '.html';
			$content = is_readable( $file ) ? file_get_contents( $file ) : false;
			if ( false === $content || '' === trim( wp_strip_all_tags( $content ) ) ) {
				\WP_CLI::error( "Missing or empty approved policy file: {$file}. Nothing was seeded." );
			}
			$policies[ $slug ] = $content;
		}
		if ( $apply && false === get_option( 'veloria_migration_original_options', false ) ) {
			$original = array();
			foreach ( array_merge( array( 'show_on_front', 'page_on_front', 'permalink_structure', 'woocommerce_permalinks' ), array_keys( self::SHORTCODES ), array( 'woocommerce_shop_page_id' ) ) as $option ) {
				$original[ $option ] = get_option( $option, null );
			}
			if ( ! add_option( 'veloria_migration_original_options', $original, '', false ) ) {
				\WP_CLI::error( 'Could not preserve original page/permalink options. Nothing was seeded.' );
			}
		}

		foreach ( self::PAGES as $slug => $def ) {
			list( $title, $template, $legacy, $wc_option ) = $def;

			$page = get_page_by_path( $slug );
			if ( ! $page && $wc_option && get_option( $wc_option ) ) {
				$page = get_post( (int) get_option( $wc_option ) );
				if ( $page && 'page' !== $page->post_type ) {
					\WP_CLI::error( "{$wc_option} points to an unexpected post type; review it before seeding." );
				}
			}
			foreach ( $legacy as $old ) {
				if ( ! $page ) {
					$page = get_page_by_path( $old );
				}
			}

			if ( $page && $apply ) {
				self::backup_page( $page );
			}
			if ( ! $page ) {
				$log( "create /{$slug} ({$title})" );
				if ( $apply ) {
					$id   = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug ), true );
					if ( is_wp_error( $id ) || ! $id ) {
						\WP_CLI::error( "Could not create /{$slug}. Review the preserved originals before retrying." );
					}
					$page = get_post( (int) $id );
				} else {
					$page = (object) array( 'ID' => 0, 'post_name' => $slug, 'post_status' => 'publish', 'post_content' => '' );
				}
			} elseif ( $page->post_name !== $slug ) {
				$log( "rename /{$page->post_name} → /{$slug}" );
				if ( $apply ) {
					self::update_page( array( 'ID' => $page->ID, 'post_name' => $slug ) );
				}
			}
			if ( ! $page ) {
				continue;
			}

			if ( 'publish' !== $page->post_status ) {
				$log( "publish /{$slug} (was {$page->post_status})" );
				if ( $apply ) {
					self::update_page( array( 'ID' => $page->ID, 'post_status' => 'publish' ) );
				}
			}

			if ( $template && ( ! $page->ID || get_page_template_slug( $page ) !== $template ) ) {
				$log( "template /{$slug} → {$template}" );
				if ( $apply ) {
					update_post_meta( $page->ID, '_wp_page_template', $template );
				}
			}

			if ( $wc_option && (int) get_option( $wc_option ) !== $page->ID ) {
				$log( "set {$wc_option} = {$page->ID}" );
				if ( $apply ) {
					update_option( $wc_option, $page->ID );
				}
			}

			if ( isset( self::SHORTCODES[ $wc_option ] ) && trim( $page->post_content ) !== self::SHORTCODES[ $wc_option ] ) {
				$log( "content /{$slug} → " . self::SHORTCODES[ $wc_option ] . ' (original kept in _veloria_migration_original)' );
				if ( $apply ) {
					self::update_page( array( 'ID' => $page->ID, 'post_content' => self::SHORTCODES[ $wc_option ] ) );
				}
			}

			if ( isset( $policies[ $slug ] ) ) {
				if ( trim( $page->post_content ) !== trim( $policies[ $slug ] ) ) {
					$log( "replace policy /{$slug} sha256=" . hash( 'sha256', $policies[ $slug ] ) . ' (original kept in _veloria_migration_original)' );
					if ( $apply ) {
						self::update_page( array( 'ID' => $page->ID, 'post_content' => $policies[ $slug ] ) );
					}
				} else {
					$log( "policy /{$slug} already matches approved content." );
				}
			}

			if ( 'home' === $slug && ( 'page' !== get_option( 'show_on_front' ) || (int) get_option( 'page_on_front' ) !== $page->ID ) ) {
				$log( "front page → /{$slug}" );
				if ( $apply ) {
					update_option( 'show_on_front', 'page' );
					update_option( 'page_on_front', $page->ID );
				}
			}
		}

		if ( '/%postname%' !== get_option( 'permalink_structure' ) ) {
			$log( 'permalink structure → /%postname%' );
		}
		$wc_permalinks = (array) get_option( 'woocommerce_permalinks', array() );
		if ( ( $wc_permalinks['product_base'] ?? '' ) !== '/product' || ( $wc_permalinks['category_base'] ?? '' ) !== 'product-category' ) {
			$log( 'WooCommerce bases → product: /product, category: product-category' );
		}
		if ( $apply ) {
			update_option( 'permalink_structure', '/%postname%' );
			$wc_permalinks['product_base']  = '/product';
			$wc_permalinks['category_base'] = 'product-category';
			update_option( 'woocommerce_permalinks', $wc_permalinks );
			// Regenerate on a fresh request, when WooCommerce registers its new bases.
			delete_option( 'rewrite_rules' );
			\WP_CLI::success( 'Pages seeded. Rewrite rules regenerate on the next request; wp rewrite flush --hard may be run in a separate invocation.' );
		} else {
			\WP_CLI::log( 'Nothing changed. Re-run with --apply to make these changes.' );
		}
	}

	/**
	 * Create editable menus (Appearance → Menus) holding the theme's current
	 * header and footer links, and assign them to the theme locations. Category
	 * and page links become real menu items, so renames never break them.
	 * Existing menus are never deleted; a previously assigned menu is only
	 * unassigned. Dry run unless --apply is given.
	 *
	 * ## OPTIONS
	 *
	 * [--apply]
	 * : Make the changes.
	 *
	 * @subcommand seed-menus
	 * @param array<int,string>    $args
	 * @param array<string,string> $assoc
	 */
	public function seed_menus( array $args, array $assoc ): void {
		$apply = ! empty( $assoc['apply'] );
		$log   = static fn( string $m ) => \WP_CLI::log( ( $apply ? '' : '[dry run] ' ) . $m );
		if ( ! function_exists( 'veloria_default_nav_items' ) ) {
			\WP_CLI::error( 'Activate the Veloria theme first; the default links come from it. Nothing was seeded.' );
		}
		$header = array();
		foreach ( veloria_default_nav_items() as $top ) {
			$children = array();
			foreach ( $top['columns'] ?? array() as $column ) {
				$children[] = array( 'label' => $column['title'], 'href' => '#', 'children' => $column['items'] );
			}
			$header[] = array( 'label' => $top['label'], 'href' => $top['href'], 'children' => $children );
		}
		$menus = array(
			'primary'      => array( 'Veloria Header', $header ),
			'footer'       => array( 'Veloria Footer - Collections', veloria_drop_empty_categories( veloria_default_footer_collections() ) ),
			'footer-links' => array( 'Veloria Footer - Important links', veloria_default_footer_links() ),
		);
		$locations = get_nav_menu_locations();
		foreach ( $menus as $location => list( $name, $links ) ) {
			$current = empty( $locations[ $location ] ) ? null : wp_get_nav_menu_object( (int) $locations[ $location ] );
			if ( $current && $current->name === $name ) {
				$log( "{$location}: already uses \"{$name}\"; left unchanged." );
				continue;
			}
			$log( "{$location}: create \"{$name}\" with " . count( $links ) . ' top-level links' . ( $current ? " (replacing assignment of \"{$current->name}\", which is kept)" : '' ) . '.' );
			if ( ! $apply ) {
				continue;
			}
			$existing = wp_get_nav_menu_object( $name );
			$menu_id  = $existing ? (int) $existing->term_id : (int) wp_create_nav_menu( $name );
			if ( ! $menu_id || is_wp_error( $menu_id ) ) {
				\WP_CLI::error( "Could not create menu \"{$name}\"." );
			}
			if ( ! $existing ) {
				self::add_menu_links( $menu_id, $links, 0 );
			}
			$locations[ $location ] = $menu_id;
		}
		if ( $apply ) {
			set_theme_mod( 'nav_menu_locations', $locations );
			\WP_CLI::success( 'Menus seeded and assigned. Edit them in Appearance → Menus.' );
		} else {
			\WP_CLI::log( 'Nothing changed. Re-run with --apply to make these changes.' );
		}
	}

	/** @param array<int,array<string,mixed>> $links */
	private static function add_menu_links( int $menu_id, array $links, int $parent ): void {
		foreach ( $links as $link ) {
			$href = (string) $link['href'];
			$item = array( 'menu-item-title' => (string) $link['label'], 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent );
			$term = preg_match( '#^/product-category/([^/?]+)$#', $href, $m ) ? get_term_by( 'slug', $m[1], 'product_cat' ) : null;
			$page = ! $term && preg_match( '#^/([a-z0-9-]+)$#', $href, $m ) ? get_page_by_path( $m[1] ) : null;
			if ( $term ) {
				$item += array( 'menu-item-type' => 'taxonomy', 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $term->term_id );
			} elseif ( $page && 'publish' === $page->post_status ) {
				$item += array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $page->ID );
			} else {
				$item += array( 'menu-item-type' => 'custom', 'menu-item-url' => $href );
			}
			$id = wp_update_nav_menu_item( $menu_id, 0, $item );
			if ( is_wp_error( $id ) ) {
				\WP_CLI::error( 'Could not add menu item "' . $link['label'] . '": ' . $id->get_error_message() );
			}
			if ( ! empty( $link['children'] ) ) {
				self::add_menu_links( $menu_id, $link['children'], (int) $id );
			}
		}
	}

	/** Preserve the original once; re-running the migration must not replace its rollback copy. */
	private static function backup_page( $page ): void {
		if ( metadata_exists( 'post', $page->ID, '_veloria_migration_original' ) ) {
			return;
		}
		$backup = array( 'post_name' => $page->post_name, 'post_status' => $page->post_status, 'post_content' => $page->post_content, 'template' => get_page_template_slug( $page ), 'saved_at_utc' => gmdate( 'c' ) );
		if ( ! add_post_meta( $page->ID, '_veloria_migration_original', wp_slash( $backup ), true ) ) {
			\WP_CLI::error( 'Could not preserve the original page ' . $page->ID . '; seeding stopped.' );
		}
	}

	private static function update_page( array $fields ): void {
		$result = wp_update_post( wp_slash( $fields ), true );
		if ( is_wp_error( $result ) || ! $result ) {
			\WP_CLI::error( 'A page update failed; original snapshots were retained for review.' );
		}
	}

	/**
	 * Compare the PHP engine with vectors from tests/parity/generate-vectors.mjs.
	 *
	 * ## OPTIONS
	 *
	 * --vectors=<file>
	 * : vectors.json path.
	 *
	 * [--coupons=<file>]
	 * : Coupon fixture used to generate the vectors.
	 *
	 * [--expected-count=<count>]
	 * : Fail unless this many vectors were supplied.
	 *
	 * @param array<int,string>    $args
	 * @param array<string,string> $assoc
	 */
	public function parity( array $args, array $assoc ): void {
		$vectors = self::read_json_list( $assoc['vectors'] ?? '', 'vectors', true );
		$coupons = self::read_json_list( $assoc['coupons'] ?? VELORIA_COMMERCE_DIR . 'tests/parity/coupons.fixture.json', 'coupons', false );
		if ( isset( $assoc['expected-count'] ) && ( ! ctype_digit( (string) $assoc['expected-count'] ) || (int) $assoc['expected-count'] !== count( $vectors ) ) ) {
			\WP_CLI::error( 'Vector count does not match --expected-count.' );
		}
		$engine  = new DiscountEngine( array() );
		$bad     = 0;
		$fields  = array( 'originalSubtotal', 'itemCount', 'isPrepaid', 'tierDiscount', 'prepaidDiscount', 'manualCouponDiscount', 'appliedCoupons', 'codFee', 'shippingCost', 'finalTotal', 'isCapped', 'savingsBreakdown' );
		foreach ( $vectors as $index => $v ) {
			if ( ! is_array( $v ) || ! isset( $v['input']['items'], $v['input']['codes'], $v['input']['isPrepaid'], $v['output'], $v['validate']['code'], $v['validate']['subtotal'], $v['validate']['qty'], $v['validate']['existing'], $v['validate']['result'] ) || ! is_array( $v['input']['items'] ) || ! is_array( $v['input']['codes'] ) || ! is_bool( $v['input']['isPrepaid'] ) || ! is_array( $v['output'] ) || array_diff( $fields, array_keys( $v['output'] ) ) ) {
				\WP_CLI::error( "Invalid or incomplete parity vector {$index}." );
			}
			try {
				$got = $engine->calculate( $v['input']['items'], $v['input']['codes'], $v['input']['isPrepaid'], $coupons, 10.0 );
				$valid = $engine->validate_coupon( $v['validate']['code'], (float) $v['validate']['subtotal'], (int) $v['validate']['qty'], $coupons, $v['validate']['existing'], 10.0 );
			} catch ( \Throwable $error ) {
				\WP_CLI::error( "Invalid parity input at vector {$index}: " . $error->getMessage() );
			}
			$match = true;
			foreach ( $fields as $field ) {
				$match = $match && self::parity_equal( $v['output'][ $field ], $got[ $field ] ?? null );
			}
			$expected = $v['validate']['result'];
			if ( ! array_key_exists( 'valid', $expected ) || ! array_key_exists( 'error', $expected ) ) {
				\WP_CLI::error( "Incomplete coupon-validation expectation at vector {$index}." );
			}
			$match = $match && $valid['valid'] === $expected['valid'] && ( $valid['error'] ?? null ) === $expected['error'];
			if ( ! $match ) {
				$bad++;
				if ( $bad <= 10 ) {
					\WP_CLI::warning( "Vector {$index} differs in calculation or coupon validation." );
				}
			}
		}
		if ( $bad ) {
			\WP_CLI::error( "{$bad} mismatches." );
		}
		\WP_CLI::success( count( $vectors ) . ' vectors match all calculation fields and coupon results. Defaults tested; saved store settings and checkout need separate checks. sha256=' . hash_file( 'sha256', $assoc['vectors'] ) );
	}

	private static function read_json_list( string $file, string $label, bool $nonempty ): array {
		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			\WP_CLI::error( "Unreadable {$label} JSON file." );
		}
		try {
			$data = json_decode( (string) file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $error ) {
			\WP_CLI::error( "Invalid {$label} JSON: " . $error->getMessage() );
		}
		if ( ! is_array( $data ) || ! array_is_list( $data ) || ( $nonempty && ! $data ) ) {
			\WP_CLI::error( "{$label} must be " . ( $nonempty ? 'a nonempty' : 'a' ) . ' JSON list.' );
		}
		return $data;
	}

	private static function parity_equal( $expected, $actual ): bool {
		if ( ( is_int( $expected ) || is_float( $expected ) ) && ( is_int( $actual ) || is_float( $actual ) ) ) {
			return (float) $expected === (float) $actual;
		}
		if ( is_array( $expected ) && is_array( $actual ) ) {
			if ( count( $expected ) !== count( $actual ) || array_is_list( $expected ) !== array_is_list( $actual ) ) {
				return false;
			}
			foreach ( $expected as $key => $value ) {
				if ( ! array_key_exists( $key, $actual ) || ! self::parity_equal( $value, $actual[ $key ] ) ) {
					return false;
				}
			}
			return true;
		}
		return $expected === $actual;
	}

	/**
	 * Find captured Razorpay payments that have no WooCommerce order. Read-only.
	 *
	 * ## OPTIONS
	 *
	 * --from=<date>
	 * : Start date, YYYY-MM-DD.
	 *
	 * [--to=<date>]
	 * : End date, YYYY-MM-DD. Default: now.
	 *
	 * [--timezone=<timezone>]
	 * : IANA timezone for date boundaries. Defaults to the WordPress site timezone.
	 *
	 * @subcommand reconcile-razorpay
	 * @param array<int,string>    $args
	 * @param array<string,string> $assoc
	 */
	public function reconcile_razorpay( array $args, array $assoc ): void {
		try {
			$timezone = isset( $assoc['timezone'] ) ? new \DateTimeZone( $assoc['timezone'] ) : wp_timezone();
		} catch ( \Exception $error ) {
			\WP_CLI::error( 'Invalid reconciliation timezone.' );
		}
		$boundary = static function ( string $value, bool $end ) use ( $timezone ): int {
			$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, $timezone );
			if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) {
				\WP_CLI::error( 'Reconciliation dates must be valid YYYY-MM-DD values.' );
			}
			return ( $end ? $date->setTime( 23, 59, 59 ) : $date )->getTimestamp();
		};
		$from = $boundary( $assoc['from'] ?? '', false );
		$to   = isset( $assoc['to'] ) ? $boundary( $assoc['to'], true ) : time();
		if ( $from > $to ) {
			\WP_CLI::error( 'The reconciliation start is after its end.' );
		}
		\WP_CLI::log( 'Window timezone=' . $timezone->getName() . ' from=' . gmdate( 'c', $from ) . ' to=' . gmdate( 'c', $to ) . ' (UTC timestamps).' );
		$rows = array();
		$seen = array();
		$eligible = 0;
		$complete = false;
		for ( $skip = 0; $skip <= 10000; $skip += 100 ) {
			$page = Razorpay::payments( $from, $to, $skip, 100 );
			if ( is_wp_error( $page ) ) {
				\WP_CLI::error( 'Reconciliation incomplete: ' . $page->get_error_message() );
			}
			if ( $skip >= 10000 && $page ) {
				\WP_CLI::error( 'Reconciliation exceeded 10,000 payments. Split the date window; this result is incomplete.' );
			}
			foreach ( $page as $payment ) {
				$id = (string) $payment['id'];
				if ( isset( $seen[ $id ] ) ) {
					\WP_CLI::error( 'Reconciliation received duplicate payment pages; retry with a smaller fixed window.' );
				}
				$seen[ $id ] = true;
				if ( 'captured' !== ( $payment['status'] ?? '' ) && 'refunded' !== ( $payment['status'] ?? '' ) ) {
					continue;
				}
				$eligible++;
				$orders = wc_get_orders( array( 'transaction_id' => $id, 'limit' => 1, 'return' => 'ids' ) );
				if ( ! $orders ) {
					$orders = wc_get_orders(
						array(
							'limit'      => 1,
							'return'     => 'ids',
							'meta_query' => array( array( 'key' => '_razorpay_payment_id', 'value' => $id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
						)
					);
				}
				if ( ! $orders ) {
					$rows[] = array(
						'payment_id' => $id,
						'status'     => $payment['status'],
						'amount'     => ( (int) $payment['amount'] ) / 100,
						'email'      => $payment['email'] ?? '',
						'contact'    => $payment['contact'] ?? '',
						'created'    => gmdate( 'Y-m-d H:i', (int) $payment['created_at'] ),
						'rzp_order'  => $payment['order_id'] ?? '',
					);
				}
			}
			if ( count( $page ) < 100 ) {
				$complete = true;
				break;
			}
		}
		if ( ! $complete ) {
			\WP_CLI::error( 'Reconciliation did not reach the end of the payment history.' );
		}
		\WP_CLI::log( 'complete=true fetched=' . count( $seen ) . ' captured_or_refunded=' . $eligible . ' matched=' . ( $eligible - count( $rows ) ) . ' unmatched=' . count( $rows ) );
		if ( $rows ) {
			\WP_CLI\Utils\format_items( 'table', $rows, array_keys( $rows[0] ) );
			\WP_CLI::error( count( $rows ) . ' payment(s) have no WooCommerce order. Resolve them before migration.' );
		}
		\WP_CLI::success( $eligible ? 'Every captured/refunded payment in the verified window has a WooCommerce order.' : 'The verified window contains no captured/refunded payments.' );
	}

	/**
	 * Import newsletter subscribers from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : CSV with one email per row (or an "email" column).
	 *
	 * @subcommand newsletter-import
	 * @param array<int,string> $args
	 */
	public function newsletter_import( array $args ): void {
		$added = Newsletter::import_file( $args[0], 'inbox-import' );
		\WP_CLI::success( "{$added} subscriber(s) added." );
	}

	/**
	 * Record carrier-verified delivery for shipped orders that have none yet
	 * (orders placed before delivery evidence existed). Dry run by default.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Only orders created in the last N days. Default 30.
	 *
	 * [--apply]
	 * : Query Shiprocket and record explicit Delivered results.
	 *
	 * @subcommand verify-deliveries
	 * @param array<int,string>    $args
	 * @param array<string,string> $assoc
	 */
	public function verify_deliveries( array $args, array $assoc ): void {
		$apply = isset( $assoc['apply'] );
		$days  = max( 1, (int) ( $assoc['days'] ?? 30 ) );
		if ( $apply && ! \Veloria\Commerce\Shipping\Shiprocket::configured() ) {
			\WP_CLI::error( 'Shiprocket credentials are not configured.' );
		}
		$orders = wc_get_orders(
			array(
				'limit'        => -1,
				'status'       => array( 'processing', 'completed' ),
				'date_created' => '>' . ( time() - $days * DAY_IN_SECONDS ),
				'meta_key'     => '_shiprocket_shipment_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
			)
		);
		$recorded = 0;
		foreach ( $orders as $order ) {
			if ( null !== \Veloria\Commerce\Orders\OrderService::delivery_timestamp( $order ) ) {
				continue;
			}
			$shipment = (string) $order->get_meta( '_shiprocket_shipment_id' );
			if ( ! $apply ) {
				\WP_CLI::log( "[dry run] Would check order #{$order->get_id()} shipment {$shipment}" );
				continue;
			}
			\Veloria\Commerce\Shipping\Shiprocket::track_shipment( $shipment, $order, true );
			$fresh = wc_get_order( $order->get_id() );
			if ( $fresh && null !== \Veloria\Commerce\Orders\OrderService::delivery_timestamp( $fresh ) ) {
				++$recorded;
				\WP_CLI::log( "Order #{$order->get_id()}: delivery recorded." );
			}
		}
		\WP_CLI::success( $apply ? "{$recorded} delivery record(s) added." : count( $orders ) . ' shipped order(s) inspected (dry run).' );
	}
}
