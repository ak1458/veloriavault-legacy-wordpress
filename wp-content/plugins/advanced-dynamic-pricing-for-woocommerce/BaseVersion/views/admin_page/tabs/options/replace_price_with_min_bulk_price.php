<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc">
    </th>
    <td class="forminp forminp-checkbox">
        <a href="https://docs.algolplus.com/algol_pricing/faq/common-3/"
           target="_blank">
            <?php esc_html_e('Guide for supported tags', 'advanced-dynamic-pricing-for-woocommerce') ?>
        </a>
    </td>
<tr>


<tr valign="top">
    <th scope="row" class="titledesc">
        <div><?php esc_html_e('Replace price with lowest bulk price', 'advanced-dynamic-pricing-for-woocommerce') ?></div>

    </th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <div style="display: inline-block; line-height: 2rem;" class="wdp-settings-template-wrap">
                <label for="replace_price_with_min_bulk_price_category">
                    <input <?php checked($options['replace_price_with_min_bulk_price_category']) ?>
                        name="replace_price_with_min_bulk_price_category"
                        id="replace_price_with_min_bulk_price_category" type="checkbox">
                    <?php esc_html_e('Apply to category/tag pages', 'advanced-dynamic-pricing-for-woocommerce') ?>
                </label>
                <br/>
                <label for="replace_price_with_min_bulk_price_category_template" class="wdp-settings-template-label">
                    <?php esc_html_e('Output template', 'advanced-dynamic-pricing-for-woocommerce') ?>
                    <textarea class="wdp-settings-template-textarea"
                        name="replace_price_with_min_bulk_price_category_template"
                        id="replace_price_with_min_bulk_price_category_template"
                        ><?php 
							echo esc_attr($options['replace_price_with_min_bulk_price_category_template']) 
					?></textarea>
                </label>
            </div>
            <div>
                <?php esc_html_e('Available tags', 'advanced-dynamic-pricing-for-woocommerce') ?>
                : <?php esc_html_e('{{price}}, {{price_suffix}}, {{price_striked}}, {{initial_price}}, {{regular_price_striked}}, {{min_max_range}}',
                    'advanced-dynamic-pricing-for-woocommerce') ?>
            </div>
        </fieldset>
    </td>
</tr>
