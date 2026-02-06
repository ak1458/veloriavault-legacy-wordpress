<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php esc_html_e('Force displaying variation price',
            'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php esc_html_e('Force displaying variation price',
                        'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="force_displaying_variation_price">
                <input <?php checked($options['force_displaying_variation_price']); ?>
                    name="force_displaying_variation_price" id="force_displaying_variation_price" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>
