<?php
/**
 * WooCommerce checkout integration.
 *
 * Orders are created by WooCommerce's own checkout (WC_Checkout), so stock
 * holds, emails, payment gateways and analytics all behave natively. This
 * class only adds what the headless /api/checkout did on top:
 *  - Indian phone / PIN / state validation,
 *  - the same order meta keys (so old and new orders report identically),
 *  - a guard that the order total equals the engine total,
 *  - Razorpay payment bookkeeping once the payment is captured.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Checkout;

use Veloria\Commerce\Pricing\CartPricing;
use Veloria\Commerce\Pricing\CouponRepository;
use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class CheckoutHandler {

	public const ORDER_SOURCE = 'WordPress Storefront';

	public static function register(): void {
		EngineOffers::register();
		add_filter( 'woocommerce_checkout_fields', array( self::class, 'checkout_fields' ), 20 );
		add_filter( 'woocommerce_checkout_posted_data', array( self::class, 'normalize_posted_data' ) );
		add_action( 'woocommerce_after_checkout_validation', array( self::class, 'validate' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'add_order_meta' ), PHP_INT_MAX, 2 );
		add_action( 'woocommerce_checkout_order_created', array( self::class, 'verify_totals' ) );
		add_action( 'woocommerce_payment_complete', array( self::class, 'record_payment' ) );
		add_filter( 'woocommerce_payment_complete_order_status', array( self::class, 'payment_complete_status' ), PHP_INT_MAX, 3 );
		add_filter( 'woocommerce_available_payment_gateways', array( self::class, 'pay_page_gateways' ) );
	}

	/**
	 * An order priced with the prepaid bonus cannot be switched to COD on the
	 * pay page: its total no longer includes the COD fee or excludes the bonus.
	 *
	 * @param array<string,\WC_Payment_Gateway> $gateways
	 * @return array<string,\WC_Payment_Gateway>
	 */
	public static function pay_page_gateways( $gateways ) {
		if ( ! is_array( $gateways ) || ! function_exists( 'is_checkout_pay_page' ) || ! is_checkout_pay_page() ) {
			return $gateways;
		}
		$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
		if ( $order instanceof \WC_Order && (float) $order->get_meta( '_prepaid_discount' ) > 0 ) {
			unset( $gateways[ (string) Settings::get( 'cod_gateway', 'cod' ) ] );
		}
		return $gateways;
	}

	/**
	 * The storefront form collects the same fields as the headless checkout:
	 * email, first/last name, address, city, state, PIN, phone. Shipping
	 * address = billing address.
	 *
	 * @param array<string,array<string,array<string,mixed>>> $fields
	 * @return array<string,array<string,array<string,mixed>>>
	 */
	public static function checkout_fields( $fields ) {
		foreach ( array( 'billing_company', 'billing_address_2' ) as $optional ) {
			if ( isset( $fields['billing'][ $optional ] ) ) {
				$fields['billing'][ $optional ]['required'] = false;
			}
		}
		foreach ( array( 'billing_email', 'billing_first_name', 'billing_last_name', 'billing_address_1', 'billing_city', 'billing_state', 'billing_postcode', 'billing_phone' ) as $required ) {
			if ( isset( $fields['billing'][ $required ] ) ) {
				$fields['billing'][ $required ]['required'] = true;
			}
		}
		return $fields;
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	public static function normalize_posted_data( $data ) {
		$indian = 'IN' === ( $data['billing_country'] ?? 'IN' );
		if ( $indian && isset( $data['billing_phone'] ) ) {
			$data['billing_phone'] = IndianAddress::normalize_phone( (string) $data['billing_phone'] );
		}
		if ( $indian && isset( $data['billing_postcode'] ) ) {
			$data['billing_postcode'] = IndianAddress::normalize_pincode( (string) $data['billing_postcode'] );
		}
		if ( $indian && ! empty( $data['billing_state'] ) ) {
			$code = IndianAddress::state_code( (string) $data['billing_state'] );
			if ( $code ) {
				$data['billing_state'] = $code;
			}
		}
		if ( empty( $data['ship_to_different_address'] ) ) {
			foreach ( array( 'first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ) as $part ) {
				$data[ 'shipping_' . $part ] = $data[ 'billing_' . $part ] ?? '';
			}
		} elseif ( 'IN' === ( $data['shipping_country'] ?? '' ) ) {
			$data['shipping_postcode'] = IndianAddress::normalize_pincode( (string) ( $data['shipping_postcode'] ?? '' ) );
			$state = IndianAddress::state_code( (string) ( $data['shipping_state'] ?? '' ) );
			if ( $state ) {
				$data['shipping_state'] = $state;
			}
		}
		return $data;
	}

	/**
	 * @param array<string,mixed> $data
	 */
	public static function validate( $data, \WP_Error $errors ): void {
		foreach ( CouponRepository::validate_session() as $message ) {
			$errors->add( 'veloria_coupon', $message );
		}
		if ( CartPricing::engine_enabled() && ! CouponRepository::uses_engine() && CartPricing::native_coupon_discount() > CartPricing::discount_cap() + 0.01 ) {
			$errors->add( 'veloria_coupon_cap', 'These coupons exceed the store discount limit. Remove a coupon or contact support.' );
		}
		$indian = 'IN' === ( $data['billing_country'] ?? 'IN' );
		if ( $indian && ! empty( $data['billing_phone'] ) && ! IndianAddress::is_valid_phone( (string) $data['billing_phone'] ) ) {
			$errors->add( 'billing_phone', 'Please enter a valid 10-digit mobile number' );
		}
		if ( $indian && ! empty( $data['billing_postcode'] ) && ! IndianAddress::is_valid_pincode( (string) $data['billing_postcode'] ) ) {
			$errors->add( 'billing_postcode', 'Please enter a valid 6-digit PIN code' );
		}
		if ( ! empty( $data['billing_address_1'] ) && strlen( trim( (string) $data['billing_address_1'] ) ) < 5 ) {
			$errors->add( 'billing_address_1', 'Please enter a complete address' );
		}

		// Never let a variation parent through without a colour.
		foreach ( WC()->cart->get_cart() as $line ) {
			if ( isset( $line['data'] ) && $line['data'] instanceof \WC_Product && $line['data']->is_type( 'variable' ) ) {
				$errors->add( 'cart', sprintf( 'Please choose a colour for %s', $line['data']->get_name() ) );
			}
		}
	}

	/**
	 * Same meta keys the headless checkout wrote (see the migration audit),
	 * so tracking, invoices, the reconciliation report and Shiprocket work
	 * identically for old and new orders.
	 *
	 * @param array<string,mixed> $data
	 */
	public static function add_order_meta( \WC_Order $order, $data ): void {
		$method     = (string) ( $data['payment_method'] ?? '' );
		$is_prepaid = CartPricing::is_prepaid( $method );
		$calc       = CartPricing::calculation_for_cart( $is_prepaid );
		// The reviewed cart includes native shipping, taxes, payment fees, coupon
		// restrictions and extension pricing. This is the displayed charge contract.
		$expected   = (float) WC()->cart->get_total( 'edit' );
		$savings    = array_sum( array_map( static fn( $s ) => (float) $s['amount'], $calc['savingsBreakdown'] ) );

		$meta = array(
			'_order_source'           => self::ORDER_SOURCE,
			'_veloria_native_checkout' => 'yes',
			'_veloria_engine_total'   => (string) $calc['finalTotal'],
			'_veloria_expected_order_total' => (string) $expected,
			'_is_prepaid'             => $is_prepaid ? 'yes' : 'no',
			'_tier_discount'          => (string) $calc['tierDiscount'],
			'_prepaid_discount'       => (string) $calc['prepaidDiscount'],
			'_manual_coupon_discount' => (string) $calc['manualCouponDiscount'],
			'_original_subtotal'      => (string) $calc['originalSubtotal'],
			'_total_savings'          => (string) $savings,
			'_headless_charge_amount' => (string) $order->get_total(),
			'_customer_ip'            => \WC_Geolocation::get_ip_address(),
		);
		foreach ( $meta as $key => $value ) {
			$order->update_meta_data( $key, $value );
		}
		// This hook runs before the order is saved and before any payment gateway.
		if ( ! is_finite( $expected ) || $expected < 0 || abs( (float) $order->get_total() - $expected ) > 0.01 ) {
			throw new \Exception( 'The order total changed unexpectedly. Please refresh checkout or contact support before paying.' );
		}
		CouponRepository::record_usage( $order, $calc['appliedCoupons'] );
	}

	/**
	 * The amount charged is WooCommerce's order total. It must equal the engine
	 * expectation stored in _veloria_engine_total; if it doesn't, something else is
	 * changing prices (e.g. a pricing plugin) and staff need to know at once.
	 */
	public static function verify_totals( \WC_Order $order ): void {
		$stored   = $order->get_meta( '_veloria_expected_order_total' );
		$expected = (float) ( '' !== $stored ? $stored : $order->get_meta( '_veloria_engine_total' ) );
		$actual   = (float) $order->get_total();
		if ( abs( $expected - $actual ) > 0.01 ) {
			$message = sprintf( 'Amount check: order total %s differs from the reviewed cart total %s.', $actual, $expected );
			$order->update_meta_data( '_amount_mismatch', sprintf( 'order %s, engine %s', $actual, $expected ) );
			$order->add_order_note( $message );
			$order->save();
			wc_get_logger()->error( $message . ' Order #' . $order->get_id(), array( 'source' => 'veloria-commerce' ) );
			$order->update_status( 'failed', 'Checkout stopped before payment because totals did not match.' );
			throw new \Exception( 'Checkout could not verify your order total. Please contact support before paying.' );
		}
	}

	/** Prevent a delayed capture from resuming fulfillment before its hooks run. */
	public static function payment_complete_status( string $status, int $order_id, $order = null ): string {
		// The gateway may have loaded its order before a customer requested review.
		$fresh = wc_get_order( $order_id );
		$order = $fresh instanceof \WC_Order ? $fresh : $order;
		if ( ! $order instanceof \WC_Order ) {
			return $status;
		}
		if ( 'yes' === (string) $order->get_meta( '_veloria_cancel_requested' ) || 'declined' === (string) $order->get_meta( '_veloria_cancel_review_state' ) ) {
			return in_array( $order->get_status(), array( 'cancelled', 'refunded' ), true ) ? $order->get_status() : 'on-hold';
		}
		// Declining cancellation leaves the order on hold for a deliberate staff
		// fulfillment decision; a capture callback is not that decision.
		return $status;
	}

	/**
	 * Mirror what the headless flow stored after a captured Razorpay payment.
	 * Cancellation refunds and the reconciliation report read these keys.
	 */
	public static function record_payment( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order || CartPricing::cod_gateway() === $order->get_payment_method() ) {
			return;
		}
		if ( 'yes' === (string) $order->get_meta( '_veloria_cancel_requested' ) ) {
			// A delayed or repeated gateway callback must not resume fulfillment
			// or turn an already-refunded cancellation back into a paid order.
			$order->update_meta_data( '_veloria_payment_after_cancel', 'yes' );
			if ( 'processed' !== (string) $order->get_meta( '_veloria_refund_state' ) && 'accepted' !== (string) $order->get_meta( '_veloria_gateway_refund_state' ) ) {
				$order->update_meta_data( '_payment_status', 'completed' );
				$order->update_status( 'on-hold', 'Payment confirmation arrived after cancellation started. Reconcile payment/refund before fulfillment.' );
			}
			$order->save();
			return;
		}
		$payment_id = (string) $order->get_transaction_id();
		if ( '' !== $payment_id ) {
			$order->update_meta_data( '_veloria_payment_id', $payment_id );
			if ( 'razorpay' === $order->get_payment_method() && preg_match( '/^pay_[A-Za-z0-9]+$/', $payment_id ) ) {
				$order->update_meta_data( '_razorpay_payment_id', $payment_id );
			}
		}
		$order->update_meta_data( '_payment_status', 'completed' );
		$order->update_meta_data( '_paid_via', $order->get_payment_method() );
		$order->save();
	}
}
