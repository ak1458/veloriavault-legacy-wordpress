<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="bulk_table_calculation_mode"><?php esc_html_e('Calculate price based on',
                'advanced-dynamic-pricing-for-woocommerce') ?></label>
    </th>
    <td class="forminp">
        <label><input type="radio" name="bulk_table_calculation_mode"
                      value="only_bulk_rule_table" <?php checked($options['bulk_table_calculation_mode'],
                'only_bulk_rule_table'); ?>>
            <?php esc_html_e('Current bulk rule', 'advanced-dynamic-pricing-for-woocommerce') ?></label>

        <label><input type="radio" name="bulk_table_calculation_mode"
                      value="all" <?php checked($options['bulk_table_calculation_mode'], 'all'); ?>>
            <?php esc_html_e('All active rules', 'advanced-dynamic-pricing-for-woocommerce') ?></label>
    </td>
</tr>
