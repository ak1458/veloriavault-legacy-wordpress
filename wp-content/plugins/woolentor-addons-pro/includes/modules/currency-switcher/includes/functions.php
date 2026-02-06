<?php
/**
 * Currency Switcher Pro - Helper Functions
 *
 * @package WoolentorPro\Modules\CurrencySwitcher
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get WooCommerce countries list for select field
 *
 * @return array
 */
function woolentor_get_wc_countries() {
    if ( ! function_exists( 'WC' ) || ! WC()->countries ) {
        return [];
    }

    $countries = WC()->countries->get_countries();
    return is_array( $countries ) ? $countries : [];
}

/**
 * Get currency by customer's geolocation
 *
 * @return string|false Currency code or false
 */
function woolentor_get_geolocation_currency() {
    if ( ! class_exists( '\WoolentorPro\Modules\CurrencySwitcher\Geolocation' ) ) {
        return false;
    }

    return \WoolentorPro\Modules\CurrencySwitcher\Geolocation::instance()->detect_currency_by_location();
}

/**
 * Check if geolocation is enabled for currency switcher
 *
 * @return bool
 */
function woolentor_is_currency_geolocation_enabled() {
    if ( ! class_exists( '\WoolentorPro\Modules\CurrencySwitcher\Geolocation' ) ) {
        return false;
    }

    return \WoolentorPro\Modules\CurrencySwitcher\Geolocation::instance()->is_enabled();
}

/**
 * Get customer's detected country code
 *
 * @return string|false Country code or false
 */
function woolentor_get_customer_country() {
    if ( ! class_exists( '\WoolentorPro\Modules\CurrencySwitcher\Geolocation' ) ) {
        return false;
    }

    return \WoolentorPro\Modules\CurrencySwitcher\Geolocation::instance()->detect_customer_country();
}
