<?php  
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woolentor_Module_Manager_Pro{
    use Singleton;

    /**
     * Constructor
     */
    public function __construct(){
        add_filter( 'woolentor_module_list',[$this, 'module_list'] );
    }

    // Pro Module List
    public function module_list( $module_list ){
        $pro_module_list = [
            
            'partial-payment' => [
                'slug'   =>'partial-payment',
                'title'  => esc_html('Partial Payment'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_partial_payment_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'pre-orders' => [
                'slug'   =>'pre-orders',
                'title'  => esc_html('Pre Orders'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_pre_order_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'gtm-conversion-tracking' => [
                'slug'   =>'gtm-conversion-tracking',
                'title'  => esc_html('GTM Conversion Tracking'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_gtm_convertion_tracking_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'size-chart' => [
                'slug'   =>'size-chart',
                'title'  => esc_html('Size Chart'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_size_chart_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'email-customizer' => [
                'slug'   =>'email-customizer',
                'title'  => esc_html('Email Customizer'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_email_customizer_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'email-automation' => [
                'slug'   =>'email-automation',
                'title'  => esc_html('Email Automation'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_email_automation_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'order-bump' => [
                'slug'   =>'order-bump',
                'title'  => esc_html('Order Bump'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_order_bump_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'product-filter' => [
                'slug'   =>'product-filter',
                'title'  => esc_html('Product Filter'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_product_filter_settings',
                    'default' => 'off'
                ],
                'main_class' => 'Woolentor_Product_Filter',
                'is_pro'     => true,
                'manage_setting' => true
            ],
            'side-mini-cart' => [
                'slug'   =>'side-mini-cart',
                'title'  => esc_html('Side Mini Cart'),
                'option' => [
                    'key'     => 'mini_side_cart',
                    'section' => 'woolentor_others_tabs',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\SideMiniCart\Side_Mini_Cart',
                'is_pro'     => true,
                'manage_setting' => true
            ],
            'quick-checkout' => [
                'slug'   =>'quick-checkout',
                'title'  => esc_html('Quick Checkout'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_quick_checkout_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\QuickCheckout\Quick_Checkout',
                'is_pro'     => true,
                'manage_setting' => true
            ],
            'google-address-autocomplete' => [
                'slug'   => 'google-address-autocomplete',
                'title'  => esc_html('Google Address Autocomplete'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_google_address_autocomplete_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\GoogleAddressAutocomplete\Google_Address_Autocomplete',
                'is_pro'     => true,
                'manage_setting' => true
            ]

        ];

        $pro_module_list = apply_filters('woolentor_pro_module_list', $pro_module_list);

        $final_module_list = array_merge($module_list, $pro_module_list);

        return $final_module_list;
        
    }


}

Woolentor_Module_Manager_Pro::instance();