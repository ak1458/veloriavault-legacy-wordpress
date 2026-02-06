<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php esc_html_e('Default fee name', 'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php esc_html_e('Default fee name', 'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="default_fee_name">
                <input value="<?php echo esc_attr($options['default_fee_name']) ?>"
                       name="default_fee_name" id="default_fee_name" type="text">

            </label>
            <div>
                <?php esc_html_e('Fees with the same name are grouped in the cart.',
                    'advanced-dynamic-pricing-for-woocommerce') ?>
            </div>
        </fieldset>
    </td>
</tr>
