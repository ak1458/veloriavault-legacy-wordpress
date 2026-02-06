<?php
namespace WoolentorPro\Modules\Smart_Cross_Sell_Popup\Admin;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Fields {
    use Singleton;

    /**
     * Settings Fields Fields;
     */
    public function sitting_fields(){
        
        $fields = [
            array(
                'id'     => 'woolentor_smart_cross_sell_popup_settings',
                'name'    => esc_html__( 'Smart Cross-sell Popup', 'woolentor' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_smart_cross_sell_popup_settings',
                'option_id'=> 'enable',
                'require_settings' => true,
                'documentation' => esc_url('https://woolentor.com/doc/smart-cross-sell-popup-module-in-woocommerce/'),
                'setting_fields' => array(
                    
                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor' ),
                        'desc'  => esc_html__( 'Enable/Disable Smart Cross-sell Popup module.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    // General Settings
                    array(
                        'id'      => 'general_settings_heading',
                        'type'      => 'title',
                        'heading'  => esc_html__( 'General Settings', 'woolentor' ),
                        'size'      => 'woolentor_style_seperator',
                    ),
    
                    array(
                        'id'        => 'popup_title',
                        'name'       => esc_html__( 'Popup Title', 'woolentor' ),
                        'desc'        => esc_html__( 'Enter the title for the popup.', 'woolentor' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'You May Also Like', 'woolentor' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
    
                    array(
                        'id'    => 'product_limit',
                        'name'   => esc_html__( 'Product Limit', 'woolentor' ),
                        'desc'    => esc_html__( 'Set maximum number of products to display.', 'woolentor' ),
                        'type'    => 'number',
                        'default' => '4',
                        'min'     => 1,
                        'max'     => 4,
                        'class'   => 'woolentor-action-field-left',
                    ),
    
                    array(
                        'id'    => 'trigger_type',
                        'name'   => esc_html__( 'Trigger Type', 'woolentor' ),
                        'desc'    => esc_html__( 'Select when to show the popup.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => 'add_to_cart',
                        'options' => array(
                            'add_to_cart' => esc_html__('After Add to Cart', 'woolentor'),
                            'exit_intent' => esc_html__('Exit Intent', 'woolentor'),
                            'time_delay'  => esc_html__('Time Delay', 'woolentor'),
                            'scroll'      => esc_html__('Scroll Position', 'woolentor'),
                            'cart_total'  => esc_html__('Cart Total', 'woolentor'),
                            'checkout'    => esc_html__('Before Checkout', 'woolentor')
                        ),
                        'class'   => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'      => 'time_delay',
                        'name'     => esc_html__( 'Time Delay (seconds)', 'woolentor' ),
                        'type'      => 'number',
                        'default'   => '30',
                        'condition' => array('key'=>'trigger_type', 'operator'=>'==', 'value'=>'time_delay'),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    
                    array(
                        'id'      => 'scroll_percent',
                        'name'     => esc_html__( 'Scroll Percentage', 'woolentor' ),
                        'type'      => 'number',
                        'default'   => '50',
                        'condition' => array('key'=>'trigger_type', 'operator'=>'==', 'value'=>'scroll'),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    
                    array(
                        'id'      => 'min_cart_amount',
                        'name'     => esc_html__( 'Minimum Cart Amount', 'woolentor' ),
                        'type'      => 'number',
                        'default'   => '100',
                        'condition' => array('key'=>'trigger_type', 'operator'=>'==', 'value'=>'cart_total'),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    
                    array(
                        'id'      => 'product_settings_heading',
                        'type'      => 'title',
                        'heading'  => esc_html__( 'Product Settings', 'woolentor' ),
                        'size'      => 'woolentor_style_seperator',
                        'class'     => 'woolentor-pro-field'
                    ),
                    
                    array(
                        'id'    => 'product_source',
                        'name'   => esc_html__( 'Product Source', 'woolentor' ),
                        'type'    => 'select',
                        'default' => 'cross_sells',
                        'options' => array(
                            'cross_sells' => esc_html__('Cross-sells', 'woolentor'),
                            'up_sells'    => esc_html__('Up-sells', 'woolentor'),
                            'related'     => esc_html__('Related Products', 'woolentor'),
                            'custom'      => esc_html__('Custom Products', 'woolentor'),
                            'category'    => esc_html__('Category Products', 'woolentor')
                        ),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    
                    array(
                        'id'        => 'custom_products',
                        'name'       => esc_html__( 'Select Products', 'woolentor' ),
                        'type'        => 'multiselect',
                        'convertnumber' => true,
                        'options'     => woolentor_post_name('product'),
                        'condition'   => array('key'=>'product_source', 'operator'=>'==', 'value'=>'custom'),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    
                    array(
                        'id'        => 'product_categories',
                        'name'       => esc_html__( 'Select Categories', 'woolentor' ),
                        'type'        => 'multiselect',
                        'convertnumber' => true,
                        'options'     => woolentor_taxonomy_list('product_cat','term_id'),
                        'condition'   => array('key'=>'product_source', 'operator'=>'==', 'value'=>'category'),
                        'class'       => 'woolentor-action-field-left',
                    ),
    
                    // Style Settings
                    array(
                        'id'      => 'style_settings_heading',
                        'type'      => 'title',
                        'heading'  => esc_html__( 'Style Settings', 'woolentor' ),
                        'size'      => 'woolentor_style_seperator',
                    ),
    
                    array(
                        'id'    => 'popup_width',
                        'name'   => esc_html__( 'Popup Width', 'woolentor' ),
                        'desc'    => esc_html__( 'Set popup width in pixel.', 'woolentor' ),
                        'type'    => 'text',
                        'default' => '700px',
                        'class'   => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'button_color',
                        'name'   => esc_html__( 'Button Color', 'woolentor' ),
                        'desc'    => esc_html__( 'Set button color.', 'woolentor' ),
                        'type'    => 'color',
                        'default' => '#ffffff',
                        'class'   => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'button_hover_color',
                        'name'   => esc_html__( 'Button Hover Color', 'woolentor' ),
                        'desc'    => esc_html__( 'Set button hover color.', 'woolentor' ),
                        'type'    => 'color',
                        'default' => '#ffffff',
                        'class'   => 'woolentor-action-field-left'
                    ),
    
                )
            )
        ];

        return $fields;

    }

}