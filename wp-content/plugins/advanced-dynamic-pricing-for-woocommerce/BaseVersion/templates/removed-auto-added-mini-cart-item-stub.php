<?php

defined('ABSPATH') or exit;

/**
 * @var string $hash
 * @var string $cartUrl
 * @var float $qty
 *
 */

?>
    <li>
        <div>
            <span class="adp-autoadd-mini-cart-item-removed-stub-plus" style="vertical-align: middle">+</span>
            <span class="adp-autoadd-mini-cart-item-removed-stub-text" style="vertical-align: middle">
				<?php
                /* translators: Message about the deletion of an automatically added product*/
                echo sprintf(esc_html__('You have deleted %d auto added products from the cart.',
                    'advanced-dynamic-pricing-for-woocommerce'), esc_html($qty));
                ?>
			</span>
            <?php
            if ( ! isset($options["dont_show_restore_link"]) || ! $options["dont_show_restore_link"]) {
                ?>
                <div class="adp-autoadd-mini-cart-item-removed-stub-url">
                    <a href="<?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $cartUrl; ?>">
                        <?php esc_html_e("Restore", 'advanced-dynamic-pricing-for-woocommerce'); ?>
                    </a>
                </div>
                <?php
            }
            ?>
        </div>
    </li>
<?php
