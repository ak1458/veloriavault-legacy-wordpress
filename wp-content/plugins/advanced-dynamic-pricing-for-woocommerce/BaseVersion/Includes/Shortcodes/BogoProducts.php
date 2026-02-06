<?php

namespace ADP\BaseVersion\Includes\Shortcodes;

use ADP\BaseVersion\Includes\Core\Rule\SingleItemRule;

defined('ABSPATH') or exit;

class BogoProducts extends Products
{
    const NAME = 'adp_products_bogo';
    const STORAGE_WITH_RULES_KEY = 'wdp_rules_products_bogo';

    /**
     * @param Rule $rule
     *
     * @return bool
     */
    protected static function filterRule($rule)
    {
        return
            $rule instanceof SingleItemRule &&
            ! $rule->getProductAdjustmentHandler() &&
            ! $rule->getProductRangeAdjustmentHandler() &&
            ! $rule->getRoleDiscounts() &&
            count($rule->getGifts()) === 0 &&
            count($rule->getItemGiftsCollection()->asArray()) > 0 &&
            count($rule->getConditions()) === 0 &&
            count($rule->getLimits()) === 0;
    }
}
