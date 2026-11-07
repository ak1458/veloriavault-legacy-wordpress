<?php
/** Native gateway refunds with a durable stop on uncertain provider results. */
namespace Veloria\Commerce\Payments;

defined( 'ABSPATH' ) || exit;

final class GatewayRefund {
	/** Called only while OrderService holds the per-order cancellation lock. */
	public static function cancel_payment( \WC_Order $order ): array {
		$state = (string) $order->get_meta( '_veloria_gateway_refund_state' );
		$id = (int) $order->get_meta( '_veloria_gateway_refund_id' );
		if ( 'accepted' === $state && $id > 0 ) {
			$refund = wc_get_order( $id );
			if ( $refund instanceof \WC_Order_Refund && (int) $refund->get_parent_id() === $order->get_id() ) {
				return array( 'success' => true );
			}
			return array( 'success' => false, 'error' => 'The recorded refund needs staff reconciliation.' );
		}
		if ( '' !== $state ) {
			return array( 'success' => false, 'error' => 'A previous gateway refund attempt needs staff reconciliation. No second refund was submitted.' );
		}
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( ! $gateway || ! $gateway->supports( 'refunds' ) || ! $order->get_transaction_id() ) {
			return array( 'success' => false, 'error' => 'This payment method requires a verified refund by support.' );
		}
		// Native ledgers may already contain staff partial refunds. Refund only
		// the outstanding balance, using the gateway's own currency/precision.
		$amount = max( 0, (float) $order->get_total() - (float) $order->get_total_refunded() );
		if ( $amount <= 0 ) {
			return array( 'success' => false, 'error' => 'The existing refund balance needs staff verification.' );
		}
		$order->update_meta_data( '_veloria_gateway_refund_state', 'requesting' );
		$order->update_meta_data( '_veloria_gateway_refund_amount', (string) $amount );
		$order->save();
		$persisted = wc_get_order( $order->get_id() );
		if ( ! $persisted || 'requesting' !== (string) $persisted->get_meta( '_veloria_gateway_refund_state' ) ) {
			return array( 'success' => false, 'error' => 'The refund could not be safely recorded. Please contact support.' );
		}
		try {
			$refund = wc_create_refund( array(
				'order_id' => $order->get_id(),
				'amount' => $amount,
				'reason' => 'Customer cancellation — original payment method',
				'refund_payment' => true,
				'restock_items' => false,
			) );
		} catch ( \Throwable $error ) {
			$refund = new \WP_Error( 'veloria_refund_unknown', $error->getMessage() );
		}
		if ( is_wp_error( $refund ) || ! $refund instanceof \WC_Order_Refund ) {
			$order->add_order_note( 'Gateway refund needs reconciliation. Automatic retry is blocked to prevent a duplicate refund.' );
			return array( 'success' => false, 'error' => 'The gateway refund result needs support review before cancellation can finish.' );
		}
		// Native success is the gateway plugin's acceptance contract. Settlement
		// remains observable through that gateway's own webhooks/admin tooling.
		$order->update_meta_data( '_veloria_gateway_refund_id', (string) $refund->get_id() );
		$order->update_meta_data( '_veloria_gateway_refund_state', 'accepted' );
		$order->update_meta_data( '_payment_status', 'refunded' );
		$order->save();
		return array( 'success' => true );
	}
}
