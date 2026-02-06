<?php
/**
 * Base Product Grid Widget for Elementor
 * Abstract class that provides common functionality for all product grid style widgets
 *
 * @package WooLentor
 */

namespace Elementor;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load dependencies
if ( ! class_exists( 'WooLentor_Product_Grid_Base' ) ) {
    require_once WOOLENTOR_ADDONS_PL_PATH . 'includes/addons/product-grid/base/class.product-grid-base.php';
}

/**
 * Base Product Grid Widget Class
 */
abstract class WooLentor_Product_Grid_Base_Widget extends Widget_Base {

    /**
     * Grid style identifier
     */
    protected $grid_style = '';

    /**
     * Grid style label
     */
    protected $grid_style_label = '';

    /**
     * Product Grid Base instance
     */
    protected $product_grid_base;

    /**
     * Constructor
     */
    public function __construct( $data = [], $args = null ) {
        parent::__construct( $data, $args );

        // Initialize Product Grid Base
        if ( class_exists( '\WooLentor_Product_Grid_Base' ) ) {
            $this->product_grid_base = \WooLentor_Product_Grid_Base::instance();
        }

        // Enqueue style-specific Assets
        $this->enqueue_style_assets();

    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return [ 'woolentor-addons' ];
    }

    /**
     * Get help URL
     */
    public function get_help_url() {
        return 'https://woolentor.com/documentation/';
    }

    /**
     * Get script dependencies
     */
    public function get_script_depends() {
        return [
            'woolentor-widgets-scripts'
        ];
    }

    /**
     * Register controls
     */
    protected function register_controls() {

        // Query Settings
        $this->register_query_controls();

        // Layout Settings
        $this->register_layout_controls();

        // Display Settings
        $this->register_display_controls();

        // Badge Settings
        $this->register_badge_controls();

        // Style-specific controls
        $this->register_style_specific_controls();

        // Style Controls
        $this->register_style_controls();

        // Additional Settings
        $this->register_additional_controls();

        // Advanced Controls
        $this->register_advanced_controls();
    }

    /**
     * Register query controls
     */
    protected function register_query_controls() {
        $this->start_controls_section(
            'section_query',
            [
                'label' => esc_html__( 'Query', 'woolentor' ),
            ]
        );

        $this->add_query_type_control();

        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'query_type', ['wlpro_f1','wlpro_f2','wlpro_f3','wlpro_f4','wlpro_f5']);

        $this->add_control(
            'include_products',
            [
                'label' => esc_html__( 'Select Products', 'woolentor' ),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => woolentor_post_name( 'product' ),
                'label_block' => true,
                'condition' => [
                    'query_type' => 'manual',
                ],
            ]
        );

        $this->add_product_per_page_control();

        $this->add_control(
            'categories',
            [
                'label' => esc_html__( 'Categories', 'woolentor' ),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => woolentor_taxonomy_list(),
                'label_block' => true,
                'condition' => [
                    'query_type!' => 'manual',
                ],
            ]
        );

