<?php
/**
 * WooCommerce → Veloria settings screen. Simple values are form fields;
 * structured values (tier rules, homepage tabs, badges) are JSON so the shape
 * matches the original config files one to one.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Admin;

use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	/** key => [label, type, help] */
	private const FIELDS = array(
		'Pricing'      => array(
			'pricing_mode'            => array( 'Discount system', 'select:veloria=Veloria automatic offers|native=WooCommerce / pricing extensions', 'Veloria keeps current tier/prepaid offers and COD fee. Native mode delegates pricing and coupons to WooCommerce and your chosen extensions.' ),
			'coupon_mode'             => array( 'Coupon system', 'select:veloria=Veloria stacking rules|native=WooCommerce coupons and extensions', 'Native pricing always uses native coupons. With Veloria pricing, native coupons share the discount cap; an excessive coupon is rejected at checkout.' ),
			'tier_rules'              => array( 'Tier discounts', 'structured', 'Add an item count and discount percentage for each tier.' ),
			'prepaid_percent'         => array( 'Prepaid bonus (%)', 'number', 'Extra discount when paying online.' ),
			'max_discount_percent'    => array( 'Discount cap (%)', 'number', 'All discounts together never exceed this share of the subtotal.' ),
			'cod_fee'                 => array( 'COD fee (₹)', 'number', '' ),
			'standard_shipping_cost'  => array( 'Shipping cost (₹)', 'number', '' ),
			'free_shipping_threshold' => array( 'Free shipping from (₹)', 'number', 'Orders at or above this amount ship free. 0 = every order ships free, so the shipping cost above is never charged.' ),
			'cod_gateway'             => array( 'Cash on delivery gateway ID', 'text', 'Normally cod. Change only when your delivery-payment extension uses another ID.' ),
			'prepaid_gateway_ids'     => array( 'Online payment bonus', 'gateways', 'Choose gateways that collect payment online. Bank transfer and cheque do not automatically receive the prepaid bonus.' ),
			'override_shipping'       => array( 'Use "Standard Shipping" only', 'checkbox', 'Replace shipping-zone rates with the single line the headless checkout used. While this is on, WooCommerce shipping zones are ignored.' ),
		),
		'Offer copy'   => array(
			'announcement_messages'   => array( 'Announcement bar', 'lines', 'One message per line.' ),
			'offer_tier_labels'       => array( 'Tier labels', 'lines', 'One per tier, lowest quantity first.' ),
			'prepaid_offer_text'      => array( 'Prepaid offer text', 'text', '' ),
			'product_promo'           => array( 'Product page offers', 'structured', 'Add, remove or reorder offer descriptions.' ),
			'trust_badges'            => array( 'Product trust badges', 'structured', '' ),
			'policy_links'            => array( 'Policy links', 'structured', '' ),
		),
		'Homepage'     => array(
			'hero_slides'             => array( 'Hero photos', 'structured', 'Choose and reorder photos. Image descriptions are read by assistive technology.' ),
			'home_features'           => array( 'Features strip', 'structured', '' ),
			'home_policies'           => array( 'Policies strip', 'structured', 'Use {shipping} in the description for the current shipping promise.' ),
			'hot_seller_label'        => array( 'Hot seller eyebrow', 'text', '' ),
			'hot_seller_title'        => array( 'Hot seller heading', 'text', '' ),
			'hot_seller_ids'          => array( 'Hot seller products', 'products', 'Choose products by name. Hold Ctrl to choose more than one.' ),
			'most_loved'              => array( 'Most loved collections', 'structured', 'Choose categories and products for each tab. Elementor pages have their own visual controls.' ),
			'spotlight'               => array( 'Spotlight section', 'structured', '' ),
			'instagram_live'          => array( 'Live Instagram photos', 'checkbox', 'Off = six branded tiles (current live behaviour).' ),
		),
		'Support'      => array(
			'support_email'           => array( 'Support email', 'text', 'Receives return requests and contact messages.' ),
			'support_phone'           => array( 'Support phone', 'text', '' ),
			'newsletter_inbox'        => array( 'Newsletter notification inbox', 'text', 'Blank = support email.' ),
			'cancel_window_hours'     => array( 'Automatic cancellation window (hours)', 'number', '48 hours from order placement by default. Later requests need staff approval. Courier cancellation must be verified before refund; payment providers control the credit date.' ),
			'return_window_days'      => array( 'Return window (days)', 'number', '' ),
		),
		'Brand'        => array(
			'footer_tagline'          => array( 'Footer tagline', 'text', '' ),
			'instagram_url'           => array( 'Instagram profile', 'url', '' ),
			'facebook_url'            => array( 'Facebook page', 'url', '' ),
		),
		'Legal'        => array(
			'legal_name'              => array( 'Seller legal name', 'text', 'Registered business name (Consumer Protection (E-Commerce) Rules 2020). Shown on Contact, footer and product pages once filled.' ),
			'legal_address'           => array( 'Registered address', 'text', '' ),
			'gstin'                   => array( 'GSTIN', 'text', 'Leave blank if not GST-registered.' ),
			'grievance_name'          => array( 'Grievance officer name', 'text', 'Required by the E-Commerce Rules: complaints acknowledged within 48 hours and resolved within one month.' ),
			'grievance_designation'   => array( 'Grievance officer designation', 'text', '' ),
			'grievance_email'         => array( 'Grievance officer email', 'text', '' ),
			'grievance_phone'         => array( 'Grievance officer phone', 'text', '' ),
			'country_of_origin'       => array( 'Country of origin', 'text', 'Shown on every product page with the MRP (incl. of all taxes).' ),
		),
		'Integrations' => array(
			'shiprocket_mode'         => array( 'Shiprocket sync', 'select:off=Off (official plugin or manual)|custom=Veloria sync (same as headless)', 'Never run both: orders would be created twice. Credentials go in wp-config.php: VELORIA_SHIPROCKET_EMAIL / VELORIA_SHIPROCKET_PASSWORD.' ),
			'shiprocket_pickup'       => array( 'Shiprocket pickup location', 'text', '' ),
			'shiprocket_channel_id'   => array( 'Shiprocket channel ID', 'text', '' ),
			'shiprocket_webhook_source' => array( 'Shiprocket channel store URL', 'text', 'Store URL the official Shiprocket channel is registered with, sent as the order webhook source (e.g. https://api.veloriavault.com/). Leave blank after updating the channel in Shiprocket to https://www.veloriavault.com.' ),
		),
		'Domain & SEO' => array(
			'canonical_host'          => array( 'Canonical host', 'text', '' ),
			'legacy_api_host'         => array( 'Legacy API host', 'text', '' ),
			'redirect_api_host'       => array( 'Redirect legacy API host to canonical', 'checkbox', 'Turn on 48 hours after cutover (keeps /wp-json reachable).' ),
			'seo_enabled'             => array( 'Theme SEO tags', 'checkbox', 'Titles, descriptions, canonicals, Open Graph, JSON-LD. Switches off automatically if Yoast or Rank Math is active.' ),
			'serve_sitemap'           => array( 'Serve /sitemap.xml', 'checkbox', '' ),
			'security_headers'        => array( 'Security headers', 'checkbox', '' ),
			'csp_mode'                => array( 'Content-Security-Policy', 'select:report=Report only (recommended until reviewed)|enforce=Enforce|off=Off', 'Report only logs violations in the browser console without blocking anything. Enforce after checking every page type, embeds and the Razorpay checkout.' ),
			'csp_extra_sources'       => array( 'Extra CSP sources', 'text', 'Hosts only, space separated, e.g. https://cdn.example.com *.example.com. Keywords, quotes and ; are removed.' ),
			'block_suspicious'        => array( 'Block suspicious URLs and scanners', 'checkbox', '' ),
			'hsts_subdomains'         => array( 'HSTS: include subdomains', 'checkbox', 'Forces HTTPS on every subdomain for a year. Only enable when all subdomains serve HTTPS.' ),
			'hsts_preload'            => array( 'HSTS: preload', 'checkbox', 'Requires "include subdomains". Browser preload lists are effectively irreversible.' ),
		),
	);

	/** Hosts, redirects and security headers: administrators only, not shop managers. */
	private const ADMIN_ONLY = array( 'canonical_host', 'legacy_api_host', 'redirect_api_host', 'security_headers', 'csp_mode', 'csp_extra_sources', 'block_suspicious', 'hsts_subdomains', 'hsts_preload' );

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 55 );
		add_action( 'admin_post_veloria_save_settings', array( self::class, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
	}

	public static function menu(): void {
		add_submenu_page( 'woocommerce', 'Manage your store', 'Store dashboard', 'manage_woocommerce', 'veloria-store', array( self::class, 'dashboard' ) );
		add_submenu_page( 'woocommerce', 'Veloria settings', 'Veloria', 'manage_woocommerce', 'veloria-settings', array( self::class, 'render' ) );
	}

	public static function assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'woocommerce_page_veloria-settings', 'woocommerce_page_veloria-store' ), true ) ) return;
		wp_enqueue_media();
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'select2' );
		$base = plugins_url( 'assets/', VELORIA_COMMERCE_DIR . 'veloria-commerce.php' );
		wp_enqueue_style( 'veloria-merchant', $base . 'admin-settings.css', array(), VELORIA_COMMERCE_VERSION );
		wp_enqueue_script( 'veloria-merchant', $base . 'admin-settings.js', array( 'media-editor' ), VELORIA_COMMERCE_VERSION, true );
	}

	public static function dashboard(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Not allowed' );
		$cards = array(
			array( 'Orders and returns', 'See orders, verify delivery dates and manage refunds.', admin_url( 'admin.php?page=wc-orders' ) ),
			array( 'Products', 'Change photos, prices, variations and stock.', admin_url( 'edit.php?post_type=product' ) ),
			array( 'Add a product', 'Create a new product for the shop.', admin_url( 'post-new.php?post_type=product' ) ),
			array( 'Pages', 'Edit policy pages in the page editor. Home, About and Contact use the theme design: opening them in Elementor and saving replaces that design.', admin_url( 'edit.php?post_type=page' ) ),
			array( 'Menus', 'Header menu and both footer columns. Add categories or pages; child items become mega-menu columns.', admin_url( 'nav-menus.php' ) ),
			array( 'Offers and store settings', 'Manage discounts, shipping rules, homepage fallback content and support details.', admin_url( 'admin.php?page=veloria-settings' ) ),
			array( 'Coupons', 'Create or update an offer code.', admin_url( 'edit.php?post_type=shop_coupon' ) ),
			array( 'Shipping and payments', 'Manage shipping zones and available gateways.', admin_url( 'admin.php?page=wc-settings' ) ),
			array( 'View store', 'Preview changes as a customer.', home_url( '/' ) ),
		);
		echo '<div class="wrap"><h1>Manage your store</h1><p>Choose what you want to change. Product details come from WooCommerce; page designs use Elementor.</p><div class="veloria-dashboard">';
		foreach ( $cards as $card ) printf( '<a href="%s"><strong>%s</strong>%s</a>', esc_url( $card[2] ), esc_html( $card[0] ), esc_html( $card[1] ) );
		echo '</div><p>Return policy: request within 7 days of verified delivery. After inspection, refund through the original payment method. For COD, arrange the customer repayment manually and record it on the order.</p></div>';
	}

	public static function render(): void {
		$values = Settings::all();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['veloria_status'] ) ? sanitize_key( $_GET['veloria_status'] ) : '';
		?>
		<div class="wrap veloria-settings">
			<h1>Veloria settings</h1>
			<?php if ( 'saved' === $status ) : ?>
				<div class="notice notice-success"><p>Settings saved.</p></div>
			<?php elseif ( 'json' === $status ) : ?>
				<div class="notice notice-error"><p>Some values were invalid. Check quantities, percentages and links. Invalid fields kept their previous values.</p></div>
			<?php endif; ?>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=veloria-store' ) ); ?>">Store dashboard</a> · For page design use Edit with Elementor. These settings control store rules and fallback theme sections.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="veloria_save_settings">
				<?php wp_nonce_field( 'veloria_save_settings' ); ?>
				<?php foreach ( self::FIELDS as $section => $fields ) : ?>
					<?php if ( 'Domain & SEO' === $section ) echo '<details><summary>Advanced: domain, SEO and security</summary>'; ?>
					<h2><?php echo esc_html( $section ); ?></h2>
					<?php if ( 'Domain & SEO' === $section && ! current_user_can( 'manage_options' ) ) echo '<p class="description">Domain and security settings can only be changed by an administrator.</p>'; ?>
					<table class="form-table" role="presentation">
						<?php foreach ( $fields as $key => $def ) : ?>
							<?php if ( in_array( $key, self::ADMIN_ONLY, true ) && ! current_user_can( 'manage_options' ) ) continue; ?>
							<tr>
								<th scope="row"><label for="veloria-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $def[0] ); ?></label></th>
								<td>
									<?php self::field( $key, $def[1], $values[ $key ] ?? null ); ?>
									<?php if ( '' !== $def[2] ) : ?>
										<p class="description"><?php echo esc_html( $def[2] ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
					<?php if ( 'Domain & SEO' === $section ) echo '</details>'; ?>
				<?php endforeach; ?>
				<?php submit_button( 'Save settings' ); ?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="veloria_save_settings">
				<input type="hidden" name="reset" value="1">
				<?php wp_nonce_field( 'veloria_save_settings' ); ?>
				<details><summary>Reset store rules</summary><p>This resets saved Veloria settings. It does not delete products or Elementor designs.</p><?php submit_button( 'Restore default settings', 'secondary' ); ?></details>
			</form>
		</div>
		<?php
	}

	/** @param mixed $value */
	private static function field( string $key, string $type, $value ): void {
		$id   = 'veloria-' . $key;
		$name = 'veloria[' . $key . ']';
		if ( 'checkbox' === $type ) {
			printf( '<input type="hidden" name="%1$s" value="0"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s>', esc_attr( $name ), esc_attr( $id ), checked( (bool) $value, true, false ) );
		} elseif ( 'number' === $type ) {
			printf( '<input type="number" step="any" class="small-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
		} elseif ( 'structured' === $type ) {
			ClientFields::render( $key, $value );
		} elseif ( 'gateways' === $type ) {
			printf( '<input type="hidden" name="%s[]" value=""><select multiple size="5" id="%s" name="%s[]">', esc_attr( $name ), esc_attr( $id ), esc_attr( $name ) );
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) printf( '<option value="%s" %s>%s</option>', esc_attr( $gateway->id ), selected( in_array( $gateway->id, (array) $value, true ), true, false ), esc_html( $gateway->get_method_title() ) );
			echo '</select>';
		} elseif ( 'products' === $type ) {
			printf( '<input type="hidden" name="%s[]" value=""><select multiple class="wc-product-search" data-placeholder="Search products by name…" data-action="woocommerce_json_search_products" data-security="%s" id="%s" name="%s[]">', esc_attr( $name ), esc_attr( wp_create_nonce( 'search-products' ) ), esc_attr( $id ), esc_attr( $name ) );
			foreach ( wc_get_products( array( 'limit' => 200, 'status' => array( 'publish', 'private', 'draft' ), 'orderby' => 'title', 'order' => 'ASC' ) ) as $product ) printf( '<option value="%d" %s>%s</option>', $product->get_id(), selected( in_array( $product->get_id(), (array) $value, true ), true, false ), esc_html( $product->get_name() ) );
			echo '</select>';
		} elseif ( 'lines' === $type ) {
			printf( '<textarea class="large-text" rows="4" id="%s" name="%s">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( implode( "\n", (array) $value ) ) );
		} elseif ( 'ids' === $type ) {
			printf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( implode( ', ', (array) $value ) ) );
		} elseif ( str_starts_with( $type, 'select:' ) ) {
			printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
			foreach ( explode( '|', substr( $type, 7 ) ) as $option ) {
				list( $opt_value, $label ) = explode( '=', $option, 2 );
				printf( '<option value="%s" %s>%s</option>', esc_attr( $opt_value ), selected( (string) $value, $opt_value, false ), esc_html( $label ) );
			}
			echo '</select>';
		} else {
			printf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
		}
	}

	public static function save(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'veloria_save_settings' ) ) {
			wp_die( 'Not allowed' );
		}
		$admin = current_user_can( 'manage_options' );
		if ( ! empty( $_POST['reset'] ) ) {
			$saved = get_option( Settings::OPTION, array() );
			delete_option( Settings::OPTION );
			if ( ! $admin && is_array( $saved ) ) {
				// A shop manager's reset must not change administrator-only settings.
				$keep = array_intersect_key( $saved, array_flip( self::ADMIN_ONLY ) );
				if ( $keep ) update_option( Settings::OPTION, $keep, true );
			}
			Settings::flush();
			wp_safe_redirect( admin_url( 'admin.php?page=veloria-settings&veloria_status=saved' ) );
			exit;
		}

		$input  = isset( $_POST['veloria'] ) && is_array( $_POST['veloria'] ) ? wp_unslash( $_POST['veloria'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$values = array();
		$bad    = false;
		foreach ( self::FIELDS as $fields ) {
			foreach ( $fields as $key => $def ) {
				if ( ! array_key_exists( $key, $input ) || ( ! $admin && in_array( $key, self::ADMIN_ONLY, true ) ) ) {
					continue;
				}
				$raw  = $input[ $key ];
				$type = $def[1];
				if ( 'checkbox' === $type ) {
					if ( is_scalar( $raw ) ) $values[ $key ] = '1' === (string) $raw;
					else $bad = true;
				} elseif ( 'structured' === $type ) {
					$clean = ClientFields::sanitize( $key, $raw );
					if ( is_wp_error( $clean ) ) $bad = true;
					else $values[ $key ] = $clean;
				} elseif ( 'gateways' === $type ) {
					if ( is_array( $raw ) ) $values[ $key ] = array_values( array_intersect( array_filter( array_map( 'sanitize_key', array_filter( $raw, 'is_scalar' ) ) ), array_keys( WC()->payment_gateways()->payment_gateways() ) ) );
					else $bad = true;
				} elseif ( 'products' === $type ) {
					if ( is_array( $raw ) ) $values[ $key ] = array_values( array_unique( array_filter( array_map( 'absint', array_filter( $raw, 'is_scalar' ) ) ) ) );
					else $bad = true;
				} elseif ( ! is_scalar( $raw ) ) {
					$bad = true;
				} elseif ( 'number' === $type ) {
					$max = in_array( $key, array( 'prepaid_percent', 'max_discount_percent' ), true ) ? 100 : 1000000;
					if ( ! is_numeric( $raw ) || (float) $raw < 0 || (float) $raw > $max ) $bad = true;
					else $values[ $key ] = (float) $raw;
				} elseif ( 'lines' === $type ) {
					$values[ $key ] = array_values( array_filter( array_map( 'sanitize_text_field', preg_split( '/\r?\n/', (string) $raw ) ?: array() ) ) );
				} elseif ( 'ids' === $type ) {
					$values[ $key ] = array_values( array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) ) );
				} elseif ( str_starts_with( $type, 'select:' ) ) {
					$allowed = array_map( static fn( $option ) => explode( '=', $option, 2 )[0], explode( '|', substr( $type, 7 ) ) );
					if ( in_array( (string) $raw, $allowed, true ) ) $values[ $key ] = (string) $raw;
					else $bad = true;
				} elseif ( 'url' === $type ) {
					$url = esc_url_raw( (string) $raw, array( 'https', 'http' ) );
					if ( '' === $raw || preg_match( '#^https?://#i', $url ) ) $values[ $key ] = $url;
					else $bad = true;
				} elseif ( 'csp_extra_sources' === $key ) {
					$values[ $key ] = \Veloria\Commerce\Security\Headers::sanitize_sources( sanitize_text_field( (string) $raw ) );
					if ( '' !== trim( (string) $raw ) && count( preg_split( '/[\s,]+/', trim( (string) $raw ) ) ?: array() ) !== count( array_filter( explode( ' ', $values[ $key ] ) ) ) ) $bad = true;
				} elseif ( in_array( $key, array( 'support_email', 'newsletter_inbox' ), true ) ) {
					if ( '' === $raw || is_email( $raw ) ) $values[ $key ] = sanitize_email( $raw );
					else $bad = true;
				} else {
					$values[ $key ] = sanitize_text_field( (string) $raw );
				}
			}
		}
		Settings::save( $values );
		\Veloria\Commerce\Catalog\Catalog::bump();
		wp_safe_redirect( admin_url( 'admin.php?page=veloria-settings&veloria_status=' . ( $bad ? 'json' : 'saved' ) ) );
		exit;
	}
}
