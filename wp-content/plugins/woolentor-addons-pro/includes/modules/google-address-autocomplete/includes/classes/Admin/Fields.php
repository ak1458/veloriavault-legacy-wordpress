<?php
/**
 * Admin settings fields for Google Address Autocomplete module
 */
namespace Woolentor\Modules\GoogleAddressAutocomplete\Admin;
use WooLentorPro\Traits\Singleton;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Fields {
    use Singleton;

    /**
     * Class constructor
     */
    public function __construct() {
        add_filter( 'woolentor_admin_fields_vue', [ $this, 'admin_fields' ], 99, 1 );
    }

    /**
     * Register admin fields
     *
     * @param array $fields Existing fields
     * @return array Modified fields
     */
    public function admin_fields( $fields ) {
        if ( isset( $fields['woolentor_others_tabs'] ) && is_array( $fields['woolentor_others_tabs'] ) ) {
            array_splice( $fields['woolentor_others_tabs'], 32, 0, $this->settings_fields() );
        }
        return $fields;
    }

    /**
     * Get module settings fields
     *
     * @return array Settings fields configuration
     */
    public function settings_fields() {
        $fields = [
            [
                'id'                => 'woolentor_google_address_autocomplete_settings',
                'name'              => esc_html__( 'Google Address Autocomplete', 'woolentor-pro' ),
                'type'              => 'module',
                'default'           => 'off',
                'section'           => 'woolentor_google_address_autocomplete_settings',
                'option_id'         => 'enable',
                'documentation'     => esc_url( 'https://woolentor.com/doc/how-to-enable-google-address-autocomplete-for-woocommerce-using-shoplentor/' ),
                'require_settings'  => true,
                'setting_fields'    => [
                    [
                        'id'        => 'enable',
                        'name'      => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'      => esc_html__( 'Enable or disable this module.', 'woolentor-pro' ),
                        'type'      => 'checkbox',
                        'default'   => 'off',
                        'class'     => 'woolentor-action-field-left'
                    ],
                    [
                        'id'        => 'google_api_key',
                        'name'      => esc_html__( 'Google API Key', 'woolentor-pro' ),
                        'desc'      => sprintf(
                            /* translators: %s: Google Cloud Console URL */
                            esc_html__( 'Enter your Google Places API key. Get it from %s. Make sure the Places API is enabled.', 'woolentor-pro' ),
                            '<a href="https://console.cloud.google.com/google/maps-apis" target="_blank">Google Cloud Console</a>'
                        ),
                        'type'      => 'text',
                        'default'   => '',
                        'class'     => 'woolentor-action-field-left',
                        'condition' => [ 'key' => 'enable', 'operator' => '==', 'value' => 'on' ]
                    ],
                    [
                        'id'        => 'target_fields',
                        'name'      => esc_html__( 'Apply To', 'woolentor-pro' ),
                        'desc'      => esc_html__( 'Select which address fields to enable autocomplete on.', 'woolentor-pro' ),
                        'type'      => 'select',
                        'default'   => 'both',
                        'options'   => [
                            'both'     => esc_html__( 'Billing & Shipping', 'woolentor-pro' ),
                            'billing'  => esc_html__( 'Billing Only', 'woolentor-pro' ),
                            'shipping' => esc_html__( 'Shipping Only', 'woolentor-pro' ),
                        ],
                        'class'     => 'woolentor-action-field-left',
                        'condition' => [ 'key' => 'enable', 'operator' => '==', 'value' => 'on' ]
                    ],
                    [
                        'id'          => 'country_restrictions',
                        'name'        => esc_html__( 'Allowed Countries', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Show address suggestions only from these countries. Enter ISO 3166-1 alpha-2 country codes separated by commas. Leave empty to allow all countries.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => '',
                        'placeholder' => esc_html__( 'e.g., US, CA, GB, AU', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                        'condition'   => [ 'key' => 'enable', 'operator' => '==', 'value' => 'on' ]
                    ],
                ]
            ]
        ];

        return $fields;
    }
}
