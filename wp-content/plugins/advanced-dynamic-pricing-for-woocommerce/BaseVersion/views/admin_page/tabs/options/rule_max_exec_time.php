<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php esc_html_e('Disable rule if it runs longer than X seconds',
            'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php esc_html_e('Disable rule if it runs longer than X seconds',
                        'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="rule_max_exec_time">
                <input value="<?php echo esc_attr($options['rule_max_exec_time']) ?>" name="rule_max_exec_time"
                       id="rule_max_exec_time" placeholder="5" type="number" min="0">
            </label>
        </fieldset>
    </td>
</tr>
