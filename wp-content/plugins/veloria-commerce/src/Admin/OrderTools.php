<?php
/** Staff delivery evidence and return decisions, using WC_Order APIs for HPOS. */
namespace Veloria\Commerce\Admin;

use Veloria\Commerce\Orders\OrderService;
use Veloria\Commerce\Shipping\Shiprocket;
use Veloria\Commerce\Shipping\ShiprocketSync;

defined( 'ABSPATH' ) || exit;

final class OrderTools {

	public static function register(): void {
		add_action( 'woocommerce_admin_order_data_after_order_details', array( self::class, 'render' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( self::class, 'save' ), 60, 2 );
		add_action( 'admin_notices', array( self::class, 'notice' ) );
	}

	private static function allowed( int $id ): bool {
		return current_user_can( 'manage_woocommerce' ) && current_user_can( 'edit_shop_order', $id );
	}

	public static function render( \WC_Order $order ): void {
		if ( ! self::allowed( $order->get_id() ) ) {
			return;
		}
		$delivered = OrderService::delivery_timestamp( $order );
		$cancel_review = (string) $order->get_meta( '_veloria_cancel_review_state' );
		wp_nonce_field( 'veloria_order_tools_' . $order->get_id(), 'veloria_order_tools_nonce' );
		?>
		<div class="clear"></div><details class="veloria-order-tools" style="margin-top:20px"><summary><strong>Delivery, return inspection &amp; advanced recovery</strong></summary>
			<h3>Veloria delivery and returns</h3>
			<p>Returns can be requested within 7 days from verified delivery. Order completion alone does not establish delivery.</p>
			<p><label><input type="checkbox" name="veloria_delivery_verified" value="yes" <?php checked( null !== $delivered ); ?>> I checked carrier proof or customer delivery evidence</label></p>
			<p><label for="veloria_delivered_at">Delivered at (<?php echo esc_html( wp_timezone_string() ); ?>)</label><br>
			<input id="veloria_delivered_at" name="veloria_delivered_at" type="datetime-local" value="<?php echo esc_attr( null !== $delivered ? wp_date( 'Y-m-d\TH:i', $delivered ) : '' ); ?>"></p>
			<p><label for="veloria_delivery_reason">Evidence / correction reason (required when changing verification)</label><br>
			<textarea id="veloria_delivery_reason" name="veloria_delivery_reason" rows="2" style="width:100%" maxlength="500"></textarea></p>
			<?php if ( $order->get_meta( '_shiprocket_shipment_id' ) ) : ?>
				<p><button type="submit" class="button" name="veloria_refresh_delivery" value="1">Check verified carrier delivery</button></p>
			<?php endif; ?>
			<?php if ( 'yes' === (string) $order->get_meta( '_return_requested' ) ) : ?>
				<p><strong>Return requested:</strong> <?php echo esc_html( (string) $order->get_meta( '_return_reason' ) ); ?><br>
				Decision: <?php echo esc_html( (string) $order->get_meta( '_veloria_return_decision' ) ?: 'Awaiting inspection' ); ?></p>
				<p><select name="veloria_return_decision"><option value="">Keep decision</option><option value="approved">Approve after inspection</option><option value="declined">Decline with reason</option></select></p>
				<p><label><input type="checkbox" name="veloria_return_notify" value="1" checked> Email the customer the return decision</label></p>
				<p>Save inspection approval first, then use <strong>Veloria — easy full refund</strong> above. It refunds the paid amount including discounts and GST to the original payment source. Restock inspected items separately.</p>
			<?php endif; ?>
			<?php if ( 'yes' === (string) $order->get_meta( '_veloria_cancel_requested' ) && 'cancelled' !== $order->get_status() ) : ?>
				<?php $refund_state = (string) $order->get_meta( '_veloria_refund_state' ); ?>
				<p><strong>Customer cancellation requested</strong> <?php echo esc_html( (int) $order->get_meta( '_veloria_cancel_accepted_at' ) ? wp_date( 'Y-m-d H:i', (int) $order->get_meta( '_veloria_cancel_accepted_at' ) ) : '' ); ?>. Do not fulfil.<br>
				<?php if ( $cancel_review ) : ?>Review: <?php echo esc_html( $cancel_review ); ?>.<br><?php endif; ?>
				Refund: <?php echo esc_html( $refund_state ?: 'not started' ); ?><?php echo $order->get_meta( '_razorpay_refund_id' ) ? ' (' . esc_html( (string) $order->get_meta( '_razorpay_refund_id' ) ) . ')' : ''; ?></p>
				<?php if ( $order->get_meta( '_veloria_refund_error' ) ) : ?><p style="color:#b32d2e"><?php echo esc_html( (string) $order->get_meta( '_veloria_refund_error' ) ); ?></p><?php endif; ?>
				<?php if ( 'requested' === $cancel_review ) : ?>
					<p>Staff approval is required before courier cancellation or refund. Check dispatch status first; delivered orders must follow the return process.</p>
					<p><label for="veloria_cancel_review_reason">Cancellation review reason (required; the decline reason is emailed to the customer)</label><br>
					<textarea id="veloria_cancel_review_reason" name="veloria_cancel_review_reason" rows="2" maxlength="500" style="width:100%"></textarea></p>
					<p><button type="submit" class="button" name="veloria_cancel_review_decision" value="approved">Approve cancellation and start refund</button>
					<button type="submit" class="button" name="veloria_cancel_review_decision" value="declined">Decline cancellation request</button><br>
					<small>Approval uses the normal courier verification and refund checks. Decline leaves the order on hold for a separate fulfillment decision.</small></p>
				<?php else : ?>
				<p><button type="submit" class="button" name="veloria_refund_retry" value="1">Retry refund now</button>
				<?php if ( in_array( $refund_state, array( 'review', 'requesting' ), true ) && ! $order->get_meta( '_razorpay_refund_id' ) && ! $order->get_meta( '_veloria_refund_key' ) && ! $order->get_meta( '_veloria_refund_attempts' ) ) : ?>
					<button type="submit" class="button" name="veloria_refund_confirm_rejected" value="1">Confirm earlier refund was rejected</button>
				<?php endif; ?>
				<br><small>Both actions re-read Razorpay's records first. A matching refund is adopted, never created twice; the courier is cancelled before any refund.</small></p>
				<?php endif; ?>
				<p><label><input type="checkbox" name="veloria_courier_stopped" value="yes"> I verified the matching Shiprocket channel/order is cancelled and cannot ship, or confirmed it has no fulfillment order.</label><br>
				<label for="veloria_courier_reference">Shiprocket order / support reference and verification note</label><br>
				<input id="veloria_courier_reference" name="veloria_courier_reference" type="text" minlength="8" maxlength="200" style="width:100%"><br>
				<small>Needed when the official channel does not return a verifiable order mapping. Verify in Shiprocket immediately before approval or Retry refund now. This confirmation permits the refund attempt; never use it while a parcel can still ship.</small></p>
			<?php endif; ?>
			<?php if ( 'declined' === $cancel_review ) : ?>
				<p><strong>Cancellation review declined.</strong> <?php echo esc_html( (string) $order->get_meta( '_veloria_cancel_review_reason' ) ); ?> The order remains on hold; review fulfillment separately.</p>
			<?php endif; ?>
			<p><strong>Shiprocket:</strong> <?php echo esc_html( (string) $order->get_meta( '_veloria_sr_sync_state' ) ?: 'Not queued' ); ?>
			(<?php echo (int) $order->get_meta( '_veloria_sr_sync_attempts' ); ?> attempts)</p>
			<?php if ( $order->get_meta( '_veloria_sr_sync_error' ) ) : ?><p style="color:#b32d2e"><?php echo esc_html( (string) $order->get_meta( '_veloria_sr_sync_error' ) ); ?></p><?php endif; ?>
			<p><button type="submit" class="button" name="veloria_shiprocket_retry" value="1">Retry / reconcile shipment</button><br><small>An uncertain creation response is reconciled with Shiprocket; it is never blindly created again.</small></p>
		</details>
		<?php
	}

	/** Strict local datetime, never PHP's permissive date rollover. */
	public static function parse_delivery_date( string $value ): ?int {
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( ! $date || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) || $date->format( 'Y-m-d\TH:i' ) !== $value ) {
			return null;
		}
		return $date->getTimestamp();
	}

