<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php esc_html_e('"Individual use" WC coupon suppress coupons added by rules',
            'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php esc_html_e('"Individual use" WC coupon suppress coupons added by rules',
                        'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="individual_wc_coupon_suppress_coupons">
                <input <?php checked($options['individual_wc_coupon_suppress_coupons']) ?>
                    name="individual_wc_coupon_suppress_coupons" id="individual_wc_coupon_suppress_coupons" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>
