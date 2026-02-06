<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php esc_html_e('Default discount name',
            'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php esc_html_e('Default discount name', 'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="default_discount_name">
                <input value="<?php echo esc_attr($options['default_discount_name']) ?>"
                       name="default_discount_name" id="default_discount_name" type="text">

            </label>
            <div>
                <?php esc_html_e('Fixed discounts with the same name are grouped in the cart.',
                    'advanced-dynamic-pricing-for-woocommerce') ?>
            </div>
        </fieldset>
    </td>
</tr>
