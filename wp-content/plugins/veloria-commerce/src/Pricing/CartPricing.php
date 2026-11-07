<?php
/**
 * Applies the discount engine to the live WooCommerce cart.
 *
 * Each gross saving becomes a negative cart fee with the same label the
 * headless site used; native fee taxes retain the inclusive saving. COD is
 * a positive non-taxable fee. WooCommerce's
 * order total then equals the engine's finalTotal, which is the amount the
 * Razorpay plugin charges, so cart, payment, order and invoice always agree.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Pricing;

use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class CartPricing {

	public static function register(): void {
		add_action( 'woocommerce_cart_calculate_fees', array( self::class, 'apply_fees' ), 20 );
		add_filter( 'woocommerce_package_rates', array( self::class, 'override_shipping_rates' ), 100, 2 );
	}

	public static function engine(): DiscountEngine {
		return new DiscountEngine(
			array(
				'tier_rules'              => (array) Settings::get( 'tier_rules' ),
				'prepaid_percent'         => (float) Settings::get( 'prepaid_percent' ),
				'max_discount_percent'    => (float) Settings::get( 'max_discount_percent' ),
				'cod_fee'                 => (float) Settings::get( 'cod_fee' ),
				'free_shipping_threshold' => (float) Settings::get( 'free_shipping_threshold' ),
				'standard_shipping_cost'  => (float) Settings::get( 'standard_shipping_cost' ),
				'lucky_draw_enabled'      => (bool) Settings::get( 'lucky_draw_enabled' ),
			)
		);
	}

	public static function cod_gateway(): string {
		return (string) Settings::get( 'cod_gateway', 'cod' );
	}

	public static function engine_enabled(): bool {
		return 'native' !== Settings::get( 'pricing_mode', 'veloria' );
	}

	/**
	 * Prepaid unless the shopper chose Cash on Delivery. Prepaid is the default
	 * before a method is picked, as on the headless checkout.
	 */
	public static function is_prepaid( ?string $payment_method = null ): bool {
		if ( null === $payment_method ) {
			$payment_method = self::chosen_payment_method();
		}
		$gateways = Settings::get( 'prepaid_gateway_ids', array( 'razorpay' ) );
		$gateways = is_array( $gateways ) ? $gateways : preg_split( '/[\s,]+/', (string) $gateways );
		return '' === $payment_method || in_array( $payment_method, (array) apply_filters( 'veloria_prepaid_gateway_ids', $gateways ), true );
	}

	public static function chosen_payment_method(): string {
		// During checkout submission WooCommerce stores the posted method in the
		// session before totals are recalculated; read the POST first anyway.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only, nonce checked by WooCommerce.
		if ( isset( $_POST['payment_method'] ) && is_string( $_POST['payment_method'] ) ) {
			return sanitize_key( wp_unslash( $_POST['payment_method'] ) );
		}
		if ( function_exists( 'WC' ) && WC()->session ) {
			return (string) WC()->session->get( 'chosen_payment_method', '' );
		}
		return '';
	}

	/**
	 * Cart lines in engine form, priced exactly like the headless checkout did
	 * (live catalog price × quantity).
	 *
	 * @return array<int,array{price:float,quantity:int}>
	 */
	public static function cart_items( ?\WC_Cart $cart = null ): array {
		$cart = $cart ?? ( function_exists( 'WC' ) ? WC()->cart : null );
		if ( ! $cart ) {
			return array();
		}
		$items = array();
		foreach ( $cart->get_cart() as $line ) {
			if ( empty( $line['data'] ) || ! $line['data'] instanceof \WC_Product ) {
				continue;
			}
			$items[] = array(
				'price'    => (float) $line['data']->get_price(),
				'quantity' => (int) $line['quantity'],
			);
		}
		return $items;
	}

	/** @return array<string,mixed> */
	public static function calculation_for_cart( ?bool $is_prepaid = null, ?\WC_Cart $cart = null ): array {
		$cart = $cart ?? ( function_exists( 'WC' ) ? WC()->cart : null );
		$calc = self::engine()->calculate(
			self::cart_items( $cart ),
			CouponRepository::session_codes(),
			$is_prepaid ?? self::is_prepaid(),
			CouponRepository::engine_coupons()
		);
		if ( ! self::engine_enabled() ) {
			$calc['tierDiscount'] = $calc['prepaidDiscount'] = $calc['manualCouponDiscount'] = $calc['codFee'] = 0;
			$calc['appliedCoupons'] = $calc['savingsBreakdown'] = array();
			$calc['isCapped'] = false;
			$calc['finalTotal'] = $cart ? (float) $cart->get_total( 'edit' ) : 0;
			$calc['shippingCost'] = $cart ? (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax() : 0;
			return $calc;
		}
		// Not every non-COD gateway is a prepaid payment. Bank transfer/cheque
		// gets neither an online-payment bonus nor a cash-on-delivery charge.
		$method = self::chosen_payment_method();
		if ( '' !== $method && self::cod_gateway() !== $method && $calc['codFee'] > 0 ) {
			$calc['finalTotal'] -= $calc['codFee'];
			$calc['codFee'] = 0;
		}
		// Native coupons keep WooCommerce's product/email/usage restrictions. The
		// engine can only spend the remaining part of its combined discount cap.
		$native = self::native_coupon_discount( $cart );
		if ( $native > 0 ) {
			$total = array_sum( array_column( $calc['savingsBreakdown'], 'amount' ) );
			$allowed = max( 0, floor( $calc['originalSubtotal'] * (float) Settings::get( 'max_discount_percent', 35 ) / 100 ) - $native );
			if ( $total > $allowed ) {
				$remaining = $allowed;
				foreach ( $calc['savingsBreakdown'] as $i => $saving ) {
					$amount = $i === count( $calc['savingsBreakdown'] ) - 1 ? $remaining : min( $remaining, round( $saving['amount'] * $allowed / $total, 2 ) );
					$ratio = $saving['amount'] > 0 ? $amount / $saving['amount'] : 0;
					$calc['savingsBreakdown'][ $i ]['amount'] = $amount;
					if ( str_starts_with( $saving['label'], 'Automatic Tier Discount' ) ) {
						$calc['tierDiscount'] = $amount;
					} elseif ( str_starts_with( $saving['label'], 'Prepaid Bonus' ) ) {
						$calc['prepaidDiscount'] = $amount;
					}
					$remaining = round( $remaining - $amount, 2 );
				}
				$calc['finalTotal'] += $total - $allowed;
				$calc['isCapped'] = true;
			}
			$calc['finalTotal'] -= $native;
		}
		if ( $cart && ! Settings::get( 'override_shipping', true ) ) {
			$shipping = (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax();
			$calc['finalTotal'] += $shipping - $calc['shippingCost'];
			$calc['shippingCost'] = $shipping;
		}
		return $calc;
	}

	public static function native_coupon_discount( ?\WC_Cart $cart = null ): float {
		$cart = $cart ?? ( function_exists( 'WC' ) ? WC()->cart : null );
		return $cart && ! CouponRepository::uses_engine() ? max( 0, (float) $cart->get_discount_total() + (float) $cart->get_discount_tax() ) : 0;
	}

	public static function discount_cap(): float {
		$subtotal = array_sum( array_map( static fn( $item ) => $item['price'] * $item['quantity'], self::cart_items() ) );
		return floor( $subtotal * (float) Settings::get( 'max_discount_percent', 35 ) / 100 );
	}

	public static function apply_fees( \WC_Cart $cart ): void {
		if ( ! self::engine_enabled() ) {
			return;
		}
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		$calc = self::calculation_for_cart( null, $cart );
		// The engine discounts tax-inclusive catalog prices. WooCommerce also
		// apportions tax to negative fees, even when taxable=false. Convert the
		// gross saving to its net fee so tax is reduced once, not twice.
		// WooCommerce sorts the positive COD fee ahead of discount fees and
		// includes it in the non-taxable allocation base.
		$net_cost = (float) $cart->get_cart_contents_total() + (float) $cart->get_shipping_total() + (float) $calc['codFee'];
		$gross_cost = $net_cost + (float) $cart->get_cart_contents_tax() + (float) $cart->get_shipping_tax();
		$discount_ratio = wc_tax_enabled() && wc_prices_include_tax() && $gross_cost > 0 ? $net_cost / $gross_cost : 1.0;

		foreach ( $calc['savingsBreakdown'] as $saving ) {
			if ( $saving['amount'] > 0 ) {
				$cart->fees_api()->add_fee(
					array(
						'id'        => 'veloria-' . sanitize_title( $saving['label'] ),
						'name'      => $saving['label'],
						'amount'    => -1 * (float) $saving['amount'] * $discount_ratio,
						'taxable'   => false,
						'tax_class' => '',
					)
				);
			}
		}

		if ( $calc['codFee'] > 0 ) {
			$cart->fees_api()->add_fee(
				array(
					'id'        => 'veloria-cod-fee',
					'name'      => 'Cash on Delivery Fee',
					'amount'    => (float) $calc['codFee'],
					'taxable'   => false,
					'tax_class' => '',
				)
			);
		}
	}

	/**
	 * Replace whatever shipping zones offer with the single "Standard Shipping"
	 * line the headless checkout always wrote (cost from settings, ₹0 today).
	 *
	 * @param array<string,\WC_Shipping_Rate> $rates
	 * @param array<string,mixed>             $package
	 * @return array<string,\WC_Shipping_Rate>
	 */
	public static function override_shipping_rates( $rates, $package ) {
		if ( ! self::engine_enabled() || ! Settings::get( 'override_shipping', true ) ) {
			return $rates;
		}
		$subtotal = 0.0;
		foreach ( (array) ( $package['contents'] ?? array() ) as $line ) {
			if ( isset( $line['data'] ) && $line['data'] instanceof \WC_Product ) {
				$subtotal += (float) $line['data']->get_price() * (int) $line['quantity'];
			}
		}
		$cost = $subtotal >= (float) Settings::get( 'free_shipping_threshold' ) ? 0 : (float) Settings::get( 'standard_shipping_cost' );

		return array(
			'veloria_standard' => new \WC_Shipping_Rate( 'veloria_standard', 'Standard Shipping', $cost, array(), 'veloria_standard', 0 ),
		);
	}
}
