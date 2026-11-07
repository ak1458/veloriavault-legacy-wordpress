<?php
/**
 * WooCommerce → Reconciliation: real amount received per order vs MRP, with
 * the Razorpay payment id (port of /api/admin/orders), plus CSV export.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Admin;

use Veloria\Commerce\Invoice\Invoice;

defined( 'ABSPATH' ) || exit;

final class ReconciliationPage {

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 61 );
		add_action( 'admin_post_veloria_reconciliation_csv', array( self::class, 'csv' ) );
	}

	public static function menu(): void {
		add_submenu_page( 'woocommerce', 'Order reconciliation', 'Reconciliation', 'manage_woocommerce', 'veloria-reconciliation', array( self::class, 'render' ) );
	}

	/** @return array<int,array<string,mixed>> */
	private static function rows( int $page, int $per_page ): array {
		$orders = wc_get_orders(
			array(
				'limit'   => $per_page,
				'paged'   => $page,
				'orderby' => 'date',
				'order'   => 'DESC',
				'type'    => 'shop_order',
			)
		);
		$rows = array();
		foreach ( $orders as $order ) {
			$m      = Invoice::model( $order );
			$rows[] = array(
				'orderNumber'    => $m['number'],
				'date'           => $m['date'],
				'status'         => $order->get_status(),
				'customer'       => trim( $m['billTo']['first_name'] . ' ' . $m['billTo']['last_name'] ),
				'email'          => $m['billTo']['email'],
				'mrpSubtotal'    => $m['mrpSubtotal'],
				'discount'       => $m['discountTotal'],
				'shipping'       => $m['shipping'],
				'codFee'         => $m['codFee'],
				'orderTotal'     => $m['grandTotal'],
				'grossPaid'      => $m['grossPaid'],
				'amountRefunded' => $m['amountRefunded'],
				'amountReceived' => $m['netReceived'],
				'amountDue'      => $m['amountDue'],
				'paymentMethod'  => $m['paymentMethod'],
				'paymentId'      => $m['paymentId'],
				'source'         => (string) $order->get_meta( '_order_source' ),
			);
		}
		return $rows;
	}

	public static function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$rows = self::rows( $page, 50 );
		?>
		<div class="wrap">
			<h1>Order reconciliation</h1>
			<p>Paid and refunded values use the local WooCommerce payment/refund records. Pending COD is shown as due, not received. These figures still require payment-provider reconciliation. <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=veloria_reconciliation_csv' ), 'veloria_reconciliation_csv' ) ); ?>">Export last 1,000 as CSV</a></p>
			<table class="widefat striped">
				<thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Customer</th><th>MRP</th><th>Discount</th><th>COD fee</th><th>Paid</th><th>Refunded</th><th>Net received</th><th>Due</th><th>Method</th><th>Payment ID</th><th>Source</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td>#<?php echo esc_html( $r['orderNumber'] ); ?></td>
						<td><?php echo esc_html( veloria_date_en_in( (string) $r['date'] ) ); ?></td>
						<td><?php echo esc_html( $r['status'] ); ?></td>
						<td><?php echo esc_html( $r['customer'] ); ?><br><small><?php echo esc_html( $r['email'] ); ?></small></td>
						<td><?php echo esc_html( veloria_inr( (float) $r['mrpSubtotal'] ) ); ?></td>
						<td><?php echo esc_html( veloria_inr( (float) $r['discount'] ) ); ?></td>
						<td><?php echo esc_html( veloria_inr( (float) $r['codFee'] ) ); ?></td>
						<td><?php echo esc_html( veloria_inr( (float) $r['grossPaid'] ) ); ?></td>
						<td><?php echo esc_html( veloria_inr( (float) $r['amountRefunded'] ) ); ?></td>
						<td><strong><?php echo esc_html( veloria_inr( (float) $r['amountReceived'] ) ); ?></strong></td>
						<td><?php echo esc_html( veloria_inr( (float) $r['amountDue'] ) ); ?></td>
						<td><?php echo esc_html( $r['paymentMethod'] ); ?></td>
						<td><code><?php echo esc_html( (string) $r['paymentId'] ); ?></code></td>
						<td><?php echo esc_html( $r['source'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<?php if ( $page > 1 ) : ?>
					<a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $page - 1 ) ); ?>">&larr; Newer</a>
				<?php endif; ?>
				<?php if ( count( $rows ) === 50 ) : ?>
					<a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1 ) ); ?>">Older &rarr;</a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	public static function csv(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'veloria_reconciliation_csv' ) ) {
			wp_die( 'Not allowed' );
		}
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="veloria-reconciliation-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out   = fopen( 'php://output', 'w' );
		$first = true;
		for ( $page = 1; $page <= 10; $page++ ) {
			$rows = self::rows( $page, 100 );
			foreach ( $rows as $row ) {
				if ( $first ) {
					fputcsv( $out, self::csv_safe( array_keys( $row ) ) );
					$first = false;
				}
				fputcsv( $out, self::csv_safe( $row ) );
			}
			if ( count( $rows ) < 100 ) {
				break;
			}
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
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
