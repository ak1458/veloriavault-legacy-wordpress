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
if( !class_exists('WooLentor_Product_Grid_Base_Widget') ){
    require_once WOOLENTOR_ADDONS_PL_PATH . 'includes/addons/product-grid/base/class.product-grid-base-widget.php';
}

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
                    'grid_list_tab' => esc_html__( 'Grid List Tab', 'woolentor' ),
                ]
            ]
        );

        $this->add_control(
            'default_view_mode',
            [
                'label' => esc_html__( 'Default View Mode', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => esc_html__( 'Grid', 'woolentor' ),
                    'list' => esc_html__( 'List', 'woolentor' ),
                ],
                'condition' => [
                    'layout' => 'grid_list_tab',
                ],
                'description' => esc_html__( 'Choose which view mode to display by default when using Grid List Tab layout', 'woolentor' ),
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__( 'Columns', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => '4',
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
                'condition' => [
                    'layout!' => 'list',
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
                    '{{WRAPPER}} .woolentor-product-grid-modern' => 'gap: {{SIZE}}{{UNIT}};',
                ],
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