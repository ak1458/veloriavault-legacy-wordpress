<?php
defined('ABSPATH') or exit;

/**
 * @var $title string
 * @var $amount_saved float
 */
?>
<li class="woocommerce-mini-cart__total total adp-discount" style="text-align: center">
    <strong><?php echo esc_html($title); ?>:</strong>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo wc_price($amount_saved); ?>
</li>
