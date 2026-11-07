<?php
/**
 * Global helpers. These are the public API the theme uses; keep their
 * signatures stable across versions.
 *
 * @package Veloria\Commerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'veloria_setting' ) ) {
	/**
	 * @param mixed $fallback
	 * @return mixed
	 */
	function veloria_setting( string $key, $fallback = null ) {
		return \Veloria\Commerce\Settings::get( $key, $fallback );
	}
}

if ( ! function_exists( 'veloria_format_number' ) ) {
	/**
	 * Number formatted like JavaScript `n.toLocaleString("en-IN")`: Indian
	 * digit grouping (12,34,567) with up to three fraction digits, or a fixed
	 * number of decimals when $decimals is given.
	 */
	function veloria_format_number( float $value, ?int $decimals = null ): string {
		$negative = $value < 0;
		$value    = abs( $value );

		if ( null === $decimals ) {
			$formatted = rtrim( rtrim( number_format( $value, 3, '.', '' ), '0' ), '.' );
		} else {
			$formatted = number_format( $value, $decimals, '.', '' );
		}

		$parts   = explode( '.', $formatted );
		$integer = $parts[0];
		$frac    = $parts[1] ?? '';

		if ( strlen( $integer ) > 3 ) {
			$last3   = substr( $integer, -3 );
			$rest    = substr( $integer, 0, -3 );
			$rest    = preg_replace( '/\B(?=(\d{2})+(?!\d))/', ',', $rest );
			$integer = $rest . ',' . $last3;
		}

		return ( $negative ? '-' : '' ) . $integer . ( '' !== $frac ? '.' . $frac : '' );
	}
}

if ( ! function_exists( 'veloria_inr' ) ) {
	/** "₹2,699" (or "₹2,699.00" with $decimals = 2). */
	function veloria_inr( float $value, ?int $decimals = null ): string {
		return '₹' . veloria_format_number( $value, $decimals );
	}
}

if ( ! function_exists( 'veloria_date_en_in' ) ) {
	/** Date formatted like `toLocaleDateString("en-IN")` → 18/10/2026. */
	function veloria_date_en_in( string $date ): string {
		$ts = strtotime( $date );
		return $ts ? wp_date( 'j/n/Y', $ts ) : '';
	}
}

if ( ! function_exists( 'veloria_catalog' ) ) {
	function veloria_catalog(): \Veloria\Commerce\Catalog\Catalog {
		return \Veloria\Commerce\Catalog\Catalog::instance();
	}
}

if ( ! function_exists( 'veloria_cart_calculation' ) ) {
	/**
	 * The live discount calculation for the current WooCommerce cart.
	 *
	 * @return array<string,mixed>
	 */
	function veloria_cart_calculation( ?bool $is_prepaid = null ): array {
		return \Veloria\Commerce\Pricing\CartPricing::calculation_for_cart( $is_prepaid );
	}
}

if ( ! function_exists( 'veloria_offer_tiers' ) ) {
	/**
	 * Offer tiers for display: [ ['label' => 'Buy 1 bag', 'percent' => 10], ... ].
	 *
	 * @return array<int,array{label:string,percent:float}>
	 */
	function veloria_offer_tiers(): array {
		$rules  = (array) veloria_setting( 'tier_rules', array() );
		usort( $rules, static fn( $a, $b ) => (int) $a['min_qty'] <=> (int) $b['min_qty'] );
		$labels = array_values( (array) veloria_setting( 'offer_tier_labels', array() ) );
		$tiers  = array();
		foreach ( array_values( $rules ) as $i => $rule ) {
			$tiers[] = array(
				'label'   => $labels[ $i ] ?? sprintf( 'Buy %d', (int) $rule['min_qty'] ),
				'percent' => (float) $rule['percent'],
			);
		}
		return $tiers;
	}
}

if ( ! function_exists( 'veloria_session_email' ) ) {
	/** Verified email for the current visitor (logged-in customer or OTP guest). */
	function veloria_session_email(): ?string {
		return \Veloria\Commerce\Auth\Session::email();
	}
}
