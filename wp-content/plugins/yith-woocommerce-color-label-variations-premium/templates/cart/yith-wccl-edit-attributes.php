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

<div class="yith-wccl-cart-container">
    <div class="yith-wccl-edit-attributes-link">
        <a class="yith-wccl-edit-product-cart"
           data-product_id="<?php echo esc_attr( $product_id )?>"
           data-variation_id="<?php echo esc_attr( $variation_id ) ?>"
           data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
        >
            <small class="yith-wccl-edit-variation"><?php
                // translators: Link of the product in the cart page to open the variations.
                echo esc_html__( 'Edit', 'yith-woocommerce-color-label-variations' );
            ?></small>
        </a>
    </div>
</div>

<?php

