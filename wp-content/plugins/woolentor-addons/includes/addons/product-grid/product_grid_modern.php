<?php
/**
 * Product Grid Modern Style Widget
 * This file follows the WooLentor naming convention for auto-loading
 *
 * @package WooLentor
 */

namespace Elementor;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load base widget class
require_once __DIR__ . '/base/class.product-grid-base-widget.php';

/**
 * Product Grid Modern Widget
 * Class name follows WooLentor convention: Woolentor_{Key}_Widget
 */
class Woolentor_Product_Grid_Modern_Widget extends WooLentor_Product_Grid_Base_Widget {

    /**
     * Grid style
     */
    protected $grid_style = 'modern';

    /**
     * Grid style label
     */
    protected $grid_style_label = 'Modern Grid & List';

    /**
     * Get widget name
     */
    public function get_name() {
        return 'woolentor-product-grid-modern';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__( 'WL: Product Grid - Modern', 'woolentor' );
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'woolentor-widget-new-icon eicon-posts-grid';
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return [ 'product', 'grid', 'list', 'modern', 'woocommerce', 'shop', 'store', 'woolentor' ];
    }

    /**
     * Register style-specific controls
     */
    protected function register_style_specific_controls() {

        // Grid Style Settings
        $this->start_controls_section(
            'section_grid_settings',
            [
                'label' => esc_html__( 'Grid View Settings', 'woolentor' ),
                'condition' => [
                    'layout' => ['grid', 'grid_list_tab'],
                ],
            ]
        );

            $this->add_control(
                'show_grid_description',
                [
                    'label' => esc_html__( 'Show description', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Show', 'woolentor' ),
                    'label_off' => esc_html__( 'Hide', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'no',
                ]
            );

            $this->add_control(
                'grid_description_length',
                [
                    'label' => esc_html__( 'Description length', 'woolentor' ),
                    'type' => Controls_Manager::NUMBER,
                    'default' => 20,
                    'condition' => [
                        'show_grid_description' => 'yes',
                    ],
                ]
            );

        $this->end_controls_section();

        // List Style Settings
        $this->start_controls_section(
            'section_list_settings',
            [
                'label' => esc_html__( 'List View Settings', 'woolentor' ),
                'condition' => [
                    'layout' => ['list', 'grid_list_tab'],
                ],
            ]
        );

        $this->add_control(
            'show_product_features',
            [
                'label' => esc_html__( 'Show Product Features', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__( 'Show product attributes as feature icons', 'woolentor' ),
            ]
        );

        $this->add_control(
            'show_stock_status',
            [
                'label' => esc_html__( 'Show Stock Status', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_quantity_selector',
            [
                'label' => esc_html__( 'Show Quantity Selector', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_list_description',
            [
                'label' => esc_html__( 'Show description', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'list_description_length',
            [
                'label' => esc_html__( 'Description length', 'woolentor' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 20,
                'condition' => [
                    'show_list_description' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Prepare grid settings from Elementor settings
     * Override base method to handle Modern-specific controls
     */
    protected function prepare_grid_settings( $settings ) {
        // Get base settings first
        $grid_settings = parent::prepare_grid_settings( $settings );

        // Helper function to get value safely
        $get_val = function( $key, $default = null ) use ( $settings ) {
            return isset( $settings[$key] ) ? $settings[$key] : $default;
        };

        // Add Modern-specific settings.
        $modern_settings = [
            'widget_name'               => $this->get_name(),
            'widget_id'                 => $this->get_id(),
            'show_grid_description'     => $get_val('show_grid_description') === 'yes',
            'grid_description_length'   => $get_val('grid_description_length', 20),
            'show_list_description'     => $get_val('show_list_description') === 'yes',
            'list_description_length'   => $get_val('list_description_length', 20),
            'show_product_features'     => $get_val('show_product_features') === 'yes',
            'show_stock_status'         => $get_val('show_stock_status') === 'yes',
            'show_quantity_selector'    => $get_val('show_quantity_selector') === 'yes',
        ];

        // Merge all settings.
        $grid_settings = array_merge( $grid_settings, $modern_settings );

        return apply_filters( 'woolentor_product_grid_modern_settings', $grid_settings, $settings );
    }
}