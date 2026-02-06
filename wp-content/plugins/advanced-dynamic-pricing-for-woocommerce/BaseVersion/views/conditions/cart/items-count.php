<?php

use ADP\BaseVersion\Includes\Core\Rule\CartCondition\Interfaces\ValueComparisonCondition;

defined('ABSPATH') or exit;

?>
<div class="wdp-column wdp-condition-subfield wdp-condition-field-method">
    <select name="rule[conditions][{c}][options][<?php echo esc_attr(ValueComparisonCondition::COMPARISON_VALUE_METHOD_KEY) ?>]">
        <option value="<"><?php esc_html_e('&lt;', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        <option value="<="><?php esc_html_e('&lt;=', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        <option value=">="><?php esc_html_e('&gt;=', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        <option value=">"><?php esc_html_e('&gt;', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        <option value="="><?php esc_html_e('=', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
    </select>
</div>

<div class="wdp-column wdp-condition-subfield wdp-condition-field-value">
    <input name="rule[conditions][{c}][options][<?php echo esc_attr(ValueComparisonCondition::COMPARISON_VALUE_KEY) ?>]" type="number" placeholder="0.00" min="0">
</div>
