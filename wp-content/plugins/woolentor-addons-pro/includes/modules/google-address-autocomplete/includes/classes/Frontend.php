<?php
/**
 * Frontend handler for Google Address Autocomplete module
 */
namespace Woolentor\Modules\GoogleAddressAutocomplete;
use WooLentorPro\Traits\Singleton;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Frontend {
    use Singleton;

    /**
     * Module settings
     *
     * @var array
     */
    private $settings = [];

    /**
     * Class constructor
     */
    private function __construct() {
        $this->settings = get_option( 'woolentor_google_address_autocomplete_settings', [] );

        // Only load if API key is set
        if ( ! empty( $this->get_setting( 'google_api_key' ) ) ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        }
    }

    /**
     * Get a setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value or default
     */
    private function get_setting( $key, $default = '' ) {
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on checkout page
        if ( ! is_checkout() ) {
            return;
        }

        $api_key = $this->get_setting( 'google_api_key' );

        if ( empty( $api_key ) ) {
            return;
        }

        // Google Maps JavaScript API with Places library
        wp_enqueue_script(
            'google-maps-places-api',
            'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=places&callback=Function.prototype',
            [],
            null,
            true
        );

        // Module frontend styles
        wp_enqueue_style(
            'woolentor-google-address-autocomplete',
            MODULE_ASSETS . '/css/frontend.css',
            [],
            WOOLENTOR_VERSION_PRO
        );

        // Module frontend script
        wp_enqueue_script(
            'woolentor-google-address-autocomplete',
            MODULE_ASSETS . '/js/frontend.js',
            [ 'jquery', 'google-maps-places-api' ],
            WOOLENTOR_VERSION_PRO,
            true
        );

        // Prepare country restrictions
        $country_restrictions = $this->get_setting( 'country_restrictions', '' );
        $countries = [];

        if ( ! empty( $country_restrictions ) ) {
            $countries = array_filter(
                array_map( 'trim', explode( ',', $country_restrictions ) )
            );
            // Convert to lowercase for Google API
            $countries = array_map( 'strtolower', $countries );
        }

        // Pass settings to JavaScript
        wp_localize_script(
            'woolentor-google-address-autocomplete',
            'woolentorGoogleAutocomplete',
            [
                'targetFields'        => $this->get_setting( 'target_fields', 'both' ),
                'countryRestrictions' => $countries,
            ]
        );
    }
}
