<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php esc_html_e('Don\'t recalculate cart on page load',
            'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php esc_html_e('Don\'t recalculate cart on page load',
                        'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="dont_recalculate_cart_on_page_load">
                <input <?php checked($options['dont_recalculate_cart_on_page_load']) ?>
                    name="dont_recalculate_cart_on_page_load" id="dont_recalculate_cart_on_page_load" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>
