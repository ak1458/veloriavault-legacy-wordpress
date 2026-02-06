<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="process_product_strategy">
            <?php esc_html_e('When the striked price should be shown', 'advanced-dynamic-pricing-for-woocommerce') ?>
        </label>
        <div style="font-weight: normal; margin: 10px 0;">
            <a href="https://docs.algolplus.com/algol_pricing/settings-algol_pricing/when-the-striked-price-should-be-shown-2/" target="_blank">
                <?php esc_html_e('Read docs', 'advanced-dynamic-pricing-for-woocommerce'); ?>
            </a>
        </div>

    </th>
    <td class="forminp">
        <select name="process_product_strategy" id="process_product_strategy">
            <option <?php selected($options['process_product_strategy'], 'when'); ?> value="when">
                <?php esc_html_e('Before matching condition', 'advanced-dynamic-pricing-for-woocommerce') ?>
            </option>

            <option <?php selected($options['process_product_strategy'], 'after'); ?> value="after">
                <?php esc_html_e('After matching condition', 'advanced-dynamic-pricing-for-woocommerce') ?>
            </option>
        </select>
    </td>
</tr>
