<?php
namespace Woolentor\Modules\SideMiniCart;
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

        $widget_list['minicart']['wl_mini_cart'] = [
            'title'    => esc_html__('Mini Cart','woolentor-pro'),
            'is_pro'   => true,
            'location' => WIDGETS_PATH,
        ];

        return $widget_list;
    }

    /**
     * Block list.
     */
    public function block_list( $block_list = [] ){

        $block_list['side_mini_cart'] = [
            'label'  => __('Side Mini Cart','woolentor'),
            'name'   => 'woolentor/side-mini-cart',
            'server_side_render' => true,
            'type'   => 'common',
            'active' => true,
            'location' => BLOCKS_PATH,
        ];

        return $block_list;
    }

}