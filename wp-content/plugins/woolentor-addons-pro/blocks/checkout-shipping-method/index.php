<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = $attributes;

$is_editor = ( isset( $_GET['is_editor_mode'] ) && $_GET['is_editor_mode'] == 'yes' ) ? true : false;

if( $is_editor ){
    \WC()->frontend_includes();
    if ( is_null( \WC()->cart ) ) {
        \WC()->session = new \WC_Session_Handler();
        \WC()->session->init();
        \WC()->cart     = new \WC_Cart();
        \WC()->customer = new \WC_Customer(get_current_user_id(), true);
    }
    \WooLentorBlocks\Sample_Data::instance()->add_product_for_empty_cart();
    \WC()->cart->calculate_totals();
}

if( is_checkout() && !$is_editor ){
    \WC()->cart->calculate_totals();
}

if( is_checkout() || ( $is_editor && !empty( \WC()->cart->cart_contents ) ) ){
    $shipping_status = ( \WC()->cart->needs_shipping() && \WC()->cart->show_shipping() ) ? true : false;
}else{
    $shipping_status = false; 
}
if( !$shipping_status ){
    return;
}

$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
$areaClasses = array( $uniqClass, 'woolentor_block_checkout_shipping_method', 'woolentor-shipping-method-1', 'woocommerce woocommerce-checkout woocommerce-page' );
!empty( $settings['align'] ) ? $areaClasses[] = 'align'.$settings['align'] : '';
!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';
!empty( $settings['layout'] ) ? $areaClasses[] = 'wl_style_'.esc_attr( $settings['layout'] ) : '';


echo '<div class="'.esc_attr( implode(' ', $areaClasses ) ).'">'; 
?>
    <?php if( !empty( $settings['sectionTitle'] ) ): ?>
        <h3 class="woolentor-title"><?php echo wp_kses_post( $settings['sectionTitle'] ) ?></h3>
    <?php endif; ?>
    <div class="woolentor-checkout__shipping-method">
        <table>
            <tbody>
                <?php
                    if( is_checkout() || ( $is_editor && !empty( \WC()->cart->cart_contents ) ) ){
                        if ( $shipping_status ){
                            do_action( 'woocommerce_review_order_before_shipping' );
                            wc_cart_totals_shipping_html();
                            do_action( 'woocommerce_review_order_after_shipping' );
                        }
                    }
                ?>
            </tbody>
        </table>
    </div> <!-- .woolentor-checkout__shipping-method -->
<?php
echo '</div>';