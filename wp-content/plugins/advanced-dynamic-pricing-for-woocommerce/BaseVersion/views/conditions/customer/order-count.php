<?php

use ADP\BaseVersion\Includes\Core\Rule\CartCondition\Interfaces\TimeRangeCondition;
use ADP\BaseVersion\Includes\Core\Rule\CartCondition\Interfaces\ValueComparisonCondition;

defined('ABSPATH') or exit;

?>
<div class="wdp-column wdp-condition-subfield wdp-condition-field-method">
    <select name="rule[conditions][{c}][options][<?php echo esc_attr(TimeRangeCondition::TIME_RANGE_KEY) ?>]">
        <optgroup label="<?php esc_attr_e('All time', 'advanced-dynamic-pricing-for-woocommerce') ?>">
            <option value="all_time"><?php esc_html_e('all time', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        </optgroup>
        <optgroup label="<?php esc_attr_e('Current', 'advanced-dynamic-pricing-for-woocommerce') ?>">
            <option value="now"><?php esc_html_e('current day', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="this week"><?php esc_html_e('current week', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="this month"><?php esc_html_e('current month', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="this year"><?php esc_html_e('current year', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        </optgroup>
        <optgroup label="<?php esc_attr_e('Days', 'advanced-dynamic-pricing-for-woocommerce') ?>">
            <option value="-1 day">1 <?php esc_html_e('day', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-2 days">2 <?php esc_html_e('days', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-3 days">3 <?php esc_html_e('days', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-4 days">4 <?php esc_html_e('days', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-5 days">5 <?php esc_html_e('days', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-6 days">6 <?php esc_html_e('days', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        </optgroup>
        <optgroup label="<?php esc_attr_e('Weeks', 'advanced-dynamic-pricing-for-woocommerce') ?>">
            <option value="-1 week">1 <?php esc_html_e('week', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-2 weeks">2 <?php esc_html_e('weeks', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-3 weeks">3 <?php esc_html_e('weeks', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-4 weeks">4 <?php esc_html_e('weeks', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        </optgroup>
        <optgroup label="<?php esc_attr_e('Months', 'advanced-dynamic-pricing-for-woocommerce') ?>">
            <option value="-1 month">1 <?php esc_html_e('month', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-2 months">2 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-3 months">3 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-4 months">4 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-5 months">5 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-6 months">6 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-7 months">7 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-8 months">8 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-9 months">9 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-10 months">10 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-11 months">11 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-12 months">12 <?php esc_html_e('months', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        </optgroup>
        <optgroup label="<?php esc_attr_e('Years', 'advanced-dynamic-pricing-for-woocommerce') ?>">
            <option value="-2 years">2 <?php esc_html_e('years', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-3 years">3 <?php esc_html_e('years', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-4 years">4 <?php esc_html_e('years', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-5 years">5 <?php esc_html_e('years', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-6 years">6 <?php esc_html_e('years', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-7 years">7 <?php esc_html_e('years', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-8 years">8 <?php esc_html_e('years', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-9 years">9 <?php esc_html_e('years', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
            <option value="-10 years">10 <?php esc_html_e('years', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        </optgroup>
    </select>
</div>

<div class="wdp-column wdp-condition-subfield wdp-condition-field-method">
    <select name="rule[conditions][{c}][options][<?php echo esc_attr(ValueComparisonCondition::COMPARISON_VALUE_METHOD_KEY) ?>]">
        <option value="<"><?php esc_html_e('&lt;', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        <option value="<="><?php esc_html_e('&lt;=', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        <option value=">="><?php esc_html_e('&gt;=', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
        <option value=">"><?php esc_html_e('&gt;', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
    </select>
</div>

<div class="wdp-column wdp-condition-subfield wdp-condition-field-value">
    <input name="rule[conditions][{c}][options][<?php echo esc_attr(ValueComparisonCondition::COMPARISON_VALUE_KEY) ?>]" type="number" placeholder="0.00" min="0">
</div>
