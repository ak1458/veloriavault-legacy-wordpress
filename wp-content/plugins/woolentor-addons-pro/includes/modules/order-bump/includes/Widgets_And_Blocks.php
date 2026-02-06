<?php
namespace Woolentor\Modules\Order_Bump;
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
        
        $widget_list['checkout']['wl_order_bump'] = [
            'title'    => esc_html__('Order Bump','woolentor'),
            'location' => WIDGETS_PATH,
        ];

        return $widget_list;
    }

    /**
     * Block list.
     */
    public function block_list( $block_list = [] ){

        $block_list['order_bump'] = [
            'label'  => __('Order Bump','woolentor'),
            'name'   => 'woolentor/order-bump',
            'server_side_render' => true,
            'type'   => 'common',
            'active' => true,
            'location' => BLOCKS_PATH,
            'enqueue_assets' => function(){
                wp_enqueue_style('woolentor-order-bump', MODULE_URL . '/assets/css/order-bump.css', [], WOOLENTOR_VERSION);
            }
        ];

        return $block_list;
    }

}