	/** Shared evidence bounds. Sources are staff inspection or authenticated carrier API only. */
	public static function record_delivery( \WC_Order $order, int $timestamp, string $source, int $actor = 0, string $reason = '' ): bool {
		$created = $order->get_date_created();
		if ( ! in_array( $source, array( 'staff', 'shiprocket' ), true ) || ! $created || $timestamp < $created->getTimestamp() || $timestamp > time() || $timestamp <= 0 ) {
			return false;
		}
		$previous = OrderService::delivery_timestamp( $order );
		if ( $previous === $timestamp ) {
			return true;
		}
		// Carrier polling must not overwrite an explicit staff correction.
		if ( 'shiprocket' === $source && 'staff' === (string) $order->get_meta( '_veloria_delivery_source' ) ) {
			return true;
		}
		$order->update_meta_data( '_veloria_delivered_at', (string) $timestamp );
		$order->update_meta_data( '_veloria_delivery_verified', 'yes' );
		$order->update_meta_data( '_veloria_delivery_source', $source );
		$order->update_meta_data( '_veloria_delivery_verified_by', $actor );
		$order->update_meta_data( '_veloria_delivery_verified_at', gmdate( 'c' ) );
		$order->add_order_note( sprintf( 'Delivery verified by %s%s: %s UTC. Previous: %s. %s', $source, $actor ? ' user #' . $actor : '', gmdate( 'Y-m-d H:i:s', $timestamp ), null === $previous ? 'unverified' : gmdate( 'Y-m-d H:i:s', $previous ) . ' UTC', sanitize_textarea_field( $reason ) ) );
		$order->save();
		return true;
	}

