<?php
/**
 * The storefront needs WooCommerce and the Veloria Commerce plugin. Without
 * them the theme shows a maintenance message to visitors instead of
 * half-working pages, and a notice to admins.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

function veloria_requirements_met(): bool {
	return class_exists( 'WooCommerce' ) && defined( 'VELORIA_COMMERCE_VERSION' );
}

if ( ! veloria_requirements_met() ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p><strong>Veloria theme:</strong> activate WooCommerce and the Veloria Commerce plugin.</p></div>';
		}
	);
	add_action(
		'template_redirect',
		static function (): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'We are updating the store. Please check back in a few minutes.', 'Veloria Vault', array( 'response' => 503 ) );
			}
		}
	);
}
