<?php
/**
 * Product Grid Editorial Style Widget
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
 * Product Grid Editorial Widget
 * Class name follows WooLentor convention: Woolentor_{Key}_Widget
 */
class Woolentor_Product_Grid_Editorial_Widget extends WooLentor_Product_Grid_Base_Widget {

    /**
     * Grid style
     */
    protected $grid_style = 'editorial';

    /**
     * Grid style label
     */
    protected $grid_style_label = 'Editorial Grid & List';

    /**
     * Get widget name
     */
    public function get_name() {
        return 'woolentor-product-grid-editorial';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__( 'WL: Product Grid - Editorial', 'woolentor' );
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'woolentor-widget-new-icon eicon-gallery-grid';
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return [ 'product', 'grid', 'list', 'editorial', 'premium', 'collection', 'woocommerce', 'shop', 'store', 'woolentor' ];
    }

    /**
     * Register style-specific controls
     */
    protected function register_style_specific_controls() {

        // Grid View Settings
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
                    'label' => esc_html__( 'Show Description', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Show', 'woolentor' ),
                    'label_off' => esc_html__( 'Hide', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );

            $this->add_control(
                'grid_description_length',
                [
                    'label' => esc_html__( 'Description Length (words)', 'woolentor' ),
                    'type' => Controls_Manager::NUMBER,
                    'default' => 15,
                    'condition' => [
                        'show_grid_description' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'show_grid_stock_indicator',
                [
                    'label' => esc_html__( 'Show Stock Indicator', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Show', 'woolentor' ),
                    'label_off' => esc_html__( 'Hide', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );

            $this->add_control(
                'show_view_details',
                [
                    'label' => esc_html__( 'Show "View Details" Button', 'woolentor' ),
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
                'grid_image_aspect_ratio',
                [
                    'label' => esc_html__( 'Image Aspect Ratio', 'woolentor' ),
                    'type' => Controls_Manager::SELECT,
                    'default' => '3-4',
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

        // List View Settings
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
                'show_list_description',
                [
                    'label' => esc_html__( 'Show Description', 'woolentor' ),
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
                    'label' => esc_html__( 'Description Length (words)', 'woolentor' ),
                    'type' => Controls_Manager::NUMBER,
                    'default' => 30,
                    'condition' => [
                        'show_list_description' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'show_list_stock_indicator',
                [
                    'label' => esc_html__( 'Show Stock Indicator', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Show', 'woolentor' ),
                    'label_off' => esc_html__( 'Hide', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );

            $this->add_control(
                'list_image_aspect_ratio',
                [
                    'label' => esc_html__( 'Image Aspect Ratio', 'woolentor' ),
                    'type' => Controls_Manager::SELECT,
                    'default' => '3-4',
                    'options' => [
                        '1-1' => esc_html__( '1:1 (Square)', 'woolentor' ),
                        '4-3' => esc_html__( '4:3 (Landscape)', 'woolentor' ),
                        '16-9' => esc_html__( '16:9 (Wide)', 'woolentor' ),
                        '3-4' => esc_html__( '3:4 (Portrait)', 'woolentor' ),
                    ],
                    'description' => esc_html__( 'Set the aspect ratio for product images in list view', 'woolentor' ),
                ]
            );

        $this->end_controls_section();

        // Common Settings
        $this->start_controls_section(
            'section_editorial_common_settings',
            [
                'label' => esc_html__( 'Common Settings', 'woolentor' ),
            ]
        );

            $this->add_control(
                'stock_text_in_stock',
                [
                    'label' => esc_html__( 'In Stock Text', 'woolentor' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'In Stock', 'woolentor' ),
                ]
            );

            $this->add_control(
                'stock_text_out_of_stock',
                [
                    'label' => esc_html__( 'Out of Stock Text', 'woolentor' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'Out of Stock', 'woolentor' ),
                ]
            );

            $this->add_control(
                'add_to_cart_text',
                [
                    'label' => esc_html__( 'Add to Cart Custom Text', 'woolentor' ),
                    'type' => Controls_Manager::TEXT,
                    'placeholder' => esc_html__( 'Add to Collection', 'woolentor' ),
                    'description' => esc_html__( 'Leave empty to use default text', 'woolentor' ),
                ]
            );

        $this->end_controls_section();
    }

    /**
     * Add Product Per page Control
     */
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

        $this->add_control(
            'layout',
            [
                'label' => esc_html__( 'Layout', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => esc_html__( 'Grid', 'woolentor' ),
                    'list' => esc_html__( 'List', 'woolentor' ),
                    'wlpro_f1' => esc_html__( 'Grid List Tab (Pro)', 'woolentor' ),
                ]
            ]
        );

        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-editorial', 'layout', ['wlpro_f1']);

        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__( 'Columns', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => esc_html__('One','woolentor'),
                    '2' => esc_html__('Two','woolentor'),
                    '3' => esc_html__('Three','woolentor'),
                    'wlpro_f1' => esc_html__('Four (Pro)','woolentor'),
                    'wlpro_f2' => esc_html__('Five (Pro)','woolentor'),
                    'wlpro_f3' => esc_html__('Six (Pro)','woolentor')
                ],
                'condition' => [
                    'layout!' => 'list',
                ],
                'prefix_class' => 'woolentor-columns%s-',
            ]
        );

        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-editorial', 'columns', ['wlpro_f1','wlpro_f2','wlpro_f3']);

        $this->add_responsive_control(
            'gap',
            [
                'label' => esc_html__( 'Gap', 'woolentor' ),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 48,
                ],
                'tablet_default' => [
                    'size' => 32,
                ],
                'mobile_default' => [
                    'size' => 20,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-editorial' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register editorial-specific style controls
     */
    protected function register_editorial_style_controls() {

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
                            '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail' => 'color: {{VALUE}};',
                        ],
                    ]
                );

                $this->add_control(
                    'view_details_bg_color',
                    [
                        'label' => esc_html__( 'Background Color', 'woolentor' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail' => 'background-color: {{VALUE}};background: {{VALUE}};',
                        ],
                    ]
                );

                $this->add_control(
                    'view_details_border_color',
                    [
                        'label' => esc_html__( 'Border Color', 'woolentor' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail' => 'border-color: {{VALUE}};',
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
                            '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail:hover' => 'color: {{VALUE}};',
                        ],
                    ]
                );
                $this->add_control(
                    'view_details_hover_bg_color',
                    [
                        'label' => esc_html__( 'Background Color', 'woolentor' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail:hover' => 'background-color: {{VALUE}};background: {{VALUE}};',
                        ],
                    ]
                );

                $this->add_control(
                    'view_details_hover_border_color',
                    [
                        'label' => esc_html__( 'Border Color', 'woolentor' ),
                        'type' => Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail:hover' => 'border-color: {{VALUE}};',
                        ],
                    ]
                );

            $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'view_details_typography',
                'selector' => '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'view_details_button_border',
                'selector' => '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail',
            ]
        );

        $this->add_control(
            'view_details_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'view_details_padding',
            [
                'label' => esc_html__( 'Padding', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'view_details_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-view-detail' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Stock Indicator Style
        $this->start_controls_section(
            'section_style_stock_indicator',
            [
                'label' => esc_html__( 'Stock Indicator', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'stock_indicator_color',
            [
                'label' => esc_html__( 'Text Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-stock-status' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'stock_dot_color_in_stock',
            [
                'label' => esc_html__( 'In Stock Dot Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-stock-status.in-stock .stock-dot' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'stock_dot_color_out_of_stock',
            [
                'label' => esc_html__( 'Out of Stock Dot Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-stock-status.out-of-stock .stock-dot' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'stock_indicator_typography',
                'selector' => '{{WRAPPER}} .woolentor-stock-status',
            ]
        );

        $this->add_responsive_control(
            'stock_indicator_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-stock-status' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Add to Cart Button style controls for list style
     */
    protected function register_add_to_cart_button_style_controls() {
        $this->start_controls_section(
            'section_style_cart_action_button',
            [
                'label' => esc_html__( 'Add To Cart Button', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_add_to_cart' => 'yes',
                ]
            ]
        );

            $this->start_controls_tabs( 'tabs_cart_button_style' );

                $this->start_controls_tab(
                    'tab_cart_action_button_normal',
                    [
                        'label' => esc_html__( 'Normal', 'woolentor' ),
                    ]
                );

                    $this->add_control(
                        'cart_action_button_text_color',
                        [
                            'label' => esc_html__( 'Text Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-editorial-list-card a.woolentor-cart-btn' => 'color: {{VALUE}}!important;',
                            ],
                        ]
                    );

                    $this->add_control(
                        'cart_action_button_background_color',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-editorial-list-card a.woolentor-cart-btn' => 'background-color: {{VALUE}}!important;background:{{VALUE}}!important;',
                            ],
                        ]
                    );

                    $this->add_control(
                        'cart_action_button_border_color',
                        [
                            'label' => esc_html__( 'Border Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-editorial-list-card a.woolentor-cart-btn' => 'border-color: {{VALUE}}!important;',
                            ],
                        ]
                    );

                $this->end_controls_tab();

                $this->start_controls_tab(
                    'tab_cart_button_hover',
                    [
                        'label' => esc_html__( 'Hover', 'woolentor' ),
                    ]
                );

                    $this->add_control(
                        'cart_button_hover_color',
                        [
                            'label' => esc_html__( 'Text Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-editorial-list-card a.woolentor-cart-btn:hover' => 'color: {{VALUE}}!important;',
                            ],
                        ]
                    );

                    $this->add_control(
                        'cart_button_background_hover_color',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-editorial-list-card a.woolentor-cart-btn:hover' => 'background-color: {{VALUE}}!important;background:{{VALUE}}!important;',
                            ],
                        ]
                    );

                    $this->add_control(
                        'cart_button_hover_border_color',
                        [
                            'label' => esc_html__( 'Border Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-editorial-list-card a.woolentor-cart-btn:hover' => 'border-color: {{VALUE}};',
                            ],
                        ]
                    );

                $this->end_controls_tab();

            $this->end_controls_tabs();

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'cart_action_button_typography',
                    'selector' => '{{WRAPPER}} .woolentor-grid-card .woolentor-editorial-list-card a.woolentor-cart-btn',
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'cart_button_border',
                    'selector' => '{{WRAPPER}} .woolentor-editorial-list-card a.woolentor-cart-btn',
                ]
            );

            $this->add_control(
                'cart_button_border_radius',
                [
                    'label' => esc_html__( 'Border Radius', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-editorial-list-card a.woolentor-cart-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'cart_button_padding',
                [
                    'label' => esc_html__( 'Padding', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', 'em', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-editorial-list-card a.woolentor-cart-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
                    ],
                ]
            );

            $this->add_control(
                'cart_button_style_notice',
                [
                    'type' => Controls_Manager::NOTICE,
                    'notice_type' => 'warning',
                    'dismissible' => false,
                    'heading' => esc_html__( 'This style option only work list style', 'woolentor' ),
                    'content' => esc_html__( 'Only For list view add to cart button style control', 'woolentor' ),
                ]
            );

        $this->end_controls_section();
    }

    /**
     * Register Badge Style Controls
     */
    protected function register_badge_style_controls(){
        // Add editorial-specific style controls first
        $this->register_editorial_style_controls();

        // Call parent badge style controls
        parent::register_badge_style_controls();
    }

    /**
     * Prepare grid settings from Elementor settings
     * Override base method to handle Editorial-specific controls
     */
    protected function prepare_grid_settings( $settings ) {
        // Get base settings first
        $grid_settings = parent::prepare_grid_settings( $settings );

        // Helper function to get value safely
        $get_val = function( $key, $default = null ) use ( $settings ) {
            return isset( $settings[$key] ) ? $settings[$key] : $default;
        };

        // Add Editorial-specific settings
        $editorial_settings = [
            'widget_name'                   => $this->get_name(),
            'widget_id'                     => $this->get_id(),
            'show_grid_description'         => $get_val('show_grid_description') === 'yes',
            'grid_description_length'       => absint($get_val('grid_description_length', 15)),
            'show_list_description'         => $get_val('show_list_description') === 'yes',
            'list_description_length'       => absint($get_val('list_description_length', 30)),
            'show_grid_stock_indicator'     => $get_val('show_grid_stock_indicator') === 'yes',
            'show_list_stock_indicator'     => $get_val('show_list_stock_indicator') === 'yes',
            'stock_text_in_stock'           => $get_val('stock_text_in_stock', esc_html__('In Stock', 'woolentor')),
            'stock_text_out_of_stock'       => $get_val('stock_text_out_of_stock', esc_html__('Out of Stock', 'woolentor')),
            'grid_image_aspect_ratio'       => $get_val('grid_image_aspect_ratio', '3-4'),
            'list_image_aspect_ratio'       => $get_val('list_image_aspect_ratio', '3-4'),
            'show_view_details'             => $get_val('show_view_details') === 'yes',
            'view_details_text'             => $get_val('view_details_text', esc_html__('View Details', 'woolentor')),
        ];

        // Merge all settings
        $grid_settings = array_merge( $grid_settings, $editorial_settings );

        return apply_filters( 'woolentor_product_grid_editorial_settings', $grid_settings, $settings );
    }
}
