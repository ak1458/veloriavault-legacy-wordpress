<?php

use ADP\BaseVersion\Includes\Core\Rule\CartCondition\Interfaces\ProductAll;
use ADP\BaseVersion\Includes\Core\Rule\Enums\ProductMeasure;

defined('ABSPATH') or exit;

/**
 * @var ProductAll $condition
 */

?>

<div class="wdp-column wdp-condition-subfield wdp-condition-field-measure">
    <select name="rule[conditions][{c}][options][<?php echo esc_attr(ProductAll::PRODUCT_MEASURE_KEY) ?>]">
        <?php foreach ($condition->getMeasures() as $measureKey => $measureTitle): ?>
            <option value="<?php echo esc_attr($measureKey) ?>"
                <?php if ($measureKey === ProductMeasure::MEASURE_QTY()->getValue()) echo 'selected' ?>>
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $measureTitle ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="wdp-column wdp-condition-field-product-all-sub">
</div>
