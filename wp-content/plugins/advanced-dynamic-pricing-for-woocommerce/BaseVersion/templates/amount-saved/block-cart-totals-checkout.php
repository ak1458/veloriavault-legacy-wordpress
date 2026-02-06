<?php

defined('ABSPATH') or exit;

/**
 * @var $title string
 * @var $amount_saved float
 * @var $additionalArgs array
 */
?>
<?php
if (!empty($additionalArgs['afterTotals'])): ?>
    <div class="wc-block-components-totals-item wc-block-components-totals-footer-item"
         style="padding: 16px"><span
            class="wc-block-components-totals-item__label"><?php
            echo esc_attr($title); ?></span>
        <div class="wc-block-components-totals-item__value"><span
                class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-footer-item-tax-value"><?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo wc_price($amount_saved); ?></span>
        </div>
        <div class="wc-block-components-totals-item__description"></div>
    </div>
<?php
else: ?>
    <div class="wc-block-components-totals-wrapper">
        <div class="wc-block-components-totals-item"><span
                class="wc-block-components-totals-item__label"><?php
                echo esc_attr($title); ?></span><span
                class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-item__value"><?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo wc_price($amount_saved); ?></span>
            <div class="wc-block-components-totals-item__description"></div>
        </div>
    </div>
<?php
endif; ?>
