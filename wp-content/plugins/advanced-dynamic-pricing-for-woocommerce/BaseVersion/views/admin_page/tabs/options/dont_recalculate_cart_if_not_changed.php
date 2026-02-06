<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php esc_html_e('Don\'t recalculate  cart if cart items don\'t have changed',
            'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php esc_html_e('Don\'t recalculate  cart if cart items don\'t have changed',
                        'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="dont_recalculate_cart_if_not_changed">
                <input <?php checked($options['dont_recalculate_cart_if_not_changed']) ?>
                    name="dont_recalculate_cart_if_not_changed" id="dont_recalculate_cart_if_not_changed" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>
