<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php esc_html_e('Show approximate price range if product has X variations or more',
            'advanced-dynamic-pricing-for-woocommerce') ?>
        <div style="font-style: italic; font-weight: normal; margin: 10px 0;">
            <label><?php esc_html_e('Set 0 to disable ',
                    'advanced-dynamic-pricing-for-woocommerce') ?></label>
        </div>
    </th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php esc_html_e('Show approximate price range if product has X variations or more',
                        'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="req_variations_for_optimization_at_shop">
                <input value="<?php echo esc_attr($options['req_variations_for_optimization_at_shop']) ?>" name="req_variations_for_optimization_at_shop"
                       id="req_variations_for_optimization_at_shop" placeholder="10" type="number" min="0" maxlength="4">
            </label>
        </fieldset>
    </td>
</tr>
