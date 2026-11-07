<?php
/**
 * Bootstraps every module. Each module registers its own hooks, so a module
 * can be removed or replaced without touching the others.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/**
	 * The storefront is live only when the Veloria theme is active (or the
	 * VELORIA_STOREFRONT constant says so). Until then the plugin is inert, so
	 * installing it on the backend that still serves the headless storefront
	 * changes nothing for live orders, headers, sitemaps or shipping sync.
	 */
	public static function storefront_active(): bool {
		$active = defined( 'VELORIA_STOREFRONT' ) ? (bool) VELORIA_STOREFRONT : 'veloria' === get_template();
		return (bool) apply_filters( 'veloria_storefront_active', $active );
	}

	public static function boot(): void {
		Install::maybe_upgrade();
		add_action( 'veloria_commerce_cleanup', array( Install::class, 'cleanup' ) );

		Admin\SettingsPage::register();
		// Turning the storefront on or off changes the registered rewrite rules;
		// WordPress regenerates them on the next request when the option is gone.
		add_action( 'switch_theme', static fn() => delete_option( 'rewrite_rules' ) );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'veloria', Cli\Commands::class );
		}

		// Hardening that is safe while the plugin is otherwise inert (installed on the
		// headless backend before cutover): WP 7.x batch fatal, public admin username.
		add_filter( 'rest_post_dispatch', array( Store\StoreApiExtension::class, 'batch_error_response' ), 9 );
		add_filter( 'rest_endpoints', array( Security\Headers::class, 'hide_users_endpoints' ) );

		if ( ! self::storefront_active() ) {
			add_action(
				'admin_notices',
				static function (): void {
					if ( current_user_can( 'manage_woocommerce' ) ) {
						echo '<div class="notice notice-info"><p><strong>Veloria Commerce</strong> is installed but inactive: it starts handling the storefront when the Veloria theme is activated.</p></div>';
					}
				}
			);
			return;
		}

		// Modules that do not need WooCommerce.
		Security\Headers::register();
		Seo\Routing::register();
		Newsletter\Newsletter::register();
		Rest\Routes::register();

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					echo '<div class="notice notice-error"><p><strong>Veloria Commerce</strong> needs WooCommerce to be active.</p></div>';
				}
			);
			return;
		}

		Pricing\CartPricing::register();
		Pricing\CouponRepository::register();
		Checkout\CheckoutHandler::register();
		Store\StoreApiExtension::register();
		Shipping\ShiprocketSync::register();
		add_action( Orders\OrderService::CHECK_ACTION, array( Orders\OrderService::class, 'scheduled_check' ) );
		// WooCommerce's core cancel URL only changes status. Customer requests
		// must use the owned-order endpoint with courier and source-refund checks.
		add_filter( 'woocommerce_valid_order_statuses_for_cancel', '__return_empty_array', PHP_INT_MAX );
		Catalog\Catalog::register_cache_busting();
		Admin\ReconciliationPage::register();
		Admin\Notices::register();
		Admin\OrderTools::register();
		Admin\SimpleRefund::register();
		Admin\ColorTerms::register();
		Admin\HealthChecks::register();
	}
}
