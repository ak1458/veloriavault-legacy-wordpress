<?php
/**
 * Product Grid Luxury Style Widget
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
 * Product Grid Luxury Widget
 * Class name follows WooLentor convention: Woolentor_{Key}_Widget
 */
class Woolentor_Product_Grid_Luxury_Widget extends WooLentor_Product_Grid_Base_Widget {

    /**
     * Grid style
     */
    protected $grid_style = 'luxury';

    /**
     * Grid style label
     */
    protected $grid_style_label = 'Luxury Modernist';

    /**
     * Get widget name
     */
    public function get_name() {
        return 'woolentor-product-grid-luxury';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__( 'WL: Product Grid - Luxury', 'woolentor' );
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'woolentor-widget-new-icon eicon-products';
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return [ 'product', 'grid', 'luxury', 'editorial', 'premium', 'woocommerce', 'shop', 'store', 'woolentor' ];
    }

    /**
     * Register style-specific controls
     */
    protected function register_style_specific_controls() {

        // Luxury Style Settings
        $this->start_controls_section(
            'section_luxury_settings',
            [
                'label' => esc_html__( 'Luxury Style Settings', 'woolentor' ),
            ]
        );

            $this->add_control(
                'show_subtitle',
                [
                    'label' => esc_html__( 'Show description', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Show', 'woolentor' ),
                    'label_off' => esc_html__( 'Hide', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                    'description' => esc_html__( 'Show product short description as subtitle', 'woolentor' ),
                ]
            );

            $this->add_control(
                'subtitle_length',
                [
                    'label' => esc_html__( 'Description Length (words)', 'woolentor' ),
                    'type' => Controls_Manager::NUMBER,
                    'default' => 5,
                    'min' => 1,
                    'max' => 50,
                    'condition' => [
                        'show_subtitle' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'show_view_details',
                [
                    'label' => esc_html__( 'Show "View Details" Link', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Show', 'woolentor' ),
                    'label_off' => esc_html__( 'Hide', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );

            $this->add_control(
                'view_details_text',
                [
                    'label' => esc_html__( 'View Details Text', 'woolentor' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'View Details', 'woolentor' ),
                    'condition' => [
                        'show_view_details' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'add_to_cart_text',
                [
                    'label' => esc_html__( 'Add to Cart Text', 'woolentor' ),
                    'type' => Controls_Manager::TEXT,
                    'description' => esc_html__( 'Custom text for add to cart button', 'woolentor' ),
                ]
            );

            $this->add_control(
                'image_aspect_ratio',
                [
                    'label' => esc_html__( 'Image Aspect Ratio', 'woolentor' ),
                    'type' => Controls_Manager::SELECT,
                    'default' => '4-5',
                    'options' => [
                        '1-1' => esc_html__( '1:1 (Square)', 'woolentor' ),
                        '3-4' => esc_html__( '3:4 (Portrait)', 'woolentor' ),
                        '4-5' => esc_html__( '4:5 (Editorial)', 'woolentor' ),
                        '9-16' => esc_html__( '9:16 (Tall)', 'woolentor' ),
                    ],
                    'description' => esc_html__( 'Set the aspect ratio for product images', 'woolentor' ),
                ]
            );

        $this->end_controls_section();
    }

    // Add Product Per page Control
    protected function add_product_per_page_control(){
        $this->add_control(
            'posts_per_page',
            [
                'label' => esc_html__( 'Products Per Page', 'woolentor' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 3,
                'min' => 1,
                'max' => 1000,
            ]
        );
    }

    /**
     * Register layout controls
     */
    protected function register_layout_controls() {
        $this->start_controls_section(
            'section_layout',
            [
                'label' => esc_html__( 'Layout', 'woolentor' ),
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__( 'Columns', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '3',
                'mobile_default' => '1',
                'options' => [
                    '1' => esc_html__('One','woolentor'),
                    '2' => esc_html__('Two','woolentor'),
                    '3' => esc_html__('Three','woolentor'),
                    'wlpro_f1' => esc_html__('Four (Pro)','woolentor'),
                    'wlpro_f2' => esc_html__('Five (Pro)','woolentor'),
                    'wlpro_f3' => esc_html__('Six (Pro)','woolentor')
                ],
                'prefix_class' => 'woolentor-columns%s-',
            ]
        );

        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'columns', ['wlpro_f1','wlpro_f2','wlpro_f3']);

        $this->add_responsive_control(
            'gap',
            [
                'label' => esc_html__( 'Gap', 'woolentor' ),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 25,
                ],
                'tablet_default' => [
                    'size' => 20,
                ],
                'mobile_default' => [
                    'size' => 15,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-luxury' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
			'pro_same_height_grid',
			[
				'label' => sprintf( esc_html__( 'Same Height Grid %s', 'woolentor' ), '<i class="eicon-pro-icon"></i>' ),
				'type' => Controls_Manager::SWITCHER,
				'classes' => 'woolentor-disable-control'
			]
		);

        $this->end_controls_section();
    }

    /**
     * Register additional luxury-specific style controls
     */
    protected function register_luxury_style_controls() {

        // View Details Link Style
        $this->start_controls_section(
            'section_style_view_details',
            [
                'label' => esc_html__( 'View Details Link', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_view_details' => 'yes',
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_view_details_style' );

        $this->start_controls_tab(
            'tab_view_details_normal',
            [
                'label' => esc_html__( 'Normal', 'woolentor' ),
            ]
        );

        $this->add_control(
            'view_details_color',
            [
                'label' => esc_html__( 'Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-view-details' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_view_details_hover',
            [
                'label' => esc_html__( 'Hover', 'woolentor' ),
            ]
        );

        $this->add_control(
            'view_details_hover_color',
            [
                'label' => esc_html__( 'Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-view-details:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'view_details_typography',
                'selector' => '{{WRAPPER}} .woolentor-view-details',
            ]
        );

        $this->add_responsive_control(
            'view_details_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-view-details' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Badge Setting
     *
     * @return void
     */
    protected function add_additional_badges_settings(){
        $this->add_control(
            'show_category_badge',
            [
                'label' => esc_html__( 'Show Category Badge', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__( 'Show category as a badge.', 'woolentor' ),
                'separator'=>'before'
            ]
        );

        $this->add_control(
            'show_discount_offer_badge',
            [
                'label' => esc_html__( 'Show Discount Percentage Badge', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__( 'Show discount percentage badge if product is on sale status.', 'woolentor' ),
                'separator'=>'before'
            ]
        );
    }

    /**
     * Register Badge Style Controls
     */
    protected function register_badge_style_controls(){


        // Additional Style Controls for Luxury Design
        $this->register_luxury_style_controls();

        // Badge Style
        $this->start_controls_section(
            'section_style_badge',
            [
                'label' => esc_html__( 'Badge', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_badges' => 'yes',
                ],
            ]
        );

            $this->add_control(
                'badge_color',
                [
                    'label' => esc_html__( 'Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-item .woolentor-badge:not(.woolentor-category-badge)' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'badge_typography',
                    'selector' => '{{WRAPPER}} .woolentor-product-item .woolentor-badge:not(.woolentor-category-badge)',
                ]
            );

            $this->add_group_control(
                Group_Control_Background::get_type(),
                [
                    'name' => 'badge_background_color',
                    'types' => [ 'classic', 'gradient' ],
                    'exclude' => ['image'],
                    'fields_options'=>[
                        'background'=>[
                            'label'=> esc_html__( 'Badge Background', 'woolentor' )
                        ]
                    ],
                    'selector' => '{{WRAPPER}} .woolentor-product-item .woolentor-badge:not(.woolentor-category-badge)',
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'badge_border',
                    'selector' => '{{WRAPPER}} .woolentor-product-item .woolentor-badge:not(.woolentor-category-badge)',
                ]
            );

            $this->add_control(
                'badge_border_radius',
                [
                    'label' => esc_html__( 'Border Radius', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-item .woolentor-badge:not(.woolentor-category-badge)' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'badge_padding',
                [
                    'label' => esc_html__( 'Padding', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-item .woolentor-badge:not(.woolentor-category-badge)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section();

        // Category Badge Style
        $this->start_controls_section(
            'section_style_category_badge',
            [
                'label' => esc_html__( 'Category Badge', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_category_badge' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'category_badge_color',
            [
                'label' => esc_html__( 'Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-category-badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_badge_bg_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-category-badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'category_badge_typography',
                'selector' => '{{WRAPPER}} .woolentor-category-badge',
            ]
        );

        $this->add_control(
            'category_badge_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-category-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'category_badge_padding',
            [
                'label' => esc_html__( 'Padding', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-category-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'category_badge_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-category-badge' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Parcent Discount Badge Style
        $this->start_controls_section(
            'section_style_discount_badge',
            [
                'label' => esc_html__( 'Discount Badge', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_discount_offer_badge' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'discount_badge_color',
            [
                'label' => esc_html__( 'Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-sale-indicator' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'discount_badge_bg_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-sale-indicator' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'discount_badge_typography',
                'selector' => '{{WRAPPER}} .woolentor-luxury-card .woolentor-sale-indicator',
            ]
        );

        $this->add_responsive_control(
            'discount_badge_width',
            [
                'label' => esc_html__( 'Width', 'woolentor' ),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 48,
                ],
                'tablet_default' => [
                    'size' => 20,
                ],
                'mobile_default' => [
                    'size' => 15,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-sale-indicator' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'discount_badge_height',
            [
                'label' => esc_html__( 'Height', 'woolentor' ),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 48,
                ],
                'tablet_default' => [
                    'size' => 30,
                ],
                'mobile_default' => [
                    'size' => 30,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-sale-indicator' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'discount_badge_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-sale-indicator' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'discount_badge_padding',
            [
                'label' => esc_html__( 'Padding', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-sale-indicator' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'discount_badge_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-sale-indicator' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // New Badge Style
        $this->start_controls_section(
            'section_style_new_badge',
            [
                'label' => esc_html__( 'New Badge', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_new_badge' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'new_badge_color',
            [
                'label' => esc_html__( 'Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-new-badge-indicator' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'new_badge_bg_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-new-badge-indicator' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'new_badge_typography',
                'selector' => '{{WRAPPER}} .woolentor-luxury-card .woolentor-new-badge-indicator',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'new_badge_border',
                'selector' => '{{WRAPPER}} .woolentor-luxury-card .woolentor-new-badge-indicator',
            ]
        );

        $this->add_control(
            'new_badge_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-new-badge-indicator' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'new_badge_padding',
            [
                'label' => esc_html__( 'Padding', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-new-badge-indicator' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'new_badge_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-new-badge-indicator' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Prepare grid settings from Elementor settings
     * Override base method to handle Luxury-specific controls
     */
    protected function prepare_grid_settings( $settings ) {
        // Get base settings first
        $grid_settings = parent::prepare_grid_settings( $settings );

        // Helper function to get value safely
        $get_val = function( $key, $default = null ) use ( $settings ) {
            return isset( $settings[$key] ) ? $settings[$key] : $default;
        };

        // Add Luxury-specific settings
        $luxury_settings = [
            'widget_name'               => $this->get_name(),
            'widget_id'                 => $this->get_id(),
            'layout'                    => 'grid',
            'show_subtitle'             => $get_val('show_subtitle') === 'yes',
            'subtitle_length'           => absint($get_val('subtitle_length', 5)),
            'show_category_badge'       => $get_val('show_category_badge') === 'yes',
            'show_discount_offer_badge' => $get_val('show_discount_offer_badge') === 'yes',
            'show_view_details'         => $get_val('show_view_details') === 'yes',
            'view_details_text'         => $get_val('view_details_text', esc_html__('View Details', 'woolentor')),
            'add_to_cart_text'          => $get_val('add_to_cart_text', esc_html__('Add to Collection', 'woolentor')),
            'image_aspect_ratio'        => $get_val('image_aspect_ratio', '4-5'),
        ];

        // Merge all settings
        $grid_settings = array_merge( $grid_settings, $luxury_settings );

        return apply_filters( 'woolentor_product_grid_luxury_settings', $grid_settings, $settings );
    }
}
