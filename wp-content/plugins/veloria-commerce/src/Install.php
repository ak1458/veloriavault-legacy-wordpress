<?php
/**
 * Activation, deactivation and versioned schema upgrades.
 *
 * Schema changes go into upgrade_to_x_y_z() methods keyed by version, so a
 * site that skips several releases still runs every step in order.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce;

defined( 'ABSPATH' ) || exit;

final class Install {

	private const DB_VERSION_OPTION = 'veloria_commerce_db_version';

	/** @var array<string,string> version => method */
	private const UPGRADES = array(
		'1.0.0' => 'upgrade_to_1_0_0',
	);

	public static function activate(): void {
		if ( ! self::maybe_upgrade( true ) ) {
			wp_die( esc_html( (string) get_option( 'veloria_commerce_upgrade_error', 'Veloria database setup failed.' ) ) );
		}
		// While inactive the plugin must not change the site's URLs; rules are
		// rebuilt when the Veloria theme is switched on (see Plugin::boot).
		if ( Plugin::storefront_active() ) {
			Seo\Routing::add_rewrite_rules();
			flush_rewrite_rules();
		}
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
		wp_clear_scheduled_hook( 'veloria_commerce_cleanup' );
	}

	public static function maybe_upgrade( bool $verify = false ): bool {
		$installed = (string) get_option( self::DB_VERSION_OPTION, '0' );
		try {
			if ( version_compare( $installed, VELORIA_COMMERCE_VERSION, '>' ) ) {
				throw new \RuntimeException( 'The database belongs to a newer Veloria release; automatic downgrade is unsupported.' );
			}
			foreach ( self::UPGRADES as $version => $method ) {
				if ( version_compare( $installed, $version, '<' ) || ( $verify && self::schema_problems() ) ) {
					self::$method();
					$problems = self::schema_problems();
					if ( $problems ) {
						throw new \RuntimeException( implode( ' ', $problems ) );
					}
					// Commit only the schema step that was actually verified.
					update_option( self::DB_VERSION_OPTION, $version, true );
				}
			}
			self::ensure_schedule();
			delete_option( 'veloria_commerce_upgrade_error' );
			return true;
		} catch ( \Throwable $error ) {
			update_option( 'veloria_commerce_upgrade_error', 'Veloria schema upgrade failed: ' . $error->getMessage(), false );
			return false;
		}
	}

	/** Read-only checks used by activation and the CLI deployment gate. */
	public static function schema_problems(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'veloria_subscribers';
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		if ( $found !== $table ) {
			return array( 'Missing newsletter subscriber table.' );
		}
		$quoted   = '`' . str_replace( '`', '``', $table ) . '`';
		$columns  = $wpdb->get_results( 'SHOW COLUMNS FROM ' . $quoted, ARRAY_A );
		$problems = array();
		$types    = array( 'id' => 'bigint', 'email' => 'varchar(190)', 'source' => 'varchar(40)', 'ip_hash' => 'char(64)', 'created_at' => 'datetime' );
		$by_name  = array_column( (array) $columns, null, 'Field' );
		foreach ( $types as $name => $type ) {
			if ( ! isset( $by_name[ $name ] ) || ! str_starts_with( strtolower( (string) $by_name[ $name ]['Type'] ), $type ) || 'NO' !== $by_name[ $name ]['Null'] ) {
				$problems[] = 'Invalid or missing newsletter column: ' . $name . '.';
			}
		}
		if ( ! str_contains( (string) ( $by_name['id']['Extra'] ?? '' ), 'auto_increment' ) ) {
			$problems[] = 'Newsletter ID must auto-increment.';
		}
		$indexes = (array) $wpdb->get_results( 'SHOW INDEX FROM ' . $quoted, ARRAY_A );
		$unique  = false;
		$keys    = array();
		foreach ( $indexes as $index ) {
			$keys[ $index['Key_name'] ][] = $index;
		}
		foreach ( $keys as $parts ) {
			$index = $parts[0];
			if ( 1 === count( $parts ) && 'email' === $index['Column_name'] && 0 === (int) $index['Non_unique'] && empty( $index['Sub_part'] ) ) {
				$unique = true;
			}
		}
		if ( ! isset( $keys['PRIMARY'] ) || 1 !== count( $keys['PRIMARY'] ) || 'id' !== $keys['PRIMARY'][0]['Column_name'] ) {
			$problems[] = 'Missing newsletter ID primary key.';
		}
		if ( ! $unique ) {
			$problems[] = 'Missing unique newsletter email index.';
		}
		return $problems;
	}

	private static function ensure_schedule(): void {
		if ( ! wp_next_scheduled( 'veloria_commerce_cleanup' ) ) {
			$result = wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'veloria_commerce_cleanup', array(), true );
			if ( is_wp_error( $result ) || false === $result ) {
				throw new \RuntimeException( 'Could not schedule Veloria maintenance.' );
			}
		}
	}

	/**
	 * Daily maintenance (cron `veloria_commerce_cleanup`). OTP challenges and
	 * rate-limit buckets are transients, which expire on their own and are
	 * swept by core's `delete_expired_transients`; the OTP attempt slots are
	 * plain options, so they are purged here.
	 */
	public static function cleanup(): void {
		Auth\Otp::purge_expired_attempts();
	}

	private static function upgrade_to_1_0_0(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = $wpdb->prefix . 'veloria_subscribers';
		$charset = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				email varchar(190) NOT NULL,
				source varchar(40) NOT NULL DEFAULT 'footer',
				ip_hash char(64) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY email (email)
			) {$charset};"
		);

	}
}
