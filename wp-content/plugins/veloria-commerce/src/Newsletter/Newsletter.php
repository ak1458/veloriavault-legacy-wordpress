<?php
/**
 * Newsletter subscribers. The headless site only emailed each signup to the
 * inbox; here every signup is stored (table {prefix}veloria_subscribers) AND
 * the inbox is still notified. Admin: WooCommerce → Newsletter (list, CSV
 * export, CSV import for the historical signups recovered from the inbox).
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Newsletter;

use Veloria\Commerce\Mail\Mailer;
use Veloria\Commerce\Security\RateLimiter;
use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class Newsletter {

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'veloria_subscribers';
	}

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 60 );
		add_action( 'admin_post_veloria_newsletter_export', array( self::class, 'export_csv' ) );
		add_action( 'admin_post_veloria_newsletter_import', array( self::class, 'import_csv' ) );
	}

	/** @return 'created'|'exists'|'error' */
	public static function subscribe( string $email, string $source = 'footer' ): string {
		global $wpdb;
		$email = strtolower( trim( $email ) );

		$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE email = %s', $email ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		if ( $exists ) {
			return 'exists';
		}

		$ok = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			self::table(),
			array(
				'email'      => $email,
				'source'     => substr( $source, 0, 40 ),
				'ip_hash'    => hash( 'sha256', RateLimiter::client_ip() . wp_salt( 'nonce' ) ),
				'created_at' => current_time( 'mysql', true ),
			)
		);
		if ( ! $ok ) {
			return 'error';
		}
		Mailer::newsletter_signup( Settings::newsletter_inbox(), $email );
		return 'created';
	}

	public static function menu(): void {
		add_submenu_page( 'woocommerce', 'Newsletter subscribers', 'Newsletter', 'manage_woocommerce', 'veloria-newsletter', array( self::class, 'render' ) );
	}

	public static function render(): void {
		global $wpdb;
		$rows  = $wpdb->get_results( 'SELECT email, source, created_at FROM ' . self::table() . ' ORDER BY id DESC LIMIT 500' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$imported = isset( $_GET['imported'] ) ? (int) $_GET['imported'] : null;
		?>
		<div class="wrap">
			<h1>Newsletter subscribers <span class="title-count"><?php echo esc_html( (string) $count ); ?></span></h1>
			<?php if ( null !== $imported ) : ?>
				<div class="notice notice-success"><p><?php echo esc_html( sprintf( '%d new subscribers imported.', $imported ) ); ?></p></div>
			<?php endif; ?>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=veloria_newsletter_export' ), 'veloria_newsletter_export' ) ); ?>">Export CSV</a>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="margin:12px 0 20px">
				<input type="hidden" name="action" value="veloria_newsletter_import">
				<?php wp_nonce_field( 'veloria_newsletter_import' ); ?>
				<label>Import CSV (one email per line, or a column named "email"): <input type="file" name="csv" accept=".csv,text/csv,text/plain" required></label>
				<button class="button">Import</button>
			</form>
			<table class="widefat striped">
				<thead><tr><th>Email</th><th>Source</th><th>Subscribed (UTC)</th></tr></thead>
				<tbody>
				<?php foreach ( (array) $rows as $row ) : ?>
					<tr><td><?php echo esc_html( $row->email ); ?></td><td><?php echo esc_html( $row->source ); ?></td><td><?php echo esc_html( $row->created_at ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function export_csv(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'veloria_newsletter_export' ) ) {
			wp_die( 'Not allowed' );
		}
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT email, source, created_at FROM ' . self::table() . ' ORDER BY id ASC', ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="veloria-newsletter-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'email', 'source', 'created_at_utc' ) );
		foreach ( (array) $rows as $row ) {
			fputcsv( $out, self::csv_safe( (array) $row ) );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	public static function import_csv(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'veloria_newsletter_import' ) ) {
			wp_die( 'Not allowed' );
		}
		$path  = isset( $_FILES['csv']['tmp_name'] ) ? (string) $_FILES['csv']['tmp_name'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$added = '' !== $path && is_uploaded_file( $path ) ? self::import_file( $path, 'import' ) : 0;
		wp_safe_redirect( admin_url( 'admin.php?page=veloria-newsletter&imported=' . $added ) );
		exit;
	}

	/** Imports without sending notification emails. Returns rows added. */
	public static function import_file( string $path, string $source ): int {
		global $wpdb;
		$added  = 0;
		$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return 0;
		}
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			foreach ( (array) $row as $cell ) {
				$email = strtolower( trim( (string) $cell ) );
				if ( is_email( $email ) ) {
					$inserted = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$wpdb->prepare(
							'INSERT IGNORE INTO ' . self::table() . ' (email, source, ip_hash, created_at) VALUES (%s, %s, %s, %s)', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
							$email,
							$source,
							'',
							current_time( 'mysql', true )
						)
					);
					$added += (int) $inserted;
					break;
				}
			}
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $added;
	}

	/**
	 * Neutralises spreadsheet formula injection: a text cell that starts with
	 * = + - @ tab or CR is prefixed with a quote. Plain numbers are kept.
	 *
	 * @param array<int|string,mixed> $row
	 * @return array<int|string,mixed>
	 */
	private static function csv_safe( array $row ): array {
		foreach ( $row as $k => $cell ) {
			if ( is_string( $cell ) && '' !== $cell && ! is_numeric( $cell ) && false !== strpbrk( $cell[0], "=+-@\t\r" ) ) {
				$row[ $k ] = "'" . $cell;
			}
		}
		return $row;
	}
}
