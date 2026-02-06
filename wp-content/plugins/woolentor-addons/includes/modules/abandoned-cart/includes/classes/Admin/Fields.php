<?php
namespace Woolentor\Modules\AbandonedCart\Admin;
use WooLentor\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Fields {
    use Singleton;

    public function __construct(){
        add_filter( 'woolentor_admin_fields_vue', [ $this, 'admin_fields' ], 99, 1 );
    }

    /**
     * Admin Field Register
     * @param mixed $fields
     * @return mixed
     */
    public function admin_fields( $fields ){
        
        if( woolentor_is_pro() && method_exists( '\WoolentorPro\Modules\AbandonedCart\Abandoned_Cart', 'sitting_fields') ){
            array_splice( $fields['woolentor_others_tabs'], 19, 0, \WoolentorPro\Modules\AbandonedCart\Abandoned_Cart::instance()->sitting_fields() );
        }else{
            array_splice( $fields['woolentor_others_tabs'], 19, 0, $this->sitting_fields() );
        }

        return $fields;
    }

    /**
     * Settings Fields Fields;
     */
    public function sitting_fields(){

        $templates = \Woolentor\Modules\AbandonedCart\Database\DB_Handler::instance()->get_email_templates( array(
            'return_type' => 'array',
            'status' => 'active',
            'per_page' => 1000,
            'page' => 1
        ) );
        $templates_options = array();
        foreach ( $templates as $template ) {
            $templates_options[$template['id']] = $template['name'];
        }
        
        $fields = [
            [
                'id'   => 'woolentor_abandoned_cart_settings',
                'name'  => esc_html__( 'Abandoned Cart', 'woolentor' ),
                'type'   => 'abandon-cart',
                'default'=> 'off',
                'section'  => 'woolentor_abandoned_cart_settings',
                'documentation' => esc_url('https://woolentor.com/doc/how-to-recover-abandoned-carts-in-woocommerce-with-shoplentor/'),
                'placeholders' => \Woolentor\Modules\AbandonedCart\Email\Placeholder_Manager::instance()->get_supported_placeholders(),
                'option_id' => 'enable',
                'require_settings'  => true,
                'setting_fields' => [
                    array(
                        'id'  => 'enable',
                        'name'  => esc_html__( 'Enable / Disable', 'woolentor' ),
                        'desc'  => esc_html__( 'You can enable / disable abandoned cart from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class'   =>'woolentor-action-field-left',
                    ),

                    array(
                        'id'      => 'cart_abandonment_heading',
                        'type'      => 'title',
                        'heading'  => esc_html__( 'Cart Abandonment Options', 'woolentor' ),
                        'size'      => 'woolentor_style_seperator',
                    ),
    
                    array(
                        'id'    => 'abandoned_time',
                        'name'   => esc_html__( 'Cart Abandoned Time', 'woolentor' ),
                        'desc'    => esc_html__( 'After how many minutes a cart should be marked as abandoned. (Minimum: 5 minutes)', 'woolentor' ),
                        'type'    => 'number',
                        'default' => '60',
                        'min'     => '1',
                        'max'     => '1440',
                        'class'   => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'      => 'cart_abandonment_email_heading',
                        'type'      => 'title',
                        'heading'  => esc_html__( 'Email Settings', 'woolentor' ),
                        'size'      => 'woolentor_style_seperator',
                    ),

                    array(
                        'id'  => 'enable_reminder_email',
                        'name'  => esc_html__( 'Enable / Disable Remind Email', 'woolentor' ),
                        'desc'  => esc_html__( 'You can enable / disable remind email from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class'   =>'woolentor-action-field-left',
                    ),

                    [
                        'id'        => 'rule_list',
                        'name'       => esc_html__( 'Rule List', 'woolentor' ),
                        'type'        => 'repeater',
                        'title_field' => 'rule_title',
                        'condition'   => [ 'key'=>'enable_reminder_email','operator'=>'==', 'value'=>'on' ],
                        'max_items'   => '2',
                        'message'     => [
                            'title' => esc_html__( 'Upgrade to Premium Version', 'woolentor' ),
                            'desc'  => esc_html__( 'With the free version, you can add 2 rules. To unlock more rules and advanced features, please upgrade to the pro version.', 'woolentor' ),
                            'pro_link' => esc_url('https://woolentor.com/pricing/?utm_source=admin&utm_medium=lockfeatures&utm_campaign=free'),
                        ],
                        'options' => [
                            'button_label' => esc_html__( 'Add New Rule', 'woolentor' ),  
                        ],
                        'fields'  => [
                            array(
                                'id'  => 'rule_title',
                                'name'  => esc_html__( 'Rule Title', 'woolentor' ),
                                'desc'  => esc_html__( 'You can set rule title from here.', 'woolentor' ),
                                'type'  => 'text',
                                'default' => esc_html__( 'Rule Title', 'woolentor' ),
                                'class'   =>'woolentor-action-field-left',
                            ),
                            array(
                                'id'  => 'send_after_time',
                                'name'  => esc_html__( 'Send After', 'woolentor' ),
                                'desc'  => esc_html__( 'You can set send after time from here.', 'woolentor' ),
                                'type'  => 'number',
                                'default' => '30',
                                'min'     => '1',
                                'max'     => '9999',
                                'step'    => '1',
                                'class'   =>'woolentor-action-field-left',
                            ),
                            array(
                                'id'  => 'send_trigger_unit',
                                'name'  => esc_html__( 'Unit', 'woolentor' ),
                                'desc'  => esc_html__( 'You can set send after time unit from here.', 'woolentor' ),
                                'type'  => 'select',
                                'default' => 'minutes',
                                'options' => [
                                    'minutes' => esc_html__( 'Minutes', 'woolentor' ),
                                    'hours' => esc_html__( 'Hours', 'woolentor' ),
                                    'days' => esc_html__( 'Days', 'woolentor' ),
                                ],
                                'class'   =>'woolentor-action-field-left',
                            ),
                            array(
                                'id'  => 'email_template',
                                'name'  => esc_html__( 'Email Template', 'woolentor' ),
                                'desc'  => esc_html__( 'You can set email template from here.', 'woolentor' ),
                                'type'  => 'select',
                                'convertnumber' => true,
                                'default' => '0',
                                'options' => [ 0 => esc_html__( 'Select Template', 'woolentor' ) ] + $templates_options,
                                'class'   =>'woolentor-action-field-left',
                            ),
                        ]
                    ],

                    array(
                        'id'  => 'from_name',
                        'name'  => esc_html__( 'From Name', 'woolentor' ),
                        'desc'  => esc_html__( 'You can set from name from here.', 'woolentor' ),
                        'type'  => 'text',
                        'default' => get_bloginfo( 'name' ),
                        'class'   =>'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'from_email_address',
                        'name'  => esc_html__( 'From Email Address', 'woolentor' ),
                        'desc'  => esc_html__( 'You can set from email address from here.', 'woolentor' ),
                        'type'  => 'text',
                        'default' => get_option( 'admin_email' ),
                        'class'   =>'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'from_reply_to_email_address',
                        'name'  => esc_html__( 'From Reply To Email Address', 'woolentor' ),
                        'desc'  => esc_html__( 'You can set from reply to email address from here.', 'woolentor' ),
                        'type'  => 'text',
                        'default' => get_option( 'admin_email' ),
                        'class'   =>'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'recovery_report_notify_to_admin',
                        'name'  => esc_html__( 'Recovery Report Notify to Admin', 'woolentor' ),
                        'desc'  => esc_html__( 'You can set recovery report notify to admin from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class'   =>'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'recovery_report_notify_to_admin_email',
                        'name'  => esc_html__( 'Recovery Report Notify Email', 'woolentor' ),
                        'desc'  => esc_html__( 'You can set recovery report notify email from here.', 'woolentor' ),
                        'type'  => 'text',
                        'default' => get_option( 'admin_email' ),
                        'class'   => 'woolentor-action-field-left',
                        'condition' => [ 'key'=>'recovery_report_notify_to_admin','operator'=>'==', 'value'=>'on' ]
                    ),

                ]
            ]

        ];

        return $fields;

    }

}