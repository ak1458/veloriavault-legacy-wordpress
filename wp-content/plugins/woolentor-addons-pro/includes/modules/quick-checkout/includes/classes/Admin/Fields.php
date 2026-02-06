<?php
namespace Woolentor\Modules\QuickCheckout\Admin;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Fields {
    use Singleton;

    public function __construct(){
        add_filter( 'woolentor_admin_fields_vue', [ $this, 'admin_fields' ], 99, 1 );
    }

    public function admin_fields( $fields ){
        array_splice( $fields['woolentor_others_tabs'], 25, 0, $this->quick_checkout_sitting_fields() );
        return $fields;
    }

    /**
     * Currency Fields;
     */
    public function quick_checkout_sitting_fields(){
        $fields = [
            [
                'id'   => 'woolentor_quick_checkout_settings',
                'name'  => esc_html__( 'Quick Checkout', 'woolentor-pro' ),
                'type'   => 'module',
                'default'=> 'off',
                'section'  => 'woolentor_quick_checkout_settings',
                'option_id' => 'enable',
                'documentation' => esc_url('https://woolentor.com/doc/quick-checkout-module/'),
                'require_settings'  => true,
                'setting_fields' => [
                    
                    [
                        'id'    => 'enable',
                        'name'   => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Enable / disable this module.', 'woolentor-pro' ),
                        'type'    => 'checkbox',
                        'default' => 'off',
                        'class'   => 'woolentor-action-field-left'
                    ],

                    [
                        'id'    => 'enable_on_shop_archive',
                        'name'   => esc_html__( 'Enable quick checkout in Shop / Archive page', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Enable this option to display a quick checkout button on shop and archive page.', 'woolentor-pro' ),
                        'type'    => 'checkbox',
                        'default' => 'on',
                        'class'   => 'woolentor-action-field-left'
                    ],

                    [
                        'id'      => 'button_heading',
                        'heading'  => esc_html__( 'Button Settings', 'woolentor-pro' ),
                        'type'      => 'title',
                        'condition' => [ 'key'=>'enable_on_shop_archive', 'operator'=>'==', 'value'=>'on' ],
                    ],

                    [
                        'id'    => 'button_position',
                        'name'   => esc_html__( 'Button position', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'This option will be affected only Shop / Archive page.', 'woolentor-pro' ),
                        'type'    => 'select',
                        'default' => 'before_cart_btn',
                        'options' => [
                            'before_cart_btn' => esc_html__( 'Before Add To Cart', 'woolentor-pro' ),
                            'after_cart_btn'  => esc_html__( 'After Add To Cart', 'woolentor-pro' ),
                            'top_thumbnail'   => esc_html__( 'Top On Image', 'woolentor-pro' ),
                            'use_shortcode'   => esc_html__( 'Use Shortcode', 'woolentor-pro' ),
                        ],
                        'class'   => 'woolentor-action-field-left',
                        'condition' => [ 'key'=>'enable_on_shop_archive', 'operator'=>'==', 'value'=>'on' ],
                    ],

                    [
                        'id'      => 'shortcode_info_data',
                        'heading'  => wp_kses_post('Place this shortcode <code>[woolentor_quick_checkout_button]</code> wherever you want the quick checkout button to appear.'),
                        'type'      => 'title',
                        'condition' => [ 'key'=>'button_position|enable_on_shop_archive', 'operator'=>'==|==', 'value'=>'use_shortcode|on' ],
                        'class'     => 'woolentor_option_field_notice'
                    ],

                    [
                        'id'        => 'button_text',
                        'name'       => esc_html__( 'Button text', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Enter your quick checkout button text.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'Buy Now', 'woolentor-pro' ),
                        'placeholder' => esc_html__( 'Buy Now', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                        'condition'   => [ 'key'=>'enable_on_shop_archive', 'operator'=>'==', 'value'=>'on' ],
                    ],
                    [
                        'id'    => 'button_icon_type',
                        'name'   => esc_html__( 'Button icon type', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Choose an icon type for the quick checkout button from here.', 'woolentor-pro' ),
                        'type'    => 'select',
                        'default' => 'none',
                        'options' => [
                            'none'     => esc_html__( 'None', 'woolentor-pro' ),
                            'customicon' => esc_html__( 'Custom Icon', 'woolentor-pro' ),
                            'customimage'=> esc_html__( 'Custom Image', 'woolentor-pro' ),
                        ],
                        'class'       => 'woolentor-action-field-left',
                        'condition'   => [ 'key'=>'enable_on_shop_archive', 'operator'=>'==', 'value'=>'on' ],
                    ],
                    [
                        'id'    => 'button_icon',
                        'name'   => esc_html__( 'Button Icon', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'You can manage the button icon.', 'woolentor-pro' ),
                        'type'    => 'iconpicker',
                        'default' => 'sli sli-wallet',
                        'class'   => 'woolentor_icon_picker woolentor-action-field-left',
                        'condition'   => [ 'key'=>'button_icon_type|enable_on_shop_archive', 'operator'=>'==|==', 'value'=>'customicon|on' ],
                    ],
                    [
                        'id'    => 'button_custom_image',
                        'name'   => esc_html__( 'Button custom icon', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Upload you custom icon from here.', 'woolentor-pro' ),
                        'type'    => 'imageupload',
                        'options' => [
                            'button_label'        => esc_html__( 'Upload', 'woolentor-pro' ),   
                            'button_remove_label' => esc_html__( 'Remove', 'woolentor-pro' ),   
                        ],
                        'class' => 'woolentor-action-field-left',
                        'condition'   => [ 'key'=>'button_icon_type|enable_on_shop_archive', 'operator'=>'==|==', 'value'=>'customimage|on' ],
                    ],
                    [
                        'id'    => 'button_icon_position',
                        'name'   => esc_html__( 'Button icon Position', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Choose an icon type for the quickview button from here.', 'woolentor-pro' ),
                        'type'    => 'select',
                        'default' => 'before_text',
                        'options' => [
                            'before_text' => esc_html__( 'Before Text', 'woolentor-pro' ),
                            'after_text'  => esc_html__( 'After Text', 'woolentor-pro' ),
                        ],
                        'class'       => 'woolentor-action-field-left',
                        'condition'   => [ 'key'=>'button_icon_type|enable_on_shop_archive', 'operator'=>'!=|==', 'value'=>'none|on' ],
                    ],

                    [
                        'id'      => 'modal_box_heading',
                        'heading'  => esc_html__( 'Checkout Settings', 'woolentor-pro' ),
                        'type'      => 'title'
                    ],

                    [
                        'id'    => 'checkout_mode',
                        'name'   => esc_html__( 'Checkout Mode', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Choose a checkout mode from here.', 'woolentor-pro' ),
                        'type'    => 'select',
                        'default' => 'popup',
                        'options' => [
                            'popup'	   => esc_html__( 'Popup Checkout', 'woolentor-pro' ),
                            'redirect' => esc_html__( 'Redirect To Checkout', 'woolentor-pro' ),
                        ],
                        'class' => 'woolentor-action-field-left',
                    ],

                ]
            ]
        ];

        return $fields;

    }

}