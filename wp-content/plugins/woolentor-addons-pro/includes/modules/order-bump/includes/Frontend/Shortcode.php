<?php
namespace Woolentor\Modules\Order_Bump;
use WooLentorPro\Traits\Singleton;

// If this file is accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}
class Shortcode{
    use Singleton;

    /**
     * Constructor
     */
    private function __construct(){
        add_shortcode('woolentor_order_bump', [$this, 'render_shortcode']);
    }

    /**
     * Shortcode Content Render
     *
     * @param [type] $atts
     * @param string $content
     */
    public function render_shortcode($atts, $content = ''){

        // Shortcode atts
        $default_atts = array(
            'id' => null,
            'block' => null
        );

        $atts = shortcode_atts($default_atts, $atts, $content);

        $order_bump_id = $atts['id'];

        // If Shortcode render from block then pass all block settings
        $block = null;
        if( isset( $atts['block'] ) ){
            $block = $atts['block'];
        }

        // Elementor Editor and Gutenberg Editor Mode
        if( woolentor_is_elementor_editor_mode() || Helper::instance()->is_gutenberg_edit_screen() ){
            $product = Helper::instance()->get_offer_product($order_bump_id);
            return Frontend::instance()->order_bump_markup($order_bump_id, $product, false, $block);
        }

        if (!Manage_Rules::instance()->validate_order_bump( $order_bump_id ) ) {
            return;
        }
        $product = Helper::instance()->get_offer_product($order_bump_id);
        return Frontend::instance()->order_bump_markup($order_bump_id, $product, false, $block);

    }

}