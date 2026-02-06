<?php
/**
 * Currency Switcher - Geolocation-based Currency Detection
 *
 * @package WoolentorPro\Modules\CurrencySwitcher\Frontend
 */

namespace WoolentorPro\Modules\CurrencySwitcher\Frontend;

use WooLentorPro\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Geolocation class for automatic currency detection
 */
class Geolocation {
    use Singleton;

    /**
     * Settings cache
     *
     * @var array|null
     */
    private $settings = null;

    /**
     * Constructor
     */
    private function __construct() {
        $this->maybe_set_currency_by_geolocation();

        // Filter to hide currency switcher if enabled
        add_filter( 'woolentor_currency_switcher_visible', [ $this, 'maybe_hide_switcher' ] );
    }

    /**
     * Get module settings
     *
     * @return array
     */
    private function get_settings() {
        if ( $this->settings === null ) {
            $this->settings = get_option( 'woolentor_currency_switcher', [] );
        }
        return $this->settings;
    }

    /**
     * Check if geolocation is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        $settings = $this->get_settings();
        return ! empty( $settings['enable_geolocation'] ) && $settings['enable_geolocation'] === 'on';
    }

    /**
     * Set currency by geolocation if enabled and no currency set yet
     */
    public function maybe_set_currency_by_geolocation() {
        // Don't run in admin (except for AJAX)
        if ( is_admin() && ! wp_doing_ajax() ) {
            return;
        }

        // Check if geolocation is enabled
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Check if user already has a currency preference
        if ( $this->user_has_currency_preference() ) {
            return;
        }

        // Detect and set currency
        $detected_currency = $this->detect_currency_by_location();

        if ( $detected_currency ) {
            $this->save_currency_preference( $detected_currency );
        }
    }

    /**
     * Check if user already has currency preference set
     *
     * @return bool
     */
    private function user_has_currency_preference() {
        $current_user_id = get_current_user_id();

        // Check user meta for logged-in users
        if ( $current_user_id ) {
            $user_currency = get_user_meta( $current_user_id, 'woolentor_current_currency_code', true );
            if ( ! empty( $user_currency ) ) {
                return true;
            }
        }

        // Check cookie for guests
        if ( isset( $_COOKIE['woolentor_current_currency_code'] ) && ! empty( $_COOKIE['woolentor_current_currency_code'] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Detect currency based on customer location
     *
     * @return string|false Currency code or false
     */
    public function detect_currency_by_location() {
        // Detect country
        $country = $this->detect_customer_country();

        if ( ! $country ) {
            return false;
        }

        // Get currency for this country
        $currency = $this->get_currency_for_country( $country );

        if ( ! $currency ) {
            return false;
        }

        // Verify currency is in our available list
        if ( ! $this->is_currency_available( $currency ) ) {
            return false;
        }

        return $currency;
    }

    /**
     * Detect customer's country via WooCommerce geolocation
     *
     * @return string|false Country code or false
     */
    public function detect_customer_country() {
        // First check if admin has set a test country for debugging
        $settings = $this->get_settings();
        if ( ! empty( $settings['test_country'] ) ) {
            return $settings['test_country'];
        }

        if ( ! class_exists( '\WC_Geolocation' ) ) {
            return false;
        }

        // Get visitor's IP
        $ip_address = $this->get_visitor_ip();

        // Check if it's a local/private IP
        if ( $this->is_local_ip( $ip_address ) ) {
            // Try to get external IP for localhost testing
            $external_ip = $this->get_external_ip();
            if ( $external_ip ) {
                $ip_address = $external_ip;
            } else {
                // Can't detect location for localhost without external IP
                return false;
            }
        }

        // Use WooCommerce geolocation with the IP
        $geo = \WC_Geolocation::geolocate_ip( $ip_address );

        return ! empty( $geo['country'] ) ? $geo['country'] : false;
    }

    /**
     * Get visitor's IP address
     *
     * @return string
     */
    private function get_visitor_ip() {
        if ( class_exists( '\WC_Geolocation' ) ) {
            return \WC_Geolocation::get_ip_address();
        }

        // Fallback IP detection
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            // Cloudflare
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            // Proxy
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            $ip = explode( ',', $ip );
            $ip = trim( $ip[0] );
        } elseif ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        return $ip;
    }

    /**
     * Check if IP is local/private
     *
     * @param string $ip IP address
     * @return bool
     */
    private function is_local_ip( $ip ) {
        // Check for localhost
        if ( in_array( $ip, [ '127.0.0.1', '::1', 'localhost' ], true ) ) {
            return true;
        }

        // Check for private IP ranges
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
            return true;
        }

        return false;
    }

    /**
     * Get external IP address (for localhost testing)
     * Uses transient to cache the result
     *
     * @return string|false External IP or false
     */
    private function get_external_ip() {
        // Check cache first
        $cached_ip = get_transient( 'woolentor_external_ip' );
        if ( $cached_ip !== false ) {
            return $cached_ip;
        }

        // Try different services to get external IP
        $services = [
            'https://api.ipify.org',
            'https://ipv4.icanhazip.com',
            'https://checkip.amazonaws.com',
        ];

        foreach ( $services as $service ) {
            $response = wp_remote_get( $service, [
                'timeout' => 5,
                'sslverify' => false,
            ] );

            if ( ! is_wp_error( $response ) ) {
                $ip = trim( wp_remote_retrieve_body( $response ) );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    // Cache for 1 hour
                    set_transient( 'woolentor_external_ip', $ip, HOUR_IN_SECONDS );
                    return $ip;
                }
            }
        }

        return false;
    }

