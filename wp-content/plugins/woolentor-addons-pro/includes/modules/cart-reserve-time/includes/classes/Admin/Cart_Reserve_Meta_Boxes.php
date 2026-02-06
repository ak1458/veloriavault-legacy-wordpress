<?php
namespace WoolentorPro\Modules\CartReserveTime\Admin;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Coupon_Meta_Boxes {
    use Singleton;

    public function __construct(){

        // Meta Boxes
        add_action( 'woolentor_pro_product_meta_boxes', [ $this, 'add_product_options' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_options' ] );

    }

    public static function get_meta_data($id, $meta_key, $default = ""){
        if (!metadata_exists('post', $id, $meta_key)) {
            return $default;
        }else{
            $meta_data = get_post_meta($id, $meta_key, true);
            return $meta_data;
        }
    }

    /**
     * Add Product Options
     */
    public function add_product_options(){
        global $thepostid, $post;
		$post_id = ( empty( $thepostid ) ? $post->ID : $thepostid );

        echo '<div class="options_group">';

            woocommerce_wp_checkbox( array(
                'id'          => '_woolentor_enable_custom_timer',
                'label'       => __( 'Custom Reserve Timer', 'woolentor' ),
                'value'       => self::get_meta_data( $post_id,'_woolentor_enable_custom_timer', 'no' ) === 'yes' ? 'yes' : 'no',
                'description' => __( 'Enable custom reserve timer for this product.', 'woolentor' )
            ) );

            woocommerce_wp_text_input( array(
                'id'          => '_woolentor_custom_timer_duration',
                'label'       => __( 'Timer Duration (Minutes)', 'woolentor' ),
                'desc_tip'    => true,
                'description' => __( 'Set custom timer duration for this product.', 'woolentor' ),
                'type'        => 'number',
                'custom_attributes' => array(
                    'min'  => '1',
                    'step' => '1'
                ),
                'value' => self::get_meta_data( $post_id , '_woolentor_custom_timer_duration' ),
            ) );

        echo "</div>";
    }

    /**
     * Save Product Options
     */
    public function save_product_options( $post_id ){

        // Check nonce
        if ( empty($_POST['_wpnonce']) || empty($_POST['post_ID']) || 
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['_wpnonce'])),
                'update-post_' . sanitize_text_field(wp_unslash($_POST['post_ID']))
            )
        ) {
            return;
        }

        $enable_custom_timer = isset( $_POST['_woolentor_enable_custom_timer'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_woolentor_enable_custom_timer', $enable_custom_timer );

        if ( isset( $_POST['_woolentor_custom_timer_duration'] ) ) {
            update_post_meta( $post_id, '_woolentor_custom_timer_duration', sanitize_text_field( $_POST['_woolentor_custom_timer_duration'] ) );
        }
    }


}