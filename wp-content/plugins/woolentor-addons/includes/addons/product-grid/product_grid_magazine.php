<?php
/**
 * Product Grid Magazine Style Widget
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
 * Product Grid Magazine Widget
 * Class name follows WooLentor convention: Woolentor_{Key}_Widget
 */
class Woolentor_Product_Grid_Magazine_Widget extends WooLentor_Product_Grid_Base_Widget {

    /**
     * Grid style
     */
    protected $grid_style = 'magazine';

    /**
     * Grid style label
     */
    protected $grid_style_label = 'Magazine Editorial Grid & List';

    /**
     * Get widget name
     */
    public function get_name() {
        return 'woolentor-product-grid-magazine';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__( 'WL: Product Grid - Magazine', 'woolentor' );
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
        return [ 'product', 'grid', 'list', 'magazine', 'editorial', 'woocommerce', 'shop', 'store', 'woolentor' ];
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

            woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-magazine', 'layout', ['wlpro_f1']);

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
                        '{{WRAPPER}} .woolentor-product-grid-magazine' => 'gap: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section();
    }

    /**
     * Register display controls
     */
    protected function register_display_controls() {
        $this->start_controls_section(
            'section_display',
            [
                'label' => esc_html__( 'Display', 'woolentor' ),
            ]
        );

        $this->add_control(
            'show_image',
            [
                'label' => esc_html__( 'Show Image', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'image',
                'default' => 'woocommerce_thumbnail',
                'condition' => [
                    'show_image' => 'yes',
                ],
            ]
        );

        $this->add_secondary_imgage_control();

        $this->add_control(
            'show_title',
            [
                'label' => esc_html__( 'Show Title', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'title_tag',
            [
                'label' => esc_html__( 'Title Tag', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'h3',
                'options' => woolentor_html_tag_lists(),
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_price',
            [
                'label' => esc_html__( 'Show Price', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_categories',
            [
                'label' => esc_html__( 'Show Categories', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_add_to_cart',
            [
                'label' => esc_html__( 'Show Add to Cart', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_quick_view',
            [
                'label' => esc_html__( 'Show Quick View', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_wishlist',
            [
                'label' => esc_html__( 'Show Wishlist', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_compare',
            [
                'label' => esc_html__( 'Show Compare', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'woolentor' ),
                'label_off' => esc_html__( 'Hide', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    public function add_secondary_imgage_control() {
		$this->add_control(
			'pro_show_secondary_image',
			[
				'label' => sprintf( esc_html__( 'Show Secondary Image on Hover %s', 'woolentor' ), '<i class="eicon-pro-icon"></i>' ),
				'type' => Controls_Manager::SWITCHER,
				'classes' => 'woolentor-disable-control'
			]
		);
	}

    /**
     * Badge Position Control Option
     *
     * @return void
     */
    protected function add_badge_position_control(){
        $this->add_control(
            'badge_position',
            [
                'label' => esc_html__( 'Badge Position', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'top-right',
                'options' => [
                    'top-right' => esc_html__('Top Right', 'woolentor' ),
                    'wlpro_f1' => esc_html__('Top Left (Pro)', 'woolentor' ),
                    'wlpro_f2' => esc_html__('Top Center (Pro)', 'woolentor' ),
                ],
                'prefix_class' => 'woolentor-badge-pos-',
            ]
        );
        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'badge_position', ['wlpro_f1','wlpro_f2']);
    }

    protected function add_card_hover_effect_control(){
        // Do not need to add option for card hover effect
    }

    protected function add_image_hover_effect_control(){
        // Do not need to add option for image hover effect
    }

    protected function register_review_style_controls(){
        // Do not need to add option for review style

        // Featured Badge Style
        $this->start_controls_section(
            'section_grid_list_featured_badge_style',
            [
                'label' => esc_html__( 'Featured Badge', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout' => ['list', 'grid_list_tab'],
                ],
            ]
        );

            $this->add_control(
                'featured_badge_text_color',
                [
                    'label' => esc_html__( 'Text Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-editorial-badge' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'featured_badge_background_color',
                [
                    'label' => esc_html__( 'Background Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-editorial-badge' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'featured_badge_typography',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-editorial-badge',
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'featured_badge_border',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-editorial-badge',
                ]
            );

            $this->add_control(
                'featured_badge_border_radius',
                [
                    'label' => esc_html__( 'Border Radius', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-editorial-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'featured_badge_padding',
                [
                    'label' => esc_html__( 'Padding', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', 'em', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-editorial-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'featured_badge_margin',
                [
                    'label' => esc_html__( 'Margin', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', 'em', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-editorial-badge' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
                    ],
                ]
            );

        $this->end_controls_section();

        // Featured Label Style
        $this->start_controls_section(
            'section_grid_list_featured_label_style',
            [
                'label' => esc_html__( 'Featured Label', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout' => ['list', 'grid_list_tab'],
                ],
            ]
        );

            $this->add_control(
                'featured_label_text_color',
                [
                    'label' => esc_html__( 'Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-editorial-label' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'featured_label_typography',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-editorial-label',
                ]
            );

            $this->add_control(
                'featured_label_text_seperator_color',
                [
                    'label' => esc_html__( 'Seperator Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-separator' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'seperator_space_between',
                [
                    'type' => Controls_Manager::SLIDER,
                    'label' => esc_html__( 'Seperator space between', 'woolentor' ),
                    'size_units' => [ 'px', 'em', 'rem' ],
                    'range' => [
                        'px' => [
                            'min' => 1,
                            'max' => 200,
                        ],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 10,
                    ],
                    'selectors' => [ 
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-separator'  => 'margin:0 {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section();
    }

    /**
     * Register price style controls
     */
    protected function register_price_style_controls() {
        $this->start_controls_section(
            'section_style_price',
            [
                'label' => esc_html__( 'Price', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_price' => 'yes',
                    'layout' => ['grid', 'grid_list_tab'],
                ],
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label' => esc_html__( 'Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-price' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-product-price del' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'sale_price_color',
            [
                'label' => esc_html__( 'Sale Price Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-price ins' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'selector' => '{{WRAPPER}} .woolentor-product-price,{{WRAPPER}} .woolentor-product-price del',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_product_details_item',
            [
                'label' => esc_html__( 'Product Details Item', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_price' => 'yes',
                    'layout' => ['list', 'grid_list_tab'],
                ],
            ]
        );

            $this->add_control(
                'product_details_item_label_style',
                [
                    'label' => esc_html__( 'Label Style', 'woolentor' ),
                    'type' => Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_control(
                'detail_label_color',
                [
                    'label' => esc_html__( 'Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-detail-label' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'detail_label_typography',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-detail-label',
                ]
            );

            $this->add_responsive_control(
                'detail_label_margin',
                [
                    'label' => esc_html__( 'Margin', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', 'em', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-detail-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'product_details_item_info_style',
                [
                    'label' => esc_html__( 'Info Style', 'woolentor' ),
                    'type' => Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_control(
                'product_details_item_info_color',
                [
                    'label' => esc_html__( 'Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-detail-value' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'product_details_item_regular_price_color',
                [
                    'label' => esc_html__( 'Regular Price Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-detail-value del' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'product_details_item_regular_price_typography',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-detail-value del',
                    'fields_options' => [
                        'typography' => [
                            'label' => esc_html__( 'Regular Price Typography', 'woolentor' )
                        ]
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'product_details_item_info_typography',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-detail-value',
                ]
            );

            $this->add_responsive_control(
                'product_details_item_info_margin',
                [
                    'label' => esc_html__( 'Margin', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', 'em', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-detail-value' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section();
    }

    /**
     * Register add to cart button style controls
     */
    protected function register_add_to_cart_button_style_controls() {
        $this->start_controls_section(
            'section_style_cart_action_button',
            [
                'label' => esc_html__( 'Add To Cart Button', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_add_to_cart' => 'yes',
                    'layout' => ['grid', 'grid_list_tab'],
                ],
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
                            'label' => esc_html__( 'Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn' => 'color: {{VALUE}}!important;',
                            ],
                        ]
                    );

                    $this->add_control(
                        'cart_action_button_background_color',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn' => 'background-color: {{VALUE}}!important;background:{{VALUE}}!important;',
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
                            'label' => esc_html__( 'Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn:hover' => 'color: {{VALUE}}!important;',
                            ],
                        ]
                    );

                    $this->add_control(
                        'cart_button_background_hover_color',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn:hover' => 'background-color: {{VALUE}}!important;background:{{VALUE}}!important;',
                            ],
                        ]
                    );

                    $this->add_control(
                        'cart_button_hover_border_color',
                        [
                            'label' => esc_html__( 'Border Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn:hover' => 'border-color: {{VALUE}};',
                            ],
                        ]
                    );

                $this->end_controls_tab();

            $this->end_controls_tabs();

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'cart_action_button_typography',
                    'selector' => '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn',
                ]
            );

            $this->add_control(
                'cart_action_button_size',
                [
                    'type' => Controls_Manager::SLIDER,
                    'label' => esc_html__( 'Icon Size', 'woolentor' ),
                    'size_units' => [ 'px', 'em', 'rem', 'custom' ],
                    'range' => [
                        'px' => [
                            'min' => 1,
                            'max' => 200,
                        ],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 18,
                    ],
                    'selectors' => [ 
                        '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn svg'  => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'cart_button_border',
                    'selector' => '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn',
                ]
            );

            $this->add_control(
                'cart_button_border_radius',
                [
                    'label' => esc_html__( 'Border Radius', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
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
                        '{{WRAPPER}} .woolentor-grid-view-content .woolentor-product-actions .woolentor-cart-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
                    ],
                ]
            );

        $this->end_controls_section();

        // List Style Add To Cart Button
        $this->start_controls_section(
            'section_list_style_cart_action_button',
            [
                'label' => esc_html__( 'Add To Cart Button', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_add_to_cart' => 'yes',
                    'layout' => ['list', 'grid_list_tab'],
                ],
            ]
        );

            $this->start_controls_tabs( 'tabs_list_cart_button_style' );

                $this->start_controls_tab(
                    'tab_list_cart_action_button_normal',
                    [
                        'label' => esc_html__( 'Normal', 'woolentor' ),
                    ]
                );

                    $this->add_control(
                        'list_cart_action_button_text_color',
                        [
                            'label' => esc_html__( 'Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content a.woolentor-primary-btn' => 'color: {{VALUE}};',
                            ],
                        ]
                    );

                    $this->add_control(
                        'list_cart_action_button_background_color',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content a.woolentor-primary-btn' => 'background-color: {{VALUE}};background:{{VALUE}};',
                            ],
                        ]
                    );

                $this->end_controls_tab();

                $this->start_controls_tab(
                    'tab_list_cart_button_hover',
                    [
                        'label' => esc_html__( 'Hover', 'woolentor' ),
                    ]
                );

                    $this->add_control(
                        'list_cart_button_hover_color',
                        [
                            'label' => esc_html__( 'Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content a.woolentor-primary-btn:hover' => 'color: {{VALUE}};',
                            ],
                        ]
                    );

                    $this->add_control(
                        'list_cart_button_background_hover_color',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content a.woolentor-primary-btn:hover' => 'background-color: {{VALUE}};background:{{VALUE}};',
                            ],
                        ]
                    );

                    $this->add_control(
                        'list_cart_button_hover_border_color',
                        [
                            'label' => esc_html__( 'Border Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content a.woolentor-primary-btn:hover' => 'border-color: {{VALUE}};',
                            ],
                        ]
                    );

                $this->end_controls_tab();

            $this->end_controls_tabs();

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'list_cart_action_button_typography',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content a.woolentor-primary-btn',
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'list_cart_button_border',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content a.woolentor-primary-btn',
                ]
            );

            $this->add_control(
                'list_cart_button_border_radius',
                [
                    'label' => esc_html__( 'Border Radius', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content a.woolentor-primary-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'list_cart_button_padding',
                [
                    'label' => esc_html__( 'Padding', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', 'em', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content a.woolentor-primary-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section();

        $this->register_list_style_view_detail_action_button();
    }

    // List Style View Detail Action Button
    protected function register_list_style_view_detail_action_button() {
        $this->start_controls_section(
            'section_list_style_view_detail_action_button',
            [
                'label' => esc_html__( 'View Detail Button', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_view_details_button' => 'yes',
                    'layout' => ['list', 'grid_list_tab'],
                ],
            ]
        );

            $this->start_controls_tabs( 'tabs_list_view_detail_button_style' );

                $this->start_controls_tab(
                    'tab_list_view_detail_button_normal',
                    [
                        'label' => esc_html__( 'Normal', 'woolentor' ),
                    ]
                );

                    $this->add_control(
                        'list_view_detail_button_text_color',
                        [
                            'label' => esc_html__( 'Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content .woolentor-secondary-btn' => 'color: {{VALUE}};',
                            ],
                        ]
                    );

                    $this->add_control(
                        'list_view_detail_button_background_color',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content .woolentor-secondary-btn' => 'background-color: {{VALUE}};background:{{VALUE}};',
                            ],
                        ]
                    );

                $this->end_controls_tab();

                $this->start_controls_tab(
                    'tab_list_view_detail_button_hover',
                    [
                        'label' => esc_html__( 'Hover', 'woolentor' ),
                    ]
                );

                    $this->add_control(
                        'list_view_detail_button_hover_color',
                        [
                            'label' => esc_html__( 'Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content .woolentor-secondary-btn:hover' => 'color: {{VALUE}};',
                            ],
                        ]
                    );

                    $this->add_control(
                        'list_view_detail_button_background_hover_color',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content .woolentor-secondary-btn:hover' => 'background-color: {{VALUE}};background:{{VALUE}};',
                            ],
                        ]
                    );

                    $this->add_control(
                        'list_view_detail_button_hover_border_color',
                        [
                            'label' => esc_html__( 'Border Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content .woolentor-secondary-btn:hover' => 'border-color: {{VALUE}};',
                            ],
                        ]
                    );

                $this->end_controls_tab();

            $this->end_controls_tabs();

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'list_view_detail_button_typography',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content .woolentor-secondary-btn',
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'list_view_detail_button_border',
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content .woolentor-secondary-btn',
                ]
            );

            $this->add_control(
                'list_view_detail_button_border_radius',
                [
                    'label' => esc_html__( 'Border Radius', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content .woolentor-secondary-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'list_view_detail_button_padding',
                [
                    'label' => esc_html__( 'Padding', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', 'em', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-view-content .woolentor-secondary-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section();
    }

    /**
     * Register button style controls
     */
    protected function register_button_style_controls() {
        $this->start_controls_section(
            'section_style_quick_action_button',
            [
                'label' => esc_html__( 'Quick Action Button', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_quick_actions' => 'yes',
                    'layout' => ['grid', 'grid_list_tab'],
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_button_style' );

        $this->start_controls_tab(
            'tab_quick_action_button_normal',
            [
                'label' => esc_html__( 'Normal', 'woolentor' ),
            ]
        );

        $this->add_control(
            'quick_action_button_text_color',
            [
                'label' => esc_html__( 'Text Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'quick_action_button_background_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'quick_action_button_border_color',
            [
                'label' => esc_html__( 'Border Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_quick_button_hover',
            [
                'label' => esc_html__( 'Hover', 'woolentor' ),
            ]
        );

        $this->add_control(
            'quick_button_hover_color',
            [
                'label' => esc_html__( 'Text Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action:hover a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'quick_button_background_hover_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'quick_button_border_hover_color',
            [
                'label' => esc_html__( 'Border Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
			'quick_action_button_size',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Size', 'woolentor' ),
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 200,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 18,
				],
                'selectors' => [ 
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action svg'  => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action'  => 'font-size: {{SIZE}}{{UNIT}};',
                ],
			]
		);

        $this->add_control(
            'quick_button_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'quick_button_padding',
            [
                'label' => esc_html__( 'Padding', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // List Style Quick Action Icon
        $this->start_controls_section(
            'section_style_list_view_quick_action_button',
            [
                'label' => esc_html__( 'Quick Action Button', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_quick_actions' => 'yes',
                    'layout' => ['list', 'grid_list_tab'],
                ],
            ]
        );

        $this->start_controls_tabs( 'list_view_tabs_button_style' );

        $this->start_controls_tab(
            'tab_list_view_button_normal',
            [
                'label' => esc_html__( 'Normal', 'woolentor' ),
            ]
        );

        $this->add_control(
            'list_view_button_text_color',
            [
                'label' => esc_html__( 'Text Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'list_view_button_background_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'list_view_button_border_color',
            [
                'label' => esc_html__( 'Border Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_list_view_button_hover',
            [
                'label' => esc_html__( 'Hover', 'woolentor' ),
            ]
        );

        $this->add_control(
            'list_view_button_hover_color',
            [
                'label' => esc_html__( 'Text Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-quick-actions .woolentor-quick-action:hover a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'list_view_button_background_hover_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'list_view_button_border_hover_color',
            [
                'label' => esc_html__( 'Border Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
			'list_view_button_size',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Size', 'woolentor' ),
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 200,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 18,
				],
                'selectors' => [ 
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn svg'  => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn'  => 'font-size: {{SIZE}}{{UNIT}};',
                ],
			]
		);

        $this->add_control(
            'list_view_button_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'list_view_button_padding',
            [
                'label' => esc_html__( 'Padding', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-wishlist-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->register_style_product_counter_controls();
    }

    /**
     * Register style product counter controls
     */
    protected function register_style_product_counter_controls() {
        $this->start_controls_section(
            'section_product_counter',
            [
                'label' => esc_html__( 'Product Counter', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_product_number' => 'yes',
                ],
            ]
        );

            $this->add_control(
                'product_counter_color',
                [
                    'label' => esc_html__( 'Grid View Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'condition' => [
                        'layout' => ['grid', 'grid_list_tab'],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-product-number' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'product_counter_typography',
                    'condition' => [
                        'layout' => ['grid', 'grid_list_tab'],
                    ],
                    'fields_options' => [
                        'typography' => [
                            'label' => esc_html__( 'Grid View Typography', 'woolentor' ),
                        ],
                    ],
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-product-number',
                ]
            );

            $this->add_control(
                'product_counter_position_grid_view_popover',
                [
                    'label' => esc_html__( 'Grid View Position', 'woolentor' ),
                    'type' => Controls_Manager::POPOVER_TOGGLE,
                    'label_off' => esc_html__( 'Default', 'woolentor' ),
                    'label_on' => esc_html__( 'Custom', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                    'condition' => [
                        'layout' => ['grid', 'grid_list_tab'],
                    ]
                ]
            );

            $this->start_popover();

                $this->add_responsive_control(
                    'product_counter_position_grid_view_top',
                    [
                        'label' => esc_html__( 'Top', 'woolentor' ),
                        'type' => Controls_Manager::SLIDER,
                        'size_units' => [ 'px', '%', 'em', 'rem' ],
                        'range' => [
                            'px' => [
                                'min' => -1000,
                                'max' => 1000,
                                'step' => 1,
                            ],
                            '%' => [
                                'min' => -100,
                                'max' => 100,
                            ],
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-product-number' => 'top: {{SIZE}}{{UNIT}};',
                        ],
                    ]
                );

                $this->add_responsive_control(
                    'product_counter_position_grid_view_left',
                    [
                        'label' => esc_html__( 'Left', 'woolentor' ),
                        'type' => Controls_Manager::SLIDER,
                        'size_units' => [ 'px', '%', 'em', 'rem' ],
                        'range' => [
                            'px' => [
                                'min' => -1000,
                                'max' => 1000,
                                'step' => 1,
                            ],
                            '%' => [
                                'min' => -100,
                                'max' => 100,
                            ],
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-product-number' => 'left: {{SIZE}}{{UNIT}};',
                        ],
                    ]
                );
            
            $this->end_popover();

            $this->add_control(
                'product_counter_color_list_view',
                [
                    'label' => esc_html__( 'List View Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'condition' => [
                        'layout' => ['list', 'grid_list_tab'],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-product-number' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'product_counter_typography_list_view',
                    'condition' => [
                        'layout' => ['list', 'grid_list_tab'],
                    ],
                    'fields_options' => [
                        'typography' => [
                            'label' => esc_html__( 'List View Typography', 'woolentor' ),
                        ],
                    ],
                    'selector' => '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-product-number',
                ]
            );

            $this->add_control(
                'product_counter_position_list_view_popover',
                [
                    'label' => esc_html__( 'List View Position', 'woolentor' ),
                    'type' => Controls_Manager::POPOVER_TOGGLE,
                    'label_off' => esc_html__( 'Default', 'woolentor' ),
                    'label_on' => esc_html__( 'Custom', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                    'condition' => [
                        'layout' => ['list', 'grid_list_tab'],
                    ]
                ]
            );

            $this->start_popover();

                $this->add_responsive_control(
                    'product_counter_position_list_view_top',
                    [
                        'label' => esc_html__( 'Top', 'woolentor' ),
                        'type' => Controls_Manager::SLIDER,
                        'size_units' => [ 'px', '%', 'em', 'rem' ],
                        'range' => [
                            'px' => [
                                'min' => -1000,
                                'max' => 1000,
                                'step' => 1,
                            ],
                            '%' => [
                                'min' => -100,
                                'max' => 100,
                            ],
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-product-number' => 'top: {{SIZE}}{{UNIT}};',
                        ],
                    ]
                );

                $this->add_responsive_control(
                    'product_counter_position_list_view_left',
                    [
                        'label' => esc_html__( 'Left', 'woolentor' ),
                        'type' => Controls_Manager::SLIDER,
                        'size_units' => [ 'px', '%', 'em', 'rem' ],
                        'range' => [
                            'px' => [
                                'min' => -1000,
                                'max' => 1000,
                                'step' => 1,
                            ],
                            '%' => [
                                'min' => -100,
                                'max' => 100,
                            ],
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-list-product-number' => 'left: {{SIZE}}{{UNIT}};',
                        ],
                    ]
                );
            
            $this->end_popover();
            

        $this->end_controls_section();
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
                    'default' => 'yes',
                ]
            );

            $this->add_control(
                'grid_description_length',
                [
                    'label' => esc_html__( 'Description length', 'woolentor' ),
                    'type' => Controls_Manager::NUMBER,
                    'default' => 15,
                    'condition' => [
                        'show_grid_description' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'show_product_number',
                [
                    'label' => esc_html__( 'Show Product Number', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Show', 'woolentor' ),
                    'label_off' => esc_html__( 'Hide', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );

            $this->add_control(
                'grid_add_to_cart_text',
                [
                    'label' => esc_html__( 'Add to Cart Text', 'woolentor' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'Discover More', 'woolentor' ),
                    'description' => esc_html__( 'Custom text for add to cart button', 'woolentor' ),
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
                    'default' => 30,
                    'condition' => [
                        'show_list_description' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'show_editorial_badge',
                [
                    'label' => esc_html__( 'Show Featured Badge', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Yes', 'woolentor' ),
                    'label_off' => esc_html__( 'No', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );

            $this->add_control(
                'editorial_badge_label',
                [
                    'label' => esc_html__( 'Featured Badge Label', 'woolentor' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'Featured', 'woolentor' ),
                    'condition' => [
                        'show_editorial_badge' => 'yes',
                    ],
                ]
            );
            
            $this->add_control(
                'editorial_badge_text',
                [
                    'label' => esc_html__( 'Featured Badge Text', 'woolentor' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'Featured', 'woolentor' ),
                    'condition' => [
                        'show_editorial_badge' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'show_product_details',
                [
                    'label' => esc_html__( 'Show Product Details', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Yes', 'woolentor' ),
                    'label_off' => esc_html__( 'No', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                    'description' => esc_html__( 'Shows product attributes like Color, Size, etc.', 'woolentor' ),
                ]
            );

            $this->add_control(
                'show_view_details_button',
                [
                    'label' => esc_html__( 'Show View Details Button', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Yes', 'woolentor' ),
                    'label_off' => esc_html__( 'No', 'woolentor' ),
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
                        'show_view_details_button' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'list_add_to_cart_text',
                [
                    'label' => esc_html__( 'Add to Cart Text', 'woolentor' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'Add to Collection', 'woolentor' ),
                    'description' => esc_html__( 'Custom text for add to cart button', 'woolentor' ),
                ]
            );

            $this->add_control(
                'show_list_product_number',
                [
                    'label' => esc_html__( 'Show Product Number', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Show', 'woolentor' ),
                    'label_off' => esc_html__( 'Hide', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );

        $this->end_controls_section();
    }

    /**
     * Prepare grid settings from Elementor settings
     * Override base method to handle Magazine-specific controls
     */
    protected function prepare_grid_settings( $settings ) {
        // Get base settings first
        $grid_settings = parent::prepare_grid_settings( $settings );

        // Helper function to get value safely
        $get_val = function( $key, $default = null ) use ( $settings ) {
            return isset( $settings[$key] ) ? $settings[$key] : $default;
        };

        // Add Magazine-specific settings.
        $magazine_settings = [
            'widget_name'               => $this->get_name(),
            'widget_id'                 => $this->get_id(),
            'show_grid_description'     => $get_val('show_grid_description') === 'yes',
            'grid_description_length'   => $get_val('grid_description_length', 15),
            'show_product_number'       => $get_val('show_product_number') === 'yes',
            'grid_add_to_cart_text'     => $get_val('grid_add_to_cart_text', esc_html__( 'Discover More', 'woolentor' )),
            'list_add_to_cart_text'     => $get_val('list_add_to_cart_text', esc_html__( 'Add to Collection', 'woolentor' )),
            'show_list_description'     => $get_val('show_list_description') === 'yes',
            'list_description_length'   => $get_val('list_description_length', 30),
            'show_list_product_number'  => $get_val('show_list_product_number') === 'yes',
            'show_editorial_badge'      => $get_val('show_editorial_badge') === 'yes',
            'editorial_badge_text'      => $get_val('editorial_badge_text', esc_html__( 'Featured', 'woolentor' )),
            'editorial_badge_label'     => $get_val('editorial_badge_label', esc_html__( 'Featured', 'woolentor' )),
            'show_product_details'      => $get_val('show_product_details') === 'yes',
            'show_view_details_button'  => $get_val('show_view_details_button') === 'yes',
            'view_details_text'         => $get_val('view_details_text', esc_html__( 'View Details', 'woolentor' )),
        ];

        // Merge all settings.
        $grid_settings = array_merge( $grid_settings, $magazine_settings );

        return apply_filters( 'woolentor_product_grid_magazine_settings', $grid_settings, $settings );
    }
}
