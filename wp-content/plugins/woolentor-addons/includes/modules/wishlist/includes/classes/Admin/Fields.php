<?php
namespace Woolentor\Modules\WishSuite\Admin;
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
        
        array_splice( $fields['woolentor_others_tabs'], 7, 0, $this->sitting_fields() );
        return $fields;
    }

    public function sitting_fields(){
        $fields = [
            array(
                'id'     => 'wishlist',
                'name'    => esc_html__( 'Wishlist', 'woolentor' ),
                'type'     => 'groupsetting',
                'default' => 'off',
                'label_on' => __( 'ON', 'woolentor' ),
                'label_off' => __( 'OFF', 'woolentor' ),
                'documentation' => esc_url('https://woolentor.com/doc/wishlist-for-woocommerce/'),
                'setting_tabs'=>[
                    [
                        'id'     => 'general',
                        'name'    => esc_html__( 'General Settings', 'woolentor' ),
                        'setting_group' => 'wishsuite_general_tabs',
                        'fields' => [
                            array(
                                'id'      => 'enable_login_limit',
                                'name'     => __( 'Limit Wishlist Use', 'woolentor' ),
                                'type'      => 'checkbox',
                                'default'   => 'off',
                                'desc'      => esc_html__( 'Enable this option to allow only the logged-in users to use the Wishlist feature.', 'woolentor' ),
                            ),
            
                            array(
                                'id'      => 'logout_button',
                                'name'     => __( 'Wishlist Icon Tooltip Text', 'woolentor' ),
                                'desc'      => __( 'Enter a text for the tooltip that will be shown when someone hover over the Wishlist icon.', 'woolentor' ),
                                'type'      => 'text',
                                'default'   => __( 'Please login', 'woolentor' ),
                                'condition' => array( 'key'=>'enable_login_limit', 'operator'=>'==', 'value'=>'on' )
                            ),
                        ]
                    ],

                    [
                        'id'     => 'button',
                        'name'    => esc_html__( 'Button Settings', 'woolentor' ),
                        'setting_group' => 'wishsuite_settings_tabs',
                        'fields' => [
                            array(
                                'id'  => 'btn_show_shoppage',
                                'name'  => __( 'Show button in product list', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                            ),
            
                            array(
                                'id'  => 'btn_show_productpage',
                                'name'  => __( 'Show button in single product page', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'on',
                            ),
            
                            array(
                                'id'    => 'shop_btn_position',
                                'name'   => __( 'Shop page button position', 'woolentor' ),
                                'desc'    => __( 'You can manage wishlist button position in product list page.', 'woolentor' ),
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
                                'heading' => wp_kses_post('<code>[wishsuite_button]</code> Use this shortcode into your theme/child theme to place the wishlist button.'),
                                'class' => 'element_section_title_area message-info',
                                'condition' => array( 
                                    'key'=>'shop_btn_position', 
                                    'operator'=>'==', 
                                    'value'=>'use_shortcode'
                                ),
                            ),

                            array(
                                'id'    => 'shop_custom_hook_message',
                                'heading'=> esc_html__( 'Some themes remove the above positions. In that case, custom position is useful. Here you can place the custom/default hook name & priority to inject & adjust the wishlist button for the product loop.', 'woolentor' ),
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
                                'desc'    => __( 'You can manage wishlist button position in single product page.', 'woolentor' ),
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
                                'heading'=> wp_kses_post('<code>[wishsuite_button]</code> Use this shortcode into your theme/child theme to place the wishlist button.'),
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
                                'heading'=> esc_html__( 'Some themes remove the above positions. In that case, custom position is useful. Here you can place the custom/default hook name & priority to inject & adjust the wishlist button for the single product page.', 'woolentor' ),
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
                                'id'        => 'button_text',
                                'name'       => __( 'Button Text', 'woolentor' ),
                                'desc'        => __( 'Enter your wishlist button text.', 'woolentor' ),
                                'type'        => 'text',
                                'default'     => __( 'Wishlist', 'woolentor' ),
                                'placeholder' => __( 'Wishlist', 'woolentor' ),
                            ),
            
                            array(
                                'id'        => 'added_button_text',
                                'name'       => __( 'Product added text', 'woolentor' ),
                                'desc'        => __( 'Enter the product added text.', 'woolentor' ),
                                'type'        => 'text',
                                'default'     => __( 'Product Added', 'woolentor' ),
                                'placeholder' => __( 'Product Added', 'woolentor' ),
                            ),
            
                            array(
                                'id'        => 'exist_button_text',
                                'name'       => __( 'Already exists in the wishlist text', 'woolentor' ),
                                'desc'        => wp_kses_post( 'Enter the message for "<strong>already exists in the wishlist</strong>" text.' ),
                                'type'        => 'text',
                                'default'     => __( 'Product already added', 'woolentor' ),
                                'placeholder' => __( 'Product already added', 'woolentor' ),
                            ),

                        ]
                        
                    ],

                    [
                        'id'     => 'table',
                        'name'    => esc_html__( 'Table Settings', 'woolentor' ),
                        'setting_group' => 'wishsuite_table_settings_tabs',
                        'fields' => [
                            array(
                                'id'    => 'wishlist_page',
                                'name'   => __( 'Wishlist page', 'woolentor' ),
                                'type'    => 'select',
                                'default' => '0',
                                'options' => wishsuite_get_post_list(),
                                'desc'    => wp_kses_post('Select a wishlist page for wishlist table. It should contain the shortcode <code>[wishsuite_table]</code>'),
                            ),
            
                            array(
                                'id'    => 'wishlist_product_per_page',
                                'name'   => __( 'Products per page', 'woolentor' ),
                                'type'    => 'number',
                                'default' => '20',
                                'desc'    => __('You can choose the number of wishlist products to display per page. The default value is 20 products.', 'woolentor'),
                            ),
            
                            array(
                                'id'  => 'after_added_to_cart',
                                'name'  => __( 'Remove from the "Wishlist" after adding to the cart.', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'on',
                            ),

                            array(
                                'id' => 'show_fields',
                                'name' => __('Show fields in table', 'woolentor'),
                                'desc' => __('Choose which fields should be presented on the product compare page with table.', 'woolentor'),
                                'type' => 'shortable',
                                'options' => wishsuite_get_available_attributes(),
                                'default' => [
                                    'remove'        => esc_html__( 'Remove', 'woolentor' ),
                                    'image'         => esc_html__( 'Image', 'woolentor' ),
                                    'title'         => esc_html__( 'Title', 'woolentor' ),
                                    'price'         => esc_html__( 'Price', 'woolentor' ),
                                    'quantity'      => esc_html__( 'Quantity', 'woolentor' ),
                                    'add_to_cart'   => esc_html__( 'Add To Cart', 'woolentor' ),
                                ],
                            ),

                            array(
                                'id'    => 'table_heading',
                                'name'   => __( 'Table heading text', 'woolentor' ),
                                'desc'    => __( 'You can change table heading text from here.', 'woolentor' ),
                                'type'    => 'multitext',
                                'options' => wishsuite_table_heading()
                            ),
            
                            array(
                                'id' => 'empty_table_text',
                                'name' => __('Empty table text', 'woolentor'),
                                'desc' => __('Text will be displayed if the user doesn\'t add any product to  the wishlist.', 'woolentor'),
                                'type' => 'textarea'
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
                            ),
            
                            array(
                                'id'    => 'social_share_button_area_title',
                                'heading'=> esc_html__( 'Social share button', 'woolentor' ),
                                'type'    => 'title',
                                'class' => 'element_section_title_area',
                            ),
            
                            array(
                                'id'  => 'enable_social_share',
                                'name'  => esc_html__( 'Enable social share button', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'on',
                                'desc'    => esc_html__( 'Enable social share button.', 'woolentor' ),
                            ),
            
                            array(
                                'id'        => 'social_share_button_title',
                                'name'       => esc_html__( 'Social share button title', 'woolentor' ),
                                'desc'        => esc_html__( 'Enter your social share button title.', 'woolentor' ),
                                'type'        => 'text',
                                'default'     => esc_html__( 'Share:', 'woolentor' ),
                                'placeholder' => esc_html__( 'Share', 'woolentor' ),
                                'condition' => [
                                    'key' => 'enable_social_share',
                                    'operator' => '==',
                                    'value' => 'on'
                                ]
                            ),
            
                            array(
                                'id' => 'social_share_buttons',
                                'name' => esc_html__('Enable share buttons', 'woolentor'),
                                'desc'    => esc_html__( 'You can manage your social share buttons.', 'woolentor' ),
                                'type' => 'shortable',
                                'options' => [
                                    'facebook'      => esc_html__( 'Facebook', 'woolentor' ),
                                    'twitter'       => esc_html__( 'Twitter', 'woolentor' ),
                                    'pinterest'     => esc_html__( 'Pinterest', 'woolentor' ),
                                    'linkedin'      => esc_html__( 'Linkedin', 'woolentor' ),
                                    'email'         => esc_html__( 'Email', 'woolentor' ),
                                    'reddit'        => esc_html__( 'Reddit', 'woolentor' ),
                                    'telegram'      => esc_html__( 'Telegram', 'woolentor' ),
                                    'odnoklassniki' => esc_html__( 'Odnoklassniki', 'woolentor' ),
                                    'whatsapp'      => esc_html__( 'WhatsApp', 'woolentor' ),
                                    'vk'            => esc_html__( 'VK', 'woolentor' ),
                                ],
                                'default' => [
                                    'facebook'   => esc_html__( 'Facebook', 'woolentor' ),
                                    'twitter'    => esc_html__( 'Twitter', 'woolentor' ),
                                    'pinterest'  => esc_html__( 'Pinterest', 'woolentor' ),
                                    'linkedin'   => esc_html__( 'Linkedin', 'woolentor' ),
                                    'telegram'   => esc_html__( 'Telegram', 'woolentor' ),
                                ],
                                'condition' => [
                                    'key' => 'enable_social_share',
                                    'operator' => '==',
                                    'value' => 'on'
                                ]
                            ),

                        ]
                    ],

                    [
                        'id'     => 'style',
                        'name'    => esc_html__( 'Style Settings', 'woolentor' ),
                        'setting_group' => 'wishsuite_style_settings_tabs',
                        'fields' => [
                            array(
                                'id'    => 'button_style',
                                'name'   => __( 'Button style', 'woolentor' ),
                                'desc'    => __( 'Choose a style for the wishlist button from here.', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'default',
                                'options' => [
                                    'default'     => esc_html__( 'Default style', 'woolentor' ),
                                    'themestyle'  => esc_html__( 'Theme style', 'woolentor' ),
                                    'custom'      => esc_html__( 'Custom style', 'woolentor' ),
                                ]
                            ),
            
                            array(
                                'id'    => 'button_icon_type',
                                'name'   => __( 'Button icon type', 'woolentor' ),
                                'desc'    => __( 'Choose an icon for the wishlist button from here.', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'default',
                                'options' => [
                                    'none'     => esc_html__( 'None', 'woolentor' ),
                                    'default'  => esc_html__( 'Default icon', 'woolentor' ),
                                    'custom'   => esc_html__( 'Custom icon', 'woolentor' ),
                                ]
                            ),

                            array(
                                'id'    => 'button_custom_icon',
                                'name'   => __( 'Button custom icon', 'woolentor' ),
                                'type'    => 'imageupload',
                                'options' => [
                                    'button_label' => esc_html__( 'Upload', 'woolentor' ),   
                                    'button_remove_label' => esc_html__( 'Remove', 'woolentor' ),   
                                ],
                                'condition' => [
                                    'key' => 'button_icon_type',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'    => 'addedbutton_icon_type',
                                'name'   => __( 'Added Button icon type', 'woolentor' ),
                                'desc'    => __( 'Choose an icon for the wishlist button from here.', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'default',
                                'options' => [
                                    'none'     => esc_html__( 'None', 'woolentor' ),
                                    'default'  => esc_html__( 'Default icon', 'woolentor' ),
                                    'custom'   => esc_html__( 'Custom icon', 'woolentor' ),
                                ]
                            ),
            
                            array(
                                'id'    => 'addedbutton_custom_icon',
                                'name'   => __( 'Added Button custom icon', 'woolentor' ),
                                'type'    => 'imageupload',
                                'options' => [
                                    'button_label' => esc_html__( 'Upload', 'woolentor' ),   
                                    'button_remove_label' => esc_html__( 'Remove', 'woolentor' ),   
                                ],
                                'condition' => [
                                    'key' => 'addedbutton_icon_type',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'    => 'table_style',
                                'name'   => __( 'Table style', 'woolentor' ),
                                'desc'    => __( 'Choose a style for the wishlist table here.', 'woolentor' ),
                                'type'    => 'select',
                                'default' => 'default',
                                'options' => [
                                    'default' => esc_html__( 'Default style', 'woolentor' ),
                                    'custom'  => esc_html__( 'Custom style', 'woolentor' ),
                                ]
                            ),

                            array(
                                'id'    => 'button_custom_style_title',
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
                                'id'    => 'button_color',
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
                                'id'    => 'button_custom_margin',
                                'name'  => __( 'Margin', 'woolentor' ),
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
                                'id'    => 'table_custom_style_title',
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
                                'id'  => 'table_heading_color',
                                'name' => esc_html__( 'Heading Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the heading color of the wishlist table.' ),
                                'type'  => 'color',
                                'class' => 'table_custom_style',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_heading_bg_color',
                                'name' => esc_html__( 'Heading Background Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the heading background color of the wishlist table.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
                            array(
                                'id'  => 'table_heading_border_color',
                                'name' => esc_html__( 'Heading Border Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the heading border color of the wishlist table.', ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_border_color',
                                'name' => esc_html__( 'Border Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the border color of the wishlist table.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'    => 'table_custom_style_add_to_cart',
                                'heading'=> __( 'Add To Cart Button style', 'woolentor' ),
                                'type'    => 'title',
                                'class' => 'element_section_title_area',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
            
                            array(
                                'id'  => 'table_cart_button_color',
                                'name' => esc_html__( 'Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the add to cart button color of the wishlist table.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
                            array(
                                'id'  => 'table_cart_button_bg_color',
                                'name' => esc_html__( 'Background Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the add to cart button background color of the wishlist table.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
                            array(
                                'id'  => 'table_cart_button_hover_color',
                                'name' => esc_html__( 'Hover Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the add to cart button hover color of the wishlist table.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),
                            array(
                                'id'  => 'table_cart_button_hover_bg_color',
                                'name' => esc_html__( 'Hover Background Color', 'woolentor' ),
                                'desc'  => wp_kses_post( 'Set the add to cart button hover background color of the wishlist table.' ),
                                'type'  => 'color',
                                'condition' => [
                                    'key' => 'table_style',
                                    'operator' => '==',
                                    'value' => 'custom'
                                ]
                            ),


                        ]
                    ]
                ]
            )

        ];

        return $fields;
    }


}