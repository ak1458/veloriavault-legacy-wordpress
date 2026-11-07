<?php
/**
 * Discount engine: line-for-line port of src/lib/coupon-calculator.ts.
 *
 * Standalone PHP, with the store timezone supplied by WordPress when available,
 * so it can be unit-tested and compared
 * against the TypeScript original (tests/parity). Rounding follows JavaScript
 * exactly: Math.round is round-half-up, Math.floor is floor, and every
 * arithmetic step happens in the same order on IEEE doubles.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Pricing;

final class DiscountEngine {

	/** @var array<string,mixed> */
	private array $config;

	/**
	 * @param array<string,mixed> $config Keys: tier_rules, prepaid_percent,
	 *     max_discount_percent, cod_fee, free_shipping_threshold,
	 *     standard_shipping_cost, lucky_draw_enabled.
	 */
	public function __construct( array $config ) {
		$this->config = $config + array(
			'tier_rules'              => array(
				array( 'min_qty' => 1, 'percent' => 10 ),
				array( 'min_qty' => 2, 'percent' => 15 ),
			),
			'prepaid_percent'         => 5,
			'max_discount_percent'    => 35,
			'cod_fee'                 => 149,
			'free_shipping_threshold' => 0,
			'standard_shipping_cost'  => 0,
			'lucky_draw_enabled'      => false,
		);
	}

	/** JavaScript Math.round(): round half up (toward +Infinity). */
	public static function js_round( float $value ): float {
		$floor = floor( $value );
		return ( $value - $floor >= 0.5 ) ? $floor + 1 : $floor;
	}

	/** Render a number the way a JS template literal would (10 not 10.0). */
	public static function js_number( float $value ): string {
		if ( floor( $value ) === $value && abs( $value ) < 1e15 ) {
			return (string) (int) $value;
		}
		return rtrim( rtrim( sprintf( '%.15F', $value ), '0' ), '.' );
	}

	public function tier_percent( int $item_count ): float {
		$rules = $this->config['tier_rules'];
		usort( $rules, static fn( $a, $b ) => (int) $b['min_qty'] <=> (int) $a['min_qty'] );
		foreach ( $rules as $rule ) {
			if ( $item_count >= (int) $rule['min_qty'] && (int) $rule['min_qty'] > 0 ) {
				return (float) $rule['percent'];
			}
		}
		return 0.0;
	}

	/**
	 * @param array<string,mixed>      $coupon
	 * @return string|null Error message, or null when valid.
	 */
	public static function coupon_validation_error( array $coupon, float $subtotal, int $item_count, ?int $now_ms = null ): ?string {
		$now_ms = $now_ms ?? (int) floor( microtime( true ) * 1000 );

		if ( empty( $coupon['isActive'] ) ) {
			return 'This coupon is not active';
		}

		if ( ! empty( $coupon['startDate'] ) ) {
			$start = self::parse_coupon_date( (string) $coupon['startDate'] );
			if ( null !== $start && $start > $now_ms ) {
				return 'This coupon is not yet active';
			}
		}

		if ( ! empty( $coupon['expiryDate'] ) ) {
			$expiry = self::parse_coupon_date( (string) $coupon['expiryDate'], true );
			// A date-only expiry ends at the next store-local midnight (exclusive).
			if ( null !== $expiry && $now_ms >= $expiry ) {
				return 'This coupon has expired';
			}
		}

		if ( ! empty( $coupon['usageLimit'] ) && (int) ( $coupon['usageCount'] ?? 0 ) >= (int) $coupon['usageLimit'] ) {
			return 'This coupon has reached its usage limit';
		}

		if ( ! empty( $coupon['minPurchase'] ) && $subtotal < (float) $coupon['minPurchase'] ) {
			return 'Minimum purchase of ₹' . self::js_number( (float) $coupon['minPurchase'] ) . ' required';
		}

		if ( ! empty( $coupon['minQuantity'] ) && $item_count < (int) $coupon['minQuantity'] ) {
			return 'Minimum of ' . (int) $coupon['minQuantity'] . ' items required for this coupon';
		}

		return null;
	}

	/**
	 * Merchant dates use the store timezone; explicit timestamp offsets are kept.
	 */
	private static function parse_coupon_date( string $value, bool $end_of_day = false ): ?int {
		try {
			$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'Asia/Kolkata' );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
				$date = new \DateTimeImmutable( $value . 'T00:00:00', $tz );
				if ( $date->format( 'Y-m-d' ) !== $value ) return null;
				if ( $end_of_day ) $date = $date->modify( '+1 day' );
			} else {
				$date = new \DateTimeImmutable( $value, $tz );
			}
			return (int) $date->format( 'U' ) * 1000;
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * @param array<int,array<string,mixed>> $coupons Available coupon definitions.
	 * @return array<string,mixed>|null
	 */
	private function find_coupon( string $code, array $coupons, ?float $lucky_draw_discount ): ?array {
		foreach ( $coupons as $coupon ) {
			if ( strtoupper( (string) $coupon['code'] ) === strtoupper( $code ) ) {
				return $coupon;
			}
		}
		if ( 'LUCKYDRAW' === strtoupper( $code ) && $lucky_draw_discount ) {
			return array(
				'id'           => 'lucky-draw-dynamic',
				'code'         => 'LUCKYDRAW',
				'type'         => 'percentage',
				'category'     => 'lucky_draw',
				'stackable'    => true,
				'amount'       => $lucky_draw_discount,
				'description'  => 'Lucky Draw Winner - ' . self::js_number( $lucky_draw_discount ) . '% Off',
				'isActive'     => true,
				'isAutomatic'  => false,
				'usageCount'   => 0,
			);
		}
		return null;
	}

	/**
	 * @param string[]                       $codes
	 * @param array<int,array<string,mixed>> $coupons
	 * @return array<int,array{coupon:array<string,mixed>,rawAmount:float,discountAmount:float,appliedTo:string}>
	 */
	private function resolve_valid_coupons( array $codes, array $coupons, float $subtotal, int $item_count, ?float $lucky ): array {
		$unique = array_values( array_unique( array_map( 'strtoupper', array_map( 'strval', $codes ) ) ) );
		$valid  = array();

		foreach ( $unique as $code ) {
			$coupon = $this->find_coupon( $code, $coupons, $lucky );
			if ( ! $coupon || null !== self::coupon_validation_error( $coupon, $subtotal, $item_count ) ) {
				continue;
			}
			$raw = 0.0;
			if ( 'percentage' === $coupon['type'] ) {
				$raw = self::js_round( ( $subtotal * (float) $coupon['amount'] ) / 100 );
			} elseif ( 'fixed_cart' === $coupon['type'] ) {
				$raw = (float) $coupon['amount'];
			}
			if ( ! empty( $coupon['maxDiscount'] ) ) {
				$raw = min( $raw, (float) $coupon['maxDiscount'] );
			}
			if ( $raw > 0 ) {
				$valid[] = array(
					'coupon'         => $coupon,
					'rawAmount'      => $raw,
					'discountAmount' => $raw,
					'appliedTo'      => 'subtotal',
				);
			}
		}

		$non_stackable = array_values( array_filter( $valid, static fn( $c ) => empty( $c['coupon']['stackable'] ) ) );
		if ( $non_stackable ) {
			// Stable sort by rawAmount desc (Array.prototype.sort is stable).
			usort( $non_stackable, static fn( $a, $b ) => $b['rawAmount'] <=> $a['rawAmount'] );
			return array_slice( $non_stackable, 0, 1 );
		}

		return $valid;
	}

	/**
	 * @param array<int,array{price:float|int|string,quantity:int|string}> $items
	 * @param string[]                                                      $applied_codes
	 * @param array<int,array<string,mixed>>                                $coupons
	 * @return array<string,mixed> Same shape as DiscountCalculation in src/types/coupon.ts.
	 */
	public function calculate( array $items, array $applied_codes, bool $is_prepaid, array $coupons = array(), ?float $lucky_draw_discount = null ): array {
		$lucky = ! empty( $this->config['lucky_draw_enabled'] ) ? $lucky_draw_discount : null;

		$original_subtotal = 0.0;
		$item_count        = 0;
		foreach ( $items as $item ) {
			$original_subtotal += (float) $item['price'] * (int) $item['quantity'];
			$item_count        += (int) $item['quantity'];
		}

		if ( 0.0 === $original_subtotal ) {
			return array(
				'originalSubtotal'     => 0,
				'itemCount'            => 0,
				'isPrepaid'            => $is_prepaid,
				'appliedCoupons'       => array(),
				'tierDiscount'         => 0,
				'prepaidDiscount'      => 0,
				'manualCouponDiscount' => 0,
				'codFee'               => 0,
				'shippingCost'         => 0,
				'finalTotal'           => 0,
				'isCapped'             => false,
				'savingsBreakdown'     => array(),
			);
		}

		// 1. Automatic tier discount.
		$tier_percent      = $this->tier_percent( $item_count );
		$raw_tier_discount = self::js_round( ( $original_subtotal * $tier_percent ) / 100 );

		// 2. Manual coupons.
		$resolved   = $this->resolve_valid_coupons( $applied_codes, $coupons, $original_subtotal, $item_count, $lucky );
		$raw_manual = 0.0;
		foreach ( $resolved as $entry ) {
			$raw_manual += $entry['rawAmount'];
		}

		// 3. Prepaid bonus.
		$prepaid_percent     = (float) $this->config['prepaid_percent'];
		$raw_prepaid_discount = $is_prepaid ? self::js_round( ( $original_subtotal * $prepaid_percent ) / 100 ) : 0.0;

		// 4. Global cap, scaled proportionally and floored.
		$raw_total    = $raw_tier_discount + $raw_manual + $raw_prepaid_discount;
		$max_discount = floor( ( $original_subtotal * (float) $this->config['max_discount_percent'] ) / 100 );
		$is_capped    = $raw_total > $max_discount;
		$scale        = $is_capped ? $max_discount / $raw_total : 1;

		$final_tier    = floor( $raw_tier_discount * $scale );
		$final_prepaid = floor( $raw_prepaid_discount * $scale );

		$applied = array();
		foreach ( $resolved as $entry ) {
			$entry['discountAmount'] = floor( $entry['rawAmount'] * $scale );
			$applied[]               = $entry;
		}
		$final_manual = 0.0;
		foreach ( $applied as $entry ) {
			$final_manual += $entry['discountAmount'];
		}

		$total_discounts          = $final_tier + $final_manual + $final_prepaid;
		$subtotal_after_discounts = max( 0, $original_subtotal - $total_discounts );

		$shipping    = $original_subtotal >= (float) $this->config['free_shipping_threshold'] ? 0.0 : (float) $this->config['standard_shipping_cost'];
		$cod_fee     = ! $is_prepaid ? (float) $this->config['cod_fee'] : 0.0;
		$final_total = max( 0, $subtotal_after_discounts + $shipping + $cod_fee );

		$savings = array();
		if ( $final_tier > 0 ) {
			$savings[] = array(
				'label'  => 'Automatic Tier Discount (' . self::js_number( $tier_percent ) . '%)',
				'amount' => $final_tier,
			);
		}
		if ( $final_prepaid > 0 ) {
			$savings[] = array(
				'label'  => 'Prepaid Bonus (' . self::js_number( $prepaid_percent ) . '%)',
				'amount' => $final_prepaid,
			);
		}
		foreach ( $applied as $entry ) {
			if ( $entry['discountAmount'] > 0 ) {
				$savings[] = array(
					'label'  => 'LUCKYDRAW' === $entry['coupon']['code'] ? 'Lucky Draw Discount' : 'Coupon: ' . $entry['coupon']['code'],
					'amount' => $entry['discountAmount'],
				);
			}
		}

		return array(
			'originalSubtotal'     => $original_subtotal,
			'itemCount'            => $item_count,
			'isPrepaid'            => $is_prepaid,
			'tierDiscount'         => $final_tier,
			'prepaidDiscount'      => $final_prepaid,
			'manualCouponDiscount' => $final_manual,
			'appliedCoupons'       => $applied,
			'codFee'               => $cod_fee,
			'shippingCost'         => $shipping,
			'finalTotal'           => (float) $final_total,
			'isCapped'             => $is_capped,
			'savingsBreakdown'     => $savings,
		);
	}

	/**
	 * Port of validateCoupon(): used when a shopper types a code.
	 *
	 * @param array<int,array<string,mixed>> $coupons
	 * @param string[]                       $existing
	 * @return array{valid:bool,coupon?:array<string,mixed>,error?:string}
	 */
	public function validate_coupon( string $code, float $subtotal, int $item_count, array $coupons, array $existing = array(), ?float $lucky = null ): array {
		$lucky  = ! empty( $this->config['lucky_draw_enabled'] ) ? $lucky : null;
		$coupon = $this->find_coupon( $code, $coupons, $lucky );

		if ( ! $coupon ) {
			if ( 'LUCKYDRAW' === strtoupper( $code ) ) {
				return array( 'valid' => false, 'error' => 'Lucky Draw coupon is invalid or expired. Please spin the wheel.' );
			}
			return array( 'valid' => false, 'error' => 'Invalid coupon code' );
		}

		$error = self::coupon_validation_error( $coupon, $subtotal, $item_count );
		if ( null !== $error ) {
			return array( 'valid' => false, 'error' => $error );
		}

		if ( $existing ) {
			if ( empty( $coupon['stackable'] ) ) {
				return array( 'valid' => false, 'error' => 'This coupon cannot be combined with other offers.' );
			}
			$existing_coupons = array_filter( array_map( fn( $c ) => $this->find_coupon( (string) $c, $coupons, $lucky ), $existing ) );
			foreach ( $existing_coupons as $existing_coupon ) {
				if ( empty( $existing_coupon['stackable'] ) ) {
					return array( 'valid' => false, 'error' => 'You already have a non-combinable offer applied to your cart.' );
				}
			}
			$categories = array_map( static fn( $c ) => $c['category'] ?? '', $existing_coupons );
			if ( in_array( $coupon['category'], $categories, true ) ) {
				return array(
					'valid' => false,
					'error' => 'You can only use one ' . preg_replace( '/_/', ' ', (string) $coupon['category'], 1 ) . ' code at a time.',
				);
			}
		}

		return array( 'valid' => true, 'coupon' => $coupon );
	}
}
