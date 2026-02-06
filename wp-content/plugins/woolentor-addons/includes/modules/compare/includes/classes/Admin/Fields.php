<?php
namespace Woolentor\Modules\Compare\Admin;
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
        
        array_splice( $fields['woolentor_others_tabs'], 8, 0, $this->sitting_fields() );
        return $fields;
    }

    public function sitting_fields(){
        $fields = [
            array(
                'id'     => 'compare',
                'name'    => esc_html__( 'Compare', 'woolentor' ),
                'type'     => 'groupsetting',
                'default'  => 'off',
                'label_on' => __( 'ON', 'woolentor' ),
                'label_off' => __( 'OFF', 'woolentor' ),
                'documentation' => esc_url('https://woolentor.com/doc/woocommerce-product-compare/'),
                'active_tab'=> 'button',
                'setting_tabs'=>[

                    [
                        'id'     => 'button',
                        'name'    => esc_html__( 'Button Settings', 'woolentor' ),
                        'setting_group' => 'ever_compare_settings_tabs',
                        'fields' => [
                            array(
                                'id'  => 'btn_show_shoppage',
                                'name'  => __( 'Show button in product list page', 'woolentor' ),
                                'desc'  => __( 'Show compare button in product list page.', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                            ),

                            array(
                                'id'  => 'btn_show_productpage',
                                'name'  => __( 'Show button in single product page', 'woolentor' ),
                                'desc'  => __( 'Show compare button in single product page.', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'on',
                            ),

                            array(
                                'id'    => 'shop_btn_position',
                                'name'   => __( 'Shop page button position', 'woolentor' ),
                                'desc'    => __( 'You can manage compare button position in product list page.', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'after_cart_btn',
                                'options' => [
                                    'before_cart_btn' => __( 'Before Add To Cart', 'woolentor' ),
                                    'after_cart_btn'  => __( 'After Add To Cart', 'woolentor' ),
                                    'top_thumbnail'   => __( 'Top On Image', 'woolentor' ),
                                    'use_shortcode'   => __( 'Use Shortcode', 'woolentor' ),
                                    'custom_position' => __( 'Custom Position', 'woolentor' ),
                                ],
                            ),

                            array(
                                'id' => 'shop_use_shortcode_message',
                                'type'=>'title',
                                'heading' => wp_kses_post('<code>[evercompare_button]</code> Use this shortcode into your theme/child theme to place the compare button.'),
                                'class' => 'element_section_title_area message-info',
                                'condition' => array( 
                                    'key'=>'shop_btn_position', 
                                    'operator'=>'==', 
                                    'value'=>'use_shortcode'
                                ),
                            ),

                            array(
                                'id'    => 'shop_custom_hook_message',
                                'heading'=> esc_html__( 'Some themes remove the above positions. In that case, custom position is useful. Here you can place the custom/default hook name & priority to inject & adjust the compare button for the product loop.', 'woolentor' ),
                                'type'    => 'title',
                                'class' => 'element_section_title_area message-info',
                                'condition' => array( 
                                    'key'=>'shop_btn_position', 
                                    'operator'=>'==', 
                                    'value'=>'custom_position'
                                ),
                            ),
            
                            array(
                                'id'        => 'shop_custom_hook_name',
                                'name'       => esc_html__( 'Hook name', 'woolentor' ),
                                'desc'        => esc_html__( 'e.g: woocommerce_after_shop_loop_item_title', 'woolentor' ),
                                'type'        => 'text',
                                'condition' => array( 
                                    'key'=>'shop_btn_position', 
                                    'operator'=>'==', 
                                    'value'=>'custom_position'
                                ),
                            ),
            
                            array(
                                'id'        => 'shop_custom_hook_priority',
                                'name'       => esc_html__( 'Hook priority', 'woolentor' ),
                                'desc'        => esc_html__( 'Default: 10', 'woolentor' ),
                                'type'        => 'number',
                                'default'     => '10',
                                'condition' => array( 
                                    'key'=>'shop_btn_position', 
                                    'operator'=>'==', 
                                    'value'=>'custom_position'
                                ),
                            ),

                            array(
                                'id'    => 'product_btn_position',
                                'name'   => __( 'Product page button position', 'woolentor' ),
                                'desc'    => __( 'You can manage compare button position in single product page.', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'after_cart_btn',
                                'options' => [
                                    'before_cart_btn' => __( 'Before Add To Cart', 'woolentor' ),
                                    'after_cart_btn'  => __( 'After Add To Cart', 'woolentor' ),
                                    'after_thumbnail' => __( 'After Image', 'woolentor' ),
                                    'after_summary'   => __( 'After Summary', 'woolentor' ),
                                    'use_shortcode'   => __( 'Use Shortcode', 'woolentor' ),
                                    'custom_position' => __( 'Custom Position', 'woolentor' ),
                                ],
                            ),
            
                            array(
                                'id'    => 'product_use_shortcode_message',
                                'heading'=> wp_kses_post('<code>[evercompare_button]</code> Use this shortcode into your theme/child theme to place the compare button.'),
                                'type'    => 'title',
                                'class' => 'element_section_title_area message-info',
                                'condition' => array( 
                                    'key'=>'product_btn_position', 
                                    'operator'=>'==', 
                                    'value'=>'use_shortcode'
                                ),
                            ),
            
                            array(
                                'id'    => 'product_custom_hook_message',
                                'heading'=> esc_html__( 'Some themes remove the above positions. In that case, custom position is useful. Here you can place the custom/default hook name & priority to inject & adjust the compare button for the single product page.', 'woolentor' ),
                                'type'    => 'title',
                                'class' => 'element_section_title_area message-info',
                                'condition' => array( 
                                    'key'=>'product_btn_position', 
                                    'operator'=>'==', 
                                    'value'=>'custom_position'
                                ),
                            ),
            
                            array(
                                'id'        => 'product_custom_hook_name',
                                'name'      => __( 'Hook name', 'woolentor' ),
                                'desc'      => __( 'e.g: woocommerce_after_single_product_summary', 'woolentor' ),
                                'type'      => 'text',
                                'condition' => array( 
                                    'key'=>'product_btn_position', 
                                    'operator'=>'==', 
                                    'value'=>'custom_position'
                                ),
                            ),
            
                            array(
                                'id'        => 'product_custom_hook_priority',
                                'name'      => __( 'Hook priority', 'woolentor' ),
                                'desc'        => __( 'Default: 10', 'woolentor' ),
                                'default'     => '10',
                                'type'        => 'number',
                                'condition' => array( 
                                    'key'=>'product_btn_position', 
                                    'operator'=>'==', 
                                    'value'=>'custom_position'
                                ),
                            ),

                            array(
                                'id'  => 'open_popup',
                                'name'  => __( 'Open popup', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'on',
                                'desc'    => __( 'You can manage the popup window from here.', 'woolentor' ),
                            ),

                            array(
                                'id'        => 'button_text',
                                'name'       => __( 'Button text', 'woolentor' ),
                                'desc'        => __( 'Enter your compare button text.', 'woolentor' ),
                                'type'        => 'text',
                                'default'     => __( 'Compare', 'woolentor' ),
                                'placeholder' => __( 'Compare', 'woolentor' ),
                            ),
            
                            array(
                                'id'        => 'added_button_text',
                                'name'       => __( 'Added button text', 'woolentor' ),
                                'desc'        => __( 'Enter your compare added button text.', 'woolentor' ),
                                'type'        => 'text',
                                'default'     => __( 'Added', 'woolentor' ),
                                'placeholder' => __( 'Added', 'woolentor' ),
                            ),
            
                            array(
                                'id'    => 'button_icon_type',
                                'name'  => esc_html__( 'Button icon type', 'woolentor' ),
                                'desc'  => esc_html__( 'Choose an icon type for the compare button from here.', 'woolentor' ),
                                'type'  => 'select',
                                'default' => 'default',
                                'options' => [
                                    'none'     => esc_html__( 'None', 'woolentor' ),
                                    'default'  => esc_html__( 'Default', 'woolentor' ),
                                    'custom'   => esc_html__( 'Custom', 'woolentor' ),
                                ]
                            ),

                            array(
                                'id'    => 'button_custom_icon',
                                'name'   => esc_html__( 'Button custom icon', 'woolentor' ),
                                'type'    => 'imageupload',
                                'options' => [
                                    'button_label'        => esc_html__( 'Upload', 'woolentor' ),   
                                    'button_remove_label' => esc_html__( 'Remove', 'woolentor' ),
                                ],
                                'desc'    => esc_html__( 'Upload you custom icon from here.', 'woolentor' ),
                                'condition' => [
                                    'key'=>'button_icon_type',
                                    'operator'=>'==',
                                    'value'=>'custom'
                                ]
                            ),
            
                            array(
                                'id'    => 'added_button_icon_type',
                                'name'   => __( 'Added button icon type', 'woolentor' ),
                                'desc'    => __( 'Choose an icon for the compare button from here.', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'default',
                                'options' => [
                                    'none'     => esc_html__( 'None', 'woolentor' ),
                                    'default'  => esc_html__( 'Default', 'woolentor' ),
                                    'custom'   => esc_html__( 'Custom', 'woolentor' ),
                                ]
                            ),
            
                            array(
                                'id'    => 'added_button_custom_icon',
                                'name'   => __( 'Added button custom icon', 'woolentor' ),
                                'type'    => 'imageupload',
                                'options' => [
                                    'button_label'        => esc_html__( 'Upload', 'woolentor' ),   
                                    'button_remove_label' => esc_html__( 'Remove', 'woolentor' ),   
                                ],
                                'condition' => [
                                    'key'=>'added_button_icon_type',
                                    'operator'=>'==',
                                    'value'=>'custom'
                                ]
                            ),

                        ]
                        
                    ],

                    [
                        'id'     => 'table',
                        'name'    => esc_html__( 'Table Settings', 'woolentor' ),
                        'setting_group' => 'ever_compare_table_settings_tabs',
                        'fields' => [

                            array(
                                'id'    => 'compare_page',
                                'name'   => __( 'Compare page', 'woolentor' ),
                                'desc' => wp_kses_post('Select a compare page for compare table. It should contain the shortcode <code>[evercompare_table]</code>'),
                                'type'    => 'select',
                                'default' => '0',
                                'options' => ever_compare_get_post_list()
                            ),
            
                            array(
                                'id'  => 'enable_shareable_link',
                                'name'  => __( 'Enable shareable link', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'desc'    => __( 'If you enable this you can easily share your compare page link with specific products.', 'woolentor' ),
                            ),

                            array(
                                'id'    => 'linkshare_btn_pos',
                                'name'   => __( 'Share link button position', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'right',
                                'options' => [
                                    'left' => __('Left','woolentor'),
                                    'center' => __('Center','woolentor'),
                                    'right' => __('Right','woolentor')
                                ],
                                'condition' => [
                                    'key'=>'enable_shareable_link',
                                    'operator'=>'==',
                                    'value'=>'on'
                                ]
                            ),
                            array(
                                'id'        => 'shareable_link_button_text',
                                'name'       => __( 'Share link button text', 'woolentor' ),
                                'placeholder' => __( 'Copy shareable link', 'woolentor' ),
                                'type'        => 'text',
                                'condition' => [
                                    'key'=>'enable_shareable_link',
                                    'operator'=>'==',
                                    'value'=>'on'
                                ]
                            ),
            
                            array(
                                'id'        => 'shareable_link_after_button_text',
                                'name'       => __( 'Text to show after link is copied', 'woolentor' ),
                                'placeholder' => __( 'Copied', 'woolentor' ),
                                'type'        => 'text',
                                'condition' => [
                                    'key'=>'enable_shareable_link',
                                    'operator'=>'==',
                                    'value'=>'on'
                                ]
                            ),
            
                            array(
                                'id'    => 'limit',
                                'name'   => esc_html__( 'Limit', 'woolentor' ),
                                'desc'    => esc_html__( 'You can manage your maximum compare quantity from here.', 'woolentor' ),
                                'type'    => 'number',
                                'min'              => 1,
                                'max'              => 1500,
                                'step'             => 1,
                                'default'          => 10,
                                'sanitize_callback' => 'floatval',
                            ),

                            array(
                                'id' => 'show_fields',
                                'name' => __('Show fields in table', 'woolentor'),
                                'desc' => __('Choose which fields should be presented on the product compare page with table.', 'woolentor'),
                                'type' => 'shortable',
                                'options' => ever_compare_get_available_attributes(),
                                'default' => [
                                    'title'         => esc_html__( 'title', 'woolentor' ),
                                    'ratting'       => esc_html__( 'ratting', 'woolentor' ),
                                    'price'         => esc_html__( 'price', 'woolentor' ),
                                    'add_to_cart'   => esc_html__( 'add_to_cart', 'woolentor' ),
                                    'description'   => esc_html__( 'description', 'woolentor' ),
                                    'availability'  => esc_html__( 'availability', 'woolentor' ),
                                    'sku'           => esc_html__( 'sku', 'woolentor' ),
                                    'weight'        => esc_html__( 'weight', 'woolentor' ),
                                    'dimensions'    => esc_html__( 'dimensions', 'woolentor' ),
                                ],
                            ),

                            array(
                                'id'    => 'table_heading_section_title',
                                'heading'=> esc_html__( 'Custom heading', 'woolentor' ),
                                'type'    => 'title',
                            ),

                            array(
                                'id'    => 'table_heading',
                                'name'   => __( 'Fields heading text', 'woolentor' ),
                                'desc'    => __( 'You can change heading text from here.', 'woolentor' ),
                                'type'    => 'multitext',
                                'options' => ever_compare_table_heading()
                            ),

                            array(
                                'id' => 'reached_max_limit_message',
                                'name' => __('Reached maximum limit message', 'woolentor'),
                                'desc' => __('You can manage message for maximum product added in the compare table.', 'woolentor'),
                                'type' => 'textarea'
                            ),
            
                            array(
                                'id' => 'empty_table_text',
                                'name' => __('Empty compare page text', 'woolentor'),
                                'desc' => __('Text will be displayed if user don\'t add any products to compare', 'woolentor'),
                                'type' => 'textarea'
                            ),
            
                            array(
                                'id'        => 'shop_button_text',
                                'name'      => __( 'Return to shop button text', 'woolentor' ),
                                'desc'        => __( 'Enter your return to shop button text.', 'woolentor' ),
                                'type'        => 'text',
                                'default'     => __( 'Return to shop', 'woolentor' ),
                                'placeholder' => __( 'Return to shop', 'woolentor' ),
                            ),

                            array(
                                'id'    => 'image_size',
                                'name'   => esc_html__( 'Image Size', 'woolentor' ),
                                'desc'    => esc_html__( 'Enter your required image size.', 'woolentor' ),
                                'type'    => 'dimensions',
                                'options' => [
                                    'width'   => esc_html__( 'Width', 'woolentor' ),
                                    'height'  => esc_html__( 'Height', 'woolentor' ),
                                ],
                                'default' => array(
                                    'width' => 80,
                                    'height' => 80
                                ),
                                'class'       => 'woolentor-action-field-left woolentor-dimention-field-left',
                            ),

                            array(
                                'id'    => 'hard_crop',
                                'name'  => esc_html__( 'Image Hard Crop', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'on',
                            )

                        ]
                    ],

                    [
                        'id'     => 'style',
                        'name'    => esc_html__( 'Style Settings', 'woolentor' ),
                        'setting_group' => 'ever_compare_style_tabs',
                        'fields' => [

                            array(
                                'id'    => 'button_style',
                                'name'  => esc_html__( 'Button style', 'woolentor' ),
                                'desc'    => esc_html__( 'Choose a style for the compare button from here.', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'theme',
                                'options' => [
                                    'default'   => esc_html__( 'Default', 'woolentor' ),
                                    'theme'     => esc_html__( 'Theme', 'woolentor' ),
                                    'custom'    => esc_html__( 'Custom', 'woolentor' ),
                                ]
                            ),
            
                            array(
                                'id'    => 'table_style',
                                'name'  => esc_html__( 'Table style', 'woolentor' ),
                                'desc'    => esc_html__( 'Choose a table style from here.', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'default',
                                'options' => [
                                    'default'   => esc_html__( 'Default', 'woolentor' ),
                                    'custom'    => esc_html__( 'Custom', 'woolentor' ),
                                ]
                            ),

                            array(
                                'id'    => 'button_custom_style_area_title',
                                'heading'=> __( 'Button custom style', 'woolentor' ),
                                'type'    => 'title',
                                'class' => 'button_custom_style element_section_title_area',
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),

                            array(
                                'id'  => 'button_color',
                                'name' => esc_html__( 'Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the color of the button.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'button_hover_color',
                                'name' => esc_html__( 'Hover Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the hover color of the button.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'background_color',
                                'name' => esc_html__( 'Background Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the background color of the button.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'hover_background_color',
                                'name' => esc_html__( 'Hover Background Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the hover background color of the button.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'    => 'button_custom_padding',
                                'name'  => __( 'Padding', 'woolentor' ),
                                'type'  => 'dimensions',
                                'options' => [
                                    'top'   => esc_html__( 'Top', 'woolentor' ),   
                                    'right' => esc_html__( 'Right', 'woolentor' ),   
                                    'bottom'=> esc_html__( 'Bottom', 'woolentor' ),   
                                    'left'  => esc_html__( 'Left', 'woolentor' ),
                                    'unit'  => esc_html__( 'Unit', 'woolentor' ),
                                ],
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'    => 'button_custom_margin',
                                'name'  => __( 'Margin', 'woolentor' ),
                                'type'  => 'dimensions',
                                'options' => [
                                    'top'   => esc_html__( 'Top', 'woolentor' ),   
                                    'right' => esc_html__( 'Right', 'woolentor' ),   
                                    'bottom'=> esc_html__( 'Bottom', 'woolentor' ),   
                                    'left'  => esc_html__( 'Left', 'woolentor' ),
                                    'unit'  => esc_html__( 'Unit', 'woolentor' ),
                                ],
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'    => 'button_custom_border',
                                'name'  => __( 'Border width', 'woolentor' ),
                                'type'    => 'dimensions',
                                'options' => [
                                    'top'   => esc_html__( 'Top', 'woolentor' ),   
                                    'right' => esc_html__( 'Right', 'woolentor' ),   
                                    'bottom'=> esc_html__( 'Bottom', 'woolentor' ),   
                                    'left'  => esc_html__( 'Left', 'woolentor' ),
                                    'unit'  => esc_html__( 'Unit', 'woolentor' ),
                                ],
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
                            array(
                                'id'    => 'button_custom_border_color',
                                'name'  => esc_html__( 'Border Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the button color of the button.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'    => 'button_custom_border_radius',
                                'name'   => __( 'Border Radius', 'woolentor' ),
                                'type'    => 'dimensions',
                                'options' => [
                                    'top'   => esc_html__( 'Top', 'woolentor' ),   
                                    'right' => esc_html__( 'Right', 'woolentor' ),   
                                    'bottom'=> esc_html__( 'Bottom', 'woolentor' ),   
                                    'left'  => esc_html__( 'Left', 'woolentor' ),
                                    'unit'  => esc_html__( 'Unit', 'woolentor' ),
                                ],
                                'condition' => [
                                    'key' => 'button_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),

                            array(
                                'id'    => 'table_custom_style_area_title',
                                'heading'=> __( 'Table custom style', 'woolentor' ),
                                'type'    => 'title',
                                'class' => 'element_section_title_area',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),

                            array(
                                'id'  => 'table_border_color',
                                'name' => esc_html__( 'Border color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the border color of the table.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'    => 'table_column_padding',
                                'name'   => __( 'Column Padding', 'woolentor' ),
                                'type'    => 'dimensions',
                                'options' => [
                                    'top'   => esc_html__( 'Top', 'woolentor' ),   
                                    'right' => esc_html__( 'Right', 'woolentor' ),   
                                    'bottom'=> esc_html__( 'Bottom', 'woolentor' ),   
                                    'left'  => esc_html__( 'Left', 'woolentor' ),
                                    'unit'  => esc_html__( 'Unit', 'woolentor' ),
                                ],
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_event_color',
                                'name' => esc_html__( 'Column background color (Event)' ),
                                'desc'  => wp_kses_post( 'Set the background color of the table event column.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_odd_color',
                                'name' => esc_html__( 'Column background color (Odd)', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the background color of the table odd column.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_heading_event_color',
                                'name' => esc_html__( 'Heading color (Event)', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the heading color of the table event column.'),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_heading_odd_color',
                                'name' => esc_html__( 'Heading color (Odd)', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the heading color of the table odd column.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_content_event_color',
                                'name' => esc_html__( 'Content color (Event)', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the content color of the table event column.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_content_odd_color',
                                'name' => esc_html__( 'Content color (Odd)', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the content color of the table odd column.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_content_link_color',
                                'name' => esc_html__( 'Content link color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the content link color of the table.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_content_link_hover_color',
                                'name' => esc_html__( 'Content link hover color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the content link hover color of the table.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            )

                        ]
                    ]
                ]
            )

        ];

        return $fields;
    }


}