    /**
     * Get currency for a country code
     *
     * @param string $country_code Two-letter country code
     * @return string|false Currency code or false
     */
    public function get_currency_for_country( $country_code ) {
        $settings = $this->get_settings();
        $mapping = isset( $settings['country_currency_map'] ) ? $settings['country_currency_map'] : [];

        // First check admin-configured mapping
        if ( ! empty( $mapping ) && is_array( $mapping ) ) {
            foreach ( $mapping as $map ) {
                if ( isset( $map['countries'] ) && is_array( $map['countries'] ) ) {
                    if ( in_array( $country_code, $map['countries'], true ) ) {
                        return isset( $map['currency'] ) ? $map['currency'] : false;
                    }
                }
            }
        }

        // Fallback: Use default country-currency mapping
        return $this->get_default_currency_for_country( $country_code );
    }

    /**
     * Get default currency for country (standard mapping)
     *
     * @param string $country_code Two-letter country code
     * @return string|false Currency code or false
     */
    private function get_default_currency_for_country( $country_code ) {
        $default_map = $this->get_country_currency_defaults();
        return isset( $default_map[ $country_code ] ) ? $default_map[ $country_code ] : false;
    }

    /**
     * Check if currency is available in our currency list
     *
     * @param string $currency_code Currency code to check
     * @return bool
     */
    private function is_currency_available( $currency_code ) {
        if ( ! function_exists( 'woolentor_currency_list' ) ) {
            return false;
        }

        $currency_list = woolentor_currency_list();

        if ( empty( $currency_list ) || ! is_array( $currency_list ) ) {
            return false;
        }

        foreach ( $currency_list as $currency ) {
            if ( isset( $currency['currency'] ) && $currency['currency'] === $currency_code ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save currency preference
     *
     * @param string $currency_code Currency code to save
     */
    private function save_currency_preference( $currency_code ) {
        $current_user_id = get_current_user_id();

        if ( $current_user_id ) {
            update_user_meta( $current_user_id, 'woolentor_current_currency_code', $currency_code );
        } else {
            // Set cookie for guests (24 hours)
            if ( ! headers_sent() ) {
                setcookie(
                    'woolentor_current_currency_code',
                    $currency_code,
                    time() + 86400,
                    COOKIEPATH,
                    COOKIE_DOMAIN
                );
                // Also set in $_COOKIE for immediate use
                $_COOKIE['woolentor_current_currency_code'] = $currency_code;
            }
        }
    }

    /**
     * Maybe hide currency switcher based on settings
     *
     * @param bool $visible Current visibility state
     * @return bool
     */
    public function maybe_hide_switcher( $visible ) {
        if ( ! $this->is_enabled() ) {
            return $visible;
        }

        $settings = $this->get_settings();

        if ( ! empty( $settings['hide_switcher_with_geo'] ) && $settings['hide_switcher_with_geo'] === 'on' ) {
            return false;
        }

        return $visible;
    }

    /**
     * Default country to currency mapping
     * ISO 3166-1 alpha-2 country codes to ISO 4217 currency codes
     *
     * @return array
     */
    public function get_country_currency_defaults() {
        return [
            // Americas
            'US' => 'USD', // United States
            'CA' => 'CAD', // Canada
            'MX' => 'MXN', // Mexico
            'BR' => 'BRL', // Brazil
            'AR' => 'ARS', // Argentina
            'CL' => 'CLP', // Chile
            'CO' => 'COP', // Colombia
            'PE' => 'PEN', // Peru
            'VE' => 'VES', // Venezuela
            'EC' => 'USD', // Ecuador (uses USD)
            'UY' => 'UYU', // Uruguay
            'PY' => 'PYG', // Paraguay
            'BO' => 'BOB', // Bolivia
            'CR' => 'CRC', // Costa Rica
            'PA' => 'PAB', // Panama
            'GT' => 'GTQ', // Guatemala
            'HN' => 'HNL', // Honduras
            'NI' => 'NIO', // Nicaragua
            'SV' => 'USD', // El Salvador (uses USD)
            'DO' => 'DOP', // Dominican Republic
            'JM' => 'JMD', // Jamaica
            'TT' => 'TTD', // Trinidad and Tobago

            // Europe
            'GB' => 'GBP', // United Kingdom
            'DE' => 'EUR', // Germany
            'FR' => 'EUR', // France
            'IT' => 'EUR', // Italy
            'ES' => 'EUR', // Spain
            'NL' => 'EUR', // Netherlands
            'BE' => 'EUR', // Belgium
            'AT' => 'EUR', // Austria
            'IE' => 'EUR', // Ireland
            'PT' => 'EUR', // Portugal
            'FI' => 'EUR', // Finland
            'GR' => 'EUR', // Greece
            'SK' => 'EUR', // Slovakia
            'SI' => 'EUR', // Slovenia
            'EE' => 'EUR', // Estonia
            'LV' => 'EUR', // Latvia
            'LT' => 'EUR', // Lithuania
            'LU' => 'EUR', // Luxembourg
            'MT' => 'EUR', // Malta
            'CY' => 'EUR', // Cyprus
            'CH' => 'CHF', // Switzerland
            'SE' => 'SEK', // Sweden
            'NO' => 'NOK', // Norway
            'DK' => 'DKK', // Denmark
            'PL' => 'PLN', // Poland
            'CZ' => 'CZK', // Czech Republic
            'HU' => 'HUF', // Hungary
            'RO' => 'RON', // Romania
            'BG' => 'BGN', // Bulgaria
            'HR' => 'EUR', // Croatia (joined EUR in 2023)
            'RS' => 'RSD', // Serbia
            'UA' => 'UAH', // Ukraine
            'BY' => 'BYN', // Belarus
            'RU' => 'RUB', // Russia
            'TR' => 'TRY', // Turkey
            'IS' => 'ISK', // Iceland
            'AL' => 'ALL', // Albania
            'MK' => 'MKD', // North Macedonia
            'BA' => 'BAM', // Bosnia and Herzegovina
            'ME' => 'EUR', // Montenegro (uses EUR)
            'XK' => 'EUR', // Kosovo (uses EUR)
            'MD' => 'MDL', // Moldova
            'GE' => 'GEL', // Georgia
            'AM' => 'AMD', // Armenia
            'AZ' => 'AZN', // Azerbaijan

            // Asia Pacific
            'JP' => 'JPY', // Japan
            'CN' => 'CNY', // China
            'KR' => 'KRW', // South Korea
            'KP' => 'KPW', // North Korea
            'IN' => 'INR', // India
            'AU' => 'AUD', // Australia
            'NZ' => 'NZD', // New Zealand
            'SG' => 'SGD', // Singapore
            'HK' => 'HKD', // Hong Kong
            'TW' => 'TWD', // Taiwan
            'TH' => 'THB', // Thailand
            'MY' => 'MYR', // Malaysia
            'ID' => 'IDR', // Indonesia
            'PH' => 'PHP', // Philippines
            'VN' => 'VND', // Vietnam
            'BD' => 'BDT', // Bangladesh
            'PK' => 'PKR', // Pakistan
            'LK' => 'LKR', // Sri Lanka
            'NP' => 'NPR', // Nepal
            'MM' => 'MMK', // Myanmar
            'KH' => 'KHR', // Cambodia
            'LA' => 'LAK', // Laos
            'BN' => 'BND', // Brunei
            'MN' => 'MNT', // Mongolia
            'KZ' => 'KZT', // Kazakhstan
            'UZ' => 'UZS', // Uzbekistan
            'KG' => 'KGS', // Kyrgyzstan
            'TJ' => 'TJS', // Tajikistan
            'TM' => 'TMT', // Turkmenistan
            'AF' => 'AFN', // Afghanistan
            'MV' => 'MVR', // Maldives
            'BT' => 'BTN', // Bhutan
            'FJ' => 'FJD', // Fiji
            'PG' => 'PGK', // Papua New Guinea

            // Middle East
            'AE' => 'AED', // UAE
            'SA' => 'SAR', // Saudi Arabia
            'QA' => 'QAR', // Qatar
            'KW' => 'KWD', // Kuwait
            'BH' => 'BHD', // Bahrain
            'OM' => 'OMR', // Oman
            'IL' => 'ILS', // Israel
            'JO' => 'JOD', // Jordan
            'LB' => 'LBP', // Lebanon
            'SY' => 'SYP', // Syria
            'IQ' => 'IQD', // Iraq
            'IR' => 'IRR', // Iran
            'YE' => 'YER', // Yemen
            'PS' => 'ILS', // Palestine (uses ILS)

            // Africa
            'EG' => 'EGP', // Egypt
            'ZA' => 'ZAR', // South Africa
            'NG' => 'NGN', // Nigeria
            'KE' => 'KES', // Kenya
            'GH' => 'GHS', // Ghana
            'MA' => 'MAD', // Morocco
            'DZ' => 'DZD', // Algeria
            'TN' => 'TND', // Tunisia
            'LY' => 'LYD', // Libya
            'SD' => 'SDG', // Sudan
            'ET' => 'ETB', // Ethiopia
            'TZ' => 'TZS', // Tanzania
            'UG' => 'UGX', // Uganda
            'RW' => 'RWF', // Rwanda
            'AO' => 'AOA', // Angola
            'MZ' => 'MZN', // Mozambique
            'ZM' => 'ZMW', // Zambia
            'ZW' => 'ZWL', // Zimbabwe
            'BW' => 'BWP', // Botswana
            'NA' => 'NAD', // Namibia
            'MU' => 'MUR', // Mauritius
            'SN' => 'XOF', // Senegal (CFA Franc)
            'CI' => 'XOF', // Ivory Coast (CFA Franc)
            'CM' => 'XAF', // Cameroon (CFA Franc)
            'CD' => 'CDF', // DR Congo
        ];
    }
}
