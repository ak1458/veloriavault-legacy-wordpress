<?php
namespace WoolentorPro\Modules\Badges;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Badges {
    use Singleton;

    /**
     * Currency Fields;
     */
    public function Fields(){
        $fields = [
            [
                'id'   => 'woolentor_badges_settings',
                'name'  => esc_html__( 'Product Badges', 'woolentor-pro' ),
                'type'   => 'module',
                'default'=> 'off',
                'section'  => 'woolentor_badges_settings',
                'option_id' => 'enable',
                'documentation' => esc_url('https://woolentor.com/doc/product-badges-module/'),
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
                        'id'        => 'badges_list',
                        'name'       => esc_html__( 'Badge List', 'woolentor-pro' ),
                        'type'        => 'repeater',
                        'title_field' => 'badge_title',
                        'condition'   => [ 'key'=>'enable','operator'=>'==', 'value'=>'on' ],
                        'options' => [
                            'button_label' => esc_html__( 'Add New Badge', 'woolentor-pro' ),  
                        ],
                        'fields'  => [
                            [
                                'id'        => 'badge_title',
                                'name'       => esc_html__( 'Badge Title', 'woolentor-pro' ),
                                'type'        => 'text',
                                'class'       => 'woolentor-action-field-left'
                            ],
                            [
                                'id'        => 'badge_type',
                                'name'       => esc_html__( 'Badge Type', 'woolentor-pro' ),
                                'type'        => 'select',
                                'default'     => 'text',
                                'options' => [
                                    'text' => esc_html__( 'Text', 'woolentor-pro' ),
                                    'image'=> esc_html__( 'Image', 'woolentor-pro' ),
                                ],
                                'class'       => 'woolentor-action-field-left'
                            ],
                            [
                                'id'        => 'badge_text',
                                'name'       => esc_html__( 'Badge Text', 'woolentor-pro' ),
                                'type'        => 'text',
                                'class'       => 'woolentor-action-field-left',
                                'condition' => [ 'key'=>'badge_type', 'operator'=>'==', 'value'=>'text' ],
                            ],
                            [
                                'id'  => 'badge_text_color',
                                'name' => esc_html__( 'Text Color', 'woolentor-pro' ),
                                'desc'  => esc_html__( 'Badge text color.', 'woolentor-pro' ),
                                'type'  => 'color',
                                'class' => 'woolentor-action-field-left',
                                'condition' => [ 'key'=>'badge_type', 'operator'=>'==', 'value'=>'text' ],
                            ],
                            [
                                'id'  => 'badge_bg_color',
                                'name' => esc_html__( 'Background Color', 'woolentor-pro' ),
                                'desc'  => esc_html__( 'Badge background color.', 'woolentor-pro' ),
                                'type'  => 'color',
                                'class' => 'woolentor-action-field-left',
                                'condition' => [ 'key'=>'badge_type', 'operator'=>'==', 'value'=>'text' ],
                            ],
                            [
                                'id'              => 'badge_font_size',
                                'name'             => esc_html__( 'Text Font Size (PX)', 'woolentor-pro' ),
                                'desc'              => esc_html__( 'Set the font size for badge text.', 'woolentor-pro' ),
                                'min'               => 1,
                                'max'               => 1000,
                                'default'           => '15',
                                'step'              => '1',
                                'type'              => 'number',
                                'sanitize_callback' => 'number',
                                'condition' => [ 'key'=>'badge_type', 'operator'=>'==', 'value'=>'text' ],
                                'class'       => 'woolentor-action-field-left',
                            ],
                            [
                                'id'    => 'badge_padding',
                                'name'   => esc_html__( 'Badge padding', 'woolentor-pro' ),
                                'desc'    => esc_html__( 'Badge area padding.', 'woolentor-pro' ),
                                'type'    => 'dimensions',
                                'options' => [
                                    'top'   => esc_html__( 'Top', 'woolentor-pro' ),
                                    'right' => esc_html__( 'Right', 'woolentor-pro' ),
                                    'bottom'=> esc_html__( 'Bottom', 'woolentor-pro' ),
                                    'left'  => esc_html__( 'Left', 'woolentor-pro' ),
                                    'unit'  => esc_html__( 'Unit', 'woolentor-pro' ),
                                ],
                                'class' => 'woolentor-action-field-left woolentor-dimention-field-left',
                                'condition' => [ 'key'=>'badge_type', 'operator'=>'==', 'value'=>'text' ],
                            ],
                            [
                                'id'    => 'badge_border_radius',
                                'name'   => esc_html__( 'Badge border radius', 'woolentor-pro' ),
                                'desc'    => esc_html__( 'Badge area button border radius.', 'woolentor-pro' ),
                                'type'    => 'dimensions',
                                'options' => [
                                    'top'   => esc_html__( 'Top', 'woolentor-pro' ),
                                    'right' => esc_html__( 'Right', 'woolentor-pro' ),
                                    'bottom'=> esc_html__( 'Bottom', 'woolentor-pro' ),
                                    'left'  => esc_html__( 'Left', 'woolentor-pro' ),
                                    'unit'  => esc_html__( 'Unit', 'woolentor-pro' ),
                                ],
                                'class' => 'woolentor-action-field-left woolentor-dimention-field-left',
                                'condition' => [ 'key'=>'badge_type', 'operator'=>'==', 'value'=>'text' ],
                            ],
                            [
                                'id'    => 'badge_image',
                                'name'   => esc_html__( 'Badge Image', 'woolentor-pro' ),
                                'desc'    => esc_html__( 'Upload your custom badge from here.', 'woolentor-pro' ),
                                'type'    => 'imageupload',
                                'options' => [
                                    'button_label'        => esc_html__( 'Upload', 'woolentor-pro' ),   
                                    'button_remove_label' => esc_html__( 'Remove', 'woolentor-pro' ),   
                                ],
                                'class' => 'woolentor-action-field-left',
                                'condition'   => [ 'key'=>'badge_type', 'operator'=>'==', 'value'=>'image' ],
                            ],

                            [
                                'id'    => 'badge_image_size',
                                'name'   => esc_html__( 'Image Size', 'woolentor-pro' ),
                                'desc'    => esc_html__( 'Set the image size for badge image.', 'woolentor-pro' ),
                                'type'    => 'dimensions',
                                'options' => [
                                    'width'   => esc_html__( 'Width', 'woolentor-pro' ),
                                    'height'  => esc_html__( 'Height', 'woolentor-pro' ),
                                    'unit'    => esc_html__( 'Unit', 'woolentor-pro' ),
                                ],
                                'condition'   => [ 'key'=>'badge_type', 'operator'=>'==', 'value'=>'image' ],
                            ],

                            [
                                'id'      => 'badge_setting_heading',
                                'heading'  => esc_html__( 'Badge Settings', 'woolentor-pro' ),
                                'type'      => 'title'
                            ],

                            [
                                'id'    => 'badge_position',
                                'name'   => esc_html__( 'Badge Position', 'woolentor-pro' ),
                                'desc'    => esc_html__( 'Choose a badge position from here.', 'woolentor-pro' ),
                                'type'    => 'select',
                                'default' => 'top_left',
                                'options' => [
                                    'top_left'   => esc_html__( 'Top Left', 'woolentor-pro' ),
                                    'top_right'  => esc_html__( 'Top Right', 'woolentor-pro' ),
                                    'bottom_left'=> esc_html__( 'Bottom Left', 'woolentor-pro' ),
                                    'bottom_right'=> esc_html__( 'Bottom Right', 'woolentor-pro' ),
                                    'custom_position'=> esc_html__( 'Custom Position', 'woolentor-pro' ),
                                ],
                                'class'       => 'woolentor-action-field-left',
                            ],
                            [
                                'id'    => 'badge_custom_position',
                                'name'   => esc_html__( 'Custom Position', 'woolentor-pro' ),
                                'desc'    => esc_html__( 'Badge Custom Position.', 'woolentor-pro' ),
                                'type'    => 'dimensions',
                                'options' => [
                                    'top'   => esc_html__( 'Top', 'woolentor-pro' ),
                                    'right' => esc_html__( 'Right', 'woolentor-pro' ),
                                    'bottom'=> esc_html__( 'Bottom', 'woolentor-pro' ),
                                    'left'  => esc_html__( 'Left', 'woolentor-pro' ),
                                    'unit'  => esc_html__( 'Unit', 'woolentor-pro' ),
                                ],
                                'class' => 'woolentor-action-field-left woolentor-dimention-field-left',
                                'condition' => [ 'key'=>'badge_position', 'operator'=>'==', 'value'=>'custom_position' ],
                            ],
                            [
                                'id'    => 'badge_condition',
                                'name'   => esc_html__( 'Badge Condition', 'woolentor-pro' ),
                                'type'    => 'select',
                                'default' => 'none',
                                'options' => [
                                    'none' => esc_html__( 'Select Option', 'woolentor-pro' ),
                                    'all_product' => esc_html__( 'All Products', 'woolentor-pro' ),
                                    'selected_product'=> esc_html__( 'Selected Product', 'woolentor-pro' ),
                                    'category'=> esc_html__( 'Category', 'woolentor-pro' ),
                                    'on_sale'=> esc_html__( 'On Sale Only', 'woolentor-pro' ),
                                    'outof_stock'=> esc_html__( 'Out Of Stock', 'woolentor-pro' ),
                                ],
                                'class'       => 'woolentor-action-field-left',
                            ],

                            [
                                'id'        => 'categories',
                                'name'       => esc_html__( 'Select Categories', 'woolentor-pro' ),
                                'desc'        => esc_html__( 'Select the categories in which products the badge will be show.', 'woolentor-pro' ),
                                'type'        => 'multiselect',
                                'convertnumber' => true,
                                'options'     => woolentor_taxonomy_list('product_cat','term_id'),
                                'condition'   => [ 'key'=>'badge_condition', 'operator'=>'==', 'value'=>'category' ],
                                'class'       => 'woolentor-action-field-left'
                            ],

                            [
                                'id'        => 'products',
                                'name'       => esc_html__( 'Select Products', 'woolentor-pro' ),
                                'desc'        => esc_html__( 'Select individual products in which the badge will be show.', 'woolentor-pro' ),
                                'type'        => 'multiselect',
                                'convertnumber' => true,
                                'options'     => woolentor_post_name( 'product' ),
                                'condition'   => [ 'key'=>'badge_condition', 'operator'=>'==', 'value'=>'selected_product' ],
                                'class'       => 'woolentor-action-field-left'
                            ],

                            [
                                'id'        => 'exclude_products',
                                'name'       => esc_html__( 'Exclude Products', 'woolentor-pro' ),
                                'type'        => 'multiselect',
                                'convertnumber' => true,
                                'options'     => woolentor_post_name( 'product' ),
                                'condition'   => [ 'key'=>'badge_condition', 'operator'=>'!=', 'value'=>'none' ],
                                'class'       => 'woolentor-action-field-left'
                            ],


                        ],
                    ],

                ]
            ]
        ];

        return $fields;

    }

}