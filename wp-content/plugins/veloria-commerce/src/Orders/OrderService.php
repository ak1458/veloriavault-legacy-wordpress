<?php
/**
 * Customer-facing order operations: lookup with ownership check, DTOs,
 * cancellation with refund, and return requests. Port of
 * src/lib/orders.ts, src/lib/order-dto.ts and the /api/orders/* routes.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Orders;

use Veloria\Commerce\Mail\Mailer;
use Veloria\Commerce\Payments\Razorpay;
use Veloria\Commerce\Payments\GatewayRefund;
use Veloria\Commerce\Settings;
use Veloria\Commerce\Shipping\Shiprocket;

defined( 'ABSPATH' ) || exit;

final class OrderService {

	private const CANCELLABLE = array( 'pending', 'processing', 'on-hold' );
	private const RETURNABLE  = array( 'processing', 'completed' );

	public static function owns( \WC_Order $order, string $email ): bool {
		$billing = strtolower( trim( (string) $order->get_billing_email() ) );
		return '' !== $billing && $billing === strtolower( trim( $email ) );
	}

	/**
	 * Order number == order ID (no sequential-number plugin is installed), the
	 * same assumption the headless tracker made.
	 */
	public static function find( string $order_number ): ?\WC_Order {
		$number = trim( ltrim( trim( $order_number ), '#' ) );
		if ( ! preg_match( '/^\d+$/', $number ) ) {
			return null;
		}
		$order = wc_get_order( (int) $number );
		return $order instanceof \WC_Order && ! $order instanceof \WC_Order_Refund ? $order : null;
	}

	/** @return array{ok:bool,mode?:string,reason?:string} */
	public static function is_cancellable( \WC_Order $order, ?int $now = null ): array {
		$now     = $now ?? time();
		$support = Settings::support_email();
		$blocked = self::cancellation_block_reason( $order );
		if ( null !== $blocked ) {
			return array( 'ok' => false, 'reason' => $blocked );
		}
		$created = $order->get_date_created();
		if ( ! $created || $created->getTimestamp() > $now ) {
			return array( 'ok' => false, 'reason' => "The order date needs verification. Please contact {$support}." );
		}
		$review = (string) $order->get_meta( '_veloria_cancel_review_state' );
		if ( 'requested' === $review ) {
			return array( 'ok' => false, 'mode' => 'review', 'reason' => 'Your cancellation request is already awaiting staff review.' );
		}
		if ( 'declined' === $review ) {
			return array( 'ok' => false, 'reason' => "Your cancellation request was declined after review. Please contact {$support}." );
		}
		$hours = (int) Settings::get( 'cancel_window_hours', 48 );
		$accepted = (int) $order->get_meta( '_veloria_cancel_accepted_at' );
		$continuing = ( $accepted > 0 && $accepted >= $created->getTimestamp() && $accepted <= $now && $accepted - $created->getTimestamp() <= $hours * HOUR_IN_SECONDS )
			|| self::legacy_cancellation_recorded( $order )
			|| self::approved_cancel_review( $order );
		// A late request only queues a review. It must never reach courier or
		// payment APIs until an authorized staff decision is recorded.
		if ( ! $continuing && $now - $created->getTimestamp() > $hours * HOUR_IN_SECONDS ) {
			return array( 'ok' => true, 'mode' => 'review' );
		}
		// A booked shipment without a verifiable remote ID still requires the
		// existing courier clearance; staff review does not bypass that guard.
		if ( self::has_shipment( $order ) && ! (int) $order->get_meta( '_shiprocket_order_id' ) && ! Shiprocket::has_staff_clearance( $order ) && ! Shiprocket::has_verified_cancellation( $order ) ) {
			if ( ! $continuing ) {
				return array( 'ok' => true, 'mode' => 'review' );
			}
			return array( 'ok' => false, 'reason' => "This order has already been booked with the courier. Please contact {$support} to cancel it." );
		}
		return array( 'ok' => true, 'mode' => 'automatic' );
	}

	/** Shared status/delivery guard, including for staff approval. */
	private static function cancellation_block_reason( \WC_Order $order ): ?string {
		$support = Settings::support_email();
		if ( ! in_array( $order->get_status(), self::CANCELLABLE, true ) ) {
			return "This order can no longer be cancelled online. Please contact {$support}.";
		}
		// A return request or recorded delivery means the goods left the warehouse:
		// those orders go through the return/inspection flow, never an instant refund.
		if ( 'yes' === (string) $order->get_meta( '_return_requested' ) || '' !== (string) $order->get_meta( '_veloria_return_decision' ) || null !== self::delivery_timestamp( $order ) ) {
			return "This order has been delivered or has a return request. Please contact {$support}.";
		}
		return null;
	}

	/** Staff-only markers establish permission to continue a late cancellation. */
	private static function approved_cancel_review( \WC_Order $order ): bool {
		return 'approved' === (string) $order->get_meta( '_veloria_cancel_review_state' )
			&& 'yes' === (string) $order->get_meta( '_veloria_cancel_requested' )
			&& (int) $order->get_meta( '_veloria_cancel_review_decided_by' ) > 0
			&& (int) $order->get_meta( '_veloria_cancel_review_decided_at' ) > 0;
	}

	/**
	 * The previous cancellation code wrote these server-only markers after its
	 * time-window check, before sending a refund. They establish an existing
	 * request, not a new late cancellation. Never invent an accepted timestamp.
	 */
	private static function legacy_cancellation_recorded( \WC_Order $order ): bool {
		if ( 'yes' !== (string) $order->get_meta( '_veloria_cancel_requested' ) ) {
			return false;
		}
		if ( (int) $order->get_meta( '_veloria_cancel_legacy_verified_at' ) > 0 ) {
			return true;
		}
		if ( (int) $order->get_meta( '_veloria_cancel_accepted_at' ) > 0 || '' !== (string) $order->get_meta( '_veloria_refund_key' ) || $order->get_meta( '_veloria_refund_attempts' ) ) {
			return false;
		}
		$state = (string) $order->get_meta( '_veloria_refund_state' );
		$id    = (string) $order->get_meta( '_razorpay_refund_id' );
		return ( '' === $id && in_array( $state, array( 'requesting', 'review' ), true ) )
			|| ( in_array( $state, array( 'pending', 'processed' ), true ) && (bool) preg_match( '/^rfnd_[A-Za-z0-9]+$/', $id ) );
	}

	/** Any courier booking evidence: custom sync IDs or the official plugin's AWB. */
	public static function has_shipment( \WC_Order $order ): bool {
		foreach ( array( '_shiprocket_shipment_id', '_shiprocket_awb', 'awb' ) as $key ) {
			if ( '' !== trim( (string) $order->get_meta( $key ) ) ) {
				return true;
			}
		}
		return false;
	}

	/** Customer-facing refund progress for a cancellation; never staff diagnostics. */
	public static function refund_progress( \WC_Order $order ): ?string {
		if ( 'cancelled' !== $order->get_status() ) {
			$review = (string) $order->get_meta( '_veloria_cancel_review_state' );
			if ( 'requested' === $review ) {
				return 'Cancellation review requested. Your order is on hold while our team checks whether it can be stopped. No refund has been issued yet.';
			}
			if ( 'declined' === $review ) {
				return 'Your cancellation request was declined after review. Please contact support for the reason and next steps.';
			}
			if ( 'approved' === $review && '' === (string) $order->get_meta( '_veloria_refund_state' ) && '' === (string) $order->get_meta( '_shiprocket_cancel_state' ) ) {
				return 'Cancellation approved after review. Your order is on hold while our team confirms courier cancellation and any refund to the original payment method.';
			}
		}
		if ( 'yes' !== (string) $order->get_meta( '_veloria_cancel_requested' ) || 'cancelled' === $order->get_status() ) {
			return null;
		}
		switch ( (string) $order->get_meta( '_veloria_refund_state' ) ) {
			case 'pending':
				return 'Cancellation accepted. Your refund is being processed by the payment provider.';
			case '':
				return 'manual-review' === (string) $order->get_meta( '_shiprocket_cancel_state' )
					? 'Cancellation requested. We are confirming it with the courier before refunding.'
					: null;
			default:
				return 'Cancellation requested and fulfillment is on hold. Our team is completing your refund to the original payment method.';
		}
	}

	/** @return array{ok:bool,reason?:string} */
	public static function is_returnable( \WC_Order $order, ?int $now = null ): array {
		$now     = $now ?? time();
		$support = Settings::support_email();
		if ( ! in_array( $order->get_status(), self::RETURNABLE, true ) ) {
			return array( 'ok' => false, 'reason' => 'Returns can only be requested for processing or delivered orders.' );
		}
		$days    = (int) Settings::get( 'return_window_days', 7 );
		$delivered = self::delivery_timestamp( $order );
		$created = $order->get_date_created();
		if ( null === $delivered || $delivered > $now || ( $created && $delivered < $created->getTimestamp() ) ) {
			return array( 'ok' => false, 'reason' => "Your delivery date needs verification. Please contact {$support} to request a return; the window starts at delivery." );
		}
		if ( $now - $delivered > $days * DAY_IN_SECONDS ) {
			return array( 'ok' => false, 'reason' => "The {$days}-day return window has passed. Please contact {$support}." );
		}
		return array( 'ok' => true );
	}

	/** Populated only by verified carrier delivery or an explicit staff check. */
	public static function delivery_timestamp( \WC_Order $order ): ?int {
		$value = (string) $order->get_meta( '_veloria_delivered_at' );
		if ( 'yes' !== (string) $order->get_meta( '_veloria_delivery_verified' ) || ! ctype_digit( $value ) || (int) $value <= 0 ) {
			return null;
		}
		return (int) $value;
	}

	/** Real amount charged: custom discounts live in meta for headless-era orders. */
	public static function amount_paid( \WC_Order $order ): string {
		$charge = (string) $order->get_meta( '_headless_charge_amount' );
		$native = 'WordPress Storefront' === (string) $order->get_meta( '_order_source' ) || 'yes' === (string) $order->get_meta( '_veloria_native_checkout' );
		return ! $native && is_numeric( $charge ) && is_finite( (float) $charge ) && (float) $charge >= 0 ? $charge : (string) $order->get_total();
	}

	/**
	 * Same shape as OrderDTO in src/lib/order-dto.ts.
	 *
	 * @return array<string,mixed>
	 */
	public static function dto( \WC_Order $order ): array {
		$payment_id = (string) ( $order->get_transaction_id() ?: $order->get_meta( '_razorpay_payment_id' ) );
		$return_requested = 'yes' === (string) $order->get_meta( '_return_requested' );
		$return_decision  = (string) $order->get_meta( '_veloria_return_decision' );
		$lines      = array();
		foreach ( $order->get_items() as $item ) {
			$lines[] = array(
				'name'     => $item->get_name(),
				'quantity' => (int) $item->get_quantity(),
				'total'    => (string) $item->get_total(),
			);
		}
		$created = $order->get_date_created();
		return array(
			'id'            => $order->get_id(),
			'number'        => (string) $order->get_order_number(),
			'status'        => $order->get_status(),
			'total'         => (string) $order->get_total(),
			'amountPaid'    => self::amount_paid( $order ),
			'paymentMethod' => $order->get_payment_method_title(),
			'paymentId'     => '' !== $payment_id ? $payment_id : null,
			'dateCreated'   => $created ? $created->format( 'Y-m-d\TH:i:s' ) : '',
			'lineItems'     => $lines,
			'returnRequested' => $return_requested,
			'returnRequestedAt' => $return_requested ? ( (string) $order->get_meta( '_return_requested_at' ) ?: null ) : null,
			'returnState' => $return_requested ? ( in_array( $return_decision, array( 'approved', 'declined' ), true ) ? $return_decision : 'requested' ) : null,
			'returnRequestReason' => $return_requested ? ( (string) $order->get_meta( '_return_reason' ) ?: null ) : null,
		) + self::actions( $order ) + self::money( $order );
	}

	/**
	 * Server-side eligibility shared by every order screen, so buttons are only
	 * offered when the matching endpoint would accept the request.
	 *
	 * @return array{canCancel:bool,cancelMode:?string,cancelReviewState:?string,cancelReason:?string,canReturn:bool,returnReason:?string,refundProgress:?string}
	 */
	public static function actions( \WC_Order $order ): array {
		$cancel = self::is_cancellable( $order );
		$review = (string) $order->get_meta( '_veloria_cancel_review_state' );
		$return = 'yes' === (string) $order->get_meta( '_return_requested' ) ? array( 'ok' => false, 'reason' => 'A return has already been requested for this order.' ) : self::is_returnable( $order );
		return array(
			'canCancel'      => $cancel['ok'],
			'cancelMode'     => $cancel['mode'] ?? null,
			'cancelReviewState' => in_array( $review, array( 'requested', 'approved', 'declined' ), true ) ? $review : null,
			'cancelReason'   => $cancel['ok'] ? null : ( $cancel['reason'] ?? null ),
			'canReturn'      => $return['ok'],
			'returnReason'   => $return['ok'] ? null : ( $return['reason'] ?? null ),
			'refundProgress' => self::refund_progress( $order ),
		);
	}

	/**
	 * Order total vs money actually collected/returned. COD is not "paid" until
	 * the courier collects it.
	 *
	 * @return array{isCod:bool,codDue:?string,amountCaptured:?string,amountRefunded:string}
	 */
	public static function money( \WC_Order $order ): array {
		$is_cod = (string) Settings::get( 'cod_gateway', 'cod' ) === $order->get_payment_method();
		$paid   = $is_cod ? ( 'completed' === $order->get_status() && null !== self::delivery_timestamp( $order ) ) : ( $order->is_paid() || (bool) $order->get_date_paid() );
		return array(
			'isCod'          => $is_cod,
			'codDue'         => $is_cod && ! $paid && ! in_array( $order->get_status(), array( 'cancelled', 'refunded', 'failed' ), true ) ? self::amount_paid( $order ) : null,
			'amountCaptured' => $paid ? self::amount_paid( $order ) : null,
			'amountRefunded' => wc_format_decimal( (float) $order->get_total_refunded(), 2 ),
		);
	}

	/**
	 * Public tracking payload (same shape as /api/orders/track).
	 *
	 * @return array<string,mixed>
	 */
	public static function tracking_details( \WC_Order $order ): array {
		$lines = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;
			$image   = $product ? wp_get_attachment_image_url( (int) $product->get_image_id(), 'thumbnail' ) : '';
			$lines[] = array(
				'name'     => $item->get_name(),
				'quantity' => (int) $item->get_quantity(),
				'total'    => (string) $item->get_total(),
				'image'    => $image ?: null,
			);
		}
		$shipping = array();
		foreach ( $order->get_shipping_methods() as $line ) {
			$shipping[] = array(
				'methodTitle' => $line->get_method_title(),
				'total'       => (string) $line->get_total(),
			);
		}
		$created = $order->get_date_created();
		$states  = WC()->countries->get_states( 'IN' );
		$state   = static fn( string $code ): string => is_array( $states ) && isset( $states[ $code ] ) ? html_entity_decode( (string) $states[ $code ] ) : $code;
		// Ship to the separate shipping address when the customer gave one.
		$has_shipping = method_exists( $order, 'has_shipping_address' ) && $order->has_shipping_address();
		$prefix       = $has_shipping ? 'shipping' : 'billing';
		$get          = static fn( string $field ): string => (string) $order->{ 'get_' . $prefix . '_' . $field }();

		return array(
			'id'            => $order->get_id(),
			'number'        => (string) $order->get_order_number(),
			'status'        => $order->get_status(),
			'total'         => (string) $order->get_total(),
			'amountPaid'    => self::amount_paid( $order ),
			'paymentMethod' => $order->get_payment_method_title(),
			'dateCreated'   => $created ? $created->format( 'Y-m-d\TH:i:s' ) : '',
			'billing'       => array(
				'firstName' => $order->get_billing_first_name(),
				'lastName'  => $order->get_billing_last_name(),
				'email'     => $order->get_billing_email(),
				'phone'     => $order->get_billing_phone(),
				'address'   => $order->get_billing_address_1(),
				'address2'  => $order->get_billing_address_2(),
				'city'      => $order->get_billing_city(),
				'state'     => $state( (string) $order->get_billing_state() ),
				'postcode'  => $order->get_billing_postcode(),
			),
			'shipping'      => array(
				'firstName' => $get( 'first_name' ),
				'lastName'  => $get( 'last_name' ),
				'address'   => $get( 'address_1' ),
				'address2'  => $get( 'address_2' ),
				'city'      => $get( 'city' ),
				'state'     => $state( $get( 'state' ) ),
				'postcode'  => $get( 'postcode' ),
				'sameAsBilling' => ! $has_shipping,
			),
			'lineItems'     => $lines,
			'shippingLines' => $shipping,
		) + self::actions( $order ) + self::money( $order );
	}

	public const CHECK_ACTION = 'veloria_refund_check';
	private const CHECK_DELAYS = array( 600, 1800, 7200, 21600, 86400 );
	private const MAX_CHECKS   = 12;

	/**
	 * Cancel with a full refund for captured prepaid orders. Same rules as
	 * /api/orders/cancel: refuse to cancel if the refund fails, so an order is
	 * never cancelled without the money going back. $automatic is the
	 * background refund check: it never starts a new refund attempt.
	 *
	 * @return array{success:bool,refunded?:bool,error?:string,status?:int}
	 */
	public static function cancel( \WC_Order $order, bool $automatic = false, string $courier_reference = '' ): array {
		$order_id = $order->get_id();
		$lock = 'veloria_cancel_lock_' . $order_id;
		if ( ! add_option( $lock, time(), '', false ) ) {
			return array( 'success' => false, 'status' => 409, 'retryable' => true, 'error' => 'Cancellation is already being processed. Please contact support if it does not finish.' );
		}
		// Shipment creation holds this lock while it talks to the courier, so a
		// cancellation and a shipment creation can never interleave.
		$ship_lock = 'veloria_sr_lock_' . $order_id;
		if ( ! add_option( $ship_lock, time(), '', false ) ) {
			delete_option( $lock );
			return array( 'success' => false, 'status' => 409, 'retryable' => true, 'error' => 'Your order is being handed to the courier right now. Please try again in a minute.' );
		}
		try {
			// Reload after taking the lock: a second request must see the first result.
			$order = wc_get_order( $order->get_id() );
			if ( ! $order instanceof \WC_Order ) {
				return array( 'success' => false, 'status' => 404, 'error' => 'Order could not be loaded.' );
			}
			if ( 'cancelled' === $order->get_status() && 'customer' === (string) $order->get_meta( '_cancelled_via' ) ) {
				return array( 'success' => true, 'refunded' => '' !== (string) $order->get_meta( '_razorpay_refund_id' ) || 'accepted' === (string) $order->get_meta( '_veloria_gateway_refund_state' ) );
			}
			if ( '' !== $courier_reference ) {
				if ( $automatic || ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $order_id ) || 'yes' !== (string) $order->get_meta( '_veloria_cancel_requested' ) || strlen( $courier_reference ) < 8 || strlen( $courier_reference ) > 200 ) {
					return array( 'success' => false, 'status' => 403, 'error' => 'Courier clearance requires an authorized staff member and a reference from the matching Shiprocket order.' );
				}
				$order->update_meta_data( '_veloria_sr_staff_clearance', array( 'actor' => get_current_user_id(), 'at' => time(), 'reference' => $courier_reference, 'fingerprint' => Shiprocket::cancellation_fingerprint( $order ) ) );
				$order->save();
			}
			if ( $automatic && 'requested' !== (string) $order->get_meta( '_veloria_cancel_review_state' ) ) {
				$order->update_meta_data( '_veloria_refund_checks', (int) $order->get_meta( '_veloria_refund_checks' ) + 1 );
				$order->save();
			}
			$result = self::cancel_locked( $order, $automatic );
			if ( ! $result['success'] ) {
				self::release_cancel_hold( $order_id );
			}
			return $result;
		} catch ( \Throwable $error ) {
			wc_get_logger()->error( 'Cancellation requires review for order ' . $order_id . ': ' . $error->getMessage(), array( 'source' => 'veloria-commerce' ) );
			return array( 'success' => false, 'status' => 502, 'error' => 'Cancellation could not be completed. Please contact support; do not submit another payment or refund.' );
		} finally {
			if ( '' !== $courier_reference && isset( $order ) && $order instanceof \WC_Order ) {
				$order->delete_meta_data( '_veloria_sr_staff_clearance' );
				$order->save();
			}
			delete_option( $ship_lock );
			delete_option( $lock );
		}
	}

	/** Background continuation of an accepted cancellation (pending/unknown refund). */
	public static function scheduled_check( $order_id ): void {
		$order = wc_get_order( (int) $order_id );
		if ( ! $order instanceof \WC_Order || 'yes' !== (string) $order->get_meta( '_veloria_cancel_requested' ) || 'cancelled' === $order->get_status() || in_array( (string) $order->get_meta( '_veloria_cancel_review_state' ), array( 'requested', 'declined' ), true ) ) {
			return;
		}
		$result = self::cancel( $order, true );
		if ( ! $result['success'] && ! empty( $result['retryable'] ) ) {
			// WP-Cron removes a one-shot event before invoking it. A busy operation
			// must enqueue its continuation and must not spend the provider budget.
			$order = wc_get_order( (int) $order_id );
			if ( $order instanceof \WC_Order && 'yes' === (string) $order->get_meta( '_veloria_cancel_requested' ) && 'cancelled' !== $order->get_status() ) {
				self::schedule_check( $order, MINUTE_IN_SECONDS );
			}
		}
	}

	private static function schedule_check( \WC_Order $order, ?int $delay = null ): void {
		$checks = (int) $order->get_meta( '_veloria_refund_checks' );
		if ( $checks >= self::MAX_CHECKS ) {
			self::flag_attention( $order, 'Refund still unconfirmed after ' . $checks . ' automatic checks.' );
			return;
		}
		$args = array( $order->get_id() );
		if ( ! wp_next_scheduled( self::CHECK_ACTION, $args ) ) {
			$delay = $delay ?? self::CHECK_DELAYS[ min( $checks, count( self::CHECK_DELAYS ) - 1 ) ];
			$scheduled = wp_schedule_single_event( time() + $delay, self::CHECK_ACTION, $args, true );
			if ( is_wp_error( $scheduled ) || false === $scheduled ) {
				self::flag_attention( $order, 'Automatic refund verification could not be scheduled. Check WordPress cron and verify the provider refund before retrying.' );
				$order->add_order_note( 'Automatic refund verification could not be scheduled. Fulfillment remains on hold; staff must verify the refund.' );
			}
		}
	}

	/** Orders whose cancellation refund needs a person, shown on admin screens. */
	private static function flag_attention( \WC_Order $order, string $reason ): void {
		$list = get_option( 'veloria_refund_attention', array() );
		$list = is_array( $list ) ? $list : array();
		$list[ $order->get_id() ] = array( 'reason' => $reason, 'at' => time() );
		update_option( 'veloria_refund_attention', $list, false );
	}

	private static function clear_attention( int $order_id ): void {
		$list = get_option( 'veloria_refund_attention', array() );
		if ( is_array( $list ) && isset( $list[ $order_id ] ) ) {
			unset( $list[ $order_id ] );
			update_option( 'veloria_refund_attention', $list, false );
		}
	}

	/**
	 * A cancellation that failed before any courier or payment action must not
	 * leave the shipping hold behind, or the paid order would never ship.
	 */
	private static function release_cancel_hold( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order || 'yes' !== (string) $order->get_meta( '_veloria_cancel_requested' ) ) {
			return;
		}
		if ( in_array( (string) $order->get_meta( '_veloria_cancel_review_state' ), array( 'requested', 'approved' ), true ) ) {
			return;
		}
		foreach ( array( '_shiprocket_cancel_state', '_veloria_refund_state', '_razorpay_refund_id', '_veloria_gateway_refund_state' ) as $key ) {
			if ( '' !== (string) $order->get_meta( $key ) ) {
				return;
			}
		}
		if ( (float) $order->get_total_refunded() > 0 ) {
			return;
		}
		$order->delete_meta_data( '_veloria_cancel_requested' );
		$order->delete_meta_data( '_veloria_cancel_accepted_at' );
		$order->delete_meta_data( '_veloria_cancel_legacy_verified_at' );
		$order->save();
	}

	/** Serialized cancellation. Unknown provider results are never blindly resubmitted. */
	private static function cancel_locked( \WC_Order $order, bool $automatic = false ): array {
		$eligibility = self::is_cancellable( $order );
		if ( 'requested' === (string) $order->get_meta( '_veloria_cancel_review_state' ) ) {
			return self::pending_cancel_review( $order );
		}
		if ( $eligibility['ok'] && 'review' === ( $eligibility['mode'] ?? '' ) ) {
			if ( $automatic ) {
				return array( 'success' => false, 'status' => 409, 'error' => 'A new cancellation review must be requested by the customer.' );
			}
			$order->update_meta_data( '_veloria_cancel_requested', 'yes' );
			$order->update_meta_data( '_veloria_cancel_review_state', 'requested' );
			$order->update_meta_data( '_veloria_cancel_review_requested_at', time() );
			$order->update_status( 'on-hold', 'Customer cancellation needs staff review. Stop fulfillment; no courier or refund action was started.' );
			$order->add_order_note( 'We received your cancellation request and put your order on hold for review. We will check whether it can be stopped and update you. No refund has been issued yet.', true );
			$order->save();
			self::flag_attention( $order, 'Cancellation request needs staff approval before any courier or refund action.' );
			return self::pending_cancel_review( $order );
		}
		$finishing_refund = in_array( $order->get_status(), array( 'refunded', 'on-hold' ), true ) && (
			( 'processed' === (string) $order->get_meta( '_veloria_refund_state' ) && '' !== (string) $order->get_meta( '_razorpay_refund_id' ) )
			|| ( 'accepted' === (string) $order->get_meta( '_veloria_gateway_refund_state' ) && (int) $order->get_meta( '_veloria_gateway_refund_id' ) > 0 )
		);
		if ( ! $eligibility['ok'] && ! $finishing_refund ) {
			return array( 'success' => false, 'error' => $eligibility['reason'], 'status' => 409 );
		}
		$legacy = self::legacy_cancellation_recorded( $order );
		$order->update_meta_data( '_veloria_cancel_requested', 'yes' );
		if ( $legacy && ! (int) $order->get_meta( '_veloria_cancel_legacy_verified_at' ) ) {
			$order->update_meta_data( '_veloria_cancel_legacy_verified_at', time() );
			$order->add_order_note( 'Continuing a cancellation recorded before refund idempotency was introduced. The existing cancellation/refund markers establish the prior request; no new cancellation window was granted.' );
		} elseif ( ! $legacy && ! self::approved_cancel_review( $order ) && ! (int) $order->get_meta( '_veloria_cancel_accepted_at' ) ) {
			$order->update_meta_data( '_veloria_cancel_accepted_at', time() );
		}
		$order->save();

		// Stop the courier first: money is only returned once the parcel can no
		// longer ship. A failed courier cancel holds the order for staff, unrefunded.
		$courier = Shiprocket::cancel_for_order( $order );
		if ( ! $courier['success'] ) {
				$order->update_meta_data( '_shiprocket_cancel_state', 'manual-review' );
				$order->update_status( 'on-hold', 'Customer cancellation requested; courier cancellation could not be confirmed. Stop fulfillment and review before any refund.' );
				self::flag_attention( $order, (string) ( $courier['error'] ?? 'Courier cancellation could not be confirmed.' ) );
				return array( 'success' => false, 'status' => 502, 'error' => 'Your cancellation needs courier confirmation. Support will finish it and arrange any refund; fulfillment has been put on hold.' );
		}

		$refunded   = false;
		$payment_id = (string) ( $order->get_meta( '_razorpay_payment_id' ) ?: $order->get_transaction_id() );
		$is_cod     = (string) Settings::get( 'cod_gateway', 'cod' ) === $order->get_payment_method();
		$paid       = 'completed' === (string) $order->get_meta( '_payment_status' ) || (bool) $order->get_date_paid() || ( ! $is_cod && $order->is_paid() );
		$refund_id  = (string) $order->get_meta( '_razorpay_refund_id' );
		$is_razorpay = 'razorpay' === $order->get_payment_method()
			|| ( '' !== (string) $order->get_meta( '_razorpay_payment_id' ) && str_starts_with( (string) $order->get_meta( '_paid_via' ), 'razorpay' ) );
		if ( ! $is_cod && ! $is_razorpay && ( $paid || '' !== $payment_id || $finishing_refund ) ) {
			$result = GatewayRefund::cancel_payment( $order );
			if ( ! $result['success'] ) {
				return array( 'success' => false, 'status' => 409, 'error' => $result['error'] );
			}
			$refunded = true;
		} elseif ( $paid || '' !== $refund_id || '' !== $payment_id ) {
			if ( $is_cod || ! preg_match( '/^pay_[A-Za-z0-9]+$/', $payment_id ) || 'INR' !== $order->get_currency() ) {
				return array( 'success' => false, 'status' => 409, 'error' => 'This paid order needs a verified refund from support before it can be cancelled.' );
			}
			$amount = (int) round( (float) self::amount_paid( $order ) * 100 );
			if ( $amount <= 0 ) {
				return array( 'success' => false, 'status' => 409, 'error' => 'The refundable amount needs staff review.' );
			}
			$refund = self::razorpay_refund( $order, $payment_id, $amount, $automatic );
			if ( 'invalid' === $refund['state'] ) {
				return array( 'success' => false, 'status' => 409, 'error' => (string) $refund['error'] );
			}
			if ( 'processed' !== $refund['state'] ) {
				return self::hold_for_refund( $order, $refund );
			}
			$refund_id = (string) $refund['refundId'];
			$recorded  = false;
			foreach ( $order->get_refunds() as $existing ) {
				if ( (string) $existing->get_meta( '_razorpay_refund_id' ) === $refund_id || $existing->get_reason() === 'Customer cancellation — Razorpay ' . $refund_id ) {
					$recorded = true;
					break;
				}
			}
			if ( ! $recorded ) {
				$lines = self::full_refund_lines( $order );
				$local_refund = wc_create_refund( array( 'order_id' => $order->get_id(), 'amount' => $amount / 100, 'reason' => 'Customer cancellation — Razorpay ' . $refund_id, 'line_items' => is_wp_error( $lines ) ? array() : $lines, 'refund_payment' => false, 'restock_items' => false ) );
				if ( is_wp_error( $local_refund ) ) {
					self::flag_attention( $order, 'Razorpay refund ' . $refund_id . ' processed but the WooCommerce refund record failed.' );
					if ( in_array( $order->get_status(), array( 'pending', 'processing' ), true ) ) {
						$order->update_status( 'on-hold', 'Razorpay refund ' . $refund_id . ' processed; WooCommerce refund record failed. Do not fulfil.' );
					}
					return array( 'success' => false, 'status' => 502, 'error' => 'The payment refund was processed; support must finish the order refund record. Do not request another refund.' );
				}
				$local_refund->update_meta_data( '_razorpay_refund_id', $refund_id );
				$local_refund->save();
			}
			$order->update_meta_data( '_payment_status', 'refunded' );
			$order->add_order_note( sprintf( 'Customer cancellation — Razorpay refund %s processed and recorded in WooCommerce.', $refund_id ) );
			$refunded = true;
		}

		$order->update_meta_data( '_cancelled_via', 'customer' );
		$order->update_meta_data( '_cancelled_at', gmdate( 'c' ) );
		$order->update_status( 'cancelled', 'Cancelled by customer online.' );
		self::clear_attention( $order->get_id() );

		return array( 'success' => true, 'refunded' => $refunded );
	}

	/** An acknowledgement of review is never a cancellation or refund receipt. */
	private static function pending_cancel_review( \WC_Order $order ): array {
		return array( 'success' => true, 'pendingReview' => true, 'refunded' => false, 'cancelReviewState' => 'requested', 'message' => self::refund_progress( $order ) );
	}

	/**
	 * Reconcile-first Razorpay refund for one cancellation. States:
	 * requesting (POST sent, keyed) → pending → processed; rejected / failed
	 * (provider definitely moved no money); review (staff must decide).
	 *
	 * @return array{state:string,refundId?:string,error?:string}
	 */
	private static function razorpay_refund( \WC_Order $order, string $payment_id, int $amount, bool $automatic ): array {
		$state     = (string) $order->get_meta( '_veloria_refund_state' );
		$refund_id = (string) $order->get_meta( '_razorpay_refund_id' );
		$key       = (string) $order->get_meta( '_veloria_refund_key' );

		if ( '' === $refund_id && 'requesting' === $state ) {
			// The earlier POST outcome is unknown: the provider's records decide.
			$refunds = Razorpay::payment_refunds( $payment_id );
			if ( null === $refunds ) {
				return array( 'state' => 'unknown', 'error' => 'Razorpay refund records could not be read.' );
			}
			$live = array_values( array_filter( $refunds, static fn( $r ) => 'failed' !== $r['status'] ) );
			if ( count( $live ) > 1 || ( 1 === count( $live ) && $live[0]['amount'] !== $amount ) ) {
				return self::record_refund_state( $order, 'review', 'Razorpay holds ' . count( $live ) . ' refund(s) for this payment that do not match one full cancellation refund.' );
			}
			if ( 1 === count( $live ) ) {
				$refund_id = $live[0]['id'];
				$order->update_meta_data( '_razorpay_refund_id', $refund_id );
				$order->add_order_note( 'Recovered Razorpay refund ' . $refund_id . ' from provider records.' );
				$order->save();
			} elseif ( '' === $key ) {
				// Legacy attempt without an idempotency key. One empty lookup is not
				// proof the old request was rejected, so a person must confirm it.
				return self::record_refund_state( $order, 'review', 'An earlier refund attempt has no idempotency key and Razorpay currently shows no refund. Confirm in the Razorpay dashboard that it was rejected, then use "Confirm earlier refund was rejected" on this order.' );
			}
			// Keyed attempt and no refund yet: replay below with the same key and body.
		}

		if ( '' !== $refund_id ) {
			$result = Razorpay::refund_status( $refund_id, $payment_id, $amount );
		} else {
			if ( 'review' === $state || ( $automatic && in_array( $state, array( 'rejected', 'failed' ), true ) ) ) {
				return array( 'state' => $state, 'error' => (string) $order->get_meta( '_veloria_refund_error' ) );
			}
			if ( 'requesting' !== $state ) {
				if ( (float) $order->get_total_refunded() > 0 ) {
					return self::record_refund_state( $order, 'review', 'WooCommerce already records a refund on this order.' );
				}
				if ( '' !== $state ) {
					// Retrying after a rejection/failure: the provider must hold no live refund.
					$refunds = Razorpay::payment_refunds( $payment_id );
					if ( null === $refunds ) {
						return array( 'state' => $state, 'error' => 'Razorpay refund records could not be read.' );
					}
					foreach ( $refunds as $existing ) {
						if ( 'failed' !== $existing['status'] ) {
							return self::record_refund_state( $order, 'review', 'Razorpay already holds refund ' . $existing['id'] . ' (' . $existing['status'] . ').' );
						}
					}
				}
				$verified = Razorpay::captured_payment( $payment_id, $amount );
				if ( ! $verified['success'] ) {
					return '' === $state ? array( 'state' => 'invalid', 'error' => (string) $verified['error'] ) : array( 'state' => $state, 'error' => (string) $verified['error'] );
				}
				$key      = 'vv' . $order->get_id() . '-' . bin2hex( random_bytes( 8 ) );
				$attempts = $order->get_meta( '_veloria_refund_attempts' );
				$attempts = is_array( $attempts ) ? $attempts : array();
				$attempts[] = array( 'key' => $key, 'amount' => $amount, 'at' => time() );
				$order->update_meta_data( '_veloria_refund_state', 'requesting' );
				$order->update_meta_data( '_veloria_refund_key', $key );
				$order->update_meta_data( '_veloria_refund_amount', $amount );
				$order->update_meta_data( '_veloria_refund_attempts', $attempts );
				$order->save();
				$persisted = wc_get_order( $order->get_id() );
				if ( ! $persisted || $key !== (string) $persisted->get_meta( '_veloria_refund_key' ) ) {
					return array( 'state' => 'unknown', 'error' => 'Refund attempt could not be safely recorded.' );
				}
			} elseif ( (int) $order->get_meta( '_veloria_refund_amount' ) !== $amount ) {
				return self::record_refund_state( $order, 'review', 'The order amount changed since the keyed refund attempt; an idempotent replay must use the original body.' );
			}
			$result = Razorpay::refund( $payment_id, $amount, $key );
		}

		if ( $result['success'] ) {
			$order->update_meta_data( '_razorpay_refund_id', (string) $result['refundId'] );
			$order->update_meta_data( '_veloria_refund_state', (string) $result['status'] );
			$order->delete_meta_data( '_veloria_refund_error' );
			$order->save();
			return array( 'state' => (string) $result['status'], 'refundId' => (string) $result['refundId'] );
		}
		$outcome = (string) ( $result['outcome'] ?? 'unknown' );
		wc_get_logger()->error( sprintf( 'Refund %s for order %d payment %s: %s', strtoupper( $outcome ), $order->get_id(), $payment_id, $result['error'] ?? '' ), array( 'source' => 'veloria-commerce' ) );
		if ( 'rejected' === $outcome || 'failed' === $outcome ) {
			if ( 'failed' === $outcome && '' !== $refund_id ) {
				$failed   = $order->get_meta( '_veloria_refund_failed_ids' );
				$failed   = is_array( $failed ) ? $failed : array();
				$failed[] = $refund_id;
				$order->update_meta_data( '_veloria_refund_failed_ids', $failed );
				$order->delete_meta_data( '_razorpay_refund_id' );
			}
			$order->delete_meta_data( '_veloria_refund_key' );
			$reason = 'rejected' === $outcome ? 'Razorpay rejected the refund: ' . (string) ( $result['error'] ?? '' ) : 'Razorpay reported the refund as failed.';
			return self::record_refund_state( $order, $outcome, $reason . ' No money moved. Resolve the cause (e.g. add refund balance in Razorpay), then use "Retry refund now".' );
		}
		return array( 'state' => 'unknown', 'error' => (string) ( $result['error'] ?? '' ) );
	}

	/** @return array{state:string,error:string} */
	private static function record_refund_state( \WC_Order $order, string $state, string $reason ): array {
		$changed = $state !== (string) $order->get_meta( '_veloria_refund_state' ) || $reason !== (string) $order->get_meta( '_veloria_refund_error' );
		$order->update_meta_data( '_veloria_refund_state', $state );
		$order->update_meta_data( '_veloria_refund_error', $reason );
		if ( $changed ) {
			$order->add_order_note( 'Cancellation refund ' . $state . ': ' . $reason );
		}
		$order->save();
		self::flag_attention( $order, $reason );
		return array( 'state' => $state, 'error' => $reason );
	}

	/** Accepted cancellation whose refund is not finished: keep the parcel out of fulfillment. */
	private static function hold_for_refund( \WC_Order $order, array $refund ): array {
		$state = (string) $refund['state'];
		if ( in_array( $order->get_status(), array( 'pending', 'processing' ), true ) ) {
			$order->update_status( 'on-hold', 'Customer cancellation accepted; refund ' . $state . '. Do not fulfil this order.' );
		}
		if ( in_array( $state, array( 'pending', 'unknown', 'requesting' ), true ) ) {
			self::schedule_check( $order );
		}
		$support = Settings::support_email();
		if ( 'pending' === $state ) {
			return array( 'success' => false, 'status' => 409, 'error' => 'Your cancellation is accepted and the order will not ship. Your refund is pending with the payment provider and will be confirmed automatically.' );
		}
		return array( 'success' => false, 'status' => 409, 'error' => "Your cancellation request is recorded and the order will not ship. Our team is completing your refund to the original payment method; please do not request it again. Questions: {$support}." );
	}

	/**
	 * Staff confirmation that a legacy (unkeyed) attempt was rejected, after the
	 * provider shows no refund and an untouched captured payment.
	 */
	public static function confirm_refund_rejected( \WC_Order $order, int $user_id ): bool {
		$id = $order->get_id();
		if ( $user_id <= 0 || $user_id !== get_current_user_id() || ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $id ) ) {
			return false;
		}
		$lock = 'veloria_cancel_lock_' . $id;
		$ship_lock = 'veloria_sr_lock_' . $id;
		if ( ! add_option( $lock, time(), '', false ) ) {
			return false;
		}
		if ( ! add_option( $ship_lock, time(), '', false ) ) {
			delete_option( $lock );
			return false;
		}
		try {
			$order = wc_get_order( $id );
			if ( ! $order instanceof \WC_Order || $order instanceof \WC_Order_Refund || 'yes' !== (string) $order->get_meta( '_veloria_cancel_requested' )
				|| ! in_array( (string) $order->get_meta( '_veloria_refund_state' ), array( 'review', 'requesting' ), true )
				|| '' !== (string) $order->get_meta( '_razorpay_refund_id' ) || '' !== (string) $order->get_meta( '_veloria_refund_key' )
				|| $order->get_meta( '_veloria_refund_attempts' ) || (float) $order->get_total_refunded() > 0 ) {
				return false;
			}
			$payment_id = (string) ( $order->get_meta( '_razorpay_payment_id' ) ?: $order->get_transaction_id() );
			$refunds = Razorpay::payment_refunds( $payment_id );
			$amount = (int) round( (float) self::amount_paid( $order ) * 100 );
			if ( array() !== $refunds || ! Razorpay::captured_payment( $payment_id, $amount )['success'] ) {
				return false;
			}
			if ( self::legacy_cancellation_recorded( $order ) ) {
				$order->update_meta_data( '_veloria_cancel_legacy_verified_at', time() );
			}
			$order->add_order_note( 'User #' . $user_id . ' confirmed the earlier unkeyed refund attempt was rejected; Razorpay shows no refund and the full captured amount. A new keyed attempt is now allowed.' );
			$order->save();
			self::record_refund_state( $order, 'rejected', 'Earlier unkeyed attempt confirmed rejected by staff. Use "Retry refund now" once Razorpay has refund balance.' );
			return true;
		} finally {
			delete_option( $ship_lock );
			delete_option( $lock );
		}
	}

	/** Full inspected-return refund. No browser-supplied amounts; same durable Razorpay identity. */
	public static function staff_return_refund( \WC_Order $order, string $reason ): array {
		$id = $order->get_id();
		if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $id ) || mb_strlen( trim( $reason ) ) < 5 ) {
			return array( 'success' => false, 'error' => 'Authorized staff and a refund reason are required.' );
		}
		$lock = 'veloria_cancel_lock_' . $id;
		$ship_lock = 'veloria_sr_lock_' . $id;
		if ( ! add_option( $lock, time(), '', false ) ) {
			return array( 'success' => false, 'error' => 'Another refund operation is in progress. Reopen the order shortly.' );
		}
		if ( ! add_option( $ship_lock, time(), '', false ) ) {
			delete_option( $lock );
			return array( 'success' => false, 'error' => 'A courier operation is in progress. Reopen the order shortly.' );
		}
		try {
			$order = wc_get_order( $id );
			if ( ! $order instanceof \WC_Order || $order instanceof \WC_Order_Refund || 'yes' !== (string) $order->get_meta( '_return_requested' ) || 'approved' !== (string) $order->get_meta( '_veloria_return_decision' ) || null === self::delivery_timestamp( $order ) || 'yes' === (string) $order->get_meta( '_veloria_cancel_requested' ) ) {
				return array( 'success' => false, 'error' => 'Save verified delivery and approve the inspected return first. Resolve any cancellation separately.' );
			}
			$amount = (int) round( (float) self::amount_paid( $order ) * 100 );
			if ( (float) $order->get_total_refunded() > 0 ) {
				return array( 'success' => false, 'error' => 'An existing refund is recorded. Review it before issuing any further refund.' );
			}
			$payment_id = (string) ( $order->get_meta( '_razorpay_payment_id' ) ?: $order->get_transaction_id() );
			if ( 'razorpay' !== $order->get_payment_method() || 'INR' !== $order->get_currency() || ! preg_match( '/^pay_[A-Za-z0-9]+$/', $payment_id ) || $amount <= 0 ) {
				return array( 'success' => false, 'error' => 'This payment needs support reconciliation; the simple source refund supports captured Razorpay payments only.' );
			}
			$lines = self::full_refund_lines( $order );
			if ( is_wp_error( $lines ) ) {
				return array( 'success' => false, 'error' => $lines->get_error_message() );
			}
			$refund = self::razorpay_refund( $order, $payment_id, $amount, false );
			if ( 'processed' !== $refund['state'] ) {
				return array( 'success' => false, 'error' => (string) ( $refund['error'] ?? 'Refund is pending with Razorpay. Use Check / retry refund to verify its existing reference; do not issue a manual refund.' ) );
			}
			$local = wc_create_refund( array( 'order_id' => $id, 'amount' => $amount / 100, 'reason' => 'Inspected return — Razorpay ' . $refund['refundId'], 'line_items' => $lines, 'refund_payment' => false, 'restock_items' => false ) );
			if ( is_wp_error( $local ) ) {
				return array( 'success' => false, 'error' => 'Razorpay processed the refund. The WooCommerce record needs reconciliation; do not issue another refund.' );
			}
			$local->update_meta_data( '_razorpay_refund_id', $refund['refundId'] );
			$local->save();
			$order->update_meta_data( '_payment_status', 'refunded' );
			$order->add_order_note( 'Full inspected-return source refund confirmed by Razorpay ' . $refund['refundId'] . '. Staff #' . get_current_user_id() . ': ' . mb_substr( sanitize_textarea_field( $reason ), 0, 500 ) );
			$order->save();
			self::clear_attention( $id );
			return array( 'success' => true, 'refunded' => true );
		} finally {
			delete_option( $ship_lock );
			delete_option( $lock );
		}
	}

	/** Include signed discount fees and every tax component; never refund the pre-discount price. */
	public static function full_refund_lines( \WC_Order $order ) {
		$lines = array();
		$sum = 0.0;
		foreach ( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $id => $item ) {
			$taxes = $item->get_taxes();
			$taxes = $taxes['total'] ?? array();
			$total = (float) $item->get_total();
			$sum += $total + array_sum( array_map( 'floatval', $taxes ) );
			$lines[ $id ] = array( 'qty' => $item->get_quantity(), 'refund_total' => $total, 'refund_tax' => $taxes );
		}
		if ( abs( (int) round( $sum * 100 ) - (int) round( (float) self::amount_paid( $order ) * 100 ) ) > 1 ) {
			return new \WP_Error( 'refund_lines', 'The paid amount and order tax lines differ. Support must reconcile this legacy order before a full refund.' );
		}
		return $lines;
	}

	/** Staff-initiated retry of an accepted cancellation (same safety rules as the customer path). */
	public static function staff_retry( \WC_Order $order, string $courier_reference = '' ): array {
		if ( 'yes' !== (string) $order->get_meta( '_veloria_cancel_requested' ) ) {
			return array( 'success' => false, 'error' => 'No customer cancellation is pending on this order.' );
		}
		$eligibility = self::is_cancellable( $order );
		if ( 'requested' === (string) $order->get_meta( '_veloria_cancel_review_state' ) || 'review' === ( $eligibility['mode'] ?? '' ) ) {
			return array( 'success' => false, 'status' => 409, 'error' => 'This cancellation needs an explicit staff approval before any refund retry.' );
		}
		return self::cancel( $order, false, $courier_reference );
	}

	/**
	 * Decide a queued request under the same cancellation and shipment locks.
	 * Approval authorizes the normal courier-first flow; it never supplies courier
	 * evidence by itself. The admin form separately supplies any verified reference.
	 *
	 * @return array<string,mixed>
	 */
	public static function decide_cancel_review( \WC_Order $order, string $decision, int $user_id, string $reason, string $courier_reference = '' ): array {
		$id = $order->get_id();
		$reason = mb_substr( trim( sanitize_textarea_field( $reason ) ), 0, 500 );
		if ( $user_id <= 0 || $user_id !== get_current_user_id() || ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $id ) ) {
			return array( 'success' => false, 'status' => 403, 'error' => 'Only authorized order staff can decide a cancellation request.' );
		}
		if ( ! in_array( $decision, array( 'approved', 'declined' ), true ) || mb_strlen( $reason ) < 5 || ( '' !== $courier_reference && ( strlen( $courier_reference ) < 8 || strlen( $courier_reference ) > 200 ) ) ) {
			return array( 'success' => false, 'status' => 400, 'error' => 'Choose approve or decline and enter the review reason. Any courier reference must be 8 to 200 characters.' );
		}
		$lock = 'veloria_cancel_lock_' . $id;
		$ship_lock = 'veloria_sr_lock_' . $id;
		if ( ! add_option( $lock, time(), '', false ) ) {
			return array( 'success' => false, 'status' => 409, 'error' => 'Cancellation is being processed. Try the review decision again shortly.' );
		}
		if ( ! add_option( $ship_lock, time(), '', false ) ) {
			delete_option( $lock );
			return array( 'success' => false, 'status' => 409, 'error' => 'A courier operation is in progress. Try the review decision again shortly.' );
		}
		try {
			$order = wc_get_order( $id );
			if ( ! $order instanceof \WC_Order || $order instanceof \WC_Order_Refund ) {
				return array( 'success' => false, 'status' => 404, 'error' => 'Order could not be loaded.' );
			}
			$state = (string) $order->get_meta( '_veloria_cancel_review_state' );
			if ( $decision === $state ) {
				return array( 'success' => true, 'cancelReviewState' => $state, 'message' => 'This review decision is already recorded. Use Retry refund now for an approved request that still needs completion.' );
			}
			if ( 'requested' !== $state || 'yes' !== (string) $order->get_meta( '_veloria_cancel_requested' ) ) {
				return array( 'success' => false, 'status' => 409, 'error' => 'There is no undecided cancellation review on this order.' );
			}
			if ( 'approved' === $decision ) {
				$blocked = self::cancellation_block_reason( $order );
				$created = $order->get_date_created();
				if ( null !== $blocked || ! $created || $created->getTimestamp() > time() ) {
					return array( 'success' => false, 'status' => 409, 'error' => $blocked ?? 'The order date needs verification before cancellation can be approved.' );
				}
			} else {
				// A queued review never moved money or stopped a courier. If those
				// markers exist, do not erase a potentially active cancellation.
				foreach ( array( '_shiprocket_cancel_state', '_veloria_refund_state', '_razorpay_refund_id', '_veloria_gateway_refund_state', '_veloria_refund_key' ) as $key ) {
					if ( '' !== (string) $order->get_meta( $key ) ) {
						return array( 'success' => false, 'status' => 409, 'error' => 'Courier or refund activity is recorded. Resolve it before declining this request.' );
					}
				}
				if ( (float) $order->get_total_refunded() > 0 || $order->get_meta( '_veloria_refund_attempts' ) ) {
					return array( 'success' => false, 'status' => 409, 'error' => 'A refund is recorded. Resolve its status before declining this request.' );
				}
			}
			$order->update_meta_data( '_veloria_cancel_review_state', $decision );
			$order->update_meta_data( '_veloria_cancel_review_decided_at', time() );
			$order->update_meta_data( '_veloria_cancel_review_decided_by', $user_id );
			$order->update_meta_data( '_veloria_cancel_review_reason', $reason );
			$order->add_order_note( 'Cancellation review ' . $decision . ' by user #' . $user_id . '. Reason: ' . $reason );
			if ( 'declined' === $decision ) {
				$order->delete_meta_data( '_veloria_cancel_requested' );
				$order->update_status( 'on-hold', 'Cancellation review declined. Cancellation shipping hold released; staff must decide fulfillment separately. This action does not dispatch or restart shipment.' );
				$order->add_order_note( 'Your cancellation request was declined after review. Reason: ' . $reason . '. Please contact support with your order number for the next steps.', true );
				$order->save();
				self::clear_attention( $id );
				wp_clear_scheduled_hook( self::CHECK_ACTION, array( $id ) );
				return array( 'success' => true, 'refunded' => false, 'cancelReviewState' => 'declined', 'message' => 'Cancellation declined. The order remains on hold for staff disposition.' );
			}
			$order->add_order_note( 'Your cancellation request has been approved after review. We will confirm it can no longer ship before arranging any refund to the original payment method. This notice is not a refund confirmation.', true );
			if ( '' !== $courier_reference ) {
				$order->update_meta_data( '_veloria_sr_staff_clearance', array( 'actor' => $user_id, 'at' => time(), 'reference' => $courier_reference, 'fingerprint' => Shiprocket::cancellation_fingerprint( $order ) ) );
			}
			$order->save();
			self::flag_attention( $order, 'Cancellation approved; complete verified courier cancellation and original-method refund.' );
			return self::cancel_locked( $order ) + array( 'cancelReviewState' => 'approved' );
		} catch ( \Throwable $error ) {
			wc_get_logger()->error( 'Cancellation review requires attention for order ' . $id . ': ' . $error->getMessage(), array( 'source' => 'veloria-commerce' ) );
			return array( 'success' => false, 'status' => 502, 'error' => 'The review could not be completed. Reopen the order and check its recorded decision and refund status before retrying.' );
		} finally {
			if ( '' !== $courier_reference && isset( $order ) && $order instanceof \WC_Order ) {
				$order->delete_meta_data( '_veloria_sr_staff_clearance' );
				$order->save();
			}
			delete_option( $ship_lock );
			delete_option( $lock );
		}
	}

	/** @return array{success:bool,error?:string,status?:int} */
	public static function request_return( \WC_Order $order, string $email, string $reason ): array {
		if ( 'yes' === (string) $order->get_meta( '_return_requested' ) ) {
			return array( 'success' => true );
		}
		// Orders shipped before delivery evidence existed: ask the carrier once.
		$shipment = (string) $order->get_meta( '_shiprocket_shipment_id' );
		if ( null === self::delivery_timestamp( $order ) && '' !== $shipment && Shiprocket::configured() ) {
			Shiprocket::track_shipment( $shipment, $order, true );
			$order = wc_get_order( $order->get_id() ) ?: $order;
		}
		$eligibility = self::is_returnable( $order );
		if ( ! $eligibility['ok'] ) {
			return array( 'success' => false, 'error' => $eligibility['reason'], 'status' => 409 );
		}

		$order->add_order_note( sprintf( 'Return requested by customer (%s). Reason: %s', $email, $reason ) );
		$order->update_meta_data( '_return_requested', 'yes' );
		$order->update_meta_data( '_return_reason', mb_substr( $reason, 0, 500 ) );
		$order->update_meta_data( '_return_requested_at', gmdate( 'c' ) );
		$order->update_status( 'on-hold', 'Return requested.' );

		Mailer::return_request( Settings::support_email(), (string) $order->get_order_number(), $email, $reason );

		return array( 'success' => true );
	}
}
