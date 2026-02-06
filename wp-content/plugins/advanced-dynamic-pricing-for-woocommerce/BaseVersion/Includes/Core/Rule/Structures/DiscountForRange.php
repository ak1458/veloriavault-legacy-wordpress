<?php

namespace ADP\BaseVersion\Includes\Core\Rule\Structures;

use Exception;

defined('ABSPATH') or exit;

class DiscountForRange extends Discount
{
    const TYPE_RANGE_AMOUNT = 'range_fixed_amount';
    const TYPE_RANGE_FIXED_VALUE = 'range_fixed_value';

    const AVAILABLE_SET_TYPES = array(
        self::TYPE_RANGE_AMOUNT,
        self::TYPE_PERCENTAGE,
        self::TYPE_RANGE_FIXED_VALUE,
    );

    public function __construct($context, $type, $value)
    {
        if ( ! in_array($type, self::AVAILABLE_SET_TYPES)) {
            $context->handleError(new Exception(sprintf("Discount type '%s' not supported", $type)));
        }

        $this->type         = $type;
        $this->value        = floatval($value);
        $this->currencyCode = $context->getCurrencyCode();
    }
}
