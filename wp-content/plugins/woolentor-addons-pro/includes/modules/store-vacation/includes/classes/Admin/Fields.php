<?php
namespace WoolentorPro\Modules\StoreVacation\Admin;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Fields {
    use Singleton;

    /**
     * Settings Fields Fields;
     */
    public function sitting_fields(){
        $fields = [
            [
                'id'     => 'woolentor_store_vacation_settings',
                'name'    => esc_html__( 'Store Vacation', 'woolentor' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_store_vacation_settings',
                'option_id'=> 'enable',
                'require_settings' => true,
                'documentation' => esc_url('https://woolentor.com/doc/setup-the-store-vacation-module-in-woocommerce/'),
                'setting_fields' => array(
                    
                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor' ),
                        'desc'  => esc_html__( 'Enable/Disable store vacation mode', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'vacation_start_date',
                        'name'   => esc_html__( 'Start Date', 'woolentor' ),
                        'type'    => 'date',
                        'desc'    => esc_html__( 'Select vacation start date', 'woolentor' ),
                        'class'   => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'vacation_end_date',
                        'name'   => esc_html__( 'End Date', 'woolentor' ),
                        'type'    => 'date',
                        'desc'    => esc_html__( 'Select vacation end date', 'woolentor' ),
                        'class'   => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'      => 'notice_heading',
                        'heading'  => esc_html__( 'Notice Settings', 'woolentor' ),
                        'type'      => 'title'
                    ),
        
                    array(
                        'id'    => 'notice_position',
                        'name'   => esc_html__( 'Notice Position', 'woolentor' ),
                        'type'    => 'select',
                        'default' => 'woocommerce_before_cart',
                        'options' => array(
                            'woocommerce_before_shop_loop'      => esc_html__('Before Shop Loop', 'woolentor'),
                            'woocommerce_before_single_product' => esc_html__('Before Single Product', 'woolentor'),
                            'woocommerce_before_cart'           => esc_html__('Before Cart', 'woolentor'),
                            'shop_and_single_product'           => esc_html__('Shop & Single Product', 'woolentor'),
                            'use_shortcode'                     => esc_html__( 'Use Shortcode / Widget', 'woolentor' ),
                        ),
                        'class'   => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'vacation_use_shortcode_message',
                        'heading'=> wp_kses_post('Use the shortcode <code>[woolentor_vacation_notice]</code> or the widget to display the vacation notice wherever you need it.'),
                        'type'    => 'title',
                        'condition' => array( 'key'=>'notice_position', 'operator'=>'==', 'value'=>'use_shortcode' ),
                        'class'     => 'woolentor_option_field_notice'
                    ),

                    array(
                        'id'    => 'vacation_message',
                        'name'   => esc_html__( 'Vacation Message', 'woolentor' ),
                        'type'    => 'textarea',
                        'desc'    => esc_html__( 'Enter message to display during vacation. You can use these placeholders: {start_date}, {end_date}, {days_remaining}', 'woolentor' ),
                        'default' => esc_html__( '🏖️ Dear valued customers, our store is currently on vacation from {start_date} to {end_date}. During this time, new orders will be temporarily suspended. We will resume normal operations on {end_date}. Thank you for your understanding!', 'woolentor' ),
                    ),
        
                    array(
                        'id'    => 'notice_color',
                        'name'   => esc_html__( 'Notice Text Color', 'woolentor' ),
                        'type'    => 'color',
                        'default' => '#000000',
                        'class'   => 'woolentor-action-field-left'
                    ),
        
                    array(
                        'id'    => 'notice_bg_color',
                        'name'   => esc_html__( 'Notice Background Color', 'woolentor' ),
                        'type'    => 'color',
                        'default' => '#ffffff',
                        'class'   => 'woolentor-action-field-left'
                    ),

                    // Product Settings
                    array(
                        'id'      => 'product_heading',
                        'heading'  => esc_html__( 'Product Settings', 'woolentor' ),
                        'type'      => 'title'
                    ),

                    array(
                        'id'    => 'hide_add_to_cart',
                        'name'   => esc_html__( 'Turn Off Purchases', 'woolentor' ),
                        'type'    => 'checkbox',
                        'desc'    => esc_html__( 'Turn off purchases during vacation', 'woolentor' ),
                        'default' => 'off',
                        'class'   => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'product_availability_text',
                        'name'   => esc_html__( 'Product Availability Text', 'woolentor' ),
                        'type'    => 'text',
                        'desc'    => esc_html__( 'Text to show instead of Add to Cart button', 'woolentor' ),
                        'default' => esc_html__( 'Available after vacation', 'woolentor' ),
                        'class'   => 'woolentor-action-field-left',
                        'condition' => array( 'key'=>'hide_add_to_cart', 'operator'=>'==', 'value'=>'on' ),
                    ),




                    array(
                        'id'      => 'advanced_heading',
                        'heading'  => esc_html__( 'Advanced Settings', 'woolentor' ),
                        'type'      => 'title'
                    ),
            
                    array(
                        'id'    => 'multiple_schedules',
                        'name'   => esc_html__( 'Multiple Schedules', 'woolentor' ),
                        'type'    => 'repeater',
                        'title_field' => 'title',
                        'fields'  => [
                            array(
                                'id'        => 'title',
                                'name'       => esc_html__( 'Schedule Title', 'woolentor' ),
                                'type'        => 'text',
                            ),
                            array(
                                'id'        => 'recurring',
                                'name'       => esc_html__( 'Recurring', 'woolentor' ),
                                'type'        => 'select',
                                'options'     => array(
                                    'none'      => esc_html__('None', 'woolentor'),
                                    'weekly'    => esc_html__('Weekly', 'woolentor'),
                                    'monthly'   => esc_html__('Monthly', 'woolentor'),
                                    'yearly'    => esc_html__('Yearly', 'woolentor'),
                                ),
                            ),
                            array(
                                'id'        => 'start_date',
                                'name'       => esc_html__( 'Start Date', 'woolentor' ),
                                'type'        => 'date',
                            ),
                            array(
                                'id'        => 'end_date',
                                'name'       => esc_html__( 'End Date', 'woolentor' ),
                                'type'        => 'date',
                                'condition' => array( 'key'=>'recurring', 'operator'=>'==', 'value'=>'none' ),
                            ),
                            array(
                                'id'        => 'message',
                                'name'       => esc_html__( 'Message', 'woolentor' ),
                                'type'        => 'textarea',
                            )
                        ]
                    ),
            
                    array(
                        'id'      => 'category_specific_heading',
                        'heading'  => esc_html__( 'Category Specific Settings', 'woolentor' ),
                        'type'      => 'title',
                    ),
            
                    array(
                        'id'    => 'category_specific_vacation',
                        'name'   => esc_html__( 'Category Specific', 'woolentor' ),
                        'type'    => 'repeater',
                        'title_field' => 'category',
                        'fields'  => [
                            array(
                                'id'        => 'category',
                                'name'       => esc_html__( 'Category', 'woolentor' ),
                                'type'        => 'select',
                                'options'     => woolentor_taxonomy_list('product_cat','term_id'),
                            ),
                            array(
                                'id'        => 'start_date',
                                'name'       => esc_html__( 'Start Date', 'woolentor' ),
                                'type'        => 'date',
                            ),
                            array(
                                'id'        => 'end_date',
                                'name'       => esc_html__( 'End Date', 'woolentor' ),
                                'type'        => 'date',
                            ),
                            array(
                                'id'        => 'message',
                                'name'       => esc_html__( 'Message', 'woolentor' ),
                                'type'        => 'textarea',
                            ),
                        ]
                    ),
            
                    array(
                        'id'      => 'notice_customization_heading',
                        'heading'  => esc_html__( 'Notice Customization', 'woolentor' ),
                        'type'      => 'title'
                    ),
            
                    array(
                        'id'    => 'notice_style',
                        'name'   => esc_html__( 'Notice Style', 'woolentor' ),
                        'type'    => 'select',
                        'default' => 'banner',
                        'options' => array(
                            'banner'    => esc_html__('Banner', 'woolentor'),
                            'popup'     => esc_html__('Popup', 'woolentor'),
                            'floating'  => esc_html__('Floating', 'woolentor'),
                        ),
                        'class'   => 'woolentor-action-field-left',
                    ),
            
                    array(
                        'id'    => 'show_countdown',
                        'name'   => esc_html__( 'Show Countdown', 'woolentor' ),
                        'type'    => 'checkbox',
                        'class'   => 'woolentor-action-field-left',
                        'condition' => array( 'key'=>'notice_style', 'operator'=>'==', 'value'=>'popup' ),
                    ),

                    array(
                        'id'    => 'floating_position',
                        'name'   => esc_html__( 'Floating Notice Position', 'woolentor' ),
                        'type'    => 'select',
                        'default' => 'bottom-right',
                        'options' => array(
                            'top-left'     => esc_html__('Top Left', 'woolentor'),
                            'top-right'    => esc_html__('Top Right', 'woolentor'),
                            'bottom-left'  => esc_html__('Bottom Left', 'woolentor'),
                            'bottom-right' => esc_html__('Bottom Right', 'woolentor'),
                        ),
                        'condition' => array( 'key'=>'notice_style', 'operator'=>'==', 'value'=>'floating' ),
                        'class'   => 'woolentor-action-field-left'
                    ),
            
                    array(
                        'id'      => 'access_control_heading',
                        'heading'  => esc_html__( 'Access Control', 'woolentor' ),
                        'type'      => 'title'
                    ),
            
                    array(
                        'id'    => 'allowed_user_roles',
                        'name'   => esc_html__( 'Allowed User Roles', 'woolentor' ),
                        'type'    => 'multiselect',
                        'options' => function_exists('woolentor_get_editable_roles') ? woolentor_get_editable_roles() : [],
                        'class'   => 'woolentor-action-field-left',
                    ),
            
                    array(
                        'id'    => 'allowed_ips',
                        'name'   => esc_html__( 'Allowed IPs', 'woolentor' ),
                        'type'    => 'textarea',
                        'desc'    => esc_html__('Enter IPs separated by commas', 'woolentor'),
                    ),
            
                    array(
                        'id'      => 'seo_heading',
                        'heading'  => esc_html__( 'SEO Settings', 'woolentor' ),
                        'type'      => 'title'
                    ),
            
                    array(
                        'id'    => 'vacation_meta_title',
                        'name'   => esc_html__( 'Meta Title', 'woolentor' ),
                        'type'    => 'text'
                    ),
            
                    array(
                        'id'    => 'vacation_meta_description',
                        'name'   => esc_html__( 'Meta Description', 'woolentor' ),
                        'type'    => 'textarea'
                    ),
    
                )
            ]

        ];

        return $fields;
    }

}