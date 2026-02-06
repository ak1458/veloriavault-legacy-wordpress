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
if( !class_exists('WooLentor_Product_Grid_Base_Widget') ){
    require_once WOOLENTOR_ADDONS_PL_PATH . 'includes/addons/product-grid/base/class.product-grid-base-widget.php';
}

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

    // Add Query Control
    protected function add_query_type_control(){
        $this->add_control(
            'query_type',
            [
                'label' => esc_html__( 'Query Type', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'products',
                'options' => [
                    'products'          => esc_html__( 'All Products', 'woolentor' ),
                    'recent'            => esc_html__( 'Recent Products', 'woolentor' ),
                    'manual'            => esc_html__( 'Manual Selection', 'woolentor' ),
                    'featured'          => esc_html__( 'Featured', 'woolentor' ),
                    'sale'              => esc_html__( 'On Sale', 'woolentor' ),
                    'best_selling'      => esc_html__( 'Best Selling', 'woolentor' ),
                    'top_rated'         => esc_html__( 'Top Rated', 'woolentor' ),
                    'recently_viewed'   => esc_html__( 'Recently Viewed', 'woolentor' ),
                ],
            ]
        );
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
                    '4' => esc_html__('Four','woolentor'),
                    '5' => esc_html__('Five','woolentor'),
                    '6' => esc_html__('Six','woolentor')
                ],
                'prefix_class' => 'woolentor-columns%s-',
            ]
        );

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
            'same_height_grid',
            [
                'label' => esc_html__( 'Same Height Grid', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    public function add_secondary_imgage_control() {
        $this->add_control(
            'show_secondary_image',
            [
                'label' => esc_html__( 'Show Secondary Image on Hover', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'condition' => [
                    'show_image' => 'yes',
                ],
            ]
        );
	}

    /**
     * Badge Style Option
     *
     * @return void
     */
    protected function add_badge_style_control(){
        $this->add_control(
            'badge_style',
            [
                'label' => esc_html__( 'Badge Style', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'gradient',
                'options' => [
                    'solid' => esc_html__( 'Solid Color', 'woolentor' ),
                    'gradient' => esc_html__( 'Gradient', 'woolentor' ),
                    'outline' => esc_html__( 'Outline', 'woolentor' ),
                ],
                'prefix_class' => 'woolentor-badge-style-',
            ]
        );
    }

    /**
     * Badge Style Option
     *
     * @return void
     */
    protected function add_badge_position_control(){
        $this->add_control(
            'badge_position',
            [
                'label' => esc_html__( 'Badge Position', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'top-left',
                'options' => [
                    'top-left' => esc_html__( 'Top Left', 'woolentor' ),
                    'top-right' => esc_html__( 'Top Right', 'woolentor' ),
                    'top-center' => esc_html__( 'Top Center', 'woolentor' ),
                ],
                'prefix_class' => 'woolentor-badge-pos-',
            ]
        );
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
     * Card Hover Effect control
     *
     * @return void
     */
    protected function add_card_hover_effect_control(){
        $this->add_control(
            'card_hover_effect',
            [
                'label' => esc_html__( 'Card Hover Effect', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'lift',
                'options' => [
                    'none' => esc_html__( 'None', 'woolentor' ),
                    'lift' => esc_html__( 'Lift Up', 'woolentor' ),
                    'scale' => esc_html__( 'Scale', 'woolentor' ),
                    'shadow' => esc_html__( 'Enhanced Shadow', 'woolentor' ),
                ],
                'prefix_class' => 'woolentor-card-hover-',
            ]
        );
    }

    /**
     * Image Hover Effect control
     *
     * @return void
     */
    protected function add_image_hover_effect_control(){
        $this->add_control(
            'image_hover_effect',
            [
                'label' => esc_html__( 'Image Hover Effect', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'zoom',
                'options' => [
                    'none' => esc_html__( 'None', 'woolentor' ),
                    'zoom' => esc_html__( 'Zoom In', 'woolentor' ),
                    'fade' => esc_html__( 'Fade Effect', 'woolentor' ),
                    'grayscale' => esc_html__( 'Grayscale to Color', 'woolentor' ),
                ],
                'prefix_class' => 'woolentor-image-hover-',
            ]
        );
    }

    /**
     * Pagination Control Register
     *
     * @return void
     */
    protected function add_pagination_control(){

        $this->add_control(
            'enable_pagination',
            [
                'label' => esc_html__( 'Enable Pagination', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'pagination_type',
            [
                'label' => esc_html__( 'Pagination Type', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'numbers',
                'options' => [
                    'numbers' => esc_html__( 'Numbers', 'woolentor' ),
                    'load_more' => esc_html__( 'Load More', 'woolentor' ),
                    'infinite' => esc_html__( 'Infinite Scroll', 'woolentor' ),
                ],
                'condition' => [
                    'enable_pagination' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'load_more_text',
            [
                'label' => esc_html__( 'Button Text', 'woolentor' ),
                'label_block' =>true,
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__( 'Load More', 'woolentor' ),
                'placeholder' => esc_html__( 'Load More', 'woolentor' ),
                'condition' => [
                    'pagination_type' => 'load_more',
                    'enable_pagination' => 'yes',
                ],
                'description'=> esc_html__('Load More Button text','woolentor'),
            ]
        );

        $this->add_control(
            'load_more_complete_text',
            [
                'label' => esc_html__( 'Complete Button Text', 'woolentor' ),
                'label_block' =>true,
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__( 'No more products', 'woolentor' ),
                'placeholder' => esc_html__( 'No more products', 'woolentor' ),
                'condition' => [
                    'pagination_type' => 'load_more',
                    'enable_pagination' => 'yes',
                ],
                'description'=> esc_html__('After all product are load complete then show this text','woolentor'),
            ]
        );
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

    // Manage LoadMore Button Style control
    protected function add_loadmore_button_style_control(){

        $this->add_control(
            'loadmore_spinner_color',
            [
                'label' => esc_html__( 'Spinner Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-ajax-loader .spinner' => 'border-color: {{VALUE}}',
                ],
                'condition' => [
                    'pagination_type' => ['load_more','infinite'],
                ],
                'separator'=>'before'
            ]
        );

        $this->add_control(
            'loadmore_active_spinner_color',
            [
                'label' => esc_html__( 'Active Spinner Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-ajax-loader .spinner' => 'border-left-color: {{VALUE}}',
                ],
                'condition' => [
                    'pagination_type' => ['load_more','infinite'],
                ]
            ]
        );

        $this->start_controls_tabs(
            'loadmore_pagination_style_tabs',
            [
                'condition' => [
                    'pagination_type' => ['load_more','infinite'],
                ],
            ]
        );

            // Loadmore normal style
            $this->start_controls_tab(
                'loadmore_style_normal_tab',
                [
                    'label' => esc_html__( 'Normal', 'woolentor' ),
                ]
            );

                $this->add_control(
                    'loadmore_button_color',
                    [
                        'label' => esc_html__( 'Color', 'woolentor' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-load-more-btn' => 'color: {{VALUE}}',
                        ]
                    ]
                );
    
                $this->add_control(
                    'loadmore_bg_color',
                    [
                        'label' => esc_html__( 'Background Color', 'woolentor-pro' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-load-more-btn' => 'background-color: {{VALUE}}',
                        ]
                    ]
                );
    
                $this->add_control(
                    'loadmore_border_color',
                    [
                        'label' => esc_html__( 'Border Color', 'woolentor-pro' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-load-more-btn' => 'border-color: {{VALUE}}',
                        ]
                    ]
                );

            $this->end_controls_tab();

            // Loadmore hover style
            $this->start_controls_tab(
                'loadmore_style_hover_tab',
                [
                    'label' => esc_html__( 'Hover', 'woolentor' ),
                ]
            );

                $this->add_control(
                    'loadmore_button_hover_color',
                    [
                        'label' => esc_html__( 'Color', 'woolentor' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-load-more-btn:hover' => 'color: {{VALUE}}',
                        ]
                    ]
                );
    
                $this->add_control(
                    'loadmore_hover_bg_color',
                    [
                        'label' => esc_html__( 'Background Color', 'woolentor-pro' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-load-more-btn:hover' => 'background-color: {{VALUE}}',
                        ]
                    ]
                );
    
                $this->add_control(
                    'loadmore_hover_border_color',
                    [
                        'label' => esc_html__( 'Border Color', 'woolentor-pro' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-load-more-btn:hover' => 'border-color: {{VALUE}}',
                        ]
                    ]
                );

            $this->end_controls_tab();

            // Loadmore All Loaded button style
            $this->start_controls_tab(
                'loadmore_style_disable_button_tab',
                [
                    'label' => esc_html__( 'Disable', 'woolentor' ),
                ]
            );

                $this->add_control(
                    'loadmore_disable_button_color',
                    [
                        'label' => esc_html__( 'Color', 'woolentor' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-load-more-btn:disabled' => 'color: {{VALUE}}',
                        ]
                    ]
                );
    
                $this->add_control(
                    'loadmore_disable_button_bg_color',
                    [
                        'label' => esc_html__( 'Background Color', 'woolentor-pro' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-load-more-btn:disabled' => 'background-color: {{VALUE}}',
                        ]
                    ]
                );
    
                $this->add_control(
                    'loadmore_disable_button_border_color',
                    [
                        'label' => esc_html__( 'Border Color', 'woolentor-pro' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-load-more-btn:disabled' => 'border-color: {{VALUE}}',
                        ]
                    ]
                );

            $this->end_controls_tab();

        $this->end_controls_tabs();
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
            'same_height_grid'          => $get_val('same_height_grid') === 'yes',
        ];

        // Merge all settings
        $grid_settings = array_merge( $grid_settings, $luxury_settings );

        return apply_filters( 'woolentor_product_grid_luxury_settings', $grid_settings, $settings );
    }
}
