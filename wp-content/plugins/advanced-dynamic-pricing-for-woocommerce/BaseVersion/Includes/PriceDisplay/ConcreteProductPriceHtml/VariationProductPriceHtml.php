<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\ConcreteProductPriceHtml;

use ADP\BaseVersion\Includes\PriceDisplay\ConcreteProductPriceHtml;
use ADP\BaseVersion\Includes\PriceDisplay\PriceFormatters\DefaultFormatter;

defined('ABSPATH') or exit;

class VariationProductPriceHtml extends SimpleProductPriceHtml implements ConcreteProductPriceHtml
{
    /**
     * @param string $priceHtml
     *
     * @return string
     */
    public function getFormattedPriceHtml($priceHtml)
    {
        $processedProduct       = $this->processedProduct;
        $defaultFormatter = new DefaultFormatter($this->context);

        if ($processedProduct->areRulesApplied()) {
            $priceHtml = $this->getHtml(1.0);
        }

        return $defaultFormatter->isNeeded($processedProduct)
            ? $defaultFormatter->getHtml($priceHtml, $processedProduct)
            : $priceHtml;
    }
}
