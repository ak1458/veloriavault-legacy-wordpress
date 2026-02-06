<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc">
        <?php esc_html_e('Apply pricing rules while doing cron', 'advanced-dynamic-pricing-for-woocommerce') ?>
        <div style="font-style: italic; font-weight: normal; margin: 10px 0;">
            <label>
                <?php
                printf(
                    /* translators: Enabled option for active plugins*/
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.I18n.MissingTranslatorsComment
                    _x('this option is always on for the %1$s and %2$s plugins.',
                        'this option is always on for the [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) and [Facebook for WooCommerce](https://wordpress.org/plugins/facebook-for-woocommerce/) plugins.',
                        'advanced-dynamic-pricing-for-woocommerce'),
                    '<a href="https://wordpress.org/plugins/wordpress-seo/" target="_blank">Yoast SEO</a>',
                    '<a href="https://wordpress.org/plugins/facebook-for-woocommerce/" target="_blank">Facebook for WooCommerce</a>'
                );
                ?>
            </label>
        </div>
    </th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <label for="update_prices_while_doing_cron">
                <input <?php checked($options['update_prices_while_doing_cron']) ?>
                    name="update_prices_while_doing_cron" id="update_prices_while_doing_cron" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>
