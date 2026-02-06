<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woolentor_Wl_Order_Bump_Widget extends Widget_Base{
    public function get_name() {
        return 'woolentor_order_bump';
    }
    
    public function get_title() {
        return __( 'WL: Order Bump', 'woolentor' );
    }

    public function get_icon() {
        return 'eicon-woocommerce-cross-sells';
    }

    public function get_categories() {
        return [ 'woolentor-addons-pro' ];
    }

    public function get_help_url() {
        return 'https://woolentor.com/documentation/';
    }

    public function get_style_depends(){
        return [];
    }

    public function get_keywords(){
        return ['offer','order','checkout','order bump','woolentor','shoplentor'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'section_general',
            [
                'label' => __( 'General', 'woolentor' ),
            ]
        );
            
            $this->add_control(
                'selected_order_bump',
                [
                    'label'   => __( 'Select Order Bump', 'woolentor' ),
                    'type'    => Controls_Manager::SELECT,
                    'default' => '0',
                    'options' => [ '0' => __( 'Select Order Bump', 'woolentor' ) ] + \Woolentor\Modules\Order_Bump\Manage_Rules::instance()->fetch_offers(true),
                    'label_block' => true
                ]
            );

        $this->end_controls_section(); // General Settings

        // Product Title Style Controls.
        $this->start_controls_section(
            'style_product_title_section',
            [
                'label' => __( 'Product Title', 'woolentor' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

            $this->add_control(
                'title_color',
                [
                    'label' => __( 'Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump-content .wl-title' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'title_typography',
                    'label' => __( 'Typography', 'woolentor' ),
                    'selector' => '{{WRAPPER}} .woolentor-order-bump-content .wl-title',
                ]
            );

            $this->add_control(
                'title_margin',
                [
                    'label' => __( 'Margin', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump-content .wl-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section(); // End Product Title Style Controls.

        // Product Price Style Controls.
        $this->start_controls_section(
            'style_product_price_section',
            [
                'label' => __( 'Product Price', 'woolentor' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

            $this->add_control(
                'heading_regular_price',
                [
                    'label' => __( 'Regular Price', 'woolentor' ),
                    'type' => Controls_Manager::HEADING,
                    'separator' => 'after',
                ]
            );

            $this->add_control(
                'regular_price_color',
                [
                    'label' => __( 'Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump-content .wl-price .price del .woocommerce-Price-amount' => 'color: {{VALUE}};',
                        'body {{WRAPPER}} div.product .woolentor-order-bump-content span.price' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'regular_price_typography',
                    'label' => __( 'Typography', 'woolentor' ),
                    'selector' => '{{WRAPPER}} .woolentor-order-bump-content .wl-price .price del .woocommerce-Price-amount',
                ]
            );

            $this->add_control(
                'heading_sale_price',
                [
                    'label' => __( 'Sale Price', 'woolentor' ),
                    'type' => Controls_Manager::HEADING,
                    'separator' => 'after',
                ]
            );

            $this->add_control(
                'sale_price_color',
                [
                    'label' => __( 'Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump-content .wl-price .woocommerce-Price-amount' => 'color: {{VALUE}};'
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'sale_price_typography',
                    'label' => __( 'Typography', 'woolentor' ),
                    'selector' => '{{WRAPPER}} .woolentor-order-bump-content .wl-price .woocommerce-Price-amount',
                ]
            );

            $this->add_control(
                'price_margin',
                [
                    'label' => __( 'Margin', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump-content .wl-price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section(); // End Product Price Style Controls.

        // Product Content Style Controls.
        $this->start_controls_section(
            'style_product_content_section',
            [
                'label' => __( 'Product Content', 'woolentor' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

            $this->add_control(
                'content_color',
                [
                    'label' => __( 'Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump-content .wl-desc' => 'color: {{VALUE}};',
                        '{{WRAPPER}} .woolentor-order-bump-content .wl-desc p' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'content_typography',
                    'label' => __( 'Typography', 'woolentor' ),
                    'selector' => '{{WRAPPER}} .woolentor-order-bump-content .wl-desc',
                ]
            );

            $this->add_control(
                'content_margin',
                [
                    'label' => __( 'Margin', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump-content .wl-desc' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section(); // End Product Content Style Controls.

        // Image Style Controls.
        $this->start_controls_section(
            'style_image_section',
            [
                'label' => __( 'Product Image', 'woolentor' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'product_image_border',
                    'label' => __( 'Border', 'woolentor' ),
                    'selector' => '{{WRAPPER}} .woolentor-order-bump .wl-image img',
                ]
            );

            $this->add_responsive_control(
                'product_image_border_radius',
                [
                    'label' => __( 'Border Radius', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump .wl-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'product_image_margin',
                [
                    'label' => __( 'Margin', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump .wl-image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section(); // End Image Style Controls.

         // Grab Deal Button Style Controls.
         $this->start_controls_section(
            'style_grab_deal_section',
            [
                'label' => __( 'Grab Deal Button', 'woolentor' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

            $this->add_control(
                'grab_deal_bg_color',
                [
                    'label' => __( 'Background Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump .woolentor-order-bump-action' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'grab_deal_label_color',
                [
                    'label' => __( 'Label Color', 'woolentor' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump-action .wl-checkbox-wrapper label' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'grab_deal_label_typography',
                    'label' => __( 'Typography', 'woolentor' ),
                    'selector' => '{{WRAPPER}} .woolentor-order-bump-action .wl-checkbox-wrapper label',
                ]
            );

            $this->add_control(
                'grab_deal_area_padding',
                [
                    'label' => __( 'Padding', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-order-bump .woolentor-order-bump-action' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section(); // End Product Content Style Controls.

    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        if( empty( $settings['selected_order_bump'] ) ){
            echo esc_html__( 'Please Select Order Bump','woolentor-pro' );
        }else{
            $shortcode_attributes = [
                'id' => $settings['selected_order_bump'],
            ];
            echo woolentor_do_shortcode( 'woolentor_order_bump', $shortcode_attributes );
        }

    }
}