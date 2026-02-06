<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woolentor_Admin_Init_Pro {

    /**
     * [$_instance]
     * @var null
     */
    private static $_instance = null;

    /**
     * [instance] Initializes a singleton instance
     * @return [Woolentor_Admin_Fields]
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        add_filter('woolentor_admin_fields_vue',[ $this, 'admin_fields' ], 10, 1 );

        // Element tabs admin fields
        add_filter('woolentor_elements_tabs_admin_fields_vue',[ $this, 'elements_tabs_admin_fields' ], 10, 1 );
        add_filter('woolentor_elements_tabs_admin_fields_vue',[ $this, 'elements_tabs_additional_widget_admin_fields' ], 100, 1 );

        // Admin Tabs Menu
        add_filter('woolentor_admin_field_tabs', [$this, 'remove_free_vs_pro'], 10, 1);

        // Template Builder
        add_filter('woolentor_template_menu_tabs',[ $this, 'template_menu_navs' ], 10, 1 );
        add_filter('woolentor_template_types',[ $this, 'template_type' ], 10, 1 );
        
    }

    public function admin_fields( $fields ){

        $fields['woolentor_woo_template_tabs'] = array(

            array(
                'id'  => 'enablecustomlayout',
                'name' => esc_html__( 'Enable / Disable Template Builder', 'woolentor-pro' ),
                'desc'  => esc_html__( 'You can enable/disable template builder from here.', 'woolentor-pro' ),
                'type'  => 'checkbox',
                'default' => 'on'
            ),

            array(
                'id'  => 'shoppageproductlimit',
                'name' => esc_html__( 'Product Limit', 'woolentor-pro' ),
                'desc'  => esc_html__( 'You can handle the product limit for the Shop page limit', 'woolentor-pro' ),
                'min'               => 1,
                'max'               => 100,
                'step'              => '1',
                'type'              => 'number',
                'default'           => '2',
                'sanitize_callback' => 'floatval',
                'condition'         => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'singleproductpage',
                'name'   => esc_html__( 'Single Product Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'You can select a custom template for the product details page layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('single') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productarchivepage',
                'name'   => esc_html__( 'Product Shop Page Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'You can select a custom template for the Shop page layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('shop','archive') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productallarchivepage',
                'name'   => esc_html__( 'Product Archive Page Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'You can select a custom template for the Product Archive page layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('shop','archive') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productcartpage',
                'name'   => esc_html__( 'Cart Page Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'You can select a template for the Cart page layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('cart') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productemptycartpage',
                'name'   => esc_html__( 'Empty Cart Page Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'You can select Custom empty cart page layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('emptycart') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productcheckoutpage',
                'name'   => esc_html__( 'Checkout Page Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'You can select a template for the Checkout page layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('checkout') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productcheckouttoppage',
                'name'   => esc_html__( 'Checkout Page Top Content', 'woolentor-pro' ),
                'desc'    => esc_html__( 'You can checkout top content(E.g: Coupon form, login form etc)', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('checkouttop') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productthankyoupage',
                'name'   => esc_html__( 'Thank You Page Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'Select a template for the Thank you page layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('thankyou') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productmyaccountpage',
                'name'   => esc_html__( 'My Account Page Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'Select a template for the My Account page layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('myaccount') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productmyaccountloginpage',
                'name'   => esc_html__( 'My Account Login page Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'Select a template for the Login page layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('myaccountlogin') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'productquickview',
                'name'   => esc_html__( 'Product Quick View Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'Select a template for the product\'s quick view layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('quickview') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

            array(
                'id'    => 'mini_cart_layout',
                'name'   => esc_html__( 'Mini Cart Template', 'woolentor-pro' ),
                'desc'    => esc_html__( 'Select a template for the mini cart layout', 'woolentor-pro' ),
                'type'    => 'select',
                'default' => '0',
                'options' => [
                    'group'=>[
                        'woolentor' => [
                            'label' => __( 'WooLentor', 'woolentor' ),
                            'options' => function_exists('woolentor_wltemplate_list') ? woolentor_wltemplate_list( array('minicart') ) : null
                        ],
                        'elementor' => [
                            'label' => __( 'Elementor', 'woolentor' ),
                            'options' => woolentor_elementor_template()
                        ]
                    ]
                ],
                'condition' => array( 'key'=>'enablecustomlayout', 'operator'=>'==', 'value'=>'on' )
            ),

        );

        $fields['woolentor_gutenberg_tabs'] = array(

            array(
                'id'    => 'css_add_via',
                'name'   => esc_html__( 'Add CSS through', 'woolentor' ),
                'desc'    => esc_html__( 'Choose how you want to add the newly generated CSS.', 'woolentor' ),
                'type'    => 'select',
                'default' => 'internal',
                'options' => array(
                    'internal' => esc_html__('Internal','woolentor'),
                    'external' => esc_html__('External','woolentor'),
                ),
                'group' => 'settings',
            ),

            array(
                'id'  => 'container_width',
                'name' => esc_html__( 'Container Width', 'woolentor' ),
                'desc'  => esc_html__( 'You can set the container width from here.', 'woolentor' ),
                'min'               => 1,
                'max'               => 10000,
                'step'              => '1',
                'type'              => 'number',
                'default'           => '1140',
                'sanitize_callback' => 'floatval',
                'group' => 'settings',
            ),

            array(
                'id'      => 'general_blocks_heading',
                'heading'  => esc_html__( 'General', 'woolentor' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'    => 'product_tab',
                'name'   => esc_html__( 'Product Tab', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_grid_modern',
                'name'   => esc_html__( 'Product Grid - Modern', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on',
                'documentation' => esc_url('https://woolentor.com/doc/how-to-use-product-grid-modern-layout-in-gutenberg/'),
                'badge'   => [
                    'is_active' => true,
                    'type'      => 'new',
                    'label'     => esc_html__('New','woolentor-pro')
                ]
            ),
            array(
                'id'    => 'product_grid_luxury',
                'name'   => esc_html__( 'Product Grid - Luxury', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on',
                'documentation' => esc_url('https://woolentor.com/doc/how-to-use-product-grid-luxury-layout-in-gutenberg/'),
                'badge'   => [
                    'is_active' => true,
                    'type'      => 'new',
                    'label'     => esc_html__('New','woolentor-pro')
                ]
            ),
            array(
                'id'    => 'product_grid_editorial',
                'name' => esc_html__( 'Product Grid - Editorial', 'woolentor-pro' ),
                'type'    => 'element',
                'default' => 'on',
                'documentation'=>esc_url('https://woolentor.com/doc/how-to-use-product-grid-editorial-layout-in-gutenberg/'),
                'badge'   => [
                    'is_active' => true,
                    'type'      => 'new',
                    'label'     => esc_html__('New','woolentor-pro')
                ]
            ),
            array(
                'id'    => 'product_grid_magazine',
                'name' => esc_html__( 'Product Grid - Magazine', 'woolentor-pro' ),
                'type'    => 'element',
                'default' => 'on',
                'documentation'=>esc_url('https://woolentor.com/doc/how-to-use-product-grid-magazine-layout-in-gutenberg/'),
                'badge'   => [
                    'is_active' => true,
                    'type'      => 'new',
                    'label'     => esc_html__('New','woolentor-pro')
                ]
            ),

            array(
                'id'    => 'product_grid',
                'name'   => esc_html__( 'Product Grid', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'customer_review',
                'name'   => esc_html__( 'Customer Review', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'promo_banner',
                'name'   => esc_html__( 'Promo Banner', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'special_day_offer',
                'name'   => esc_html__( 'Special Day Offer', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'image_marker',
                'name'   => esc_html__( 'Image Marker', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'store_feature',
                'name'   => esc_html__( 'Store Feature', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'brand_logo',
                'name'   => esc_html__( 'Brand Logo', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'category_grid',
                'name'   => esc_html__( 'Category Grid', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'faq',
                'name'   => esc_html__( 'FAQ', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_curvy',
                'name'   => esc_html__( 'Product Curvy', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'archive_title',
                'name'   => esc_html__( 'Archive Title', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'breadcrumbs',
                'name'   => esc_html__( 'Breadcrumbs', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'recently_viewed_products',
                'name'   => esc_html__( 'Recently Viewed Products', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'shop_blocks_heading',
                'heading'  => esc_html__( 'Shop / Archive', 'woolentor' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'    => 'shop_archive_product',
                'name'   => esc_html__( 'Product Archive (Default)', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_filter',
                'name'   => esc_html__( 'Product Filter', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_horizontal_filter',
                'name'   => esc_html__( 'Product Horizontal Filter', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'archive_result_count',
                'name'   => esc_html__( 'Archive Result Count', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'archive_catalog_ordering',
                'name'   => esc_html__( 'Archive Catalog Ordering', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'single_blocks_heading',
                'heading'  => esc_html__( 'Single Product', 'woolentor' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'   => 'product_title',
                'name'  => esc_html__('Product Title','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_price',
                'name'   => esc_html__('Product Price','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'   => 'product_addtocart',
                'name'  => esc_html__('Product Add To Cart','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_short_description',
                'name'   => esc_html__('Product Short Description','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_description',
                'name'   => esc_html__('Product Description','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_rating',
                'name'   => esc_html__('Product Rating','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_image',
                'name'   => esc_html__('Product Image','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_video_gallery',
                'name'   => esc_html__('Product Video Gallery','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_advance_image',
                'name'   => esc_html__('Advance Product Image','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_thumbnails_zoom_image',
                'name'   => esc_html__('Product Image With Zoom','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_meta',
                'name'   => esc_html__('Product Meta','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_additional_info',
                'name'   => esc_html__('Product Additional Info','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_tabs',
                'name'   => esc_html__('Product Tabs','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_stock',
                'name'   => esc_html__('Product Stock','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_qrcode',
                'name'   => esc_html__('Product QR Code','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_related',
                'name'   => esc_html__('Product Related','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_upsell',
                'name'   => esc_html__('Product Upsell','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_reviews',
                'name'   => esc_html__('Product Reviews','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_categories',
                'name'   => esc_html__('Product Categories','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_tags',
                'name'   => esc_html__('Product Tags','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_sku',
                'name'   => esc_html__('Product SKU','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'call_for_price',
                'name'   => esc_html__('Call for Price','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'suggest_price',
                'name'   => esc_html__('Suggest Price','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_social_share',
                'name'   => esc_html__('Product Social Share','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_stock_progressbar',
                'name'   => esc_html__('Stock Progressbar','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_sale_schedule',
                'name'   => esc_html__('Product Sale Schedule','woolentor'),
                'type'    => 'element',
                'default' => 'on'
            ),
            array(
                'id'    => 'product_navigation',
                'name'   => esc_html__('Product Navigation','woolentor'),
                'type'    => 'element',
                'default' => 'on',
            ),

            array(
                'id'      => 'cart_blocks_heading',
                'heading'  => esc_html__( 'Cart', 'woolentor' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),
            array(
                'id'  => 'cart_table',
                'name' => esc_html__( 'Product Cart Table', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'cart_table_list',
                'name' => esc_html__( 'Product Cart Table (List Style)', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'cart_total',
                'name' => esc_html__( 'Product Cart Total', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'corss_sell',
                'name' => esc_html__( 'Product Cross Sell', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'return_to_shop',
                'name' => esc_html__( 'Return To Shop Button', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'cart_empty_message',
                'name' => esc_html__( 'Empty Cart Message', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'checkout_blocks_heading',
                'heading'  => esc_html__( 'Checkout', 'woolentor' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),
            array(
                'id'  => 'checkout_billing_form',
                'name' => esc_html__( 'Checkout Billing Form', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'checkout_shipping_form',
                'name' => esc_html__( 'Checkout Shipping Form', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'checkout_additional_form',
                'name' => esc_html__( 'Checkout Additional Form', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'checkout_coupon_form',
                'name' => esc_html__( 'Checkout Coupon Form', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'checkout_payment',
                'name' => esc_html__( 'Checkout Payment Method', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'checkout_shipping_method',
                'name' => esc_html__( 'Checkout Shipping Method', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'checkout_order_review',
                'name' => esc_html__( 'Checkout Order Review', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'checkout_login_form',
                'name' => esc_html__( 'Checkout Login Form', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'myaccount_blocks_heading',
                'heading'  => esc_html__( 'My Account', 'woolentor' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),
            array(
                'id'  => 'my_account',
                'name' => esc_html__( 'My Account', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'my_account_navigation',
                'name' => esc_html__( 'My Account Navigation', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'my_account_dashboard',
                'name' => esc_html__( 'My Account Dashboard', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'my_account_download',
                'name' => esc_html__( 'My Account Download', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'my_account_edit',
                'name' => esc_html__( 'My Account Edit', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),
            array(
                'id'  => 'my_account_address',
                'name' => esc_html__( 'My Account Address', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),
            array(
                'id'  => 'my_account_order',
                'name' => esc_html__( 'My Account Order', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),
            array(
                'id'  => 'my_account_logout',
                'name' => esc_html__( 'My Account Logout', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),
            array(
                'id'  => 'my_account_login_form',
                'name' => esc_html__( 'Login Form', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),
            array(
                'id'  => 'my_account_registration_form',
                'name' => esc_html__( 'Registration Form', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),
            array(
                'id'  => 'my_account_lost_password',
                'name' => esc_html__( 'Lost Password Form', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),
            array(
                'id'  => 'my_account_reset_password',
                'name' => esc_html__( 'Reset Password Form', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),

            array(
                'id'      => 'thankyou_blocks_heading',
                'heading'  => esc_html__( 'Thank You', 'woolentor' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),
            array(
                'id'  => 'thankyou_order',
                'name' => esc_html__( 'Thank You Order', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),
            array(
                'id'  => 'thankyou_address_details',
                'name' => esc_html__( 'Thank You Address', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),
            array(
                'id'  => 'thankyou_order_details',
                'name' => esc_html__( 'Thank You Order Details', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ),

            array(
                'id'      => 'additional_blocks_heading',
                'heading'  => esc_html__( 'Additional', 'woolentor' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            )

        );

        $fields['woolentor_elements_tabs'] = apply_filters( 'woolentor_elements_tabs_admin_fields_vue', array() );

        $fields['woolentor_others_tabs'] = array(

            array(
                'id'     => 'woolentor_rename_label_tabs',
                'name'    => esc_html__( 'Rename Label', 'woolentor' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_rename_label_tabs',
                'option_id'=> 'enablerenamelabel',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/change-woocommerce-text/'),
                'setting_fields' => array(
                    
                    array(
                        'id'  => 'enablerenamelabel',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can enable / disable rename label from here.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class'   =>'enablerenamelabel woolentor-action-field-left',
                    ),
    
                    array(
                        'id'      => 'shop_page_heading',
                        'heading'  => esc_html__( 'Shop Page', 'woolentor-pro' ),
                        'type'      => 'title'
                    ),
                    
                    array(
                        'id'        => 'wl_shop_add_to_cart_txt',
                        'name'       => esc_html__( 'Add to Cart Button Text', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Change the Add to Cart button text for the Shop page.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'placeholder' => esc_html__( 'Add to Cart', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
    
                    array(
                        'id'      => 'product_details_page_heading',
                        'heading'  => esc_html__( 'Product Details Page', 'woolentor-pro' ),
                        'type'      => 'title',
                    ),
    
                    array(
                        'id'        => 'wl_add_to_cart_txt',
                        'name'       => esc_html__( 'Add to Cart Button Text', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Change the Add to Cart button text for the Product details page.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'placeholder' => esc_html__( 'Add to Cart', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
    
                    array(
                        'id'        => 'wl_description_tab_menu_title',
                        'name'       => esc_html__( 'Description', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Change the tab title for the product description.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'placeholder' => esc_html__( 'Description', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    
                    array(
                        'id'        => 'wl_additional_information_tab_menu_title',
                        'name'       => esc_html__( 'Additional Information', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Change the tab title for the product additional information', 'woolentor-pro' ),
                        'type'        => 'text',
                        'placeholder' => esc_html__( 'Additional information', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    
                    array(
                        'id'        => 'wl_reviews_tab_menu_title',
                        'name'       => esc_html__( 'Reviews', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Change the tab title for the product review', 'woolentor-pro' ),
                        'type'        => 'text',
                        'placeholder' => __( 'Reviews', 'woolentor-pro' ),
                        'class'       =>'woolentor-action-field-left',
                    ),
    
                    array(
                        'id'      => 'checkout_page_heading',
                        'heading'  => esc_html__( 'Checkout Page', 'woolentor-pro' ),
                        'type'      => 'title'
                    ),
    
                    array(
                        'id'        => 'wl_checkout_placeorder_btn_txt',
                        'name'       => esc_html__( 'Place order', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Change the label for the Place order field.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'placeholder' => esc_html__( 'Place order', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                )
            ),

            array(
                'id'     => 'woolentor_sales_notification_tabs',
                'name'    => esc_html__( 'Sales Notification', 'woolentor' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_sales_notification_tabs',
                'option_id'=> 'enableresalenotification',
                'require_settings'=> true,
                'documentation' => esc_url('https://woolentor.com/doc/sales-notification-for-woocommerce/'),
                'setting_fields' => array(

                    array(
                        'id'  => 'enableresalenotification',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor' ),
                        'desc'  => esc_html__( 'You can enable / disable sales notification from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left',
                        'label_on' => __( 'ON', 'woolentor' ),
                        'label_off' => __( 'OFF', 'woolentor' ),
                    ),
                    
                    array(
                        'id'    => 'notification_content_type',
                        'name'   => esc_html__( 'Notification Content Type', 'woolentor' ),
                        'desc'    => esc_html__( 'Select Content Type', 'woolentor' ),
                        'type'    => 'radio',
                        'default' => 'actual',
                        'options' => array(
                            'actual' => esc_html__('Real','woolentor'),
                            'fakes'  => esc_html__('Manual','woolentor'),
                        ),
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'noification_fake_data',
                        'name'   => esc_html__( 'Choose Template', 'woolentor' ),
                        'desc'    => esc_html__( 'Choose template for manual notification.', 'woolentor' ),
                        'type'    => 'multiselect',
                        'default' => '',
                        'options' => woolentor_elementor_template(),
                        'condition' => array(
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'fakes'
                        ),
                        'placeholder' => esc_html__( 'Select Template', 'woolentor' ),
                    ),
    
                    array(
                        'id'    => 'notification_pos',
                        'name'   => esc_html__( 'Position', 'woolentor' ),
                        'desc'    => esc_html__( 'Set the position of the Sales Notification Position on frontend.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => 'bottomleft',
                        'options' => array(
                            'topleft'       => esc_html__( 'Top Left','woolentor' ),
                            'topright'      => esc_html__( 'Top Right','woolentor' ),
                            'bottomleft'    => esc_html__( 'Bottom Left','woolentor' ),
                            'bottomright'   => esc_html__( 'Bottom Right','woolentor' ),
                        ),
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'notification_layout',
                        'name'   => esc_html__( 'Image Position', 'woolentor' ),
                        'desc'    => esc_html__( 'Set the image position of the notification.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => 'imageleft',
                        'options' => array(
                            'imageleft'   => esc_html__( 'Image Left','woolentor' ),
                            'imageright'  => esc_html__( 'Image Right','woolentor' ),
                        ),
                        'condition' => array(
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ),
                        'class'   => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'notification_timing_area_title',
                        'heading'=> esc_html__( 'Notification Timing', 'woolentor' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'class'   => 'element_section_title_area',
                    ),
    
                    array(
                        'id'    => 'notification_loadduration',
                        'name'   => esc_html__( 'First loading time', 'woolentor' ),
                        'desc'    => esc_html__( 'When to start notification load duration.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => '3',
                        'options' => array(
                            '2'    => esc_html__( '2 seconds','woolentor' ),
                            '3'    => esc_html__( '3 seconds','woolentor' ),
                            '4'    => esc_html__( '4 seconds','woolentor' ),
                            '5'    => esc_html__( '5 seconds','woolentor' ),
                            '6'    => esc_html__( '6 seconds','woolentor' ),
                            '7'    => esc_html__( '7 seconds','woolentor' ),
                            '8'    => esc_html__( '8 seconds','woolentor' ),
                            '9'    => esc_html__( '9 seconds','woolentor' ),
                            '10'   => esc_html__( '10 seconds','woolentor' ),
                            '20'   => esc_html__( '20 seconds','woolentor' ),
                            '30'   => esc_html__( '30 seconds','woolentor' ),
                            '40'   => esc_html__( '40 seconds','woolentor' ),
                            '50'   => esc_html__( '50 seconds','woolentor' ),
                            '60'   => esc_html__( '1 minute','woolentor' ),
                            '90'   => esc_html__( '1.5 minutes','woolentor' ),
                            '120'  => esc_html__( '2 minutes','woolentor' ),
                        ),
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'notification_time_showing',
                        'name'   => esc_html__( 'Notification showing time', 'woolentor' ),
                        'desc'    => esc_html__( 'How long to keep the notification.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => '4',
                        'options' => array(
                            '2'   => esc_html__( '2 seconds','woolentor' ),
                            '4'   => esc_html__( '4 seconds','woolentor' ),
                            '5'   => esc_html__( '5 seconds','woolentor' ),
                            '6'   => esc_html__( '6 seconds','woolentor' ),
                            '7'   => esc_html__( '7 seconds','woolentor' ),
                            '8'   => esc_html__( '8 seconds','woolentor' ),
                            '9'   => esc_html__( '9 seconds','woolentor' ),
                            '10'  => esc_html__( '10 seconds','woolentor' ),
                            '20'  => esc_html__( '20 seconds','woolentor' ),
                            '30'  => esc_html__( '30 seconds','woolentor' ),
                            '40'  => esc_html__( '40 seconds','woolentor' ),
                            '50'  => esc_html__( '50 seconds','woolentor' ),
                            '60'  => esc_html__( '1 minute','woolentor' ),
                            '90'  => esc_html__( '1.5 minutes','woolentor' ),
                            '120' => esc_html__( '2 minutes','woolentor' ),
                        ),
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'notification_time_int',
                        'name'   => esc_html__( 'Time Interval', 'woolentor' ),
                        'desc'    => esc_html__( 'Set the interval time between notifications.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => '4',
                        'options' => array(
                            '2'   => esc_html__( '2 seconds','woolentor' ),
                            '4'   => esc_html__( '4 seconds','woolentor' ),
                            '5'   => esc_html__( '5 seconds','woolentor' ),
                            '6'   => esc_html__( '6 seconds','woolentor' ),
                            '7'   => esc_html__( '7 seconds','woolentor' ),
                            '8'   => esc_html__( '8 seconds','woolentor' ),
                            '9'   => esc_html__( '9 seconds','woolentor' ),
                            '10'  => esc_html__( '10 seconds','woolentor' ),
                            '20'  => esc_html__( '20 seconds','woolentor' ),
                            '30'  => esc_html__( '30 seconds','woolentor' ),
                            '40'  => esc_html__( '40 seconds','woolentor' ),
                            '50'  => esc_html__( '50 seconds','woolentor' ),
                            '60'  => esc_html__( '1 minute','woolentor' ),
                            '90'  => esc_html__( '1.5 minutes','woolentor' ),
                            '120' => esc_html__( '2 minutes','woolentor' ),
                        ),
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'notification_product_display_option_title',
                        'heading'=> esc_html__( 'Product Query Option', 'woolentor' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class'   => 'element_section_title_area',
                    ),
    
                    array(
                        'id'              => 'notification_limit',
                        'name'             => esc_html__( 'Limit', 'woolentor' ),
                        'desc'              => esc_html__( 'Set the number of notifications to display.', 'woolentor' ),
                        'min'               => 1,
                        'max'               => 100,
                        'default'           => '5',
                        'step'              => '1',
                        'type'              => 'number',
                        'sanitize_callback' => 'number',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class'       => 'woolentor-action-field-left',
                    ),
    
                    array(
                        'id'  => 'showallproduct',
                        'name' => esc_html__( 'Show/Display all products from each order', 'woolentor' ),
                        'desc'  => esc_html__( 'Manage show all product from each order.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class'   => 'woolentor-action-field-left',
                    ),
    
                    array(
                        'id'    => 'notification_uptodate',
                        'name'   => esc_html__( 'Order Upto', 'woolentor' ),
                        'desc'    => esc_html__( 'Do not show purchases older than.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => '7',
                        'options' => array(
                            '1'   => esc_html__( '1 day','woolentor' ),
                            '2'   => esc_html__( '2 days','woolentor' ),
                            '3'   => esc_html__( '3 days','woolentor' ),
                            '4'   => esc_html__( '4 days','woolentor' ),
                            '5'   => esc_html__( '5 days','woolentor' ),
                            '6'   => esc_html__( '6 days','woolentor' ),
                            '7'   => esc_html__( '1 week','woolentor' ),
                            '10'  => esc_html__( '10 days','woolentor' ),
                            '14'  => esc_html__( '2 weeks','woolentor' ),
                            '21'  => esc_html__( '3 weeks','woolentor' ),
                            '28'  => esc_html__( '4 weeks','woolentor' ),
                            '35'  => esc_html__( '5 weeks','woolentor' ),
                            '42'  => esc_html__( '6 weeks','woolentor' ),
                            '49'  => esc_html__( '7 weeks','woolentor' ),
                            '56'  => esc_html__( '8 weeks','woolentor' ),
                        ),
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'    => 'notification_display_item_option_title',
                        'heading'=> esc_html__( 'Display Item and Custom Label', 'woolentor-pro' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class'   => 'element_section_title_area',
                    ),
                    array(
                        'id'  => 'show_buyer_name',
                        'name' => esc_html__( 'Show Buyer Name', 'woolentor' ),
                        'desc'  => esc_html__( 'You can display / hide Buyer Name from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class'   => 'woolentor-action-field-left',
                    ),
                    array(
                        'id'  => 'show_city',
                        'name' => esc_html__( 'Show City', 'woolentor' ),
                        'desc'  => esc_html__( 'You can display / hide city from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class'   => 'woolentor-action-field-left',
                    ),
                    array(
                        'id'  => 'show_state',
                        'name' => esc_html__( 'Show State', 'woolentor' ),
                        'desc'  => esc_html__( 'You can display / hide state from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class'   => 'woolentor-action-field-left',
                    ),
                    array(
                        'id'  => 'show_country',
                        'name' => esc_html__( 'Show Country', 'woolentor' ),
                        'desc'  => esc_html__( 'You can display / hide country from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class'   => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'purchased_by',
                        'name'       => esc_html__( 'Purchased By Label', 'woolentor' ),
                        'desc'        => esc_html__( 'You can insert a label for the purchased by text.', 'woolentor' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'By', 'woolentor-pro' ),
                        'placeholder' => esc_html__( 'By', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left'
                    ),
                    array(
                        'id'        => 'price_prefix',
                        'name'       => esc_html__( 'Price Label', 'woolentor' ),
                        'desc'        => esc_html__( 'You can insert a label for the price.', 'woolentor' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'Price :', 'woolentor-pro' ),
                        'placeholder' => esc_html__( 'Price :', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'notification_animation_area_title',
                        'heading'=> esc_html__( 'Animation', 'woolentor' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'class'   => 'element_section_title_area',
                    ),
    
                    array(
                        'id'    => 'notification_inanimation',
                        'name'   => esc_html__( 'Animation In', 'woolentor' ),
                        'desc'    => esc_html__( 'Choose entrance animation.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => 'fadeInLeft',
                        'options' => array(
                            'bounce'            => esc_html__( 'bounce','woolentor' ),
                            'flash'             => esc_html__( 'flash','woolentor' ),
                            'pulse'             => esc_html__( 'pulse','woolentor' ),
                            'rubberBand'        => esc_html__( 'rubberBand','woolentor' ),
                            'shake'             => esc_html__( 'shake','woolentor' ),
                            'swing'             => esc_html__( 'swing','woolentor' ),
                            'tada'              => esc_html__( 'tada','woolentor' ),
                            'wobble'            => esc_html__( 'wobble','woolentor' ),
                            'jello'             => esc_html__( 'jello','woolentor' ),
                            'heartBeat'         => esc_html__( 'heartBeat','woolentor' ),
                            'bounceIn'          => esc_html__( 'bounceIn','woolentor' ),
                            'bounceInDown'      => esc_html__( 'bounceInDown','woolentor' ),
                            'bounceInLeft'      => esc_html__( 'bounceInLeft','woolentor' ),
                            'bounceInRight'     => esc_html__( 'bounceInRight','woolentor' ),
                            'bounceInUp'        => esc_html__( 'bounceInUp','woolentor' ),
                            'fadeIn'            => esc_html__( 'fadeIn','woolentor' ),
                            'fadeInDown'        => esc_html__( 'fadeInDown','woolentor' ),
                            'fadeInDownBig'     => esc_html__( 'fadeInDownBig','woolentor' ),
                            'fadeInLeft'        => esc_html__( 'fadeInLeft','woolentor' ),
                            'fadeInLeftBig'     => esc_html__( 'fadeInLeftBig','woolentor' ),
                            'fadeInRight'       => esc_html__( 'fadeInRight','woolentor' ),
                            'fadeInRightBig'    => esc_html__( 'fadeInRightBig','woolentor' ),
                            'fadeInUp'          => esc_html__( 'fadeInUp','woolentor' ),
                            'fadeInUpBig'       => esc_html__( 'fadeInUpBig','woolentor' ),
                            'flip'              => esc_html__( 'flip','woolentor' ),
                            'flipInX'           => esc_html__( 'flipInX','woolentor' ),
                            'flipInY'           => esc_html__( 'flipInY','woolentor' ),
                            'lightSpeedIn'      => esc_html__( 'lightSpeedIn','woolentor' ),
                            'rotateIn'          => esc_html__( 'rotateIn','woolentor' ),
                            'rotateInDownLeft'  => esc_html__( 'rotateInDownLeft','woolentor' ),
                            'rotateInDownRight' => esc_html__( 'rotateInDownRight','woolentor' ),
                            'rotateInUpLeft'    => esc_html__( 'rotateInUpLeft','woolentor' ),
                            'rotateInUpRight'   => esc_html__( 'rotateInUpRight','woolentor' ),
                            'slideInUp'         => esc_html__( 'slideInUp','woolentor' ),
                            'slideInDown'       => esc_html__( 'slideInDown','woolentor' ),
                            'slideInLeft'       => esc_html__( 'slideInLeft','woolentor' ),
                            'slideInRight'      => esc_html__( 'slideInRight','woolentor' ),
                            'zoomIn'            => esc_html__( 'zoomIn','woolentor' ),
                            'zoomInDown'        => esc_html__( 'zoomInDown','woolentor' ),
                            'zoomInLeft'        => esc_html__( 'zoomInLeft','woolentor' ),
                            'zoomInRight'       => esc_html__( 'zoomInRight','woolentor' ),
                            'zoomInUp'          => esc_html__( 'zoomInUp','woolentor' ),
                            'hinge'             => esc_html__( 'hinge','woolentor' ),
                            'jackInTheBox'      => esc_html__( 'jackInTheBox','woolentor' ),
                            'rollIn'            => esc_html__( 'rollIn','woolentor' ),
                            'rollOut'           => esc_html__( 'rollOut','woolentor' ),
                        ),
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'    => 'notification_outanimation',
                        'name'   => esc_html__( 'Animation Out', 'woolentor' ),
                        'desc'    => esc_html__( 'Choose exit animation.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => 'fadeOutRight',
                        'options' => array(
                            'bounce'             => esc_html__( 'bounce','woolentor' ),
                            'flash'              => esc_html__( 'flash','woolentor' ),
                            'pulse'              => esc_html__( 'pulse','woolentor' ),
                            'rubberBand'         => esc_html__( 'rubberBand','woolentor' ),
                            'shake'              => esc_html__( 'shake','woolentor' ),
                            'swing'              => esc_html__( 'swing','woolentor' ),
                            'tada'               => esc_html__( 'tada','woolentor' ),
                            'wobble'             => esc_html__( 'wobble','woolentor' ),
                            'jello'              => esc_html__( 'jello','woolentor' ),
                            'heartBeat'          => esc_html__( 'heartBeat','woolentor' ),
                            'bounceOut'          => esc_html__( 'bounceOut','woolentor' ),
                            'bounceOutDown'      => esc_html__( 'bounceOutDown','woolentor' ),
                            'bounceOutLeft'      => esc_html__( 'bounceOutLeft','woolentor' ),
                            'bounceOutRight'     => esc_html__( 'bounceOutRight','woolentor' ),
                            'bounceOutUp'        => esc_html__( 'bounceOutUp','woolentor' ),
                            'fadeOut'            => esc_html__( 'fadeOut','woolentor' ),
                            'fadeOutDown'        => esc_html__( 'fadeOutDown','woolentor' ),
                            'fadeOutDownBig'     => esc_html__( 'fadeOutDownBig','woolentor' ),
                            'fadeOutLeft'        => esc_html__( 'fadeOutLeft','woolentor' ),
                            'fadeOutLeftBig'     => esc_html__( 'fadeOutLeftBig','woolentor' ),
                            'fadeOutRight'       => esc_html__( 'fadeOutRight','woolentor' ),
                            'fadeOutRightBig'    => esc_html__( 'fadeOutRightBig','woolentor' ),
                            'fadeOutUp'          => esc_html__( 'fadeOutUp','woolentor' ),
                            'fadeOutUpBig'       => esc_html__( 'fadeOutUpBig','woolentor' ),
                            'flip'               => esc_html__( 'flip','woolentor' ),
                            'flipOutX'           => esc_html__( 'flipOutX','woolentor' ),
                            'flipOutY'           => esc_html__( 'flipOutY','woolentor' ),
                            'lightSpeedOut'      => esc_html__( 'lightSpeedOut','woolentor' ),
                            'rotateOut'          => esc_html__( 'rotateOut','woolentor' ),
                            'rotateOutDownLeft'  => esc_html__( 'rotateOutDownLeft','woolentor' ),
                            'rotateOutDownRight' => esc_html__( 'rotateOutDownRight','woolentor' ),
                            'rotateOutUpLeft'    => esc_html__( 'rotateOutUpLeft','woolentor' ),
                            'rotateOutUpRight'   => esc_html__( 'rotateOutUpRight','woolentor' ),
                            'slideOutUp'         => esc_html__( 'slideOutUp','woolentor' ),
                            'slideOutDown'       => esc_html__( 'slideOutDown','woolentor' ),
                            'slideOutLeft'       => esc_html__( 'slideOutLeft','woolentor' ),
                            'slideOutRight'      => esc_html__( 'slideOutRight','woolentor' ),
                            'zoomOut'            => esc_html__( 'zoomOut','woolentor' ),
                            'zoomOutDown'        => esc_html__( 'zoomOutDown','woolentor' ),
                            'zoomOutLeft'        => esc_html__( 'zoomOutLeft','woolentor' ),
                            'zoomOutRight'       => esc_html__( 'zoomOutRight','woolentor' ),
                            'zoomOutUp'          => esc_html__( 'zoomOutUp','woolentor' ),
                            'hinge'              => esc_html__( 'hinge','woolentor' ),
                        ),
                        'class' => 'woolentor-action-field-left'
                    ),
                    
                    array(
                        'id'    => 'notification_style_area_title',
                        'heading'=> esc_html__( 'Style', 'woolentor' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'class' => 'element_section_title_area',
                    ),
    
                    array(
                        'id'        => 'notification_width',
                        'name'       => esc_html__( 'Width', 'woolentor' ),
                        'desc'        => esc_html__( 'You can handle the sales notification width.', 'woolentor' ),
                        'type'        => 'text',
                        'default'     => esc_html__( '550px', 'woolentor' ),
                        'placeholder' => esc_html__( '550px', 'woolentor' ),
                        'class'       => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'        => 'notification_mobile_width',
                        'name'       => esc_html__( 'Width for mobile', 'woolentor' ),
                        'desc'        => esc_html__( 'You can handle the sales notification width.', 'woolentor' ),
                        'type'        => 'text',
                        'default'     => esc_html__( '90%', 'woolentor' ),
                        'placeholder' => esc_html__( '90%', 'woolentor' ),
                        'class'       => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'  => 'background_color',
                        'name' => esc_html__( 'Background Color', 'woolentor' ),
                        'desc'  => esc_html__( 'Set the background color of the sales notification.', 'woolentor' ),
                        'type'  => 'color',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class' => 'woolentor-action-field-left',
                        'size' => 'large',
                    ),
    
                    array(
                        'id'  => 'heading_color',
                        'name' => esc_html__( 'Heading Color', 'woolentor' ),
                        'desc'  => esc_html__( 'Set the heading color of the sales notification.', 'woolentor' ),
                        'type'  => 'color',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class' => 'woolentor-action-field-left',
                        'size' => 'large',
                    ),
    
                    array(
                        'id'  => 'content_color',
                        'name' => esc_html__( 'Content Color', 'woolentor' ),
                        'desc'  => esc_html__( 'Set the content color of the sales notification.', 'woolentor' ),
                        'type'  => 'color',
                        'condition' => [
                            'key' => 'notification_content_type',
                            'operator' => '==',
                            'value' => 'actual'
                        ],
                        'class' => 'woolentor-action-field-left',
                        'size' => 'large',
                    ),
    
                    array(
                        'id'  => 'cross_color',
                        'name' => esc_html__( 'Cross Icon Color', 'woolentor' ),
                        'desc'  => esc_html__( 'Set the cross icon color of the sales notification.', 'woolentor' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                        'size' => 'large',
                    ),

                )
            ),

            array(
                'id'     => 'woolentor_shopify_checkout_settings',
                'name'    => esc_html__( 'Shopify Style Checkout', 'woolentor' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_shopify_checkout_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/how-to-make-woocommerce-checkout-like-shopify/'),
                'setting_fields' => array(

                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor' ),
                        'desc'  => esc_html__( 'You can enable / disable shopify style checkout page from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'logo',
                        'name'   => esc_html__( 'Logo', 'woolentor' ),
                        'desc'    => esc_html__( 'You can upload your logo for shopify style checkout page from here.', 'woolentor' ),
                        'type'    => 'imageupload',
                        'options' => [
                            'button_label'        => esc_html__( 'Upload', 'woolentor' ),   
                            'button_remove_label' => esc_html__( 'Remove', 'woolentor' ),   
                        ],
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'logo_page',
                        'name'       => esc_html__( 'Logo URL', 'woolentor' ),
                        'desc'        => esc_html__( 'Link your logo to an existing page or a custom URL.', 'woolentor' ),
                        'type'        => 'select',
                        'options'     => (['custom'=> esc_html__( 'Custom URL', 'woolentor' )] + woolentor_post_name( 'page', ['limit'=>-1] )),
                        'default'     => '0',
                        'condition'   => array( 'key'=>'logo','operator'=> '!=','value'=> '' ),
                        'class'       => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'logo_custom_url',
                        'name'       => esc_html__( 'Custom URL', 'woolentor' ),
                        'desc'        => esc_html__( 'Insert a custom URL for the logo.', 'woolentor' ),
                        'type'        => 'text',
                        'placeholder' => esc_html__( 'your-domain.com', 'woolentor' ),
                        'condition'   => array( 'key'=>'logo_page','operator'=> '==', 'value'=>'custom' ),
                        'class'       => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'custommenu',
                        'name'   => esc_html__( 'Bottom Menu', 'woolentor' ),
                        'desc'    => esc_html__( 'You can choose menu for shopify style checkout page.', 'woolentor' ),
                        'type'    => 'select',
                        'default' => '0',
                        'options' => array( '0'=> esc_html__('Select Menu','woolentor') ) + woolentor_get_all_create_menus(),
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'show_phone',
                        'name'   => esc_html__( 'Show Phone Number Field', 'woolentor' ),
                        'desc'    => esc_html__( 'Show the Phone Number Field.', 'woolentor' ),
                        'type'    => 'checkbox',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'show_company',
                        'name'   => esc_html__( 'Show Company Name Field', 'woolentor' ),
                        'desc'    => esc_html__( 'Show the Company Name Field.', 'woolentor' ),
                        'type'    => 'checkbox',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'hide_cart_nivigation',
                        'name'   => esc_html__( 'Hide Cart Navigation', 'woolentor' ),
                        'desc'    => esc_html__( 'Hide the "Cart" menu and "Return to cart" button.', 'woolentor' ),
                        'type'    => 'checkbox',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'hide_shipping_step',
                        'name'   => esc_html__( 'Hide Shipping Step', 'woolentor' ),
                        'desc'    => esc_html__( 'Turn it ON to hide the "Shipping" Step.', 'woolentor' ),
                        'type'    => 'checkbox',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'customize_labels',
                        'name'       => esc_html__( 'Rename Labels?', 'woolentor' ),
                        'desc'        => esc_html__( 'Enable it to customize labels of the checkout page.', 'woolentor' ),
                        'type'        => 'checkbox',
                        'class'       => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'labels_list',
                        'name'       => esc_html__( 'Labels', 'woolentor' ),
                        'type'        => 'repeater',
                        'title_field' => 'select_tab',
                        'condition'   => array( 'key'=>'customize_labels', 'operator'=>'==', 'value'=>'on' ),
                        'max_items' => '3',
                        'options' => [
                            'button_label' => esc_html__( 'Add Custom Label', 'woolentor' ),
                        ],
                        'fields'  => [

                            array(
                                'id'    => 'select_tab',
                                'name'   => esc_html__( 'Select Tab', 'woolentor' ),
                                'desc'    => esc_html__( 'Select the tab for which you want to change the labels. ', 'woolentor' ),
                                'type'    => 'select',
                                'class'   => 'woolentor-action-field-left',
                                'default' => 'information',
                                'options' => array(
                                    'information'  => esc_html__('Information','woolentor'),
                                    'shipping'      => esc_html__('Shipping','woolentor'),
                                    'payment'       => esc_html__('Payment','woolentor'),
                                ),
                            ),

                            array(
                                'id'        => 'tab_label',
                                'name'       => esc_html__( 'Tab Label', 'woolentor' ),
                                'type'        => 'text',
                                'class'       => 'woolentor-action-field-left',
                            ),

                            array(
                                'id'        => 'label_1',
                                'name'       => esc_html__( 'Button Label One', 'woolentor' ),
                                'type'        => 'text',
                                'class'       => 'woolentor-action-field-left',
                            ),

                            array(
                                'id'        => 'label_2',
                                'name'       => esc_html__( 'Button Label Two', 'woolentor' ),
                                'type'        => 'text',
                                'class'       => 'woolentor-action-field-left',
                            ),

                        ]
                    ),
                    
                )

            ),

            array(
                'id'     => 'woolentor_flash_sale_settings',
                'name'    => esc_html__( 'Flash Sale Countdown', 'woolentor' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_flash_sale_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/enable-sales-countdown-timer-in-woocommerce/'),
                'setting_fields' => array(

                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor' ),
                        'desc'  => esc_html__( 'You can enable / disable flash sale from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'deals',
                        'name'       => esc_html__( 'Sale Events', 'woolentor' ),
                        'type'        => 'repeater',
                        'title_field' => 'title',
                        'fields'  => [

                            array(
                                'id'        => 'title',
                                'name'       => esc_html__( 'Event Name', 'woolentor' ),
                                'type'        => 'text',
                                'class'       => 'woolentor-action-field-left',
                                'condition' => array( 'key'=>'status','operator'=> '==', 'value'=>'on' ),
                            ),

                            array(
                                'id'        => 'status',
                                'name'       => esc_html__( 'Enable', 'woolentor' ),
                                'desc'        => esc_html__( 'Enable / Disable', 'woolentor' ),
                                'type'        => 'checkbox',
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'start_date',
                                'name'       => esc_html__( 'Valid From', 'woolentor' ),
                                'desc'        => __( 'The date and time the event should be enabled. Please set time based on your server time settings. Current Server Date / Time: '. current_time('Y M d'), 'woolentor' ),
                                'type'        => 'date',
                                'condition' => array( 'key'=>'status','operator'=> '==', 'value'=>'on' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'end_date',
                                'name'       => esc_html__( 'Valid To', 'woolentor' ),
                                'desc'        => esc_html__( 'The date and time the event should be disabled.', 'woolentor' ),
                                'type'        => 'date',
                                'condition' => array( 'key'=>'status','operator'=> '==', 'value'=>'on' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'apply_on_all_products',
                                'name'       => esc_html__( 'Apply On All Products', 'woolentor' ),
                                'type'        => 'checkbox',
                                'default'     => 'off',
                                'condition'   => array( 'key'=>'status','operator'=> '==', 'value'=>'on' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'categories',
                                'name'       => esc_html__( 'Select Categories', 'woolentor' ),
                                'desc'        => esc_html__( 'Select the categories in which products the discount will be applied.', 'woolentor' ),
                                'type'        => 'multiselect',
                                'convertnumber' => true,
                                'options'     => woolentor_taxonomy_list('product_cat','term_id'),
                                'condition'   => array( 'key'=>'status|apply_on_all_products', 'operator'=>'==|==', 'value'=>'on|off' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'products',
                                'name'       => esc_html__( 'Select Products', 'woolentor' ),
                                'desc'        => esc_html__( 'Select individual products in which the discount will be applied.', 'woolentor' ),
                                'type'        => 'multiselect',
                                'convertnumber' => true,
                                'options'     => woolentor_post_name( 'product' ),
                                'condition'   => array( 'key'=>'status|apply_on_all_products', 'operator'=>'==|==', 'value'=>'on|off' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'exclude_products',
                                'name'       => esc_html__( 'Exclude Products', 'woolentor' ),
                                'type'        => 'multiselect',
                                'convertnumber' => true,
                                'options'     => woolentor_post_name( 'product' ),
                                'condition'   => array( 'key'=>'status','operator'=> '==', 'value'=>'on' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'discount_type',
                                'name'       => esc_html__( 'Discount Type', 'woolentor' ),
                                'type'        => 'select',
                                'default'     => 'percentage_discount',
                                'options'     => array(
                                    'fixed_discount'      => esc_html__( 'Fixed Discount', 'woolentor' ),
                                    'percentage_discount' => esc_html__( 'Percentage Discount', 'woolentor' ),
                                    'fixed_price'         => esc_html__( 'Fixed Price', 'woolentor' ),
                                ),
                                'condition'   => array( 'key'=>'status','operator'=> '==', 'value'=>'on' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'discount_value',
                                'name' => esc_html__( 'Discount Value', 'woolentor-pro' ),
                                'min'               => 0.0,
                                'step'              => 0.01,
                                'type'              => 'number',
                                'default'           => '50',
                                'sanitize_callback' => 'floatval',
                                'condition'         => array( 'key'=>'status','operator'=> '==', 'value'=>'on' ),
                                'class'             => 'woolentor-action-field-left',
                            ),

                            array(
                                'id'        => 'apply_discount_only_for_registered_customers',
                                'name'       => esc_html__( 'Apply Discount Only For Registered Customers', 'woolentor' ),
                                'type'        => 'checkbox',
                                'condition'   => array( 'key'=>'status','operator'=> '==', 'value'=>'on' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                        ]
                    ),

                    array(
                        'id'        => 'manage_price_label',
                        'name'       => esc_html__( 'Manage Price Label', 'woolentor' ),
                        'desc'        => esc_html__( 'Manage how you want the price labels to appear, or leave it blank to display only the flash-sale price without any labels. Available placeholders: {original_price}, {flash_sale_price}', 'woolentor' ),
                        'type'        => 'text',
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'    => 'override_sale_price',
                        'name'   => esc_html__( 'Override Sale Price', 'woolentor' ),
                        'type'    => 'checkbox',
                        'default' => 'off',
                        'class'   => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'enable_countdown_on_product_details_page',
                        'name'   => esc_html__( 'Show Countdown On Product Details Page', 'woolentor' ),
                        'type'    => 'checkbox',
                        'default' => 'on',
                        'class'   => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'      => 'countdown_style',
                        'name'     => esc_html__( 'Countdown Style', 'woolentor' ),
                        'type'      => 'select',
                        'options'   => array(
                            '1'      => esc_html__('Style One', 'woolentor'),
                            '2'      => esc_html__('Style Two', 'woolentor'),
                        ),
                        'default'   => '2',
                        'condition' => array( 'key'=>'enable_countdown_on_product_details_page', 'operator'=>'==', 'value'=>'on' ),
                        'class'     => 'woolentor-action-field-left'
                    ),

                        array(
                            'id'        => 'countdown_position',
                            'name'       => esc_html__( 'Countdown Position', 'woolentor' ),
                            'type'        => 'select',
                            'options'     => array(
                            'woocommerce_before_add_to_cart_form'      => esc_html__('Add to cart - Before', 'woolentor'),
                            'woocommerce_after_add_to_cart_form'       => esc_html__('Add to cart - After', 'woolentor'),
                            'woocommerce_product_meta_start'           => esc_html__('Product meta - Before', 'woolentor'),
                            'woocommerce_product_meta_end'             => esc_html__('Product meta - After', 'woolentor'),
                            'woocommerce_single_product_summary'       => esc_html__('Product summary - Before', 'woolentor'),
                            'woocommerce_after_single_product_summary' => esc_html__('Product summary - After', 'woolentor'),
                            ),
                            'condition'   => array( 'key'=>'enable_countdown_on_product_details_page', 'operator'=>'==', 'value'=>'on' ),
                            'class'       => 'woolentor-action-field-left'
                        ),

                    array(
                        'id'    => 'countdown_timer_title',
                        'name'   => esc_html__( 'Countdown Timer Title', 'woolentor' ),
                        'type'    => 'text',
                        'default' => esc_html__('Hurry Up! Offer ends in', 'woolentor'),
                        'condition' => array( 'key'=>'enable_countdown_on_product_details_page', 'operator'=>'==', 'value'=>'on' ),
                        'class'   => 'woolentor-action-field-left'
                    ),
                    
                )

            ),

            array(
                'id'     => 'woolentor_partial_payment_settings',
                'name'    => esc_html__( 'Partial Payment', 'woolentor-pro' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_partial_payment_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/how-to-accept-partial-payment-in-woocommerce/'),
                'setting_fields' => array(

                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can enable / disable partial payment from here.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'amount_type',
                        'name'   => esc_html__( 'Amount Type', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Choose how you want to received the partial payment.', 'woolentor-pro' ),
                        'type'    => 'select',
                        'default' => 'percentage',
                        'options' => [
                            'fixedamount' => esc_html__('Fixed Amount','woolentor-pro'),
                            'percentage' => esc_html__('Percentage','woolentor-pro'),
                        ],
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'  => 'amount',
                        'name' => esc_html__( 'Amount', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Enter the partial payment amount based on the amount type you chose above (should not be more than 99 for percentage or more than order total for fixed )', 'woolentor-pro' ),
                        'min'               => 0.0,
                        'step'              => 0.01,
                        'type'              => 'number',
                        'default'           => '50',
                        'sanitize_callback' => 'floatval',
                        'class'             => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'    => 'default_selected',
                        'name'   => esc_html__( 'Default payment type', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Select a payment type that you want to set by default.', 'woolentor-pro' ),
                        'type'    => 'select',
                        'default' => 'partial',
                        'options' => [
                            'partial' => esc_html__('Partial Payment','woolentor-pro'),
                            'full'    => esc_html__('Full Payment','woolentor-pro'),
                        ],
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'disallowed_payment_method_ppf',
                        'name'   => esc_html__( 'Disallowed payment method for first installment', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Select payment methods that you want to disallow for first installment.', 'woolentor-pro' ),
                        'type'    => 'multiselect',
                        'options' => function_exists('woolentor_get_payment_method') ? woolentor_get_payment_method() : ['notfound'=>esc_html__('Not Found','woolentor-pro')],
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'disallowed_payment_method_pps',
                        'name'   => esc_html__( 'Disallowed payment method for second installment', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Select payment methods that you want to disallow for second installment.', 'woolentor-pro' ),
                        'type'    => 'multiselect',
                        'options' => function_exists('woolentor_get_payment_method') ? woolentor_get_payment_method() : ['notfound'=>esc_html__('Not Found','woolentor-pro')],
                        'class' => 'woolentor-action-field-left'
                    ),

                    // array(
                    //     'id'  => 'payment_reminder',
                    //     'name' => esc_html__( 'Second installment payment reminder date in day', 'woolentor-pro' ),
                    //     'desc'  => esc_html__( 'Send a reminder email before second payment due date', 'woolentor-pro' ),
                    //     'type'              => 'number',
                    //     'default'           => '5',
                    //     'sanitize_callback' => 'floatval',
                    //     'class'             => 'woolentor-action-field-left',
                    // ),

                    array(
                        'id'    => 'shop_loop_btn_area_title',
                        'heading'=> esc_html__( 'Shop / Product Loop', 'woolentor-pro' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'class'   => 'element_section_title_area',
                    ),

                    array(
                        'id'        => 'partial_payment_loop_btn_text',
                        'name'       => esc_html__( 'Add to cart button text', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'You can change the add to cart button text for the products that allow partial payment.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'placeholder' => esc_html__( 'Partial Payment', 'woolentor-pro' ),
                        'default'     => esc_html__( 'Partial Payment', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'    => 'single_product_custom_text_title',
                        'heading'=> esc_html__( 'Single Product', 'woolentor-pro' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'class'   => 'element_section_title_area',
                    ),

                    array(
                        'id'        => 'partial_payment_button_text',
                        'name'       => esc_html__( 'Partial payment button label', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Insert the label for the partial payment option.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'placeholder' => esc_html__( 'Partial Payment', 'woolentor-pro' ),
                        'default'     => esc_html__( 'Partial Payment', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'full_payment_button_text',
                        'name'       => esc_html__( 'Full payment button label', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Insert the label for the full payment option.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'Full Payment', 'woolentor-pro' ),
                        'placeholder' => esc_html__( 'Full Payment', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'partial_payment_discount_text',
                        'name'       => esc_html__( 'First deposit label', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Insert the first deposit label from here. Available placeholders: {price} ', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'First Instalment : {price} Per item', 'woolentor-pro' ),
                        'placeholder' => esc_html__( 'First Installment', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'    => 'checkout_custom_text_title',
                        'heading'=> esc_html__( 'Cart / Checkout', 'woolentor-pro' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'class'   => 'element_section_title_area',
                    ),

                    array(
                        'id'        => 'first_installment_text',
                        'name'       => esc_html__( 'First installment amount label', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Enter the first installment amount label.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'First Installment', 'woolentor-pro' ),
                        'placeholder' => esc_html__( 'First Installment', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'second_installment_text',
                        'name'       => esc_html__( 'Second installment amount label', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Enter the second installment amount label.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'Second Installment', 'woolentor-pro' ),
                        'placeholder' => esc_html__( 'Second Installment', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'to_pay',
                        'name'       => esc_html__( 'Amount to pay label', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Enter the label for amount to pay.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'To Pay', 'woolentor-pro' ),
                        'placeholder' => esc_html__( 'To Pay', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    
                )

            ),

            array(
                'id'     => 'woolentor_pre_order_settings',
                'name'    => esc_html__( 'Pre Orders', 'woolentor-pro' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_pre_order_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/how-to-set-pre-order-for-woocommerce/'),
                'setting_fields' => array(

                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can enable / disable pre orders from here.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'add_to_cart_btn_text',
                        'name'       => esc_html__( 'Add to cart button text', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'You can change the add to cart button text for the products that allow pre order.', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__('Pre Order','woolentor-pro'),
                        'placeholder' => esc_html__( 'Pre Order', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'manage_price_lavel',
                        'name'       => esc_html__( 'Manage Price Label', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Manage how you want the price labels to appear, or leave it blank to display only the pre-order price without any labels. Available placeholders: {original_price}, {preorder_price}', 'woolentor-pro' ),
                        'default'     => esc_html__( '{original_price} Pre order price: {preorder_price}', 'woolentor-pro' ),
                        'type'        => 'text',
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'availability_date',
                        'name'       => esc_html__( 'Availability date label', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Manage how you want the availability date labels to appear. Available placeholders: {availability_date}, {availability_time}', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'Available on: {availability_date} at {availability_time}', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'show_countdown',
                        'name' => esc_html__( 'Show Countdown', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can enable / disable pre orders countdown from here.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'countdown_heading_title',
                        'heading'=> esc_html__( 'Countdown Custom Label', 'woolentor-pro' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'class'   => 'element_section_title_area',
                        'condition' => array( 'key'=>'show_countdown', 'operator'=>'==', 'value'=>'on' ),
                    ),

                    array(
                        'id'        => 'customlabel_days',
                        'name'       => esc_html__( 'Days', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'Days', 'woolentor-pro' ),
                        'condition'   => array( 'key'=>'show_countdown', 'operator'=>'==', 'value'=>'on' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    array(
                        'id'        => 'customlabel_hours',
                        'name'       => esc_html__( 'Hours', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'Hours', 'woolentor-pro' ),
                        'condition'   => array( 'key'=>'show_countdown', 'operator'=>'==', 'value'=>'on' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    array(
                        'id'        => 'customlabel_minutes',
                        'name'       => esc_html__( 'Minutes', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'Min', 'woolentor-pro' ),
                        'condition'   => array( 'key'=>'show_countdown', 'operator'=>'==', 'value'=>'on' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    array(
                        'id'        => 'customlabel_seconds',
                        'name'       => esc_html__( 'Seconds', 'woolentor-pro' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'Sec', 'woolentor-pro' ),
                        'condition'   => array( 'key'=>'show_countdown', 'operator'=>'==', 'value'=>'on' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                ),
            ),

            array(
                'id'     => 'woolentor_backorder_settings',
                'name'    => esc_html__( 'Backorder', 'woolentor' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_backorder_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/how-to-enable-woocommerce-backorder/'),
                'setting_fields' => array(
                
                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor' ),
                        'desc'  => esc_html__( 'You can enable / disable backorder module from here.', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'backorder_limit',
                        'name'   => esc_html__( 'Backorder Limit', 'woolentor' ),
                        'desc'    => esc_html__( 'Set "Backorder Limit" on all "Backorder" products across the entire website. You can also set limits for each product individually from the "Inventory" tab.', 'woolentor' ),
                        'type'    => 'number',
                        'class'   => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'backorder_availability_date',
                        'name'   => esc_html__( 'Availability Date', 'woolentor' ),
                        'type'    => 'date',
                        'class'   => 'woolentor-action-field-left'
                    ),
                
                    array(
                        'id'        => 'backorder_availability_message',
                        'name'       => esc_html__( 'Availability Message', 'woolentor' ),
                        'desc'        => esc_html__( 'Manage how you want the "Message" to appear. Use this {availability_date} placeholder to display the date you set. ', 'woolentor' ),
                        'type'        => 'text',
                        'default'     => esc_html__( 'On Backorder: Will be available on {availability_date}', 'woolentor' ),
                        'class'       => 'woolentor-action-field-left',
                    ),
                    
                )
                
            ),

            array(
                'id'     => 'woolentor_checkout_fields',
                'name'    => esc_html__( 'Checkout Fields Manager', 'woolentor' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_checkout_fields',
                'option_id'=> 'billing_enable,shipping_enable,additional_enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/checkout-field-editor/'),
                'setting_fields' => array(

                    array(
                        'id'  => 'billing_enable',
                        'name' => esc_html__( 'Modify Billing Field', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'billing',
                        'name'       => esc_html__( 'Manage Billing Form Field', 'woolentor' ),
                        'type'        => 'repeater',
                        'title_field' => 'field_label',
                        'condition'   => array( 'key'=>'billing_enable', 'operator'=>'==', 'value'=>'on' ),
                        'fields'  => [

                            array(
                                'id'        => 'field_key',
                                'name'       => esc_html__( 'Field name', 'woolentor' ),
                                'type'        => 'select',
                                'options' => [
                                    'first_name'=> esc_html__( 'First Name', 'woolentor-pro' ),
                                    'last_name' => esc_html__( 'Last Name', 'woolentor-pro' ),
                                    'company'   => esc_html__( 'Company', 'woolentor-pro' ),
                                    'country'   => esc_html__( 'Country', 'woolentor-pro' ),
                                    'address_1' => esc_html__( 'Street address', 'woolentor-pro' ),
                                    'address_2' => esc_html__( 'Apartment address', 'woolentor-pro' ),
                                    'city'      => esc_html__( 'Town / City', 'woolentor-pro' ),
                                    'state'     => esc_html__( 'District', 'woolentor-pro' ),
                                    'postcode'  => esc_html__( 'Postcode / ZIP', 'woolentor-pro' ),
                                    'phone'     => esc_html__( 'Phone', 'woolentor-pro' ),
                                    'email'     => esc_html__( 'Email', 'woolentor-pro' ),
                                    'customadd' => esc_html__( 'Add Custom', 'woolentor-pro' )
                                ],
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_type',
                                'name'       => esc_html__( 'Field Type', 'woolentor' ),
                                'type'        => 'select',
                                'options'     => class_exists('WooLentor_Checkout_Field_Manager') ? WooLentor_Checkout_Field_Manager::instance()->field_types() : [],
                                'condition'   => array( 'key'=>'field_key', 'operator'=>'==', 'value'=>'customadd' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_key_custom',
                                'name'       => esc_html__( 'Custom key', 'woolentor' ),
                                'type'        => 'text',
                                'condition'   => array( 'key'=>'field_key', 'operator'=>'==', 'value'=>'customadd' ),
                                'class'       => 'woolentor-action-field-left'
                            ),
                            
                            array(
                                'id'        => 'field_label',
                                'name'       => esc_html__( 'Label', 'woolentor' ),
                                'type'        => 'text',
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'title_tag',
                                'name'       => esc_html__( 'Title Tag', 'woolentor' ),
                                'type'        => 'select',
                                'options'     => function_exists('woolentor_html_tag_lists') ? woolentor_html_tag_lists() : [],
                                'default'     => 'h3',
                                'condition'   => array( 'key'=>'field_type', 'operator'=>'==', 'value'=>'heading' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_placeholder',
                                'name'       => esc_html__( 'Placeholder', 'woolentor' ),
                                'type'        => 'text',
                                'condition'   => array( 'key'=>'field_type','operator'=>'not-any','value'=>'radio,heading,checkbox,checkboxgroup' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_default_value',
                                'name'       => esc_html__( 'Default Value', 'woolentor' ),
                                'type'        => 'text',
                                'condition'   => array( 'key'=>'field_type','operator'=>'not-any','value'=>'heading,checkbox' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_validation',
                                'name'       => esc_html__( 'Validation', 'woolentor' ),
                                'type'        => 'multiselect',
                                'options' => [
                                    'email'     => esc_html__( 'Email', 'woolentor-pro' ),
                                    'phone'     => esc_html__( 'Phone', 'woolentor-pro' ),
                                    'postcode'  => esc_html__( 'Postcode', 'woolentor-pro' ),
                                    'state'     => esc_html__( 'State', 'woolentor-pro' ),
                                    'number'    => esc_html__( 'Number', 'woolentor-pro' )
                                ],
                                'condition'   => array( 'key'=>'field_type', 'operator'=>'not-any', 'value'=>'heading,multiselect,checkbox,checkboxgroup' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_class',
                                'name'       => esc_html__( 'Class', 'woolentor-pro' ),
                                'type'        => 'text',
                                'desc'        => esc_html__( 'You can use ( form-row-first, form-row-last, form-row-wide, woolentor-one-third )' , 'woolentor-pro' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_options',
                                'name'       => esc_html__( 'Options', 'woolentor-pro' ),
                                'type'        => 'textarea',
                                'desc'        => 'Add a single option by using the format: Value, Label<br/>For multiple options, use a pipe symbol to separate them. For instance: value_1, label_1 | value_2, label_2  | value_3, label_3',
                                'placeholder' => esc_html__('one,Select One','woolentor-pro'),
                                'condition'   => array( 'key'=>'field_type', 'operator'=>'any', 'value'=>'select,radio,multiselect,checkboxgroup' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'field_required',
                                'name' => esc_html__( 'Required', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'condition'   => array( 'key'=>'field_type','operator'=>'!=','value'=>'heading' ),
                                'class' => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'field_show_email',
                                'name' => esc_html__( 'Show in Email', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'condition'   => array( 'key'=>'field_key|field_type', 'operator'=>'==|!=', 'value'=>'customadd|heading' ),
                                'class' => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'field_show_order',
                                'name' => esc_html__( 'Show in Order Detail Page', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'condition'   => array( 'key'=>'field_key|field_type', 'operator'=>'==|!=', 'value'=>'customadd|heading' ),
                                'class' => 'woolentor-action-field-left'
                            )

                        ],

                        'default' => class_exists('WooLentor_Checkout_Field_Manager') && !empty(WooLentor_Checkout_Field_Manager::instance()->get_previous_fields('billing') ) ?WooLentor_Checkout_Field_Manager::instance()->get_previous_fields('billing') : [
                            [
                                'field_key'             => 'first_name',
                                'field_label'           => esc_html__( 'First Name', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-first',
                                'field_required'        => 'on',
                            ],
                            [
                                'field_key'             => 'last_name',
                                'field_label'           => esc_html__( 'Last Name', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-last',
                                'field_required'        => 'on',
                            ],
                            [
                                'field_key'             => 'company',
                                'field_label'           => esc_html__( 'Company name', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide',
                                'field_required'        => 'off',
                            ],
                            [
                                'field_key'             => 'country',
                                'field_label'           => esc_html__( 'Country', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide,address-field,update_totals_on_change',
                                'field_required'        => 'on',
                            ],
                            [
                                'field_key'             => 'address_1',
                                'field_label'           => esc_html__( 'Street address', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'off',
                            ],
                            [
                                'field_key'             => 'address_2',
                                'field_label'           => esc_html__( 'Apartment address','woolentor-pro'),
                                'field_placeholder'     => esc_html__( 'Apartment, suite, unit etc. (optional)', 'woolentor-pro' ),
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'off',
                            ],
                            [
                                'field_key'             => 'city',
                                'field_label'           => esc_html__( 'Town / City', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'on',
                            ],
                            [
                                'field_key'             => 'state',
                                'field_label'           => esc_html__( 'State / County', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => ['state'],
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'off',
                            ],
                            [
                                'field_key'             => 'postcode',
                                'field_label'           => esc_html__( 'Postcode / ZIP', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => ['postcode'],
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'on',
                            ],
                            [
                                'field_key'             => 'phone',
                                'field_label'           => esc_html__( 'Phone', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => ['phone'],
                                'field_class'           => 'form-row-wide',
                                'field_required'        => 'on',
                            ],
                            [
                                'field_key'             => 'email',
                                'field_label'           => esc_html__( 'Email address', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => ['email'],
                                'field_class'           => 'form-row-wide',
                                'field_required'        => 'on',
                            ],
                        ]
                    ),

                    array(
                        'id'  => 'shipping_enable',
                        'name' => esc_html__( 'Modify Shipping Field', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'shipping',
                        'name'       => esc_html__( 'Manage Shipping Form Field', 'woolentor' ),
                        'type'        => 'repeater',
                        'title_field' => 'field_label',
                        'condition'   => array( 'key'=>'shipping_enable', 'operator'=>'==', 'value'=>'on' ),
                        'fields'  => [

                            array(
                                'id'        => 'field_key',
                                'name'       => esc_html__( 'Field name', 'woolentor' ),
                                'type'        => 'select',
                                'options' => [
                                    'first_name'=> esc_html__( 'First Name', 'woolentor-pro' ),
                                    'last_name' => esc_html__( 'Last Name', 'woolentor-pro' ),
                                    'company'   => esc_html__( 'Company', 'woolentor-pro' ),
                                    'country'   => esc_html__( 'Country', 'woolentor-pro' ),
                                    'address_1' => esc_html__( 'Street address', 'woolentor-pro' ),
                                    'address_2' => esc_html__( 'Apartment address', 'woolentor-pro' ),
                                    'city'      => esc_html__( 'Town / City', 'woolentor-pro' ),
                                    'state'     => esc_html__( 'District', 'woolentor-pro' ),
                                    'postcode'  => esc_html__( 'Postcode / ZIP', 'woolentor-pro' ),
                                    'customadd' => esc_html__( 'Add Custom', 'woolentor-pro' )
                                ],
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_type',
                                'name'       => esc_html__( 'Field Type', 'woolentor' ),
                                'type'        => 'select',
                                'options'     => class_exists('WooLentor_Checkout_Field_Manager') ? WooLentor_Checkout_Field_Manager::instance()->field_types() : [],
                                'condition'   => array( 'key'=>'field_key', 'operator'=>'==', 'value'=>'customadd' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_key_custom',
                                'name'       => esc_html__( 'Custom key', 'woolentor' ),
                                'type'        => 'text',
                                'condition'   => array( 'key'=>'field_key', 'operator'=>'==', 'value'=>'customadd' ),
                                'class'       => 'woolentor-action-field-left'
                            ),
                            
                            array(
                                'id'        => 'field_label',
                                'name'       => esc_html__( 'Label', 'woolentor' ),
                                'type'        => 'text',
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'title_tag',
                                'name'       => esc_html__( 'Title Tag', 'woolentor' ),
                                'type'        => 'select',
                                'options'     => function_exists('woolentor_html_tag_lists') ? woolentor_html_tag_lists() : [],
                                'default'     => 'h3',
                                'condition'   => array( 'key'=>'field_type', 'operator'=>'==', 'value'=>'heading' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_placeholder',
                                'name'       => esc_html__( 'Placeholder', 'woolentor' ),
                                'type'        => 'text',
                                'condition'   => array( 'key'=>'field_type','operator'=>'not-any','value'=>'radio,heading,checkbox,checkboxgroup' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_default_value',
                                'name'       => esc_html__( 'Default Value', 'woolentor' ),
                                'type'        => 'text',
                                'condition'   => array( 'key'=>'field_type','operator'=>'not-any','value'=>'heading,checkbox' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_validation',
                                'name'       => esc_html__( 'Validation', 'woolentor' ),
                                'type'        => 'multiselect',
                                'options' => [
                                    'email'     => esc_html__( 'Email', 'woolentor-pro' ),
                                    'phone'     => esc_html__( 'Phone', 'woolentor-pro' ),
                                    'postcode'  => esc_html__( 'Postcode', 'woolentor-pro' ),
                                    'state'     => esc_html__( 'State', 'woolentor-pro' ),
                                    'number'    => esc_html__( 'Number', 'woolentor-pro' )
                                ],
                                'condition'   => array( 'key'=>'field_type', 'operator'=>'not-any', 'value'=>'heading,multiselect,checkbox,checkboxgroup' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_class',
                                'name'       => esc_html__( 'Class', 'woolentor-pro' ),
                                'type'        => 'text',
                                'desc'        => esc_html__( 'You can use ( form-row-first, form-row-last, form-row-wide, woolentor-one-third )' , 'woolentor-pro' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_options',
                                'name'       => esc_html__( 'Options', 'woolentor-pro' ),
                                'type'        => 'textarea',
                                'desc'        => 'Add a single option by using the format: Value, Label<br/>For multiple options, use a pipe symbol to separate them. For instance: value_1, label_1 | value_2, label_2  | value_3, label_3',
                                'placeholder' => esc_html__('one,Select One','woolentor-pro'),
                                'condition'   => array( 'key'=>'field_type', 'operator'=>'any', 'value'=>'select,radio,multiselect,checkboxgroup' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'field_required',
                                'name' => esc_html__( 'Required', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'condition'   => array( 'key'=>'field_type','operator'=>'!=','value'=>'heading' ),
                                'class' => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'field_show_email',
                                'name' => esc_html__( 'Show in Email', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'condition'   => array( 'key'=>'field_key|field_type', 'operator'=>'==|!=', 'value'=>'customadd|heading' ),
                                'class' => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'field_show_order',
                                'name' => esc_html__( 'Show in Order Detail Page', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'condition'   => array( 'key'=>'field_key|field_type', 'operator'=>'==|!=', 'value'=>'customadd|heading' ),
                                'class' => 'woolentor-action-field-left'
                            )
                        ],

                        'default' => class_exists('WooLentor_Checkout_Field_Manager') && !empty(WooLentor_Checkout_Field_Manager::instance()->get_previous_fields('shipping') ) ?WooLentor_Checkout_Field_Manager::instance()->get_previous_fields('shipping') : [
                            [
                                'field_key'             => 'first_name',
                                'field_label'           => esc_html__( 'First Name', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-first',
                                'field_required'        => 'yes',
                            ],
                            [
                                'field_key'             => 'last_name',
                                'field_label'           => esc_html__( 'Last Name', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-last',
                                'field_required'        => 'yes',
                            ],
                            [
                                'field_key'             => 'company',
                                'field_label'           => esc_html__( 'Company name', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide',
                                'field_required'        => 'no',
                            ],
                            [
                                'field_key'             => 'country',
                                'field_label'           => esc_html__( 'Country', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide,address-field,update_totals_on_change',
                                'field_required'        => 'yes',
                            ],
                            [
                                'field_key'             => 'address_1',
                                'field_label'           => esc_html__( 'Street address', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'yes',
                            ],
                            [
                                'field_key'             => 'address_2',
                                'field_label'           => esc_html__( 'Apartment address','woolentor-pro'),
                                'field_placeholder'     => esc_html__( 'Apartment, suite, unit etc. (optional)', 'woolentor-pro' ),
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'no',
                            ],
                            [
                                'field_key'             => 'city',
                                'field_label'           => esc_html__( 'Town / City', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'yes',
                            ],
                            [
                                'field_key'             => 'state',
                                'field_label'           => esc_html__( 'State / County', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => ['state'],
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'no',
                            ],
                            [
                                'field_key'             => 'postcode',
                                'field_label'           => esc_html__( 'Postcode / ZIP', 'woolentor-pro' ),
                                'field_placeholder'     => '',
                                'field_default_value'   => '',
                                'field_validation'      => ['postcode'],
                                'field_class'           => 'form-row-wide,address-field',
                                'field_required'        => 'yes',
                            ]
                            
                        ]
                    ),

                    array(
                        'id'  => 'additional_enable',
                        'name' => esc_html__( 'Modify Additional Field', 'woolentor' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'additional',
                        'name'       => esc_html__( 'Manage Additional Form Field', 'woolentor' ),
                        'type'        => 'repeater',
                        'title_field' => 'field_label',
                        'condition'   => array( 'key'=>'additional_enable', 'operator'=>'==', 'value'=>'on' ),
                        'fields'  => [
                            array(
                                'id'        => 'field_key',
                                'name'       => esc_html__( 'Field name', 'woolentor' ),
                                'type'        => 'select',
                                'options' => [
                                    'order_comments' => esc_html__( 'Order Notes', 'woolentor-pro' ),
                                    'customadd'      => esc_html__( 'Add Custom', 'woolentor-pro' ),
                                ],
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_type',
                                'name'       => esc_html__( 'Field Type', 'woolentor' ),
                                'type'        => 'select',
                                'options'     => class_exists('WooLentor_Checkout_Field_Manager') ? WooLentor_Checkout_Field_Manager::instance()->field_types() : [],
                                'condition'   => array( 'key'=>'field_key', 'operator'=>'==', 'value'=>'customadd' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_key_custom',
                                'name'       => esc_html__( 'Custom key', 'woolentor' ),
                                'type'        => 'text',
                                'condition'   => array( 'key'=>'field_key', 'operator'=>'==', 'value'=>'customadd' ),
                                'class'       => 'woolentor-action-field-left'
                            ),
                            
                            array(
                                'id'        => 'field_label',
                                'name'       => esc_html__( 'Label', 'woolentor' ),
                                'type'        => 'text',
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'title_tag',
                                'name'       => esc_html__( 'Title Tag', 'woolentor' ),
                                'type'        => 'select',
                                'options'     => function_exists('woolentor_html_tag_lists') ? woolentor_html_tag_lists() : [],
                                'default'     => 'h3',
                                'condition'   => array( 'key'=>'field_type', 'operator'=>'==', 'value'=>'heading' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_placeholder',
                                'name'       => esc_html__( 'Placeholder', 'woolentor' ),
                                'type'        => 'text',
                                'condition'   => array( 'key'=>'field_type','operator'=>'not-any','value'=>'radio,heading,checkbox,checkboxgroup' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_default_value',
                                'name'       => esc_html__( 'Default Value', 'woolentor' ),
                                'type'        => 'text',
                                'condition'   => array( 'key'=>'field_type','operator'=>'not-any','value'=>'heading,checkbox' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_validation',
                                'name'       => esc_html__( 'Validation', 'woolentor' ),
                                'type'        => 'multiselect',
                                'options' => [
                                    'email'     => esc_html__( 'Email', 'woolentor-pro' ),
                                    'phone'     => esc_html__( 'Phone', 'woolentor-pro' ),
                                    'postcode'  => esc_html__( 'Postcode', 'woolentor-pro' ),
                                    'state'     => esc_html__( 'State', 'woolentor-pro' ),
                                    'number'    => esc_html__( 'Number', 'woolentor-pro' )
                                ],
                                'condition'   => array( 'key'=>'field_type', 'operator'=>'not-any', 'value'=>'heading,multiselect,checkbox,checkboxgroup' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_class',
                                'name'       => esc_html__( 'Class', 'woolentor-pro' ),
                                'type'        => 'text',
                                'desc'        => esc_html__( 'You can use ( form-row-first, form-row-last, form-row-wide, woolentor-one-third )' , 'woolentor-pro' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'        => 'field_options',
                                'name'       => esc_html__( 'Options', 'woolentor-pro' ),
                                'type'        => 'textarea',
                                'desc'        => 'Add a single option by using the format: Value, Label<br/>For multiple options, use a pipe symbol to separate them. For instance: value_1, label_1 | value_2, label_2  | value_3, label_3',
                                'placeholder' => esc_html__('one,Select One','woolentor-pro'),
                                'condition'   => array( 'key'=>'field_type', 'operator'=>'any', 'value'=>'select,radio,multiselect,checkboxgroup' ),
                                'class'       => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'field_required',
                                'name' => esc_html__( 'Required', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'condition'   => array( 'key'=>'field_type','operator'=>'!=','value'=>'heading' ),
                                'class' => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'field_show_email',
                                'name' => esc_html__( 'Show in Email', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'condition'   => array( 'key'=>'field_key|field_type', 'operator'=>'==|!=', 'value'=>'customadd|heading' ),
                                'class' => 'woolentor-action-field-left'
                            ),

                            array(
                                'id'  => 'field_show_order',
                                'name' => esc_html__( 'Show in Order Detail Page', 'woolentor' ),
                                'type'  => 'checkbox',
                                'default' => 'off',
                                'condition'   => array( 'key'=>'field_key|field_type', 'operator'=>'==|!=', 'value'=>'customadd|heading' ),
                                'class' => 'woolentor-action-field-left'
                            )

                        ],

                        'default' => class_exists('WooLentor_Checkout_Field_Manager') && !empty(WooLentor_Checkout_Field_Manager::instance()->get_previous_fields('additional') ) ?WooLentor_Checkout_Field_Manager::instance()->get_previous_fields('additional') : [
                            [
                                'field_key'             => 'order_comments',
                                'field_label'           => esc_html__( 'Order Notes', 'woolentor-pro' ),
                                'field_placeholder'     => 'Notes about your order, e.g. special notes for delivery.',
                                'field_default_value'   => '',
                                'field_validation'      => '',
                                'field_class'           => 'notes',
                                'field_required'        => false,
                            ],
    
                        ]

                    )
                    
                )

            ),

            array(
                'id'     => 'woolentor_gtm_convertion_tracking_settings',
                'name'    => esc_html__( 'GTM Conversion Tracking', 'woolentor-pro' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_gtm_convertion_tracking_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/gtm-conversion-tracking/'),
                'setting_fields' => array(

                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can enable / disable GTM Conversion tracking from here.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'gtm_id',
                        'name'       => esc_html__( 'Google Tag Manager ID', 'woolentor-pro' ),
                        'type'        => 'text',
                        'placeholder' => esc_html__( 'GTM-XXXXX', 'woolentor-pro' ),
                        'desc'        => wp_kses_post( 'Enter your google tag manager id (<a href="'.esc_url('https://developers.google.com/tag-manager/quickstart').'" target="_blank">Lookup your ID</a>)' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'gtm_container_template_generate',
                        'name'       => esc_html__( 'Generate GTM Container Template', 'woolentor-pro' ),
                        'type'        => 'html',
                        'html'        => wp_kses_post( '<a class="woolentor-admin-btn woolentor-admin-btn-primary hover-effect-1" href="'.esc_url('https://hasthemes.com/tool/gtm-container-template-generator/').'" target="_blank">'.esc_html__('Generate Now','woolentor-pro').'</a>' ),
                        'desc'        => esc_html__( 'We\'ve developed a new tool that generates a Google Tag Manager template file in less than two minutes. Connecting and integrating tracking tools such as Facebook pixels, Google Analytics, and Google Ads Remarketing with GTM normally takes 2-3 hours. We made it simple, and faster than ever.', 'woolentor-pro' ),
                        'class'       => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'    => 'tracking_event_heading_title',
                        'heading'=> esc_html__( 'Tracking Event', 'woolentor-pro' ),
                        'type'    => 'title',
                        'size'    => 'margin_0 regular',
                        'class'   => 'element_section_title_area',
                    ),

                    array(
                        'id'  => 'shop_enable',
                        'name' => esc_html__( 'Shop / Archive Page Items view tracking', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Enable this option to track the Shop/Archive page items.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'  => 'product_enable',
                        'name' => esc_html__( 'Single Product Page Tracking', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Enable this option to track the single product page content.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'  => 'cart_enable',
                        'name'  => esc_html__( 'Cart Page Tracking', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Enable this option to track the all cart items.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'  => 'checkout_enable',
                        'name'  => esc_html__( 'Checkout Page Tracking', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Enable this option to track the user data on the checkout page.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'  => 'thankyou_enable',
                        'name'  => esc_html__( 'Thankyou page Tracking', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Enable this option to track the user order data on the thankyou page.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'  => 'add_to_cart_enable',
                        'name'  => esc_html__( 'Add to cart Tracking', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Enable this option to track the user behavior on the add to cart.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'  => 'single_add_to_cart_enable',
                        'name'  => esc_html__( 'Add to cart Tracking from single product', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Enable this option to track the add to cart on single product page.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'  => 'remove_from_cart_enable',
                        'name'  => esc_html__( 'Remove from cart', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Enable this option to track the remove cart item.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'on',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'product_brands',
                        'name'   => esc_html__( 'Product brands', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Select which taxonomy of products you want to set for the product brand in the data layer.', 'woolentor-pro' ),
                        'type'    => 'select',
                        'default'=>'none',
                        'options' => array( 'none' => esc_html__( 'Select Taxonomy', 'woolentor-pro' ) ) + woolentor_get_taxonomies('product', true),
                        'class' => 'woolentor-action-field-left'
                    ),
    
                    array(
                        'id'  => 'use_sku',
                        'name'  => esc_html__( 'Use SKU instead of ID', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Enable this option to track your e-commerce business using the product SKUs instead of the IDs in the data layer.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                ),
            ),

            array(
                'id'     => 'woolentor_size_chart_settings',
                'name'    => esc_html__( 'Size Chart', 'woolentor-pro' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_size_chart_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/woocommerce-product-size-chart/'),
                'setting_fields' => array(
            
                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can enable / disable size chart from here.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'show_as',
                        'name'       => esc_html__( 'Show As', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Choose where/how the size chart should be displayed.', 'woolentor-pro' ),
                        'type'        => 'select',
                        'options'     => array(
                            'additional_tab' => esc_html__('Additional Tab', 'woolentor'),
                            'popup'          => esc_html__('Popup', 'woolentor'),
                        ),
                        'class'       => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'additional_tab_label',
                        'name'   => esc_html__( 'Additional Tab Text', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Rename size chart tab label.', 'woolentor-pro' ),
                        'type'    => 'text',
                        'default' => esc_html__( 'Size Chart', 'woolentor-pro' ),
                        'condition' => array( 'key'=>'show_as', 'operator'=>'==', 'value'=>'additional_tab' ),
                        'class'   => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'    => 'popup_button_text',
                        'name'   => esc_html__( 'Button Text', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'The text appears on the button that opens the popup.', 'woolentor-pro' ),
                        'type'    => 'text',
                        'default' => esc_html__( 'Size Chart', 'woolentor-pro' ),
                        'condition' => array( 'key'=>'show_as', 'operator'=>'==', 'value'=>'popup' ),
                        'class'   => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'popup_button_positon',
                        'name'       => esc_html__( 'Button Position', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'You can popup button position from here.', 'woolentor-pro' ),
                        'type'        => 'select',
                        'options'     => array(
                            'woocommerce_before_add_to_cart_form'      => esc_html__('Add to cart - Before', 'woolentor-pro'),
                            'woocommerce_after_add_to_cart_form'       => esc_html__('Add to cart - After', 'woolentor-pro'),
                            'woocommerce_product_meta_start'           => esc_html__('Product meta - Before', 'woolentor-pro'),
                            'woocommerce_product_meta_end'             => esc_html__('Product meta - After', 'woolentor-pro'),
                            'woocommerce_single_product_summary'       => esc_html__('Product summary - Before', 'woolentor-pro'),
                            'woocommerce_after_single_product_summary' => esc_html__('Product summary - After', 'woolentor-pro'),
                        ),
                        'condition' => array( 'key'=>'show_as', 'operator'=>'==', 'value'=>'popup' ),
                        'class'       => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'hide_popup_title',
                        'name'   => esc_html__( 'Hide Title', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Hide the chart name on popup title.', 'woolentor-pro' ),
                        'type'    => 'checkbox',
                        'condition' => array( 'key'=>'show_as', 'operator'=>'==', 'value'=>'popup' ),
                    ),

                    array(
                        'id'    => 'button_icon',
                        'name'   => esc_html__( 'Button Icon', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'You can manage the size chart button icon.', 'woolentor-pro' ),
                        'type'    => 'iconpicker',
                        'default' => 'sli sli-chart',
                        'condition' => array( 'key'=>'show_as', 'operator'=>'==', 'value'=>'popup' ),
                    ),

                    array(
                        'id'  => 'button_margin',
                        'name' => esc_html__( 'Button Margin', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can manage button margin from here.', 'woolentor-pro' ),
                        'type'  => 'dimensions',
                        'options' => [
                            'top'   => esc_html__( 'Top', 'woolentor-pro' ),
                            'right' => esc_html__( 'Right', 'woolentor-pro' ),   
                            'bottom'=> esc_html__( 'Bottom', 'woolentor-pro' ),   
                            'left'  => esc_html__( 'Left', 'woolentor-pro' ),
                            'unit'  => esc_html__( 'Unit', 'woolentor-pro' ),
                        ],
                        'condition' => array( 'key'=>'show_as', 'operator'=>'==', 'value'=>'popup' ),
                        'class' => 'woolentor-action-field-left woolentor-dimention-field-left',
                    ),

                    array(
                        'id'      => 'design_options_heading',
                        'heading'  => esc_html__( 'Chart Table Style', 'woolentor-pro' ),
                        'type'      => 'title'
                    ),

                    array(
                        'id'  => 'table_head_bg_color',
                        'name' => esc_html__( 'Head BG Color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Size chart table header background.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'table_head_text_color',
                        'name' => esc_html__( 'Head Text Color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Size chart table header text color.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'table_even_row_bg_color',
                        'name' => esc_html__( 'Even Row BG Color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Size chart table even row background color.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'table_even_row_text_color',
                        'name' => esc_html__( 'Even Row Text Color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Size chart table even row text color.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'table_odd_row_bg_color',
                        'name' => esc_html__( 'Odd Row BG Color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Size chart table odd row background color.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'table_odd_row_text_color',
                        'name' => esc_html__( 'Odd Row Text Color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Size chart table odd row text color.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),
                    
                )
            
            ),

            array(
                'id'     => 'woolentor_swatch_settings',
                'name'    => esc_html__( 'Variation Swatches', 'woolentor' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_swatch_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/variation-swatches/'),
                'setting_fields' => array(

                    array(
                        'id'    => 'enable',
                        'name'   => esc_html__( 'Enable / Disable', 'woolentor' ),
                        'desc'    => esc_html__( 'Enable / disable this module.', 'woolentor' ),
                        'type'    => 'checkbox',
                        'default' => 'off',
                        'class'   => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'       => 'sp_enable_swatches',
                        'name'      => esc_html__( 'Enable On Product Details Page', 'woolentor' ),
                        'desc'       => esc_html__( 'Enable Swatches for the Product Details pages.', 'woolentor' ),
                        'type'       => 'checkbox',
                        'default'    => 'on',
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),

                    array(
                        'id'       => 'pl_enable_swatches',
                        'name'      => esc_html__( 'Enable On Shop / Archive Page', 'woolentor' ),
                        'desc'       => esc_html__( 'Enable Swatches for the products in the Shop / Archive Pages', 'woolentor' ),
                        'type'       => 'checkbox',
                        'default'    => 'off',
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),

                    array(
                        'id'       => 'heading_1',
                        'type'       => 'title',
                        'heading'   => esc_html__( 'General Options', 'woolentor' ),
                        'size'       => 'woolentor_style_seperator',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),
    
                    array(
                        'id'       => 'auto_convert_dropdowns_to_label',
                        'name'      => esc_html__( 'Auto Convert Dropdowns To Label', 'woolentor' ),
                        'desc'       => esc_html__( 'Automatically convert dropdowns to "label swatch" by default.', 'woolentor' ),
                        'type'       => 'checkbox',
                        'default'    => 'on',
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),

                    array(
                        'id'       => 'auto_convert_dropdowns_to_image',
                        'name'      => esc_html__( 'Auto Convert Dropdowns To Image', 'woolentor' ),
                        'desc'       => esc_html__( 'Automatically convert dropdowns to "Image Swatch" if variation has an image.', 'woolentor' ),
                        'type'       => 'checkbox',
                        'default'    => 'off',
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),

                    array(
                        'id'    => 'auto_convert_dropdowns_to_image_condition',
                        'name'   => esc_html__( 'Apply Auto Image For', 'woolentor' ),
                        'type'    => 'select',
                        'class'   => 'woolentor-action-field-left',
                        'default' => 'first_attribute',
                        'options' => array(
                            'first_attribute' => esc_html__('The First attribute', 'woolentor'),
                            'maximum'         => esc_html__('The attribute with Maximum variations count', 'woolentor'),
                            'minimum'         => esc_html__('The attribute with Minimum variations count', 'woolentor'),
                        ),
                        'condition'  => array('key'=>'enable|auto_convert_dropdowns_to_image', 'operator'=>'==|==', 'value'=>'on|on')
                    ),

                    array(
                        'id'       => 'tooltip',
                        'name'      => esc_html__( 'Tooltip', 'woolentor' ),
                        'desc'       => esc_html__( 'Enable Tooltip', 'woolentor' ),
                        'type'       => 'checkbox',
                        'default'    => 'on',
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),
                    
                    array(
                        'id'    => 'swatch_width_height',
                        'name'   => esc_html__( 'Swatch Width & Height', 'woolentor' ),
                        'desc'    => esc_html__( 'Change Swatch Width and Height From Here.', 'woolentor' ),
                        'type'    => 'dimensions',
                        'options' => [
                            'width'   => esc_html__( 'Width', 'woolentor' ),
                            'height'  => esc_html__( 'Height', 'woolentor' ),
                            'unit'    => esc_html__( 'Unit', 'woolentor' ),
                        ],
                        'default' => array(
                            'unit' => 'px'
                        ),
                        'class'       => 'woolentor-action-field-left woolentor-dimention-field-left',
                        'condition'   => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),

                    array(
                        'id'    => 'tooltip_width_height',
                        'name'   => esc_html__( 'Tooltip Width', 'woolentor' ),
                        'desc'    => esc_html__( 'Change Tooltip Width From Here.', 'woolentor' ),
                        'type'    => 'dimensions',
                        'options' => [
                            'width'   => esc_html__( 'Width', 'woolentor' ),
                            'unit'    => esc_html__( 'Unit', 'woolentor' ),  
                        ],
                        'default' => array(
                            'unit' => 'px'
                        ),
                        'class'       => 'woolentor-action-field-left woolentor-dimention-field-left',
                        'condition'   => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),

                    array(
                        'id'       => 'show_swatch_image_in_tooltip',
                        'type'       => 'checkbox',
                        'name'      => esc_html__('Swatch Image as Tooltip', 'woolentor'),
                        'desc'       => esc_html__('If you check this options. When a swatch type is "image" and has an image. The image will be shown into the tooltip.', 'woolentor'),
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),
                    
                    array(
                        'id'       => 'ajax_variation_threshold',
                        'type'       => 'number',
                        'name'      => esc_html__('Change AJAX Variation Threshold', 'woolentor'),
                        'placeholder'=> '30',
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on'),
                        'tooltip'    => [
                            'text' => __('If a variable product has over 30 variants, WooCommerce doesn\'t allow you to show which combinations are unavailable for purchase. That\'s why customers need to check each combination to see if it is available or not. Although you can increase the threshold, keeping it at a standard value is recommended, so it doesn\'t negatively impact your website\'s performance.
                            <br/>Here "standard value" refers to the number of highest combinations you have set for one of your products.','woolentor'),
                            'placement' => 'top',
                        ],
                    ),

                    array(
                        'id'    => 'shape_style',
                        'type'    => 'select',
                        'name'   => esc_html__('Shape Style', 'woolentor'),
                        'options' => array(
                            'squared' => esc_html__('Squared', 'woolentor'),
                            'rounded' => esc_html__('Rounded', 'woolentor'),
                            'circle'  => esc_html__('Circle', 'woolentor'),
                        ),
                        'default'    => 'squared',
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),

                    array(
                        'id'       => 'enable_shape_inset',
                        'type'       => 'checkbox',
                        'name'      => esc_html__('Enable Shape Inset', 'woolentor'),
                        'desc'       => esc_html__('Shape inset is the empty space arround the swatch.', 'woolentor'),
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),

                    array(
                        'id'       => 'show_selected_attribute_name',
                        'type'       => 'checkbox',
                        'name'      => esc_html__('Show Selected Variation Name', 'woolentor'),
                        'default'    => 'on',
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on')
                    ),

                    array(
                        'id'         => 'variation_label_separator',
                        'type'         => 'text',
                        'name'        => esc_html__('Variation Label Separator', 'woolentor'),
                        'default'      => esc_html__(' : ', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'condition'    => array( 'key'=>'enable|show_selected_attribute_name', 'operator'=>'==|==', 'value'=>'on|on' ),
                    ),

                    array(
                        'id'  => 'disabled_attribute_type',
                        'type'  => 'select',
                        'name' => esc_html__('Disabled Attribute Type', 'woolentor'),
                        'options' => array(
                            ''                => esc_html__('Cross Sign', 'woolentor'),
                            'blur_with_cross' => esc_html__('Blur With Cross', 'woolentor'),
                            'blur'            => esc_html__('Blur', 'woolentor'),
                            'hide'            => esc_html__('Hide', 'woolentor'),
                        ),
                        'desc'       => esc_html__('Note: It will not effective when you have large number of variations but the "Ajax Variation Threshold" value is less than the number of variations.', 'woolentor'),
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on'),
                    ),

                    array(
                        'id'       => 'disable_out_of_stock',
                        'type'       => 'checkbox',
                        'name'      => esc_html__('Disable Variation Form for The "Out of Stock" Products', 'woolentor'),
                        'desc'       => esc_html__('If disabled, an out of stock message will be shown instead of showing the variations form / swatches.', 'woolentor'),
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable', 'operator'=>'==', 'value'=>'on'),
                    ),

                    // Archive page options
                    array(
                        'id'      => 'heading_2',
                        'type'      => 'title',
                        'heading'  => esc_html__( 'Shop / Archive Page - Swatch Options', 'woolentor' ),
                        'size'      => 'woolentor_style_seperator',
                        'condition' => array( 'key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on' ),
                    ),

                    array(
                        'id'      => 'pl_show_swatches_label',
                        'type'      => 'checkbox',
                        'name'     =>  esc_html__('Show Swatches Label', 'woolentor'),
                        'class'     => 'woolentor-action-field-left',
                        'condition' => array( 'key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on' ),
                    ),

                    array(
                        'id'      => 'pl_show_clear_link',
                        'type'      => 'checkbox',
                        'name'     =>  esc_html__('Show Reset Button', 'woolentor'),
                        'class'     => 'woolentor-action-field-left',
                        'default'   => 'on',
                        'condition' => array( 'key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on' ),
                    ),

                    array(
                        'id'         => 'pl_enable_swatch_limit',
                        'type'         => 'checkbox',
                        'name'        =>  esc_html__('Enable Swatch Limit', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'condition'    => array( 'key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on' )
                    ),

                    array(
                        'id'         => 'pl_limit',
                        'type'         => 'number',
                        'name'        =>  esc_html__('Number of Swatch to Show', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'condition'    => array('key'=>'enable|pl_enable_swatches|pl_enable_swatch_limit', 'operator'=>'==|==|==', 'value'=>'on|on|on')
                    ),

                    array(
                        'id'         => 'pl_more_text_type',
                        'type'         => 'select',
                        'name'        =>  esc_html__('More Text Type', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'options'      => array(
                            'text' => esc_html__('Text', 'woolentor'),
                            'icon' => esc_html__('Icon', 'woolentor'),
                        ),
                        'condition'    => array('key'=>'enable|pl_enable_swatches|pl_enable_swatch_limit', 'operator'=>'==|==|==', 'value'=>'on|on|on')
                    ),

                    array(
                        'id'         => 'pl_more_icon_enable_tooltip',
                        'type'         => 'checkbox',
                        'name'        =>  esc_html__('Enable Tooltip', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'condition'    => array('key'=>'enable|pl_enable_swatches|pl_enable_swatch_limit|pl_more_text_type', 'operator'=>'==|==|==|==', 'value'=>'on|on|on|icon')
                    ),

                    array(
                        'id'         => 'pl_more_icon_tooltip_text',
                        'type'         => 'text',
                        'name'        =>  esc_html__('Tooltip Text', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'condition'    => array('key'=>'enable|pl_enable_swatches|pl_enable_swatch_limit|pl_more_text_type|pl_more_icon_enable_tooltip', 'operator'=>'==|==|==|==|==', 'value'=>'on|on|on|icon|on')
                    ),

                    array(
                        'id'         => 'pl_more_text',
                        'type'         => 'text',
                        'name'        =>  esc_html__('More Text', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'condition'    => array('key'=>'enable|pl_enable_swatches|pl_enable_swatch_limit|pl_more_text_type', 'operator'=>'==|==|==|==', 'value'=>'on|on|on|text')
                    ),

                    array(
                        'id'    => 'pl_align',
                        'type'    => 'select',
                        'name'   => esc_html__('Swatches Align', 'woolentor'),
                        'options' => array(
                            'left'   => esc_html__('Left', 'woolentor'),
                            'center' => esc_html__('Center', 'woolentor'),
                            'right'  => esc_html__('Right', 'woolentor'),
                        ),
                        'default'   => 'center',
                        'class'     => 'woolentor-action-field-left',
                        'condition' => array( 'key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on' ),
                    ),

                    array(
                        'id'    => 'pl_position',
                        'type'    => 'select',
                        'name'   => esc_html__('Swatches Position', 'woolentor'),
                        'options' => array(
                            'before_title'    => esc_html__('Before Title', 'woolentor'),
                            'after_title'     => esc_html__('After Title', 'woolentor'),
                            'before_price'    => esc_html__('Before Price', 'woolentor'),
                            'after_price'     => esc_html__('After Price', 'woolentor'),
                            'custom_position' => esc_html__('Custom Position', 'woolentor'),
                            'shortcode'       => esc_html__('Use Shortcode', 'woolentor'),
                        ),
                        'default'   => 'after_title',
                        'class'     => 'woolentor-action-field-left',
                        'condition' => array( 'key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on' ),
                    ),

                    array(
                        'id' => 'short_code_display',
                        'name'   => esc_html__('Swatches Shortcode', 'woolentor'),
                        'type'=>'html',
                        'html'=>'<code>[swatchly_pl_swatches]</code> Use this shortcode to show the variation Swatches.',
                        'condition' => array( 'key'=>'pl_position', 'operator'=>'==', 'value'=>'shortcode' ),
                    ),

                    array(
                        'id'       => 'pl_custom_position_hook_name',
                        'type'       => 'text',
                        'name'      =>  esc_html__('Hook Name', 'woolentor'),
                        'desc'       =>  esc_html__('e.g: woocommerce_after_shop_loop_item_title', 'woolentor'),
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable|pl_enable_swatches|pl_position', 'operator'=>'==|==|==', 'value'=>'on|on|custom_position'),
                    ), 

                    array(
                        'id'       => 'pl_custom_position_hook_priority',
                        'type'       => 'text',
                        'name'      =>  esc_html__('Hook Priority', 'woolentor'),
                        'desc'       =>  esc_html__('Default: 10', 'woolentor'),
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable|pl_enable_swatches|pl_position', 'operator'=>'==|==|==', 'value'=>'on|on|custom_position'),
                    ), 

                    array(
                        'id'        => 'pl_product_thumbnail_selector',
                        'type'        => 'text',
                        'name'       =>  esc_html__('Product Thumbnail Selector', 'woolentor'),
                        'placeholder' => esc_html__('Example: img.attachment-woocommerce_thumbnail', 'woolentor'),
                        'class'       => 'woolentor-action-field-left',
                        'condition'   => array( 'key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on' ),
                        'tooltip'     => [
                            'text' => esc_html__( 'Some themes remove the default product image. In this case, variation image will not be changed after choose a variation. Here you can place the CSS selector of the product thumbnail, so the product image will be chagned once a variation is choosen.', 'woolentor' ),
                            'placement' => 'top',
                        ],
                    ), 

                    array(
                        'id'         => 'pl_enable_ajax_add_to_cart',
                        'type'         => 'checkbox',
                        'name'        =>  esc_html__('Enable AJAX Add to Cart', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'condition'    => array('key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on')
                    ),

                    array(
                        'id'       => 'pl_add_to_cart_text',
                        'type'       => 'text',
                        'name'      =>  esc_html__('Add to Cart Text', 'woolentor'),
                        'desc'       =>  esc_html__('Leave it empty for default.', 'woolentor'),
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable|pl_enable_swatches|pl_enable_ajax_add_to_cart', 'operator'=>'==|==|==', 'value'=>'on|on|on'),
                    ),

                    array(
                        'id'       => 'pl_hide_wc_forward_button',
                        'type'       => 'checkbox',
                        'name'      =>  esc_html__('Hide "View Cart" button after Added to Cart', 'woolentor'),
                        'class'      => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable|pl_enable_swatches|pl_enable_ajax_add_to_cart', 'operator'=>'==|==|==', 'value'=>'on|on|on'),
                        'tooltip'     => [
                            'text' => esc_html__('After successfully add to cart, a new button shows linked to the cart page. You can controll of that button from here. Note: If redirect option is enable from WooCommerce it will not work.', 'woolentor'),
                            'placement' => 'top',
                        ],
                    ),

                    array(
                        'id'         => 'pl_enable_cart_popup_notice',
                        'type'         => 'checkbox',
                        'name'        =>  esc_html__('Enable poupup notice after added to cart', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'condition'  => array('key'=>'enable|pl_enable_swatches|pl_enable_ajax_add_to_cart', 'operator'=>'==|==|==', 'value'=>'on|on|on'),
                        'tooltip'     => [
                            'text' => esc_html__('After successfully add to cart, a pupup notice will be generated containing a button linked to the cart page. Note: If redirect option is enable from WooCommerce it will not work.', 'woolentor'),
                            'placement' => 'top',
                        ],
                    ),

                    array(
                        'id'      => 'pl_enable_catalog_mode_heading',
                        'heading'  => esc_html__( 'Shop / Archive Page - Catalog Mode', 'woolentor-pro' ),
                        'type'      => 'title',
                        'condition'    => array('key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on')
                    ),

                    array(
                        'id'         => 'pl_enable_catalog_mode',
                        'type'         => 'checkbox',
                        'name'        =>  esc_html__('Enable Catalog Mode', 'woolentor'),
                        'class'        => 'woolentor-action-field-left',
                        'condition'    => array('key'=>'enable|pl_enable_swatches', 'operator'=>'==|==', 'value'=>'on|on')
                    ),

                    array(
                        'id'         => 'pl_catalog_global_attributes',
                        'type'         => 'repeater',
                        'name'        =>  esc_html__('Catalog Mode - Global Attributes', 'woolentor'),
                        'desc'         =>  esc_html__('Select and add the global attributes below, that you want to show on the shop page. The first attribute from a product will be used if multiple attributes match it. It is possible to change the first attribute by dragging & dropping from the product edit page.', 'woolentor'),
                        'title_field'  => 'attribute',
                        'class'        => 'woolentor-action-field-left',
                        'condition'    => array('key'=>'enable|pl_enable_swatches|pl_enable_catalog_mode', 'operator'=>'==|==|==', 'value'=>'on|on|on'),
                        'options'      => [
                            'button_label'=> esc_html__('Add Attribute', 'woolentor'),
                        ],
                        'fields'       => array(
                            array(
                                'id'  => 'attribute',
                                'name' => esc_html__('Attribute', 'woolentor'),
                                'type'  => 'select',
                                'options' => wc_get_attribute_taxonomy_labels(),
                                'class'   => 'woolentor-action-field-left',
                            ),
                        ),
                    ),

                    array(
                        'id'         => 'pl_catalog_custom_attributes',
                        'type'         => 'textarea',
                        'name'        =>  esc_html__('Catalog Mode - Custom Attributes', 'woolentor'),
                        'desc'         =>  __('Write each attribute per line. <br>Note: The custom attributes values are <b>Case Sensitive</b>', 'woolentor'),
                        'class'        => '',
                        'condition'    => array('key'=>'enable|pl_enable_swatches|pl_enable_catalog_mode', 'operator'=>'==|==|==', 'value'=>'on|on|on'),
                    ),
                    

                )

            ),

            array(
                'id'     => 'woolentor_product_filter_settings',
                'name'    => esc_html__( 'Product Filter', 'woolentor-pro' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_product_filter_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation'     => esc_url('https://woolentor.com/doc/product-filter/'),
                'setting_fields' => apply_filters( 'woolentor_pro_product_filter_fields', array() )
            ),

            // order bump
            array(
                'id'              => 'woolentor_order_bump_settings',
                'name'             => esc_html__( 'Order Bump', 'woolentor-pro' ),
                'type'              => 'module',
                'default'           => 'off',
                'section'           => 'woolentor_order_bump_settings',
                'option_id'         => 'enable',
                'require_settings'  => true,
                'documentation'     => esc_url('https://woolentor.com/doc/woocommerce-order-bump/'),
                'setting_fields' => array(
                    array(
                        'id'      => 'enable',
                        'name'     => esc_html__( 'Enable', 'woolentor-pro' ),
                        'type'      => 'checkbox',
                        'desc'      => esc_html__( 'Enable Order Bump Module.', 'woolentor-pro' ),
                        'default'   => 'off',
                        'class'     => 'woolentor-action-field-left'
                    ),
                    array(
                        'id'      => 'enable_test_mode',
                        'name'     => esc_html__( 'Test Mode', 'woolentor-pro' ),
                        'type'      => 'checkbox',
                        'desc'      => esc_html__( 'Test mode displays order bumps only for the Administrator when enabled.', 'woolentor-pro' ),
                        'default'   => 'off',
                        'class'     => 'woolentor-action-field-left',
                        'condition'   => array( 'key'=>'enable', 'operator'=>'==', 'value'=>'on' ),
                    ),
                    array(
                        'id'        => 'discount_base_price',
                        'name'       => esc_html__( 'Discount Base Price', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Specify which price should be used for "Order Bump" discount calculation.', 'woolentor-pro' ),
                        'type'        => 'select',
                        'options'     => array(
                            'regular_price' => esc_html__('Regular Price', 'woolentor-pro'),
                            'sale_price'    => esc_html__('Sale Price', 'woolentor-pro'),
                        ),
                        'condition'   => array( 'key'=>'enable', 'operator'=>'==', 'value'=>'on' ),
                        'class'       => 'woolentor-action-field-left'
                    ),
                )
            ),

            array(
                'id'     => 'woolentor_email_customizer_settings',
                'name'    => esc_html__( 'Email Customizer', 'woolentor-pro' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_email_customizer_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/email-customizer/'),
                'setting_fields' => array(

                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can enable / disable email customizer from here.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'width',
                        'name'       => esc_html__( 'Width (px)', 'woolentor-pro' ),
                        'desc'        => esc_html__( 'Insert email template width.', 'woolentor-pro' ),
                        'type'        => 'number',
                        'default'     => '600',
                        'placeholder' => '600',
                        'class'       => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'        => 'go_email_template_builder',
                        'html'        => wp_kses_post( '<a href="'.admin_url('edit.php?post_type=woolentor-template&template_type=emails&tabs=emails').'" target="_blank">Create your own customized Email.</a>' ),
                        'type'        => 'html',
                        'class'       => 'woolentor-action-field-left'
                    ),

                )

            ),

            array(
                'id'     => 'woolentor_email_automation_settings',
                'name'    => esc_html__( 'Email Automation', 'woolentor-pro' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_email_automation_settings',
                'option_id'=> 'enable',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/email-automation/'),
                'setting_fields' => array(

                    array(
                        'id'  => 'enable',
                        'name' => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can enable / disable email automation from here.', 'woolentor-pro' ),
                        'type'  => 'checkbox',
                        'default' => 'off',
                        'class' => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'    => 'email_from_name',
                        'name'   => esc_html__( 'From name', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'How the sender name appears in outgoing email.', 'woolentor-pro' ),
                        'type'    => 'text',
                        'default' => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
                        'class'   => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'    => 'email_from_address',
                        'name'   => esc_html__( 'From address', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'How the sender email appears in outgoing email.', 'woolentor-pro' ),
                        'type'    => 'text',
                        'default' => get_option( 'admin_email' ),
                        'class'   => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'        => 'go_email_template_builder',
                        'html'        => wp_kses_post( 'Before you &nbsp;<a href="'.admin_url('edit.php?post_type=wlea-email').'" target="_blank">Configure the Email Automation</a> please make sure that you have enabled the automation and saved the change(s).' ),
                        'type'        => 'html',
                        'class'       => 'woolentor-action-field-left'
                    ),

                )

            ),

            // popup_builder_settings
            array(
                'id'     => 'woolentor_popup_builder_settings',
                'name'    => esc_html__( 'Popup Builder', 'woolentor-pro' ),
                'type'     => 'module',
                'default'  => 'off',
                'section'  => 'woolentor_popup_builder_settings',
                'option_id'=> 'enable',
                'documentation' => esc_url('https://woolentor.com/doc/popup-builder/'),
                'require_settings'  => true,
                'setting_fields' => array(

                    array(
                        'id'    => 'enable',
                        'name'   => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Enable / disable this module.', 'woolentor-pro' ),
                        'type'    => 'checkbox',
                        'default' => 'off',
                        'class'   => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'  => 'width',
                        'name' => esc_html__( 'Popup Width', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can set the container width of the Popup area. Example: 600px', 'woolentor-pro' ),
                        'type'              => 'text',
                        'default'           => '600px',
                        'class'             => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'height',
                        'name' => esc_html__( 'Popup Height', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'You can set the container height of the Popup area. Example: 600px', 'woolentor-pro' ),
                        'type'  => 'text',
                        'class' => 'woolentor-action-field-left',
                    ),

                    array(
                        'id'  => 'z_index',
                        'name' => esc_html__( 'Z-Index', 'woolentor-pro' ),
                        'desc'  => __( 'You can set the z-index of the Popup. <br>Example: 9999', 'woolentor-pro' ),
                        'type'     => 'number',
                        'class'    => 'woolentor-action-field-left',
                        'default'  => '9999',
                    ),
                    array(
                        'id'        => 'go_popup_template_builder',
                        'html'        => wp_kses_post( '<a href="'.admin_url('edit.php?post_type=woolentor-template&template_type=popup&tabs=popup').'" target="_blank">Create or Import Popups from here.</a>' ),
                        'type'        => 'html',
                        'class'       => 'woolentor-action-field-left'
                    ),

                )
            ),

            array(
                'id'  => 'ajaxsearch',
                'name' => esc_html__( 'AJAX Search Widget', 'woolentor-pro' ),
                'desc'  => esc_html__( 'AJAX Search Widget', 'woolentor-pro' ),
                'type'   => 'element',
                'default'=> 'off',
                'documentation' => esc_url('https://woolentor.com/doc/how-to-use-woocommerce-ajax-search/')
            ),

            array(
                'id'   => 'ajaxcart_singleproduct',
                'name'  => esc_html__( 'Single Product AJAX Add To Cart', 'woolentor-pro' ),
                'desc'   => esc_html__( 'AJAX Add to Cart on Single Product page', 'woolentor-pro' ),
                'type'   => 'element',
                'default'=> 'off',
                'documentation' => esc_url('https://woolentor.com/doc/single-product-ajax-add-to-cart/')
            ),

            array(
                'id'   => 'single_product_sticky_add_to_cart',
                'name'  => esc_html__( 'Single Product Sticky Add To Cart', 'woolentor-pro' ),
                'desc'   => esc_html__( 'Sticky Add to Cart on Single Product page', 'woolentor-pro' ),
                'type'   => 'element',
                'default'=> 'off',
                'class'  =>'single_product_sticky_add_to_cart',
                'require_settings'  => true,
                'parent_id' => 'woolentor_others_tabs',
                'documentation' => esc_url('https://woolentor.com/doc/single-product-sticky-add-to-cart/'),
                'setting_fields' => array(
                    
                    array(
                        'id'        => 'sps_add_to_cart_title_tag',
                        'name'       => esc_html__( 'Title HTML Tag', 'woolentor' ),
                        'type'        => 'select',
                        'options'     => function_exists('woolentor_html_tag_lists') ? woolentor_html_tag_lists() : [],
                        'default'     => 'h4',
                        'class'       => 'woolentor-action-field-left'
                    ),

                    array(
                        'id'  => 'sps_add_to_cart_color',
                        'name' => esc_html__( 'Sticky cart button color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Single product sticky add to cart button color.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),
        
                    array(
                        'id'  => 'sps_add_to_cart_bg_color',
                        'name' => esc_html__( 'Sticky cart button background color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Single product sticky add to cart button background color.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),
        
                    array(
                        'id'  => 'sps_add_to_cart_hover_color',
                        'name' => esc_html__( 'Sticky cart button hover color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Single product sticky add to cart button hover color.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),
        
                    array(
                        'id'  => 'sps_add_to_cart_bg_hover_color',
                        'name' => esc_html__( 'Sticky cart button hover background color', 'woolentor-pro' ),
                        'desc'  => esc_html__( 'Single product sticky add to cart button hover background color.', 'woolentor-pro' ),
                        'type'  => 'color',
                        'class' => 'woolentor-action-field-left',
                    ),
        
                    array(
                        'id'    => 'sps_add_to_cart_padding',
                        'name'   => esc_html__( 'Sticky cart button padding', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Single product sticky add to cart button padding.', 'woolentor-pro' ),
                        'type'    => 'dimensions',
                        'options' => [
                            'top'   => esc_html__( 'Top', 'woolentor-pro' ),
                            'right' => esc_html__( 'Right', 'woolentor-pro' ),
                            'bottom'=> esc_html__( 'Bottom', 'woolentor-pro' ),
                            'left'  => esc_html__( 'Left', 'woolentor-pro' ),
                            'unit'  => esc_html__( 'Unit', 'woolentor-pro' ),
                        ],
                        'class' => 'woolentor-action-field-left woolentor-dimention-field-left',
                    ),

                    array(
                        'id'    => 'sps_add_to_cart_border_radius',
                        'name'   => esc_html__( 'Sticky cart button border radius', 'woolentor-pro' ),
                        'desc'    => esc_html__( 'Single product sticky add to cart button border radius.', 'woolentor-pro' ),
                        'type'    => 'dimensions',
                        'options' => [
                            'top'   => esc_html__( 'Top', 'woolentor-pro' ),
                            'right' => esc_html__( 'Right', 'woolentor-pro' ),
                            'bottom'=> esc_html__( 'Bottom', 'woolentor-pro' ),
                            'left'  => esc_html__( 'Left', 'woolentor-pro' ),
                            'unit'  => esc_html__( 'Unit', 'woolentor-pro' ),
                        ],
                        'class' => 'woolentor-action-field-left woolentor-dimention-field-left',
                    ),

                )
            ),

            array(
                'id'   => 'redirect_add_to_cart',
                'name'  => esc_html__( 'Redirect to Checkout', 'woolentor-pro' ),
                'type'   => 'element',
                'default'=> 'off',
                'documentation' => esc_url('https://woolentor.com/doc/redirect-to-checkout/')
            ),

            array(
                'id'   => 'multi_step_checkout',
                'name'  => esc_html__( 'Multi Step Checkout', 'woolentor-pro' ),
                'type'   => 'element',
                'default'=> 'off',
                'documentation' => esc_url('https://woolentor.com/doc/woocommerce-multi-step-checkout/')
            ),

            array(
                'id'  => 'loadproductlimit',
                'name' => esc_html__( 'Load Products in Elementor Addons', 'woolentor-pro' ),
                'desc'  => esc_html__( 'Set the number of products to load in Elementor Addons', 'woolentor-pro' ),
                'min'               => 1,
                'max'               => 1000,
                'step'              => '1',
                'type'              => 'number',
                'default'           => '20',
                'sanitize_callback' => 'floatval',
                'column'            => 1,
            )

        );

        // Post Duplicator Condition
        if( !is_plugin_active('ht-mega-for-elementor/htmega_addons_elementor.php') ){

            $post_types = woolentor_get_post_types( array( 'defaultadd' => 'all' ) );
            if ( did_action( 'elementor/loaded' ) && defined( 'ELEMENTOR_VERSION' ) ) {
                $post_types['elementor_library'] = esc_html__( 'Templates', 'woolentor' );
            }

            // Add Option in array before the last element
            $lastKey = array_key_last($fields['woolentor_others_tabs']);
            $lastValue = $fields['woolentor_others_tabs'][$lastKey];
            unset($fields['woolentor_others_tabs'][$lastKey]);

            $fields['woolentor_others_tabs'][] = [
                'id'     => 'postduplicator',
                'name'    => esc_html__( 'Post Duplicator', 'woolentor' ),
                'type'     => 'element',
                'default'  => 'off',
                'require_settings'  => true,
                'documentation' => esc_url('https://woolentor.com/doc/duplicate-woocommerce-product/'),
                'parent_id' => 'woolentor_others_tabs',
                'setting_fields' => array(
                    
                    array(
                        'id'    => 'postduplicate_condition',
                        'name'   => esc_html__( 'Post Duplicator Condition', 'woolentor' ),
                        'desc'    => esc_html__( 'You can enable duplicator for individual post.', 'woolentor' ),
                        'type'    => 'multiselect',
                        'default' => '',
                        'options' => $post_types,
                        'class' => 'woolentor-full-width-field'
                    )

                )
            ];

            $fields['woolentor_others_tabs'][] = $lastValue;

        }

        // FlashSale Addons
        if( woolentor_get_option('enable', 'woolentor_flash_sale_settings') == 'on' ){
            $fields['woolentor_elements_tabs'][] = [
                'id'    => 'product_flash_sale',
                'name'   => esc_html__( 'Product Flash Sale', 'woolentor' ),
                'type'    => 'element',
                'default' => 'on'
            ];

            // Block
            $fields['woolentor_gutenberg_tabs'][] = [
                'id'  => 'product_flash_sale',
                'name' => esc_html__( 'Product Flash Sale', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ];

        }

        // Wishsuite Addons
        if( class_exists('WishSuite_Base') || class_exists('Woolentor_WishSuite_Base') ){
            $fields['woolentor_elements_tabs'][] = [
                'id'      => 'wb_wishsuite_table',
                'name'     => esc_html__( 'WishSuite Table', 'woolentor' ),
                'type'      => 'element',
                'default'   => 'on',
            ];
            $fields['woolentor_elements_tabs'][] = [
                'id'      => 'wb_wishsuite_counter',
                'name'     => esc_html__( 'WishSuite Counter', 'woolentor' ),
                'type'      => 'element',
                'default'   => 'on',
            ];

            // Block
            $fields['woolentor_gutenberg_tabs'][] = [
                'id'  => 'wishsuite_table',
                'name' => esc_html__( 'WishSuite Table', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ];
            $fields['woolentor_gutenberg_tabs'][] = [
                'id'  => 'wishsuite_counter',
                'name' => esc_html__( 'WishSuite Counter', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ];
        }

        // Ever Compare Addons
        if( class_exists('Ever_Compare') || class_exists('Woolentor_Ever_Compare') ){
            $fields['woolentor_elements_tabs'][] = [
                'id'      => 'wb_ever_compare_table',
                'name'     => esc_html__( 'Ever Compare', 'woolentor' ),
                'type'      => 'element',
                'default'   => 'on',
            ];

            // Block
            $fields['woolentor_gutenberg_tabs'][] = [
                'id'  => 'ever_compare_table',
                'name' => esc_html__( 'Ever Compare', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on',
            ];

        }

        // JustTable Addons
        if( is_plugin_active('just-tables/just-tables.php') || is_plugin_active('just-tables-pro/just-tables-pro.php') ){
            $fields['woolentor_elements_tabs'][] = [
                'id'   => 'wb_just_table',
                'name'  => esc_html__( 'JustTable', 'woolentor' ),
                'type'   => 'element',
                'default' => 'on'
            ];
        }

        // whols Addons
        if( is_plugin_active('whols/whols.php') || is_plugin_active('whols-pro/whols-pro.php') ){
            $fields['woolentor_elements_tabs'][] = [
                'id'   => 'wb_whols',
                'name'  => esc_html__( 'Whols', 'woolentor' ),
                'type'   => 'element',
                'default' => 'on'
            ];
        }

        // Multicurrency Addons
        if( is_plugin_active('wc-multi-currency/wcmilticurrency.php') || is_plugin_active('multicurrencypro/multicurrencypro.php') ){
            $fields['woolentor_elements_tabs'][] = [
                'id'   => 'wb_wc_multicurrency',
                'name'  => esc_html__( 'Multi Currency', 'woolentor' ),
                'type'   => 'element',
                'default' => 'on'
            ];
        }

        return $fields;
    }

     /**
     * [elements_tabs_admin_fields] Elements tabs admin fields
     * @return [array]
     */
    public function elements_tabs_admin_fields( $fields ){
        $fields = array_merge( $fields, array(
            array(
                'id'      => 'general_widget_heading',
                'heading'  => esc_html__( 'General', 'woolentor-pro' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'  => 'product_tabs',
                'name' => esc_html__( 'Product Tab', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'    => 'product_grid_modern',
                'name' => esc_html__( 'Product Grid - Modern', 'woolentor-pro' ),
                'type'    => 'element',
                'default' => 'on',
                'documentation' => esc_url('https://woolentor.com/doc/how-to-use-product-grid-modern-layout-for-elementor/'),
                'badge'   => [
                    'is_active' => true,
                    'type'      => 'new',
                    'label'     => esc_html__('New','woolentor-pro')
                ]
            ),
            array(
                'id'    => 'product_grid_luxury',
                'name' => esc_html__( 'Product Grid - Luxury', 'woolentor-pro' ),
                'type'    => 'element',
                'default' => 'on',
                'documentation' => esc_url('https://woolentor.com/doc/how-to-use-product-grid-luxury-widget-for-elementor/'),
                'badge'   => [
                    'is_active' => true,
                    'type'      => 'new',
                    'label'     => esc_html__('New','woolentor-pro')
                ]
            ),
            array(
                'id'    => 'product_grid_editorial',
                'name' => esc_html__( 'Product Grid - Editorial', 'woolentor-pro' ),
                'type'    => 'element',
                'default' => 'on',
                'documentation' => esc_url('https://woolentor.com/doc/product-grid-editorial-layout-for-elementor/'),
                'badge'   => [
                    'is_active' => true,
                    'type'      => 'new',
                    'label'     => esc_html__('New','woolentor-pro')
                ]
            ),
            array(
                'id'    => 'product_grid_magazine',
                'name' => esc_html__( 'Product Grid - Magazine', 'woolentor-pro' ),
                'type'    => 'element',
                'default' => 'on',
                'documentation' => esc_url('https://woolentor.com/doc/product-grid-magazine-layout-for-elementor/'),
                'badge'   => [
                    'is_active' => true,
                    'type'      => 'new',
                    'label'     => esc_html__('New','woolentor-pro')
                ]
            ),

            array(
                'id'  => 'universal_product',
                'name' => wp_kses_post( 'Universal Product (<a href="'.esc_url(admin_url( 'admin.php?page=woolentor#/style' )).'">Style Settings</a>)' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'product_curvy',
                'name' => esc_html__( 'WL: Product Curvy', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'product_image_accordion',
                'name' => esc_html__( 'WL: Product Image Accordion', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'product_accordion',
                'name' => esc_html__( 'WL: Product Accordion', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'add_banner',
                'name' => esc_html__( 'Ads Banner', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'special_day_offer',
                'name' => esc_html__( 'Special Day Offer', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_customer_review',
                'name' => esc_html__( 'Customer Review', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_image_marker',
                'name' => esc_html__( 'Image Marker', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_category',
                'name' => esc_html__( 'Category List', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_category_grid',
                'name' => esc_html__( 'Category Grid', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_onepage_slider',
                'name' => esc_html__( 'One Page Slider', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_testimonial',
                'name' => esc_html__( 'Testimonial', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_product_grid',
                'name' => esc_html__( 'Product Grid', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_recently_viewed_products',
                'name' => esc_html__( 'Recently Viewed Products', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_product_expanding_grid',
                'name' => esc_html__( 'Product Expanding Grid', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_product_filterable_grid',
                'name' => esc_html__( 'Product Filterable Grid', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_store_features',
                'name' => esc_html__( 'Store Features', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_faq',
                'name' => esc_html__( 'FAQ', 'woolentor' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_brand',
                'name' => esc_html__( 'Brand Logo', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_template_selector',
                'name' => esc_html__( 'Template Selector', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'archive_widget_heading',
                'heading'  => esc_html__( 'Shop / Archive', 'woolentor-pro' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'  => 'wb_archive_product',
                'name' => esc_html__( 'Product Archive (Default)', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_custom_archive_layout',
                'name' => esc_html__( 'Product Archive Layout (Custom)', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_archive_result_count',
                'name' => esc_html__( 'Archive Result Count', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_archive_catalog_ordering',
                'name' => esc_html__( 'Archive Catalog Ordering', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_archive_title',
                'name' => esc_html__( 'Archive Title', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_product_filter',
                'name' => esc_html__( 'Product Filter', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_product_horizontal_filter',
                'name' => esc_html__( 'Product Horizontal Filter', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_advance_product_filter',
                'name' => esc_html__( 'Advanced Product Filter', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'single_widget_heading',
                'heading'  => esc_html__( 'Single Product', 'woolentor-pro' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'  => 'wb_product_title',
                'name' => esc_html__( 'Product Title', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_add_to_cart',
                'name' => esc_html__( 'Add to Cart Button', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_breadcrumbs',
                'name' => esc_html__( 'Breadcrumbs', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_additional_information',
                'name' => esc_html__( 'Additional Information', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_data_tab',
                'name' => esc_html__( 'Product data Tab', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_related',
                'name' => esc_html__( 'Related Product', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_related_product',
                'name' => esc_html__( 'Related Product..( Custom )', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_description',
                'name' => esc_html__( 'Product Description', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_short_description',
                'name' => esc_html__( 'Product Short Description', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_price',
                'name' => esc_html__( 'Product Price', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_rating',
                'name' => esc_html__( 'Product Rating', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_reviews',
                'name' => esc_html__( 'Product Reviews', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_image',
                'name' => esc_html__( 'Product Image', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_product_advance_thumbnails',
                'name' => __( 'Advance Product Image', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            
            array(
                'id'  => 'wl_product_advance_thumbnails_zoom',
                'name' => __( 'Product Image With Zoom', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_product_video_gallery',
                'name' => esc_html__( 'Product Video Gallery', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_upsell',
                'name' => esc_html__( 'Product Upsell', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_product_upsell_custom',
                'name' => esc_html__( 'Upsell Product..( Custom )', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_stock',
                'name' => esc_html__( 'Product Stock Status', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_meta',
                'name' => esc_html__( 'Product Meta Info', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_sku',
                'name' => esc_html__( 'Product SKU', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_tags',
                'name' => esc_html__( 'Product Tags', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_categories',
                'name' => esc_html__( 'Product Categories', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_social_shere',
                'name' => esc_html__( 'Product Social Share', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_stock_progress_bar',
                'name' => esc_html__( 'Stock Progressbar', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_single_product_sale_schedule',
                'name' => esc_html__( 'Product Sale Schedule', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_call_for_price',
                'name' => esc_html__( 'Call for Price', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_suggest_price',
                'name' => esc_html__( 'Suggest Price', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wb_product_qr_code',
                'name' => esc_html__( 'QR Code', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_single_pdoduct_navigation',
                'name' => __( 'Product Navigation', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'cart_widget_heading',
                'heading'  => esc_html__( 'Cart', 'woolentor-pro' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'  => 'wl_cart_table',
                'name' => esc_html__( 'Product Cart Table', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_cart_table_list',
                'name' => esc_html__( 'Product Cart Table (List Style)', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_cart_total',
                'name' => esc_html__( 'Product Cart Total', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_cartempty_shopredirect',
                'name' => esc_html__( 'Return To Shop Button', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_cross_sell',
                'name' => esc_html__( 'Product Cross Sell', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_cross_sell_custom',
                'name' => esc_html__( 'Cross Sell Product..( Custom )', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_cartempty_message',
                'name' => esc_html__( 'Empty Cart Message', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'checkout_widget_heading',
                'heading'  => esc_html__( 'Checkout', 'woolentor-pro' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'  => 'wl_checkout_billing',
                'name' => esc_html__( 'Checkout Billing Form', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_checkout_shipping_form',
                'name' => esc_html__( 'Checkout Shipping Form', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_checkout_shipping_method',
                'name' => esc_html__( 'Checkout Shipping Method', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_checkout_additional_form',
                'name' => esc_html__( 'Checkout Additional..', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_checkout_payment',
                'name' => esc_html__( 'Checkout Payment', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_checkout_coupon_form',
                'name' => esc_html__( 'Checkout Coupon Form', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_checkout_login_form',
                'name' => esc_html__( 'Checkout Login Form', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_order_review',
                'name' => esc_html__( 'Checkout Order Review', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'myaccount_widget_heading',
                'heading'  => esc_html__( 'My Account', 'woolentor-pro' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'  => 'wl_myaccount_account',
                'name' => esc_html__( 'My Account', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_navigation',
                'name' => esc_html__( 'My Account Navigation', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_dashboard',
                'name' => esc_html__( 'My Account Dashboard', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_download',
                'name' => esc_html__( 'My Account Download', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_edit_account',
                'name' => esc_html__( 'My Account Edit', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_address',
                'name' => esc_html__( 'My Account Address', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_login_form',
                'name' => esc_html__( 'Login Form', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_register_form',
                'name' => esc_html__( 'Registration Form', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_logout',
                'name' => esc_html__( 'My Account Logout', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_order',
                'name' => esc_html__( 'My Account Order', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_lostpassword',
                'name' => esc_html__( 'Lost Password Form', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_myaccount_resetpassword',
                'name' => esc_html__( 'Reset Password Form', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'      => 'thankyou_widget_heading',
                'heading'  => esc_html__( 'Thank You', 'woolentor-pro' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),

            array(
                'id'  => 'wl_thankyou_order',
                'name' => esc_html__( 'Thank You Order', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_thankyou_customer_address_details',
                'name' => esc_html__( 'Thank You Cus.. Address', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),

            array(
                'id'  => 'wl_thankyou_order_details',
                'name' => esc_html__( 'Thank You Order Details', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            )

        ) );

        return $fields;
    }


     /**
     * [elements_tabs_additional_widget_admin_fields] Elements tabs admin fields
     * @return [array]
     */
    public function elements_tabs_additional_widget_admin_fields( $fields ){
        $fields = array_merge( $fields, array(
            array(
                'id'      => 'additional_widget_heading',
                'heading'  => esc_html__( 'Additional', 'woolentor-pro' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            )
        ) );

        return $fields;
    }

    /**
     * [remove_free_vs_pro] Remove Free VS Pro tab from admin tab
     * @return [array]
     */
    public function remove_free_vs_pro( $tabs ){

        $position = key(
			array_filter( $tabs,  
				static function( $item ) {
					return $item['id'] === 'woolentor_freevspro_tabs';
				}
			)
		);

        unset( $tabs[$position] );

        return $tabs;
    }

     /**
     * [template_menu_navs] Admin Post Type tabs
     * @return [array]
     */
    public function template_menu_navs( $navs ){

        $tabs = [
			'cart' => [
				'label'		=>__('Cart','woolentor'),
				'submenu' 	=>[
					'emptycart' => [
						'label'	=>__('Empty Cart','woolentor-pro')
					],
					'minicart' => [
						'label'		=> __('Side Mini Cart' ,'woolentor-pro')
					],
				]
			],
			'checkout' => [
				'label'	=>__('Checkout','woolentor-pro'),
				'submenu' => [
					'checkouttop' => [
						'label'	=>__('Checkout Top','woolentor-pro')
					],
				]
			],
			'thankyou' => [
				'label'	=>__('Thank You','woolentor')
			],
			'myaccount' => [
				'label'	  =>__('My Account','woolentor'),
				'submenu' => [
					'myaccountlogin' => [
						'label'	=> __('Login / Register','woolentor-pro')
					],
					'dashboard' => [
						'label'	=> __('Dashboard','woolentor-pro')
					],
					'orders' => [
						'label'	=> __('Orders','woolentor-pro')
					],
					'downloads' => [
						'label'	=> __('Downloads','woolentor-pro')
					],
					'edit-address' => [
						'label'	=> __('Address','woolentor-pro')
					],
					'edit-account' => [
						'label'	=> __('Account Details','woolentor-pro')
					],
					'lost-password' => [
						'label'	=> __('Lost Password','woolentor-pro')
					],
					'reset-password' => [
						'label'	=> __('Reset Password','woolentor-pro')
					],
				]
            ],
            'quickview' => [
				'label'	=>__('Quick View','woolentor')
			]
			
		];

        if ( ! did_action( 'elementor/loaded' ) ) {
            unset( $tabs['checkout']['submenu']['checkouttop'] );
        }

        $navs = array_merge( $navs, $tabs );
        return $navs;

    }

     /**
     * [template_type] Template types
     * @return [array]
     */
    function template_type( $types ){

        $template_type = [
			'cart' => [
				'label'		=>__('Cart','woolentor'),
				'optionkey'	=>'productcartpage'
			],
			'emptycart' => [
				'label'		=>__('Empty Cart','woolentor'),
				'optionkey'	=>'productemptycartpage'
			],
			'checkout' => [
				'label'		=>__('Checkout','woolentor'),
				'optionkey'	=>'productcheckoutpage'
			],
			'checkouttop' => [
				'label'		=>__('Checkout Top','woolentor'),
				'optionkey'	=>'productcheckouttoppage'
			],
			'thankyou' => [
				'label'		=>__('Thank You','woolentor'),
				'optionkey'	=>'productthankyoupage'
			],
			'myaccount' => [
				'label'		=>__('My Account','woolentor'),
				'optionkey'	=>'productmyaccountpage'
			],
			'myaccountlogin' => [
				'label'		=> __('My Account Login / Register','woolentor'),
				'optionkey'	=> 'productmyaccountloginpage'
			],
            'dashboard' => [
                'label'	    => __('My Account Dashboard','woolentor-pro'),
                'optionkey'	=> 'dashboard'
            ],
            'orders' => [
                'label'	=> __('My Account Orders','woolentor-pro'),
                'optionkey'	=> 'orders'
            ],
            'downloads' => [
                'label'	=> __('My Account Downloads','woolentor-pro'),
                'optionkey'	=> 'downloads'
            ],
            'edit-address' => [
                'label'	=> __('My Account Address','woolentor-pro'),
                'optionkey'	=> 'edit-address'
            ],
            'edit-account' => [
                'label'	=> __('My Account Details','woolentor-pro'),
                'optionkey'	=> 'edit-account'
            ],
            'lost-password' => [
                'label'	=> __('My Account Lost Password','woolentor-pro'),
                'optionkey'	=> 'lost-password'
            ],
            'reset-password' => [
                'label'	=> __('My Account Reset Password','woolentor-pro'),
                'optionkey'	=> 'reset-password'
            ],
            'quickview' => [
                'label'	=> __('Quick View','woolentor-pro'),
                'optionkey'	=> 'productquickview'
            ],
            'minicart' => [
                'label'	=> __('Side Mini Cart','woolentor-pro'),
                'optionkey'	=> 'mini_cart_layout'
            ]
		];

        if ( ! did_action( 'elementor/loaded' ) ) {
            unset( $template_type['checkouttop'] );
        }

        $types = array_merge( $types, $template_type );

        return $types;

    }

}

Woolentor_Admin_Init_Pro::instance();