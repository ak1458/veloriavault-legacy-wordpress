<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Variable product add to cart in loop
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 * @version 1.0.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

?>

    <div id="myModal" class="yith-wccl-modal">

        <!-- Modal content -->
        <div class="overlay"></div>
        <div class="modal-dialog">
            <div class="modal-header">
                <span class="close">&times;</span>
            </div>
            <div class="modal-content">
                <div class="yith-wccl-product-content" >
                    <?php echo do_shortcode( '[product_page id=' . $product->get_id() . ']' ); ?>
                </div>
            </div>
            <div class="modal-footer" >
                <?php
                // translators: Link of the product in the cart page to open the variations.
                ?>
                <button class="yith-wccl-modal-update"><?php echo esc_html__('Update', 'yith-woocommerce-color-label-variations')  ?></button>
            </div>
        </div>
        <?php  YITH_WCCL_Frontend()->create_wccl_data_attr( $product->get_id() ); ?>
    </div>
<?php

