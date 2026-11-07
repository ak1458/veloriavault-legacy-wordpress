<?php
/** Dependencies, template drift and actual scheduled-sale execution. */
namespace Veloria\Commerce\Admin;

defined( 'ABSPATH' ) || exit;

final class HealthChecks {
	public static function register(): void {
		add_action( 'woocommerce_scheduled_sales', array( self::class, 'record_sales_run' ), PHP_INT_MAX );
	}

	public static function record_sales_run(): void {
		update_option( 'veloria_sales_last_run', time(), false );
	}

	public static function problems(): array {
		$errors = self::runtime_problems(
			defined( 'WC_VERSION' ) ? WC_VERSION : '0',
			(int) get_option( 'veloria_sales_last_run', 0 ),
			(int) wp_next_scheduled( 'woocommerce_scheduled_sales' ),
			time()
		);
		if ( defined( 'WC_ABSPATH' ) ) {
			$files = array();
			foreach ( array_unique( array( get_template_directory(), get_stylesheet_directory() ) ) as $directory ) {
				foreach ( glob( $directory . '/*.php' ) ?: array() as $file ) {
					if ( is_readable( WC_ABSPATH . 'templates/' . basename( $file ) ) ) $files[ basename( $file ) ] = $file;
				}
				$root = $directory . '/woocommerce/';
				if ( ! is_dir( $root ) ) continue;
				foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {
					if ( $file->isFile() && 'php' === $file->getExtension() ) $files[ substr( $file->getPathname(), strlen( $root ) ) ] = $file->getPathname();
				}
			}
			foreach ( $files as $relative => $file ) {
				$core = WC_ABSPATH . 'templates/' . $relative;
				if ( ! is_readable( $core ) ) continue;
				$local_version = self::template_version( (string) file_get_contents( $file ) );
				$core_version = self::template_version( (string) file_get_contents( $core ) );
				if ( $core_version && ( ! $local_version || version_compare( $local_version, $core_version, '<' ) ) ) $errors[] = 'Outdated WooCommerce override: ' . $relative . ' (theme ' . ( $local_version ?: 'unversioned' ) . ', WooCommerce ' . $core_version . '). Review and update the override.';
			}
		}
		return $errors;
	}

	public static function template_version( string $source ): string {
		return preg_match( '/@version\s+([0-9]+(?:\.[0-9]+)+)/', $source, $match ) ? $match[1] : '';
	}

	public static function runtime_problems( string $version, int $last, int $next, int $now ): array {
		$errors = array();
		if ( version_compare( $version, '11.1', '<' ) ) $errors[] = 'WooCommerce 11.1 or newer is required; installed version is ' . $version . '.';
		if ( ! $last || $last < $now - 86400 || $last > $now ) $errors[] = 'wc_scheduled_sales has no verified successful run in the last 24 hours. Check the server cron and run the event.';
		if ( ! $next || $next < $now - 86400 ) $errors[] = 'The woocommerce_scheduled_sales event for wc_scheduled_sales() is missing or more than 24 hours overdue.';
		return $errors;
	}
}
