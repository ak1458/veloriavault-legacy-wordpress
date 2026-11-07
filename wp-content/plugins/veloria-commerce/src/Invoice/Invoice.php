<?php
/**
 * Invoice model + renderer (port of src/lib/invoice.ts and invoice-pdf.ts).
 *
 * Discounts are read from negative fee lines. Native orders use their current
 * WooCommerce total; historical headless orders retain their charge metadata.
 * Local payment and refund records are reported separately from the order total.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Invoice;

use Veloria\Commerce\Orders\OrderService;

defined( 'ABSPATH' ) || exit;

final class Invoice {

	/** @return array<string,mixed> */
	public static function model( \WC_Order $order ): array {
		$native = 'yes' === (string) $order->get_meta( '_veloria_native_checkout' ) || 'WordPress Storefront' === (string) $order->get_meta( '_order_source' );
		$lines = array();
		foreach ( $order->get_items() as $item ) {
			$lines[] = array(
				'name'   => $item->get_name(),
				'qty'    => (int) $item->get_quantity(),
				'amount' => (float) $item->get_subtotal() + ( $native ? (float) $item->get_subtotal_tax() : 0 ),
			);
		}
		$mrp = array_sum( array_column( $lines, 'amount' ) );

		$discount = 0.0;
		$cod_fee  = 0.0;
		$fees     = array();
		foreach ( $order->get_fees() as $fee ) {
			$total = (float) $fee->get_total() + ( $native ? (float) $fee->get_total_tax() : 0 );
			if ( $total < 0 ) {
				$discount += abs( $total );
			} elseif ( $total > 0 ) {
				$name = method_exists( $fee, 'get_name' ) ? $fee->get_name() : 'COD Fee';
				if ( in_array( $name, array( 'COD Fee', 'Cash on Delivery Fee' ), true ) ) $cod_fee += $total;
				else $fees[] = array( 'name' => $name, 'amount' => $total );
			}
		}
		if ( $native && method_exists( $order, 'get_discount_total' ) ) $discount += (float) $order->get_discount_total() + (float) $order->get_discount_tax();

		$payment_id = (string) ( $order->get_meta( '_razorpay_payment_id' ) ?: $order->get_transaction_id() );
		$created    = $order->get_date_created();
		$charge     = max( 0.0, (float) OrderService::amount_paid( $order ) );
		$paid       = (bool) $order->get_date_paid() || in_array( (string) $order->get_meta( '_payment_status' ), array( 'completed', 'refunded' ), true );
		$refunded   = max( 0.0, (float) $order->get_total_refunded() );
		$gross_paid = $paid ? $charge : 0.0;

		return array(
			'number'        => (string) $order->get_order_number(),
			'date'          => $created ? $created->format( 'c' ) : '',
			'billTo'        => array(
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'email'      => $order->get_billing_email(),
				'address_1'  => $order->get_billing_address_1(),
				'city'       => $order->get_billing_city(),
				'state'      => $order->get_billing_state(),
				'postcode'   => $order->get_billing_postcode(),
			),
			'lines'         => $lines,
			'mrpSubtotal'   => $mrp,
			'discountTotal' => $discount,
			'shipping'      => (float) $order->get_shipping_total() + ( $native ? (float) $order->get_shipping_tax() : 0 ),
			'codFee'        => $cod_fee,
			'fees'          => $fees,
			'grandTotal'    => $charge,
			'isPaid'        => $paid,
			'grossPaid'     => $gross_paid,
			'amountRefunded' => $refunded,
			'netReceived'   => max( 0.0, $gross_paid - $refunded ),
			'amountDue'     => ! $paid && ! in_array( $order->get_status(), array( 'cancelled', 'failed', 'refunded' ), true ) ? $charge : 0.0,
			'paymentMethod' => $order->get_payment_method_title(),
			'paymentId'     => '' !== $payment_id ? $payment_id : null,
		);
	}

	/** "Rs. 2,699" — standard PDF fonts cannot encode ₹. */
	private static function inr( float $value ): string {
		return 'Rs. ' . veloria_format_number( $value );
	}

	/** @param array<string,mixed> $m */
	public static function render( array $m ): string {
		$pdf   = new PdfDocument( 595, 842 );
		$gold  = array( 0.71, 0.6, 0.36 );
		$dark  = array( 0.1, 0.1, 0.1 );
		$grey  = array( 0.4, 0.4, 0.4 );
		$left  = 50;
		$right = 545;
		$y     = 790;

		$right_text = static function ( string $s, float $size, bool $bold, array $color ) use ( $pdf, $right, &$y ): void {
			$pdf->text( $s, $right - $pdf->text_width( $s, $size, $bold ), $y, $size, $bold, $color );
		};

		$pdf->text( 'Veloria Vault', $left, $y, 22, true, $gold );
		$right_text( 'ORDER INVOICE', 12, true, $grey );
		$y -= 30;
		$pdf->text( 'Invoice / Order #' . $m['number'], $left, $y, 12, true, $dark );
		$y -= 16;
		$pdf->text( 'Date: ' . ( $m['date'] ? veloria_date_en_in( (string) $m['date'] ) : '-' ), $left, $y, 10, false, $grey );
		$y -= 26;

		$pdf->text( 'Bill To:', $left, $y, 10, true, $dark );
		$y -= 14;
		$b = $m['billTo'];
		$pdf->text( trim( ( $b['first_name'] ?? '' ) . ' ' . ( $b['last_name'] ?? '' ) ), $left, $y, 10, false, $grey );
		$y -= 13;
		$pdf->text( sprintf( '%s, %s, %s %s', $b['address_1'] ?? '', $b['city'] ?? '', $b['state'] ?? '', $b['postcode'] ?? '' ), $left, $y, 10, false, $grey );
		$y -= 13;
		if ( ! empty( $b['email'] ) ) {
			$pdf->text( (string) $b['email'], $left, $y, 10, false, $grey );
			$y -= 13;
		}
		$y -= 12;

		$pdf->line( $left, $y, $right, $y );
		$y -= 18;
		$pdf->text( 'Item', $left, $y, 10, true, $dark );
		$right_text( 'Amount', 10, true, $dark );
		$y -= 16;
		foreach ( $m['lines'] as $line ) {
			$pdf->text( $line['name'] . '  x' . $line['qty'], $left, $y, 10, false, $dark );
			$right_text( self::inr( (float) $line['amount'] ), 10, false, $dark );
			$y -= 15;
		}
		$y -= 4;
		$pdf->line( $left, $y, $right, $y );
		$y -= 18;

		$summary = static function ( string $label, string $value, bool $bold = false, array $color = array( 0.1, 0.1, 0.1 ) ) use ( $pdf, $left, $right_text, &$y ): void {
			$pdf->text( $label, $left, $y, 10, $bold, $color );
			$right_text( $value, 10, $bold, $color );
			$y -= 15;
		};
		$summary( 'Subtotal (before discounts)', self::inr( (float) $m['mrpSubtotal'] ) );
		if ( $m['discountTotal'] > 0 ) {
			$summary( 'Discounts', '- ' . self::inr( (float) $m['discountTotal'] ), false, $gold );
		}
		if ( $m['shipping'] > 0 ) {
			$summary( 'Shipping', self::inr( (float) $m['shipping'] ) );
		}
		if ( $m['codFee'] > 0 ) {
			$summary( 'COD Fee', self::inr( (float) $m['codFee'] ) );
		}
		foreach ( $m['fees'] ?? array() as $fee ) $summary( (string) $fee['name'], self::inr( (float) $fee['amount'] ) );
		$y -= 4;
		$summary( ! empty( $m['isPaid'] ) ? 'Order total (paid)' : 'Order total', self::inr( (float) $m['grandTotal'] ), true );
		if ( ! empty( $m['amountRefunded'] ) ) {
			$summary( 'Refunded', '- ' . self::inr( (float) $m['amountRefunded'] ) );
		}
		$y -= 8;
		$pdf->text( 'Payment: ' . $m['paymentMethod'] . ( $m['paymentId'] ? ' (' . $m['paymentId'] . ')' : '' ), $left, $y, 9, false, $grey );

		return $pdf->output();
	}
}
