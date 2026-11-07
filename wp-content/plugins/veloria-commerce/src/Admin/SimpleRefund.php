<?php
/** Small, amount-safe entry point for staff source refunds. */
namespace Veloria\Commerce\Admin;

use Veloria\Commerce\Orders\OrderService;

defined( 'ABSPATH' ) || exit;

final class SimpleRefund {
	public static function register(): void {
		add_action( 'woocommerce_admin_order_data_after_order_details', array( self::class, 'render' ), 5 );
		add_action( 'wp_ajax_veloria_full_source_refund', array( self::class, 'submit' ) );
		// Core's manual line-item form must not refund the undiscounted product price.
		add_action( 'wp_ajax_woocommerce_refund_line_items', array( self::class, 'guard_native' ), 0 );
	}

	public static function render( \WC_Order $order ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $order->get_id() ) ) return;
		$paid = (float) OrderService::amount_paid( $order );
		$payment_recorded = (bool) $order->get_date_paid() || 'completed' === (string) $order->get_meta( '_payment_status' ) || 'refunded' === (string) $order->get_meta( '_payment_status' );
		$remaining = $payment_recorded ? max( 0, $paid - (float) $order->get_total_refunded() ) : 0;
		$is_return = 'yes' === (string) $order->get_meta( '_return_requested' );
		$review = 'requested' === (string) $order->get_meta( '_veloria_cancel_review_state' );
		$active = ! in_array( $order->get_status(), array( 'cancelled', 'refunded', 'failed' ), true ) && $remaining > 0 && (float) $order->get_total_refunded() <= 0 && 'razorpay' === $order->get_payment_method();
		$price = wc_price( $remaining, array( 'currency' => $order->get_currency() ) );
		?>
		<div class="clear"></div>
		<div id="veloria-simple-refund" style="margin:20px 0;padding:16px;border:1px solid #c3c4c7;border-left:4px solid #2271b1;background:#f6f7f7">
			<h3 style="margin-top:0">Veloria — easy full refund</h3>
			<p><?php echo $payment_recorded ? 'Paid after discounts &amp; GST' : 'Order total (payment not recorded)'; ?>: <strong><?php echo wp_kses_post( wc_price( $paid, array( 'currency' => $order->get_currency() ) ) ); ?></strong><br>
			Already refunded: <?php echo wp_kses_post( wc_price( $order->get_total_refunded(), array( 'currency' => $order->get_currency() ) ) ); ?><br>
			Full refund: <strong><?php echo wp_kses_post( $price ); ?></strong> → original payment source</p>
			<p>No amount, quantity or GST fields to fill. Discounts are included automatically.</p>
			<?php if ( $order->get_meta( '_veloria_refund_state' ) ) : ?><p><strong>Refund status:</strong> <?php echo esc_html( (string) $order->get_meta( '_veloria_refund_state' ) ); ?><?php if ( $order->get_meta( '_razorpay_refund_id' ) ) : ?> · <?php echo esc_html( (string) $order->get_meta( '_razorpay_refund_id' ) ); ?><?php endif; ?></p><?php endif; ?>
			<?php if ( $order->get_meta( '_veloria_refund_error' ) ) : ?><p style="color:#b32d2e"><?php echo esc_html( (string) $order->get_meta( '_veloria_refund_error' ) ); ?></p><?php endif; ?>
			<?php if ( $active ) : ?>
				<?php if ( $is_return ) : ?><p>First save the inspected return as approved below. Restock separately after checking the returned item.</p>
				<?php else : ?><p>Cancellation: courier must be stopped first. After 48 hours, the first click queues review; approval is a separate action.</p><?php endif; ?>
				<p><label for="veloria-simple-reason">Reason (required)</label><br><textarea id="veloria-simple-reason" rows="2" maxlength="500" style="width:100%" placeholder="Customer requested cancellation / inspected return approved"></textarea></p>
				<?php if ( ! $is_return ) : ?>
				<details><summary>Courier confirmation (if requested)</summary><p>Only use after verifying this exact order cannot ship.</p><p><label><input type="checkbox" id="veloria-simple-stopped"> I verified the matching courier order is stopped / has no fulfillment booking.</label></p><label for="veloria-simple-reference">Shiprocket order/support reference and verification note</label><input id="veloria-simple-reference" maxlength="200" style="width:100%"></details>
				<?php endif; ?>
				<p><label><input type="checkbox" id="veloria-simple-confirm"> I confirm the full <?php echo wp_kses_post( $price ); ?> refund to the original payment source.</label></p>
				<button type="button" class="button button-primary" id="veloria-simple-submit"><?php echo $review ? 'Approve cancellation & refund' : ( $is_return ? 'Refund / check return refund' : 'Cancel & refund / check status' ); ?></button>
				<p id="veloria-simple-result" role="status" aria-live="polite"></p>
				<script>
				(function(){
				const button=document.getElementById('veloria-simple-submit'), result=document.getElementById('veloria-simple-result');
				button.addEventListener('click',async function(){
				 const reason=document.getElementById('veloria-simple-reason').value.trim();
				 if(reason.length<5 || !document.getElementById('veloria-simple-confirm').checked){result.textContent='Enter a reason and tick the full refund confirmation.';return;}
				 const stopped=document.getElementById('veloria-simple-stopped'),reference=document.getElementById('veloria-simple-reference');
				 if(stopped && stopped.checked && reference.value.trim().length<8){result.textContent='Enter the courier order/support reference (at least 8 characters).';return;}
				 button.disabled=true;result.textContent='Checking order, courier and payment. Please wait…';
				 try {
				 const body=new URLSearchParams({action:'veloria_full_source_refund',order_id:<?php echo wp_json_encode( (string) $order->get_id() ); ?>,nonce:<?php echo wp_json_encode( wp_create_nonce( 'veloria_full_source_refund_' . $order->get_id() ) ); ?>,reason:reason,confirm:'yes',expected:<?php echo wp_json_encode( (string) round( $remaining * 100 ) ); ?>,courier_reference:stopped&&stopped.checked?reference.value.trim():''});
				 const response=await fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:body});const data=await response.json();
				 result.textContent=data.data.message || 'Reopen the order and check its recorded status.';
				 if(!data.success && /courier|shiprocket/i.test(result.textContent)){const details=document.querySelector('#veloria-simple-refund details');if(details)details.open=true;}
				 if(data.success){const link=document.createElement('a');link.href=location.href;link.textContent=' Refresh order';result.appendChild(link);}else{button.disabled=false;}
				 }catch(error){result.textContent='Response unavailable. Refresh this order to check the recorded refund before retrying.';}
				});
				})();
				</script>
			<?php else : ?><p>No new full source refund is available here. Check existing refunds or the payment method.</p><?php endif; ?>
		</div>
		<?php
	}

	/** The browser never selects the amount sent to the provider. */
	public static function process( int $id, array $input ): array {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $id ) || ! wp_verify_nonce( (string) ( $input['nonce'] ?? '' ), 'veloria_full_source_refund_' . $id ) ) {
			return array( 'success' => false, 'error' => 'Permission or security check failed. Refresh this order.' );
		}
		$order = wc_get_order( $id );
		$reason = mb_substr( trim( sanitize_textarea_field( (string) ( $input['reason'] ?? '' ) ) ), 0, 500 );
		if ( ! $order instanceof \WC_Order || $order instanceof \WC_Order_Refund || 'yes' !== ( $input['confirm'] ?? '' ) || mb_strlen( $reason ) < 5 ) {
			return array( 'success' => false, 'error' => 'Select an order, enter a reason and confirm the full source refund.' );
		}
		$remaining = (int) round( ( (float) OrderService::amount_paid( $order ) - (float) $order->get_total_refunded() ) * 100 );
		if ( $remaining <= 0 || (string) $remaining !== (string) ( $input['expected'] ?? '' ) || (float) $order->get_total_refunded() > 0 || 'razorpay' !== $order->get_payment_method() ) {
			return array( 'success' => false, 'error' => 'The balance or payment method needs review. Refresh the order; no new refund was submitted.' );
		}
		if ( 'yes' === (string) $order->get_meta( '_return_requested' ) ) {
			return OrderService::staff_return_refund( $order, $reason );
		}
		$reference = trim( sanitize_text_field( (string) ( $input['courier_reference'] ?? '' ) ) );
		if ( 'requested' === (string) $order->get_meta( '_veloria_cancel_review_state' ) ) {
			return OrderService::decide_cancel_review( $order, 'approved', get_current_user_id(), $reason, $reference );
		}
		if ( 'yes' === (string) $order->get_meta( '_veloria_cancel_requested' ) ) {
			return OrderService::staff_retry( $order, $reference );
		}
		// A new request goes through normal eligibility; it cannot skip late review.
		$order->add_order_note( 'Staff #' . get_current_user_id() . ' requested cancellation/source refund: ' . $reason );
		return OrderService::cancel( $order );
	}

	public static function submit(): void {
		try {
			$id = absint( $_POST['order_id'] ?? 0 );
			if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $id ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'veloria_full_source_refund_' . $id ) ) {
				wp_send_json_error( array( 'message' => 'Permission or security check failed. Refresh this order.' ), 403 );
			}
			$result = self::process( absint( $_POST['order_id'] ?? 0 ), wp_unslash( $_POST ) );
			$order = wc_get_order( absint( $_POST['order_id'] ?? 0 ) );
			$message = ! empty( $result['refunded'] ) ? 'Refund processed to the original payment source. Refresh the order for its refund reference.' : ( $result['message'] ?? $result['error'] ?? 'Request saved. Refresh the order to review and approve the cancellation; no refund has been issued yet.' );
			if ( empty( $result['success'] ) && $order instanceof \WC_Order && $order->get_meta( '_veloria_refund_error' ) ) $message = (string) $order->get_meta( '_veloria_refund_error' );
			if ( ! empty( $result['success'] ) ) wp_send_json_success( array( 'message' => $message ) );
			wp_send_json_error( array( 'message' => $message ) );
		} catch ( \Throwable $error ) {
			wp_send_json_error( array( 'message' => 'Refresh the order and check its refund reference before retrying. The result needs review.' ) );
		}
	}

	public static function guard_native(): void {
		$id = absint( $_POST['order_id'] ?? 0 );
		if ( ! current_user_can( 'edit_shop_order', $id ) || ! check_ajax_referer( 'order-item', 'security', false ) ) return;
		$order = wc_get_order( $id );
		if ( ! $order instanceof \WC_Order ) return;
		if ( 'razorpay' === $order->get_payment_method() ) {
			wp_send_json_error( array( 'error' => 'Use Veloria — easy full refund above for a checked original-source refund. Its amount includes all discounts and GST. Partial refunds need separate staff reconciliation.' ) );
		}
		$amount = (float) wc_format_decimal( wp_unslash( $_POST['refund_amount'] ?? 0 ) );
		$remaining = (float) OrderService::amount_paid( $order ) - (float) $order->get_total_refunded();
		if ( $amount <= 0 || round( $amount * 100 ) > round( $remaining * 100 ) ) {
			wp_send_json_error( array( 'error' => 'Refund exceeds the actual paid balance after discounts. Use Veloria — easy full refund above.' ) );
		}
		if ( 'yes' === (string) $order->get_meta( '_veloria_cancel_requested' ) || null === OrderService::delivery_timestamp( $order ) || 'approved' !== (string) $order->get_meta( '_veloria_return_decision' ) || get_option( 'veloria_cancel_lock_' . $id ) || get_option( 'veloria_sr_lock_' . $id ) || in_array( (string) $order->get_meta( '_veloria_refund_state' ), array( 'requesting', 'pending', 'review', 'processed' ), true ) ) {
			wp_send_json_error( array( 'error' => 'Use Veloria — easy full refund. Cancellation needs courier clearance; returns need saved inspection approval. Check any existing refund first.' ) );
		}
	}
}
