<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woolentor_Wl_Quickview_Product_Image_Widget extends Widget_Base {

    public function get_name() {
        return 'wl-quickview-product-thumbnail-image';
    }

    public function get_title() {
        return __( 'WL: Quickview Product Image', 'woolentor-pro' );
    }

    public function get_icon() {
        return 'eicon-product-images';
    }

    public function get_categories() {
        return array( 'woolentor-addons-pro' );
    }

    public function get_help_url() {
        return 'https://woolentor.com/documentation/';
    }

    public function get_script_depends() {
        return [
            'slick',
            'woolentor-quickview',
        ];
    }

    public function get_keywords(){
        return ['quickview','product quickview','popup'];
    }

    protected function register_controls() {

        // Product Main Image Style
        $this->start_controls_section(
            'product_main_image_style_section',
            [
                'label' => esc_html__( 'Main Image', 'woolentor-pro' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
            
            $this->add_responsive_control(
                'main_margin',
                [
                    'label' => esc_html__( 'Main Image Margin', 'woolentor-pro' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em' ],
                    'selectors' => [
                        '#woolentor-quickview-modal div.product {{WRAPPER}} .woocommerce-product-gallery__image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        '{{WRAPPER}} .woocommerce-product-gallery__image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'main_image_border',
                    'label' => esc_html__( 'Border', 'woolentor-pro' ),
                    'selector' => '#woolentor-quickview-modal div.product {{WRAPPER}} .woocommerce-product-gallery__image img,{{WRAPPER}} .woocommerce-product-gallery__image img',
                ]
            );

        $this->end_controls_section();
        
        // Product Thumbnail Image Style
        $this->start_controls_section(
            'product_thumbnail_image_style_section',
            [
                'label' => esc_html__( 'Thumbnail Image', 'woolentor-pro' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
            
            $this->add_responsive_control(
                'thumbnail_margin',
                [
                    'label' => esc_html__( 'Thumbnail Image Margin', 'woolentor-pro' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-quickview-thumb-single' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'thumbnail_image_border',
                    'label' => esc_html__( 'Border', 'woolentor-pro' ),
                    'selector' => '{{WRAPPER}} .woolentor-quickview-thumbnail-slider .slick-slide img',
                ]
            );

        $this->end_controls_section();


    }

    protected function render() {
        $settings  = $this->get_settings_for_display();

        if( Plugin::instance()->editor->is_edit_mode() ){
            $product = wc_get_product( woolentor_get_last_product_id() );
        } else{
            global $product;
            $product = wc_get_product();
        }

        if ( empty( $product ) ) { return; }


        $image_attr = [
            'thumbnail_layout' => 'slider',
            'product_data' => $product
        ];
        woolentor_get_template( 'quickview-product-images', $image_attr, true, \Woolentor\Modules\QuickView\TEMPLATE_PATH );

        ?>
        <?php if ( Plugin::instance()->editor->is_edit_mode() ) { ?>
            <script>
                ;jQuery(document).ready(function($) {
                    'use strict';
                    $('.woolentor-quickview-main-image-slider').slick({
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        arrows: true,
                        fade: true,
                        asNavFor: '.woolentor-quickview-thumbnail-slider',
                        prevArrow: '<span class="woolentor-quickview-slick-prev">&#8592;</span>',
                        nextArrow: '<span class="woolentor-quickview-slick-next">&#8594;</span>',
                    });
                    $('.woolentor-quickview-thumbnail-slider').slick({
                        slidesToShow: 4,
                        slidesToScroll: 1,
                        asNavFor: '.woolentor-quickview-main-image-slider',
                        dots: false,
                        arrows: true,
                        focusOnSelect: true,
                        prevArrow: '<span class="woolentor-quickview-slick-prev">&#8592;</span>',
                        nextArrow: '<span class="woolentor-quickview-slick-next">&#8594;</span>',
                    });
                });
            </script>
        <?php } ?>
        <?php

    }

}