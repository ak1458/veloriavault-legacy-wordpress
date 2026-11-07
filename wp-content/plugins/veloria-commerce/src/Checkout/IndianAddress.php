<?php
/**
 * Indian phone / PIN / state normalisation (port of src/lib/address-validation.ts).
 *
 * Shiprocket rejects anything that is not a 10-digit mobile or 6-digit PIN,
 * which silently leaves an order unshipped, so these are validated up front.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Checkout;

defined( 'ABSPATH' ) || exit;

final class IndianAddress {

	/** "+91 98765-43210" → "9876543210". */
	public static function normalize_phone( string $value ): string {
		$digits = preg_replace( '/\D/', '', $value ) ?? '';
		if ( 12 === strlen( $digits ) && str_starts_with( $digits, '91' ) ) {
			return substr( $digits, 2 );
		}
		if ( 11 === strlen( $digits ) && str_starts_with( $digits, '0' ) ) {
			return substr( $digits, 1 );
		}
		return $digits;
	}

	public static function is_valid_phone( string $value ): bool {
		return 1 === preg_match( '/^[6-9]\d{9}$/', $value );
	}

	public static function normalize_pincode( string $value ): string {
		return preg_replace( '/\s/', '', $value ) ?? '';
	}

	public static function is_valid_pincode( string $value ): bool {
		return 1 === preg_match( '/^[1-9]\d{5}$/', $value );
	}

	/**
	 * The headless checkout took state as free text. WooCommerce stores the
	 * state code, so map what the shopper typed ("Delhi", "new delhi", "DL",
	 * "Orissa") to the code. Returns null when nothing matches.
	 */
	public static function state_code( string $input ): ?string {
		$states = function_exists( 'WC' ) ? (array) WC()->countries->get_states( 'IN' ) : array();
		if ( ! $states ) {
			return null;
		}
		$needle = self::key( $input );
		if ( '' === $needle ) {
			return null;
		}

		foreach ( $states as $code => $name ) {
			if ( self::key( (string) $code ) === $needle || self::key( html_entity_decode( (string) $name ) ) === $needle ) {
				return (string) $code;
			}
		}

		$aliases = array(
			'newdelhi'                    => 'Delhi',
			'nctofdelhi'                  => 'Delhi',
			'delhincr'                    => 'Delhi',
			'orissa'                      => 'Odisha',
			'pondicherry'                 => 'Puducherry',
			'puducherry'                  => 'Pondicherry',
			'uttaranchal'                 => 'Uttarakhand',
			'telengana'                   => 'Telangana',
			'tamilnadu'                   => 'Tamil Nadu',
			'tn'                          => 'Tamil Nadu',
			'jk'                          => 'Jammu and Kashmir',
			'jammukashmir'                => 'Jammu and Kashmir',
			'bengal'                      => 'West Bengal',
			'andaman'                     => 'Andaman and Nicobar Islands',
			'andamannicobar'              => 'Andaman and Nicobar Islands',
			'damandiu'                    => 'Dadra and Nagar Haveli and Daman and Diu',
			'dadranagarhaveli'            => 'Dadra and Nagar Haveli and Daman and Diu',
			'chattisgarh'                 => 'Chhattisgarh',
			'chhatisgarh'                 => 'Chhattisgarh',
			'maharastra'                  => 'Maharashtra',
		);
		$target = isset( $aliases[ $needle ] ) ? self::key( $aliases[ $needle ] ) : $needle;
		// WooCommerce state names vary between versions ("Pondicherry (Puducherry)"),
		// so accept a containment match once the needle is long enough to be unambiguous.
		if ( strlen( $target ) >= 4 ) {
			foreach ( $states as $code => $name ) {
				$state_key = self::key( html_entity_decode( (string) $name ) );
				if ( $state_key === $target || str_contains( $state_key, $target ) || str_contains( $target, $state_key ) ) {
					return (string) $code;
				}
			}
		}
		return null;
	}

	private static function key( string $value ): string {
		$value = strtolower( str_replace( '&', 'and', $value ) );
		$value = preg_replace( '/\band\b/', '', $value ) ?? '';
		return preg_replace( '/[^a-z]/', '', $value ) ?? '';
	}
}
