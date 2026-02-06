<?php
namespace WoolentorPro\Modules\AdvancedCoupon\Admin;
use WooLentorPro\Traits\Singleton;
use Woolentor\Modules\AdvancedCoupon\Functions;
use Woolentor\Modules\AdvancedCoupon\Admin\Coupon_Meta_Boxes as CouponMetaBoxes;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Coupon_Meta_Boxes {
    use Singleton;

    public $first_created_coupon_id;

    public function __construct(){

        // Add Payment option
        add_action('woolentor_coupon_payment_fields',[$this,'payment_method_fields'], 10, 1);

        // Add URL Coupon Field
        add_action('woolentor_coupon_url_fields',[$this,'coupon_url_fields'], 10, 1);

        // Save meta boxes data
        add_action('woocommerce_process_shop_coupon_meta', [$this, 'save_meta_boxes_data'], 11, 2);

        // Bulk Meta Boxes
        add_action('add_meta_boxes',[$this, 'bulk_coupon_meta_boxes'], 10 );

        // Coupon Settings
        add_action('woocommerce_coupon_options_save', [$this, 'coupon_settings_save'], 99999, 2);

    }

    /**
     * Payment Method related fields
     * @param mixed $coupon_id
     * @return void
     */
    public function payment_method_fields( $coupon_id ){
        ?>
            <!-- Payment restriction Start -->
            <p class="form-field">
                <label for="woolentor_payment_method_ids"><?php esc_html_e( 'Payment methods', 'woolentor' ); ?></label>
                <select id="woolentor_payment_method_ids" name="woolentor_payment_method_ids[]" style="width:50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select payment method', 'woolentor' ); ?>">
                    <?php
                        $payment_methods = WC()->payment_gateways->payment_gateways();

                        if ( ! empty( $payment_methods ) ) {
                            $payment_method_ids = Functions::get_multiple_meta_date( $coupon_id, 'woolentor_payment_method_ids' );
    
                            foreach ( $payment_methods as $payment_method ) {
                                if ( wc_string_to_bool( $payment_method->enabled ) ) {
                                    echo '<option value="' . esc_attr( $payment_method->id ) . '" ' . selected( in_array( $payment_method->id, $payment_method_ids ), true, false ) . '>' . esc_html( wp_strip_all_tags($payment_method->title) ) . '</option>';
                                }
                            }

                        }
                    ?>
                </select>
                <?php echo wc_help_tip( esc_html__( 'The coupon will only be applicable if the selected payment method matches either condition.', 'woolentor' ) ); ?>
            </p>

            <div class="options_group" style="border-top:0;">
                <?php
                    woocommerce_wp_select([
                        'id'          => 'woolentor_payment_restrict_type',
                        'type'        => 'select',
                        'value'       => Functions::get_meta_data( $coupon_id , 'woolentor_payment_restrict_type', 'allowed' ),
                        'options'     => [
                            'allowed'    => esc_html__( 'Allowed', 'woolentor' ),
                            'disallowed' => esc_html__( 'Disallowed', 'woolentor' ),
                        ],
                        'style'       => 'width:50%;',
                        'label'       => esc_html__( 'Payment restrict type', 'woolentor' ),
                        'description' => esc_html__( 'The type of implementation for this restriction. Select "allowed" to allow coupon only to payment method under the selected method. Select "disallowed" to only allow coupon to payment that don\'t fall under the selected method.', 'woolentor' ),
                        'desc_tip'    => true,
                    ]);

                    woocommerce_wp_textarea_input([
                        'id'          => 'woolentor_payment_error_message',
                        'label'       => esc_html__( 'Payment error message', 'woolentor' ),
                        'description' => esc_html__( 'Show a personalized error message to customers attempting to use a coupon before it state date.', 'woolentor' ),
                        'desc_tip'    => true,
                        'type'        => 'text',
                        'data_type'   => 'text',
                        'placeholder' => esc_html__('This coupon is not applicable with your selected payment method.','woolentor'),
                        'value'       => Functions::get_meta_data( $coupon_id , 'woolentor_payment_error_message' ),
                    ]);

                ?>
            </div>

            <!-- Payment restriction End -->
        <?php
    }

    /**
     * Coupon URL Fields
     * @param mixed $coupon_id
     * @return void
     */
    public function coupon_url_fields( $coupon_id ){
        ?>
            <div class="options_group" style="border-top:0;">
                <?php
                    if( woolentor_get_option_pro('url_coupon','woolentor_advanced_coupon_settings', 'off') == 'on'){

                        woocommerce_wp_checkbox([
                            'id'          => 'woolentor_coupon_url_enable',
                            'value'       => Functions::get_meta_data( $coupon_id,'woolentor_coupon_url_enable', 'no' ) === 'yes' ? 'yes' : 'no',
                            'label'       => esc_html__( 'Enable URL Coupons', 'woolentor' ),
                            'description' => esc_html__( 'Enable this option to use URL coupons, keep it disabled if not needed.', 'woolentor' ),
                            'desc_tip'    => false,
                        ]);

                        woocommerce_wp_text_input([
                            'id'                => 'woolentor_coupon_url',
                            'label'             => esc_html__( 'Coupon URL', 'woolentor' ),
                            'description'       => esc_html__( 'With visitors navigating to the URL, a coupon will be applied to their cart automatically.', 'woolentor' ),
                            'type'              => 'text',
                            'data_type'         => 'text',
                            'value'             => Functions::generate_coupon_url($coupon_id),
                            'custom_attributes' => [ 'readonly' => true ],
                            'desc_tip'          => true,
                        ]);

                        woocommerce_wp_text_input([
                            'id'          => 'woolentor_code_change_in_url',
                            'label'       => esc_html__( 'Modify the Coupon URL', 'woolentor' ),
                            'description' => esc_html__( 'Insert a custom identifier or code to display at the end of the coupon URL.', 'woolentor' ),
                            'desc_tip'    => false,
                            'type'        => 'text',
                            'data_type'   => 'text',
                            'value'       => Functions::get_meta_data( $coupon_id,'woolentor_code_change_in_url' ),
                        ]);

                        woocommerce_wp_text_input([
                            'id'          => 'woolentor_coupon_url_redirect_url',
                            'label'       => esc_html__( 'Redirect Visitors to', 'woolentor' ),
                            'description' => esc_html__( 'Insert a URL where visitors will be redirected after applying the coupon.', 'woolentor' ),
                            'desc_tip'    => true,
                            'type'        => 'text',
                            'data_type'   => 'text',
                            'value'       => Functions::get_meta_data( $coupon_id,'woolentor_coupon_url_redirect_url' ),
                            'placeholder' => wc_get_cart_url(),
                        ]);

                        woocommerce_wp_textarea_input([
                            'id'          => 'woolentor_coupon_url_success_message',
                            'label'       => esc_html__( 'Success Message', 'woolentor' ),
                            'description' => esc_html__( 'Add a custom message to display when a coupon has been applied successfully, or leave it blank to use the default one.', 'woolentor' ),
                            'desc_tip'    => true,
                            'type'        => 'text',
                            'data_type'   => 'text',
                            'placeholder' => esc_html__( 'The coupon was applied successfully.', 'woolentor' ),
                            'value'       => Functions::get_meta_data( $coupon_id,'woolentor_coupon_url_success_message' ),
                        ]);
                    }else{
                        ?>
                            <p><?php esc_html_e('This option is currently disabled. To enable it, please go to the Module settings.','woolentor'); ?></p>
                            <p><a href="<?php echo admin_url( 'admin.php?page=woolentor' ); ?>" class="button button-primary button-large" target="_blank"><?php esc_html_e('Go Module Settings','woolentor');?></a></p>
                        <?php
                    }
                ?>
            </div>
        <?php
    }

    /**
     * Bulk Coupon Meta Boxes
     * @return void
     */
    public function bulk_coupon_meta_boxes(){

        global $pagenow;
        if( $pagenow !== 'post-new.php' ){
            return;
        }

        add_meta_box( 'woolentor_bulk_coupon',
            esc_html__( 'Bulk Coupon Generate Settings', 'woolentor-pro' ),
            [ $this, 'bulk_coupon_meta_box' ],
            'shop_coupon',
            'normal',
            'high'
        );

    }

    /**
     * Bulk Coupon Meta Box Fields
     * @return void
     */
    public function bulk_coupon_meta_box(){
        $bulk_enable = isset( $_GET['wlgeneratebulk'] ) ? $_GET['wlgeneratebulk'] : 'no';
        ?>
            <div class="woocommerce_options_panel">
                <div class="options_group" style="border-top:0;">
                    <?php
                        woocommerce_wp_text_input(
                            [
                                'id'     => 'woolentor_coupon_gen_limit',
                                'label'  => esc_html__('Generate Number of Coupons?', 'woolentor-pro'),
                                'type'   => 'number',
                                'value'  => 20
                            ]
                        );
                        woocommerce_wp_text_input(
                            [
                                'id'     => 'woolentor_coupon_gen_prefix',
                                'label'  => esc_html__('Coupon Prefix (Do not use space)', 'woolentor-pro'),
                                'type'   => 'text'
                            ]
                        );
                        woocommerce_wp_text_input(
                            [
                                'id'     => 'woolentor_coupon_gen_suffix',
                                'label'  => esc_html__('Coupon Suffix (Do not use space)', 'woolentor-pro'),
                                'type'   => 'text'
                            ]
                        );
                        woocommerce_wp_text_input(
                            [
                                'id'     => 'wlgeneratebulk',
                                'type'   => 'hidden',
                                'value'  => $bulk_enable,
                                'label'  => '',
                            ]
                        );
                    ?>
                </div>
            </div>
        <?php
    }

    /**
     * Bulk Coupon Manager
     * @param mixed $coupon_id
     * @param mixed $coupon
     * @return void
     */
    public function coupon_settings_save($coupon_id, $coupon){
        // Check nonce
        if ( empty($_POST['_wpnonce']) || empty($_POST['post_ID']) || 
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['_wpnonce'])),
                'update-post_' . sanitize_text_field(wp_unslash($_POST['post_ID']))
            )
        ) {
            return;
        }
        
        $bulk_enable = isset( $_POST['wlgeneratebulk'] ) ? $_POST['wlgeneratebulk'] : 'no';
        if( $bulk_enable !== 'yes'){
            return;
        }

        if ( !$this->first_created_coupon_id ) {
            $this->first_created_coupon_id = $coupon_id;
            $this->generate_coupon($_POST);
            $coupon->delete(true);
            wp_safe_redirect(admin_url('edit.php?post_type=shop_coupon'));
            exit();
        }

    }

    /**
     * Generate Bulk Coupon
     * @param mixed $post_data
     * @return void
     */
    public function generate_coupon( $post_data ){

        if (isset($post_data['woolentor_coupon_gen_limit']) && !empty($post_data['woolentor_coupon_gen_limit'])) {
            $total_coupons  = $post_data['woolentor_coupon_gen_limit'];
            $coupons_prefix = trim($post_data['woolentor_coupon_gen_prefix']);
            $coupons_suffix = trim($post_data['woolentor_coupon_gen_suffix']);
            $excerpt        = sanitize_text_field(wp_unslash($post_data['excerpt']));

            $coupons_insert_data = [
                'post_content' => '',
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
                'post_type'    => 'shop_coupon',
                'post_excerpt' => $excerpt
            ];

            for ($i = 0; $i < $total_coupons; $i++) {
                $coupons_title = Functions::generate_string();
                
                $coupons_insert_data['post_title'] = trim($coupons_prefix . $coupons_title . $coupons_suffix);

                $insert_id  = wp_insert_post( $coupons_insert_data, true );
                $insert_post= get_post( $insert_id );

                \WC_Meta_Box_Coupon_Data::save($insert_id, $insert_post);

                // Our Custom Meta Data
                CouponMetaBoxes::instance()->save_meta_boxes_data($insert_id, $insert_post);
                $this->save_meta_boxes_data($insert_id, $insert_post);

            }
        }

    }
    

    /**
     * Manage Metaboxes
     * @param mixed $coupon_id
     * @param mixed $coupon
     * @return void
     */
    public function save_meta_boxes_data($coupon_id, $coupon){

        // Check nonce
        if ( empty($_POST['_wpnonce']) || empty($_POST['post_ID']) || 
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['_wpnonce'])),
                'update-post_' . sanitize_text_field(wp_unslash($_POST['post_ID']))
            )
        ) {
            return;
        }

        // Payment method restric
        $payment_restrict_type = ( !empty($_POST['woolentor_payment_restrict_type'] ) ? sanitize_text_field( wp_unslash($_POST['woolentor_payment_restrict_type']) ) : '');
        $payment_method_ids = !empty( $_POST['woolentor_payment_method_ids'] ) ? array_filter( array_map( 'sanitize_text_field', (array) $_POST['woolentor_payment_method_ids'] ) ) : [];
        $payment_error_msg = ( !empty($_POST['woolentor_payment_error_message'] ) ? sanitize_text_field( wp_unslash($_POST['woolentor_payment_error_message']) ) : '');

        update_post_meta($coupon_id, 'woolentor_payment_restrict_type', $payment_restrict_type);
        update_post_meta($coupon_id, 'woolentor_payment_method_ids', $payment_method_ids);
        update_post_meta($coupon_id, 'woolentor_payment_error_message', $payment_error_msg);

        // URL Coupon Settings
        if( woolentor_get_option_pro('url_coupon','woolentor_advanced_coupon_settings', 'off') == 'on'){
            $coupon_url_enable = ( !empty($_POST['woolentor_coupon_url_enable'] ) ? sanitize_text_field( wp_unslash($_POST['woolentor_coupon_url_enable']) ) : 'no');
            $code_change_in_url = ( !empty($_POST['woolentor_code_change_in_url'] ) ? sanitize_text_field( wp_unslash($_POST['woolentor_code_change_in_url']) ) : '');
            $redirect_url = ( !empty($_POST['woolentor_coupon_url_redirect_url'] ) ? sanitize_text_field( wp_unslash($_POST['woolentor_coupon_url_redirect_url']) ) : '');
            $success_msg = ( !empty($_POST['woolentor_coupon_url_success_message'] ) ? sanitize_text_field( wp_unslash($_POST['woolentor_coupon_url_success_message']) ) : '');

            update_post_meta($coupon_id, 'woolentor_coupon_url_enable', $coupon_url_enable);
            update_post_meta($coupon_id, 'woolentor_code_change_in_url', $code_change_in_url);
            update_post_meta($coupon_id, 'woolentor_coupon_url_redirect_url', $redirect_url);
            update_post_meta($coupon_id, 'woolentor_coupon_url_success_message', $success_msg);
        }

    }

}