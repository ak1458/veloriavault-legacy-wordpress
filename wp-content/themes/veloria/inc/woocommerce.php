<?php
/**
 * WooCommerce integration: which template renders each WooCommerce screen.
 *
 * Cart, checkout and account pages keep their WooCommerce role (so core,
 * gateways and plugins treat them normally), but the markup comes from the
 * theme's templates/ folder. Only one WooCommerce template is overridden
 * (checkout/order-receipt.php), which keeps "outdated template" warnings
 * after WooCommerce updates to a minimum.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

// /account/return/{order_id} (src/app/account/return/[orderId]).
add_action(
	'init',
	static function (): void {
		add_rewrite_endpoint( 'return', EP_PAGES );
	}
);
add_action( 'after_switch_theme', static fn() => flush_rewrite_rules() );

add_filter(
	'woocommerce_get_query_vars',
	static function ( array $vars ): array {
		$vars['return'] = 'return';
		return $vars;
	}
);

/**
 * Route WooCommerce screens to theme templates.
 */
add_filter(
	'template_include',
	static function ( string $template ): string {
		$dir = VELORIA_THEME_DIR . '/templates/';

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return $dir . 'order-received.php';
		}
		if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
			return $dir . 'order-pay.php';
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return $dir . 'checkout.php';
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return $dir . 'cart.php';
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			global $wp;
			if ( isset( $wp->query_vars['return'] ) ) {
				return $dir . 'account-return.php';
			}
			if ( is_user_logged_in() || ! veloria_session_email() || isset( $wp->query_vars['lost-password'] ) ) {
				return $dir . 'account-wc.php';
			}
			return $dir . 'account.php';
		}
		return $template;
	},
	99
);

/**
 * Keep native account endpoints available to WooCommerce extensions.
 * An email-code session accesses guest orders through the custom dashboard;
 * it does not impersonate a registered WordPress account.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}
		global $wp;
		// Signed-out visitors without an email-code session go to /login (as before).
		if ( ! is_user_logged_in() && ! veloria_session_email() && ! isset( $wp->query_vars['lost-password'] ) && ! isset( $wp->query_vars['customer-logout'] ) ) {
			wp_safe_redirect( home_url( '/login' ) );
			exit;
		}
	},
	20
);

// Logged-in customers visiting /login or /register go to their account.
add_action(
	'template_redirect',
	static function (): void {
		if ( is_user_logged_in() && is_page() && in_array( get_page_template_slug(), array( 'page-templates/login.php', 'page-templates/register.php' ), true ) ) {
			wp_safe_redirect( home_url( '/account' ) );
			exit;
		}
	}
);

// Keep the native product archive query and pagination available to Elementor
// and WooCommerce extensions. The custom catalog can still read its own data.
add_filter(
	'redirect_canonical',
	static function ( $redirect_url ) {
		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() ) && isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}
		return $redirect_url;
	}
);

// No WooCommerce breadcrumbs, sidebars or wrappers: the templates own the layout.
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

/** Checkout prefill for logged-in customers. */
function veloria_checkout_prefill(): array {
	if ( ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->customer ) {
		return array();
	}
	$c      = WC()->customer;
	$states = WC()->countries->get_states( 'IN' );
	$state  = (string) $c->get_billing_state();
	return array_filter(
		array(
			'email'      => $c->get_billing_email() ?: $c->get_email(),
			'firstName'  => $c->get_billing_first_name() ?: $c->get_first_name(),
			'lastName'   => $c->get_billing_last_name() ?: $c->get_last_name(),
			'address'    => $c->get_billing_address_1(),
			'city'       => $c->get_billing_city(),
			'state'      => is_array( $states ) && isset( $states[ $state ] ) ? html_entity_decode( (string) $states[ $state ] ) : $state,
			'postalCode' => $c->get_billing_postcode(),
			'phone'      => $c->get_billing_phone(),
		)
	);
}
