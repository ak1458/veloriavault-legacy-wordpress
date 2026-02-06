<?php
namespace WoolentorPro\Modules\QuickView;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Widgets class.
 */
class Widgets_And_Blocks {
    use Singleton;

	/**
     * Widgets constructor.
     */
    public function __construct() {

        // Elementor Widget
        add_filter( 'woolentor_widget_list', [ $this, 'widget_list' ] );

        // Guttenberg Block
        add_filter('woolentor_block_list', [ $this, 'block_list' ] );

    }

    /**
     * Widget list.
     */
    public function widget_list( $widget_list = [] ) {

        $widget_list['single']['wl_quickview_product_image'] = [
            'title'    => esc_html__('Product Quickview','woolentor-pro'),
            'is_pro'   => true,
            'location' => WIDGETS_PATH,
        ];

        return $widget_list;
    }

    /**
     * Block list.
     */
    public function block_list( $block_list = [] ){

        $block_list['quickview_product_image'] = [
            'label'  => __('Quickview Product Image','woolentor'),
            'name'   => 'woolentor/quickview-product-image',
            'server_side_render' => true,
            'type'   => 'single',
            'is_pro' => true,
            'active' => true,
            'enqueue_assets' => function(){
                wp_enqueue_style('woolentor-quickview');
            },
            'location' => BLOCKS_PATH,
        ];

        return $block_list;
    }

}