        $this->add_control(
            'tags',
            [
                'label' => esc_html__( 'Tags', 'woolentor' ),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => woolentor_taxonomy_list( 'product_tag' ),
                'label_block' => true,
                'condition' => [
                    'query_type!' => 'manual',
                ],
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => esc_html__( 'Order By', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date'          => esc_html__( 'Date', 'woolentor' ),
                    'title'         => esc_html__( 'Title', 'woolentor' ),
                    'price'         => esc_html__( 'Price', 'woolentor' ),
                    'popularity'    => esc_html__( 'Popularity', 'woolentor' ),
                    'rating'        => esc_html__( 'Rating', 'woolentor' ),
                    'rand'          => esc_html__( 'Random', 'woolentor' ),
                    'menu_order'    => esc_html__( 'Menu Order', 'woolentor' ),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => esc_html__( 'Order', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'ASC' => esc_html__( 'Ascending', 'woolentor' ),
                    'DESC' => esc_html__( 'Descending', 'woolentor' ),
                ],
            ]
        );

        $this->add_control(
            'exclude_products',
            [
                'label' => esc_html__( 'Exclude Products', 'woolentor' ),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => woolentor_post_name( 'product' ),
                'label_block' => true,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'exclude_out_of_stock',
            [
                'label' => esc_html__( 'Exclude Out of Stock', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'exclude_no_image',
            [
                'label' => esc_html__( 'Exclude Products Without Image', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
            ]
        );
        
        $this->add_control(
            'enable_filters',
            [
                'label' => esc_html__( 'Filterable', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => esc_html__('If you want to support the Product filter module then enable it.','woolentor')
            ]
        );

        $this->end_controls_section();
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
                    'products'  => esc_html__( 'All Products', 'woolentor' ),
                    'recent'    => esc_html__( 'Recent Products', 'woolentor' ),
                    'manual'    => esc_html__( 'Manual Selection', 'woolentor' ),
                    'wlpro_f1'    => esc_html__( 'Featured (Pro)', 'woolentor' ),
                    'wlpro_f2'    => esc_html__( 'On Sale (Pro)', 'woolentor' ),
                    'wlpro_f3'    => esc_html__( 'Best Selling (Pro)', 'woolentor' ),
                    'wlpro_f4'    => esc_html__( 'Top Rated (Pro)', 'woolentor' ),
                    'wlpro_f5'    => esc_html__( 'Recently Viewed (Pro)', 'woolentor' ),
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
                'default' => 4,
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

        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'layout', ['wlpro_f1']);

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
                'condition' => [
                    'layout!' => 'list',
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
                    '{{WRAPPER}} .woolentor-product-grid-modern' => 'gap: {{SIZE}}{{UNIT}};',
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
            'show_rating',
            [
                'label' => esc_html__( 'Show Rating', 'woolentor' ),
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
     * Register badge controls
     */
    protected function register_badge_controls(){

        // Badge Settings
        $this->start_controls_section(
            'section_badge_settings',
            [
                'label' => esc_html__( 'Badge Settings', 'woolentor' ),
            ]
        );

        $this->add_control(
            'show_badges',
            [
                'label' => esc_html__( 'Show Badge', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
                'separator'=>'after'
            ]
        );

        $this->add_badge_style_control();
        $this->add_badge_position_control();

        $this->add_control(
            'show_sale_badge',
            [
                'label' => esc_html__( 'Show Sale Badge', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__( 'Show Sale badge for on sale product', 'woolentor' ),
                'separator'=>'before'
            ]
        );

        $this->add_control(
            'sale_badge_text',
            [
                'label' => esc_html__( 'Sale Badge Text', 'woolentor' ),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__( 'SALE', 'woolentor' ),
                'placeholder' => esc_html__( 'SALE!', 'woolentor' ),
                'condition' => [
                    'show_sale_badge' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_new_badge',
            [
                'label' => esc_html__( 'Show New Badge', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'yes',
                'separator'=>'before'
            ]
        );

        $this->add_control(
            'new_badge_text',
            [
                'label' => esc_html__( 'New Badge Text', 'woolentor' ),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__( 'NEW', 'woolentor' ),
                'placeholder' => esc_html__( 'NEW', 'woolentor' ),
                'condition' => [
                    'show_new_badge' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'new_badge_days',
            [
                'label' => esc_html__( 'New Badge Days', 'woolentor' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 7,
                'min' => 1,
                'max' => 90,
                'condition' => [
                    'show_new_badge' => 'yes',
                ],
                'description' => esc_html__( 'Show NEW badge for products published within this many days', 'woolentor' ),
            ]
        );

        $this->add_control(
            'show_trending_badge',
            [
                'label' => esc_html__( 'Show Trending Badge', 'woolentor' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'woolentor' ),
                'label_off' => esc_html__( 'No', 'woolentor' ),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => esc_html__( 'Show TRENDING badge for featured products', 'woolentor' ),
                'separator'=>'before'
            ]
        );

        $this->add_control(
            'trending_badge_text',
            [
                'label' => esc_html__( 'Trending Badge Text', 'woolentor' ),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__( 'HOT', 'woolentor' ),
                'placeholder' => esc_html__( 'HOT', 'woolentor' ),
                'condition' => [
                    'show_trending_badge' => 'yes',
                ],
            ]
        );

        $this->add_additional_badges_settings();

        $this->add_control(
			'badge_module_enable_notice',
			[
				'type' => Controls_Manager::NOTICE,
				'notice_type' => 'warning',
				'dismissible' => false,
				'heading' => esc_html__( 'Badge Module Enable', 'woolentor' ),
				'content' => esc_html__( 'If badge module is enable this option do not work, All badge are manage from the module', 'woolentor' ),
			]
		);

        $this->end_controls_section();

    }

    /**
     * Badge Style Control Option
     *
     * @return void
     */
    protected function add_badge_style_control(){

        $this->add_control(
            'badge_style',
            [
                'label' => esc_html__( 'Badge Style', 'woolentor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'solid',
                'options' => [
                    'solid' => esc_html__( 'Solid Color', 'woolentor' ),
                    'wlpro_f1' => esc_html__( 'Gradient (Pro)', 'woolentor' ),
                    'wlpro_f2' => esc_html__( 'Outline (Pro)', 'woolentor' ),
                ],
                'prefix_class' => 'woolentor-badge-style-',
            ]
        );
        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'badge_style', ['wlpro_f1','wlpro_f2']);

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
                'default' => 'top-left',
                'options' => [
                    'top-left' => esc_html__( 'Top Left', 'woolentor' ),
                    'wlpro_f1' => esc_html__( 'Top Right (Pro)', 'woolentor' ),
                    'wlpro_f2' => esc_html__( 'Top Center (Pro)', 'woolentor' ),
                ],
                'prefix_class' => 'woolentor-badge-pos-',
            ]
        );
        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'badge_position', ['wlpro_f1','wlpro_f2']);
    }

    /**
     * Additional Badge Control Option
     *
     * @return void
     */
    protected function add_additional_badges_settings(){
        // register from child class
    }

    /**
     * Additional Settings
     */
    protected function register_additional_controls(){

        $this->start_controls_section(
            'section_additional_settings',
            [
                'label' => esc_html__( 'Additional Settings', 'woolentor' ),
            ]
        );

            $this->add_card_hover_effect_control();
            $this->add_image_hover_effect_control();

            $this->add_control(
                'show_quick_actions',
                [
                    'label' => esc_html__( 'Show Quick Actions', 'woolentor' ),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Yes', 'woolentor' ),
                    'label_off' => esc_html__( 'No', 'woolentor' ),
                    'return_value' => 'yes',
                    'default' => 'yes',
                    'description' => esc_html__( 'Show wishlist, compare and quick view buttons on hover', 'woolentor' ),
                ]
            );



        $this->end_controls_section();
    }

    /**
     * Card Hover Effect
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
                    'wlpro_f1' => esc_html__( 'Scale (Pro)', 'woolentor' ),
                    'wlpro_f2' => esc_html__( 'Enhanced Shadow (Pro)', 'woolentor' ),
                ],
                'prefix_class' => 'woolentor-card-hover-',
            ]
        );

        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'card_hover_effect', ['wlpro_f1','wlpro_f2']);
    }

    /**
     * Image Hover Effect
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
                    'wlpro_f1' => esc_html__( 'Fade Effect (Pro)', 'woolentor' ),
                    'wlpro_f2' => esc_html__( 'Grayscale to Color (Pro)', 'woolentor' ),
                ],
                'prefix_class' => 'woolentor-image-hover-',
            ]
        );

        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'image_hover_effect', ['wlpro_f1','wlpro_f2']);
    }

    /**
     * Register advanced controls
     */
    protected function register_advanced_controls() {
        $this->start_controls_section(
            'section_advanced',
            [
                'label' => esc_html__( 'Advanced', 'woolentor' ),
            ]
        );

            $this->add_pagination_control();

        $this->end_controls_section();
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
                    'wlpro_f1' => esc_html__( 'Load More (Pro)', 'woolentor' ),
                    'wlpro_f2' => esc_html__( 'Infinite Scroll (Pro)', 'woolentor' ),
                ],
                'condition' => [
                    'enable_pagination' => 'yes',
                ],
            ]
        );

        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'pagination_type', ['wlpro_f1','wlpro_f2']);

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
     * Override in child classes
     */
    protected function register_style_specific_controls() {
        // Override in child classes to add style-specific controls
    }

    /**
     * Register style controls
     */
    protected function register_style_controls() {
        // Container styles
        $this->register_container_style_controls();

        // Image styles
        $this->register_image_style_controls();

        // Content styles
        $this->register_content_style_controls();

        // Badge styles
        $this->register_badge_style_controls();

        // Title styles
        $this->register_title_style_controls();

        // Review styles
        $this->register_review_style_controls();

        // Category Styles
        $this->register_category_style_controls();

        // Description
        $this->register_description_style_controls();

        // Price styles
        $this->register_price_style_controls();

        // Add to cart Button
        $this->register_add_to_cart_button_style_controls();

        // Button styles
        $this->register_button_style_controls();

        // Pagination Styles
        $this->register_pagination_style_controls();
    }

    /**
     * Register container style controls
     */
    protected function register_container_style_controls() {
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => esc_html__( 'Product Card', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_background',
            [
                'label' => esc_html__( 'Background', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'selector' => '{{WRAPPER}} .woolentor-product-card',
            ]
        );

        $this->add_control(
            'container_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_box_shadow',
                'selector' => '{{WRAPPER}} .woolentor-product-card',
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => esc_html__( 'Padding', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register image style controls
     */
    protected function register_image_style_controls() {
        $this->start_controls_section(
            'section_style_image',
            [
                'label' => esc_html__( 'Image', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_image' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'image_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-image > a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-editorial-grid-card .woolentor-product-overlay' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-product-image img.woolentor-product-img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-product-grid-magazine .woolentor-product-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register content style controls
     */
    protected function register_content_style_controls() {
        $this->start_controls_section(
            'section_style_content',
            [
                'label' => esc_html__( 'Content', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'content_padding',
            [
                'label' => esc_html__( 'Padding', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'content_align',
            [
                'label' => esc_html__( 'Alignment', 'woolentor' ),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__( 'Left', 'woolentor' ),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'woolentor' ),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__( 'Right', 'woolentor' ),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-content' => 'align-items: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-content-header' => 'align-items: {{VALUE}};'
                ],
                'selectors_dictionary' => [
                    'flex-start' => 'flex-start; text-align: left',
                    'center' => 'center; text-align: center',
                    'flex-end' => 'flex-end; text-align: right',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Badge Style Controls
     */
    protected function register_badge_style_controls(){
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
                        '{{WRAPPER}} .woolentor-product-item .woolentor-badge' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'badge_typography',
                    'selector' => '{{WRAPPER}} .woolentor-product-item .woolentor-badge',
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
                    'selector' => '{{WRAPPER}} .woolentor-product-item .woolentor-badge',
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'badge_border',
                    'selector' => '{{WRAPPER}} .woolentor-product-item .woolentor-badge',
                ]
            );

            $this->add_control(
                'badge_border_radius',
                [
                    'label' => esc_html__( 'Border Radius', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-product-item .woolentor-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                        '{{WRAPPER}} .woolentor-product-item .woolentor-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

        $this->end_controls_section();
    }

    /**
     * Register title style controls
     */
    protected function register_title_style_controls() {
        $this->start_controls_section(
            'section_style_title',
            [
                'label' => esc_html__( 'Title', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__( 'Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label' => esc_html__( 'Hover Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-title a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .woolentor-product-title',
            ]
        );

        $this->add_responsive_control(
            'title_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register category style controls
     */
    protected function register_category_style_controls() {
        $this->start_controls_section(
            'section_style_category',
            [
                'label' => esc_html__( 'Category', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_categories' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'category_color',
            [
                'label' => esc_html__( 'Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-categories .woolentor-product-category' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-product-category-badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_bg_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-categories .woolentor-product-category' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-product-category-badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_hover_color',
            [
                'label' => esc_html__( 'Hover Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-categories .woolentor-product-category:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-product-category-badge:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_bg_hover_color',
            [
                'label' => esc_html__( 'Hover Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-categories .woolentor-product-category:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-product-category-badge:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'category_typography',
                'selector' => '{{WRAPPER}} .woolentor-product-categories .woolentor-product-category,{{WRAPPER}} .woolentor-luxury-card .woolentor-product-category-badge',
            ]
        );

        $this->add_responsive_control(
            'category_padding',
            [
                'label' => esc_html__( 'Padding', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-categories .woolentor-product-category' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-product-category-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'category_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-categories' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-product-category-badge' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'category_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-categories .woolentor-product-category' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-product-category-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register description style controls
     */
    protected function register_description_style_controls() {
        $this->start_controls_section(
            'section_style_description',
            [
                'label' => esc_html__( 'Description', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'description_color',
            [
                'label' => esc_html__( 'Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-description p' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-product-subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'description_typography',
                'selector' => '{{WRAPPER}} .woolentor-product-description p,{{WRAPPER}} .woolentor-luxury-card .woolentor-product-subtitle',
            ]
        );

        $this->add_responsive_control(
            'description_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-description p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-product-subtitle' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                'label' => esc_html__( 'Text Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-actions .woolentor-cart-btn' => 'color: {{VALUE}}!important;',
                ],
            ]
        );

        $this->add_control(
            'cart_action_button_background_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-actions .woolentor-cart-btn' => 'background-color: {{VALUE}}!important;background:{{VALUE}}!important;',
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
                    '{{WRAPPER}} .woolentor-product-actions .woolentor-cart-btn:hover' => 'color: {{VALUE}}!important;',
                ],
            ]
        );

        $this->add_control(
            'cart_button_background_hover_color',
            [
                'label' => esc_html__( 'Background Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-actions .woolentor-cart-btn:hover' => 'background-color: {{VALUE}}!important;background:{{VALUE}}!important;',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-cart-btn::before' => 'background-color: {{VALUE}};background:{{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'cart_button_hover_border_color',
            [
                'label' => esc_html__( 'Border Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-actions .woolentor-cart-btn:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'cart_action_button_typography',
                'selector' => '{{WRAPPER}} .woolentor-grid-card .woolentor-product-actions .woolentor-cart-btn,{{WRAPPER}} .woolentor-list-card .woolentor-product-actions .woolentor-cart-btn,{{WRAPPER}} .woolentor-luxury-card a.woolentor-cart-btn',
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
                    '{{WRAPPER}} .woolentor-product-actions .woolentor-cart-btn svg'  => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-cart-arrow'  => 'font-size: {{SIZE}}{{UNIT}};',
                ],
			]
		);

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'cart_button_border',
                'selector' => '{{WRAPPER}} .woolentor-product-actions .woolentor-cart-btn',
            ]
        );

        $this->add_control(
            'cart_button_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-actions .woolentor-cart-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
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
                    '{{WRAPPER}} .woolentor-product-actions .woolentor-cart-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}!important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register review style controls
     */
    protected function register_review_style_controls() {
        $this->start_controls_section(
            'section_style_product_review',
            [
                'label' => esc_html__( 'Review', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_rating' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'review_color',
            [
                'label' => esc_html__( 'Review Start Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-stars .star' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'empty_review_color',
            [
                'label' => esc_html__( 'Empty review start', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-stars .star.empty' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
			'start_size',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Start Size', 'woolentor' ),
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 200,
					],
				],
                'selectors' => [ 
                    '{{WRAPPER}} .woolentor-product-stars .star'  => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};',
                ],
			]
		);

        $this->add_responsive_control(
			'start_space_between',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Start space between', 'woolentor' ),
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 200,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 2,
				],
                'selectors' => [ 
                    '{{WRAPPER}} .woolentor-product-stars'  => 'gap: {{SIZE}}{{UNIT}};',
                ],
			]
		);

        $this->add_control(
            'review_counter_color',
            [
                'label' => esc_html__( 'Review Counter Color', 'woolentor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-grid-modern .woolentor-review-count' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-product-rating .rating-info' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-luxury-card .woolentor-rating-text' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woolentor-product-card .woolentor-review-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'review_counter_typography',
                'selector' => '{{WRAPPER}} .woolentor-product-grid-modern .woolentor-review-count,{{WRAPPER}} .woolentor-product-rating .rating-info, {{WRAPPER}} .woolentor-luxury-card .woolentor-rating-text,{{WRAPPER}} .woolentor-product-card .woolentor-review-count',
            ]
        );

        $this->add_control(
            'review_margin',
            [
                'label' => esc_html__( 'Margin', 'woolentor' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .woolentor-product-rating' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
    }

    // Pagination style controls
    protected function register_pagination_style_controls(){
        $this->start_controls_section(
            'section_pagination',
            [
                'label' => esc_html__( 'Pagination', 'woolentor' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_pagination' => 'yes',
                ],
            ]
        );

            $this->add_responsive_control(
                'pagination_position',
                [
                    'label'   => esc_html__( 'Alignment', 'woolentor' ),
                    'type'    => Controls_Manager::CHOOSE,
                    'options' => [
                        'flex-start'    => [
                            'title' => esc_html__( 'Left', 'woolentor' ),
                            'icon'  => 'eicon-text-align-left',
                        ],
                        'center' => [
                            'title' => esc_html__( 'Center', 'woolentor' ),
                            'icon'  => 'eicon-text-align-center',
                        ],
                        'flex-end' => [
                            'title' => esc_html__( 'Right', 'woolentor' ),
                            'icon'  => 'eicon-text-align-right',
                        ],
                    ],
                    'default'     => 'center',
                    'toggle'      => false,
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-pagination'   => 'justify-content: {{VALUE}}',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'pagination_typography',
                    'label' => esc_html__( 'Typography', 'woolentor' ),
                    'selector' => '{{WRAPPER}} .woolentor-pagination ul li a,{{WRAPPER}} .woolentor-pagination ul li span,{{WRAPPER}} .woolentor-load-more-btn',
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'pagination_border',
                    'selector' => '{{WRAPPER}} .woolentor-pagination ul li a,{{WRAPPER}} .woolentor-pagination ul li span:not(.dots),{{WRAPPER}} .woolentor-load-more-btn',
                ]
            );

            $this->add_responsive_control(
                'pagination_border_radius',
                [
                    'label' => esc_html__( 'Border Radius', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-pagination ul li a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                        '{{WRAPPER}} .woolentor-pagination ul li span:not(.dots)' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                        '{{WRAPPER}} .woolentor-load-more-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                    ],
                ]
            );

            $this->add_responsive_control(
                'pagination_padding',
                [
                    'label' => esc_html__( 'Padding', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-pagination ul li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                        '{{WRAPPER}} .woolentor-pagination ul li span:not(.dots)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                        '{{WRAPPER}} .woolentor-load-more-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                    ],
                ]
            );

            $this->add_responsive_control(
                'pagination_margin',
                [
                    'label' => esc_html__( 'Margin', 'woolentor' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                    'selectors' => [
                        '{{WRAPPER}} .woolentor-pagination' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_loadmore_button_style_control();

            $this->add_responsive_control(
                'pagination_space_between',
                [
                    'type' => Controls_Manager::SLIDER,
                    'label' => esc_html__( 'Space between', 'woolentor' ),
                    'size_units' => [ 'px', 'em', 'rem', 'custom' ],
                    'range' => [
                        'px' => [
                            'min' => 1,
                            'max' => 200,
                        ],
                    ],
                    'condition' => [
                        'pagination_type' => 'numbers',
                    ],
                    'selectors' => [ 
                        '{{WRAPPER}} .woolentor-pagination ul'  => 'gap: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->start_controls_tabs(
                'product_pagination_style_tabs',
                [
                    'condition' => [
                        'pagination_type' => 'numbers',
                    ],
                ]
            );

                // Pagination normal style
                $this->start_controls_tab(
                    'pagination_style_normal_tab',
                    [
                        'label' => esc_html__( 'Normal', 'woolentor' ),
                    ]
                );
                    
                    $this->add_control(
                        'pagination_link_color',
                        [
                            'label' => esc_html__( 'Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-pagination ul li a' => 'color: {{VALUE}}',
                            ],
                        ]
                    );

                    $this->add_control(
                        'pagination_link_bg_color',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor-pro' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-pagination ul li a' => 'background-color: {{VALUE}}',
                            ],
                        ]
                    );

                    $this->add_control(
                        'pagination_border_color_normal',
                        [
                            'label' => esc_html__( 'Border Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-pagination ul li a' => 'border-color: {{VALUE}}',
                            ],
                        ]
                    );

                $this->end_controls_tab();

                // Pagination Hover style
                $this->start_controls_tab(
                    'pagination_style_hover_tab',
                    [
                        'label' => esc_html__( 'Hover', 'woolentor' ),
                    ]
                );

                    $this->add_control(
                        'pagination_link_color_hover',
                        [
                            'label' => esc_html__( 'Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-pagination ul li a:hover' => 'color: {{VALUE}}',
                            ],
                        ]
                    );

                    $this->add_control(
                        'pagination_link_bg_color_hover',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-pagination ul li a:hover' => 'background-color: {{VALUE}}',
                            ],
                        ]
                    );

                    $this->add_control(
                        'pagination_border_color_hover',
                        [
                            'label' => esc_html__( 'Border Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-pagination ul li a:hover' => 'border-color: {{VALUE}}',
                            ],
                        ]
                    );

                $this->end_controls_tab();

                // Pagination Active style
                $this->start_controls_tab(
                    'pagination_style_active_tab',
                    [
                        'label' => esc_html__( 'Active', 'woolentor' ),
                    ]
                );    

                    $this->add_control(
                        'pagination_link_color_active',
                        [
                            'label' => esc_html__( 'Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-pagination ul li span:not(.dots)' => 'color: {{VALUE}}',
                            ],
                        ]
                    );

                    $this->add_control(
                        'pagination_link_bg_color_active',
                        [
                            'label' => esc_html__( 'Background Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-pagination ul li span:not(.dots)' => 'background-color: {{VALUE}}',
                            ],
                        ]
                    );

                    $this->add_control(
                        'pagination_border_color_active',
                        [
                            'label' => esc_html__( 'Border Color', 'woolentor' ),
                            'type' => Controls_Manager::COLOR,
                            'selectors' => [
                                '{{WRAPPER}} .woolentor-pagination ul li span:not(.dots)' => 'border-color: {{VALUE}}'
                            ],
                        ]
                    );

                $this->end_controls_tab();

            $this->end_controls_tabs();

        $this->end_controls_section();
    }

    // Manage LoadMore Button Style control
    protected function add_loadmore_button_style_control(){
        woolentor_upgrade_pro_notice_elementor($this, Controls_Manager::RAW_HTML, 'woolentor-product-grid-modern', 'pagination_type_style', ['wlpro_f1']);
    }

    /**
     * Render widget output
     */
    protected function render() {
        if ( ! $this->product_grid_base ) {
            echo '<p>' . esc_html__( 'Product Grid Base not available', 'woolentor' ) . '</p>';
            return;
        }

        $settings = $this->get_settings_for_display();

        // Merge with style
        $settings['style'] = $this->grid_style;

        // Convert Elementor settings to grid settings
        $grid_settings = $this->prepare_grid_settings( $settings );

        // Render using Product Grid Base
        $this->product_grid_base->render( $grid_settings );
    }

    /**
     * Enqueue style-specific Assets
     */
    protected function enqueue_style_assets() {
        $style = $this->grid_style;

        if( ($style === 'luxury' || $style === 'editorial' || $style === 'magazine') && !wp_script_is( 'woolentor-product-grid-modern', 'enqueued' )){
            $base_handle = 'woolentor-product-grid-modern';
            $base_path = WOOLENTOR_ADDONS_PL_URL . 'assets/css/product-grid/modern.css';
            wp_enqueue_style( $base_handle, $base_path, [], WOOLENTOR_VERSION );
        }

        // Enqueue style-specific stylesheet (depends on base)
        $css_handle = 'woolentor-product-grid-' . $style;
        $css_path = WOOLENTOR_ADDONS_PL_URL . 'assets/css/product-grid/' . $style . '.css';
        wp_enqueue_style( $css_handle, $css_path, [], WOOLENTOR_VERSION );
    }

    /**
     * Prepare grid settings from Elementor settings
     */
    protected function prepare_grid_settings( $settings ) {
        // Helper function to get value safely
        $get_val = function( $key, $default = null ) use ( $settings ) {
            $value = isset( $settings[$key] ) ? $settings[$key] : $default;

            if(is_string($value) && strpos($value,'wlpro_') !== false){
                return $default;
            }

            return $value;
        };

        // Helper function to get size value safely
        $get_size = function( $key, $default = 30 ) use ( $settings ) {
            if ( isset( $settings[$key] ) && is_array( $settings[$key] ) && isset( $settings[$key]['size'] ) ) {
                $value = $settings[$key]['size'];
                if(is_string($value) && strpos($value,'wlpro_') !== false){
                    return $default;
                }
                return $value;
            }
            return $default;
        };

        // Image Size
        $get_img_size = function( $key, $default ) use ($settings){
            if( isset( $settings[$key.'_size'] ) ){
                $size = $settings[$key.'_size'];
                if($size === 'custom'){
                    return [
                        (int)$settings[$key.'_custom_dimension']['width'],
                        (int)$settings[$key.'_custom_dimension']['height']
                    ];
                }else{
                    return $size;
                }
            }
            return $default;;
        };

        $grid_settings = [
            'style'                 => $this->grid_style,
            'layout'                => $get_val('layout', 'grid'),
            'default_view_mode'     => $get_val('default_view_mode', 'grid'),
            'query_type'            => $get_val('query_type', 'recent'),
            'posts_per_page'        => $get_val('posts_per_page', 6),
            'orderby'               => $get_val('orderby', 'date'),
            'order'                 => $get_val('order', 'DESC'),
            'categories'            => $get_val('categories', []),
            'tags'                  => $get_val('tags', []),
            'include_products'      => $get_val('include_products', []),
            'exclude_products'      => $get_val('exclude_products', []),
            'exclude_out_of_stock'  => $get_val('exclude_out_of_stock') === 'yes',
            'exclude_no_image'      => $get_val('exclude_no_image') === 'yes',
            'enable_filters'        => $get_val('enable_filters') === 'yes',
            'columns'               => $get_val('columns', 3),
            'columns_tablet'        => $get_val('columns_tablet', 2),
            'columns_mobile'        => $get_val('columns_mobile', 1),
            'gap'                   => $get_size('gap', 30),
            'gap_tablet'            => $get_size('gap_tablet', 25),
            'gap_mobile'            => $get_size('gap_mobile', 20),
            'show_image'            => $get_val('show_image', 'yes') === 'yes',
            'image_size'            => $get_img_size('image','woocommerce_thumbnail'),
            'show_secondary_image'  => $get_val('show_secondary_image') === 'yes',
            'show_title'            => $get_val('show_title', 'yes') === 'yes',
            'title_tag'             => $get_val('title_tag', 'h3'),
            'show_price'            => $get_val('show_price', 'yes') === 'yes',
            'show_rating'           => $get_val('show_rating', 'yes') === 'yes',
            'show_categories'       => $get_val('show_categories', 'yes') === 'yes',
            'show_add_to_cart'      => $get_val('show_add_to_cart', 'yes') === 'yes',
            'show_badges'           => $get_val('show_badges', 'yes') === 'yes',
            'show_quick_actions'    => $get_val('show_quick_actions', 'yes') === 'yes',
            'show_quick_view'       => $get_val('show_quick_view', 'yes') === 'yes',
            'show_wishlist'         => $get_val('show_wishlist', 'yes') === 'yes',
            'show_compare'          => $get_val('show_compare', 'yes') === 'yes',

            // Badge settings
            'show_sale_badge'       => $get_val('show_sale_badge', 'yes') === 'yes',
            'sale_badge_text'       => $get_val('sale_badge_text', esc_html__('SALE','woolentor')),
            'show_new_badge'        => $get_val('show_new_badge') === 'yes',
            'new_badge_text'        => $get_val('new_badge_text', esc_html__('NEW','woolentor')),
            'new_badge_days'        => absint( $get_val('new_badge_days', 7) ),
            'show_trending_badge'   => $get_val('show_trending_badge') === 'yes',
            'trending_badge_text'   => $get_val('trending_badge_text', esc_html__('HOT','woolentor')),
            'badge_style'           => $get_val('badge_style', 'gradient'),
            'badge_position'        => $get_val('badge_position', 'top-left'),

            'enable_pagination'     => $get_val('enable_pagination') === 'yes',
            'pagination_type'       => $get_val('pagination_type', 'numbers'),
            'load_more_text'        => $get_val('load_more_text'),
            'load_more_complete_text'=> $get_val('load_more_complete_text'),
        ];

        return apply_filters( 'woolentor_product_grid_widget_settings', $grid_settings, $settings );
    }
}