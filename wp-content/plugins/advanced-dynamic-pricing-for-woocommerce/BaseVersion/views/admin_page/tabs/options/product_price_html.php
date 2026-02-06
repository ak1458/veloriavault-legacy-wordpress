<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc">
        <?php esc_html_e('Product price html template', 'advanced-dynamic-pricing-for-woocommerce') ?>
    </th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <div>
                <label for="enable_product_html_template">
                    <input <?php checked($options['enable_product_html_template']) ?>
                        name="enable_product_html_template" id="enable_product_html_template" type="checkbox">
                    <?php esc_html_e('Enable', 'advanced-dynamic-pricing-for-woocommerce') ?>
                </label>
            </div>
            <div class="wdp-settings-template-wrap">
                <label for="price_html_template" class="wdp-settings-template-label">
                    <?php esc_html_e('Output template', 'advanced-dynamic-pricing-for-woocommerce') ?>
                    <textarea class="wdp-settings-template-textarea" name="price_html_template" id="price_html_template"><?php 
                        echo esc_attr($options['price_html_template'])
                    ?></textarea>                    
                </label>
            </div>
            <div style="line-height: 1.5rem;">
                <span>
                    <?php esc_html_e('Available tags', 'advanced-dynamic-pricing-for-woocommerce') ?>
                    : <?php esc_html_e('{{price_html}},  {{regular_price_striked}}, {{discounted_price_inclTax}}, {{discounted_price_exclTax}}, {{price_inclTax}}, {{price_exclTax}}', 
                        'advanced-dynamic-pricing-for-woocommerce') ?>
                </span>
                <br>
                <span>
                    <?php esc_html_e('Only for products which are already in the cart: {{Nth_item}}, {{qty_already_in_cart}}.',
                        'advanced-dynamic-pricing-for-woocommerce') ?>

                    <a href="https://docs.algolplus.com/algol_pricing/settings-algol_pricing/product-price-html-template-available-tags-and-examples/"
                        target="_blank">
                        <?php esc_html_e('More available tags', 'advanced-dynamic-pricing-for-woocommerce') ?>
                    </a>
                </span>
            </div>
        </fieldset>
    </td>
</tr>