	public static function save( int $id, $unused = null ): void {
		if ( ! self::allowed( $id ) || ! isset( $_POST['veloria_order_tools_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['veloria_order_tools_nonce'] ) ), 'veloria_order_tools_' . $id ) ) {
			return;
		}
		$order = wc_get_order( $id );
		if ( ! $order instanceof \WC_Order || $order instanceof \WC_Order_Refund ) {
			return;
		}
		if ( isset( $_POST['veloria_cancel_review_decision'] ) ) {
			// The review buttons submit the whole order form. Do not approve
			// against old delivery evidence while silently ignoring a correction
			// entered alongside the decision; save that evidence separately first.
			if ( array_key_exists( 'veloria_delivered_at', $_POST ) || array_key_exists( 'veloria_delivery_verified', $_POST ) ) {
				$previous = OrderService::delivery_timestamp( $order );
				$verified = 'yes' === ( $_POST['veloria_delivery_verified'] ?? '' );
				$value = sanitize_text_field( wp_unslash( (string) ( $_POST['veloria_delivered_at'] ?? '' ) ) );
				$unchanged = ( ! $verified && null === $previous ) || ( $verified && null !== $previous && wp_date( 'Y-m-d\TH:i', $previous ) === $value );
				if ( ! $unchanged ) {
					self::error( 'Save delivery evidence or corrections first, then reopen the order before deciding cancellation. The review decision was not applied.' );
					return;
				}
			}
			$decision = sanitize_key( wp_unslash( (string) $_POST['veloria_cancel_review_decision'] ) );
			$review_reason = sanitize_textarea_field( wp_unslash( (string) ( $_POST['veloria_cancel_review_reason'] ?? '' ) ) );
			$reference = 'yes' === ( $_POST['veloria_courier_stopped'] ?? '' ) ? trim( sanitize_text_field( wp_unslash( (string) ( $_POST['veloria_courier_reference'] ?? '' ) ) ) ) : '';
			if ( 'yes' === ( $_POST['veloria_courier_stopped'] ?? '' ) && strlen( $reference ) < 8 ) {
				self::error( 'Enter the verified Shiprocket order or support reference before confirming the courier is stopped.' );
				return;
			}
			$result = OrderService::decide_cancel_review( $order, $decision, get_current_user_id(), $review_reason, $reference );
			if ( ! $result['success'] ) {
				self::error( (string) ( $result['error'] ?? 'Cancellation review could not be completed. Reopen the order to check its status.' ) );
			}
			return;
		}
		$reason = mb_substr( sanitize_textarea_field( wp_unslash( (string) ( $_POST['veloria_delivery_reason'] ?? '' ) ) ), 0, 500 );
		$value = sanitize_text_field( wp_unslash( (string) ( $_POST['veloria_delivered_at'] ?? '' ) ) );
		$verified = 'yes' === ( $_POST['veloria_delivery_verified'] ?? '' );
		$previous = OrderService::delivery_timestamp( $order );
		$timestamp = $verified ? self::parse_delivery_date( $value ) : null;
		// Rendered date inputs have minute precision; don't accidentally truncate carrier seconds.
		$unchanged = ( ! $verified && null === $previous ) || ( $verified && null !== $previous && wp_date( 'Y-m-d\TH:i', $previous ) === $value );
		if ( ! $unchanged ) {
			if ( mb_strlen( $reason ) < 5 ) {
				self::error( 'Delivery verification was not changed: enter the evidence or correction reason.' );
				return;
			}
			if ( $verified ) {
				if ( null === $timestamp || ! self::record_delivery( $order, $timestamp, 'staff', get_current_user_id(), $reason ) ) {
					self::error( 'Delivery verification was not changed: choose a valid date between order placement and now.' );
					return;
				}
			} else {
				$order->delete_meta_data( '_veloria_delivery_verified' );
				$order->delete_meta_data( '_veloria_delivered_at' );
				$order->update_meta_data( '_veloria_delivery_source', 'staff' );
				$order->add_order_note( 'Delivery verification removed by user #' . get_current_user_id() . '. Reason: ' . $reason );
				$order->save();
			}
		}
		$decision = sanitize_key( (string) ( $_POST['veloria_return_decision'] ?? '' ) );
		if ( in_array( $decision, array( 'approved', 'declined' ), true ) && $decision !== $order->get_meta( '_veloria_return_decision' ) ) {
			if ( 'yes' !== $order->get_meta( '_return_requested' ) || mb_strlen( $reason ) < 5 || ( 'approved' === $decision && null === OrderService::delivery_timestamp( $order ) ) ) {
				self::error( 'Return decision was not changed: a recorded request, delivery evidence and inspection reason are required.' );
				return;
			}
			$order->update_meta_data( '_veloria_return_decision', $decision );
			$order->update_meta_data( '_veloria_return_decided_at', gmdate( 'c' ) );
			$order->update_meta_data( '_veloria_return_decided_by', get_current_user_id() );
			$order->add_order_note( 'Return ' . $decision . ' after inspection by user #' . get_current_user_id() . ': ' . $reason . '. Refund, if due, must use the original payment method through the WooCommerce Refund action.' );
			if ( '1' === ( $_POST['veloria_return_notify'] ?? '' ) ) $order->add_order_note( 'approved' === $decision ? 'Your return passed inspection and has been approved. Any refund due will be processed to the original payment method; this notice is not a payment confirmation. Contact support for the refund status.' : 'Your return has been reviewed and declined. Please contact support with your order number for the reason and next steps.', true );
			$order->save();
		}
		if ( isset( $_POST['veloria_refresh_delivery'] ) ) {
			$shipment = (string) $order->get_meta( '_shiprocket_shipment_id' );
			if ( ! $shipment || ! Shiprocket::track_shipment( $shipment, $order, true ) ) {
				self::error( 'Carrier delivery could not be verified. Check shipping credentials and carrier evidence; no delivery was inferred.' );
			}
		}
		if ( isset( $_POST['veloria_shiprocket_retry'] ) ) {
			ShiprocketSync::retry( $id );
		}
		if ( isset( $_POST['veloria_refund_confirm_rejected'] ) && ! OrderService::confirm_refund_rejected( $order, get_current_user_id() ) ) {
			self::error( 'Not confirmed: Razorpay shows a refund, the payment is not fully captured, or its records could not be read. Check the Razorpay dashboard.' );
		}
		if ( isset( $_POST['veloria_refund_retry'] ) ) {
			$reference = 'yes' === ( $_POST['veloria_courier_stopped'] ?? '' ) ? trim( sanitize_text_field( wp_unslash( (string) ( $_POST['veloria_courier_reference'] ?? '' ) ) ) ) : '';
			if ( 'yes' === ( $_POST['veloria_courier_stopped'] ?? '' ) && strlen( $reference ) < 8 ) {
				self::error( 'Enter the verified Shiprocket order or support reference before confirming the courier is stopped.' );
				return;
			}
			$result = OrderService::staff_retry( wc_get_order( $id ) ?: $order, $reference );
			if ( ! $result['success'] ) {
				$fresh = wc_get_order( $id );
				self::error( 'Refund not completed: ' . ( $fresh instanceof \WC_Order && $fresh->get_meta( '_veloria_refund_error' ) ? (string) $fresh->get_meta( '_veloria_refund_error' ) : (string) ( $result['error'] ?? '' ) ) );
			}
		}
	}

	private static function error( string $message ): void {
		set_transient( 'veloria_order_tools_error_' . get_current_user_id(), $message, MINUTE_IN_SECONDS );
	}

	public static function notice(): void {
		$attention = get_option( 'veloria_refund_attention', array() );
		if ( is_array( $attention ) && $attention && current_user_can( 'manage_woocommerce' ) ) {
			$links = array();
			foreach ( array_keys( $attention ) as $order_id ) {
				$order = wc_get_order( (int) $order_id );
				// Staff may finish a case by hand; drop it once nothing is pending.
				if ( ! $order instanceof \WC_Order || 'yes' !== (string) $order->get_meta( '_veloria_cancel_requested' ) || in_array( $order->get_status(), array( 'cancelled', 'refunded' ), true ) ) {
					unset( $attention[ $order_id ] );
					continue;
				}
				$links[] = '<a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( (string) $order->get_order_number() ) . '</a>';
			}
			update_option( 'veloria_refund_attention', $attention, false );
			$links = array_slice( $links, 0, 10 );
			if ( $links ) {
				echo '<div class="notice notice-warning"><p><strong>Veloria:</strong> customer cancellation or refund needs attention: ' . implode( ', ', $links ) . '. Keep these orders out of fulfillment.</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		$message = get_transient( 'veloria_order_tools_error_' . get_current_user_id() );
		if ( $message ) {
			delete_transient( 'veloria_order_tools_error_' . get_current_user_id() );
			echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
		}
	}
}
