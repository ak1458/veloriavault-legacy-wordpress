<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\PriceFormatters;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedProductSimple;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedVariableProduct;
use ADP\BaseVersion\Includes\WC\PriceFunctions;

defined('ABSPATH') or exit;

class DefaultFormatter
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var PriceFunctions
     */
    protected $priceFunctions;

    /**
     * @param null $deprecated
     */
    public function __construct($deprecated = null)
    {
        $this->context   = adp_context();
        $this->formatter = new Formatter();

        //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
        $template = _x( htmlspecialchars_decode( $this->context->getOption("price_html_template","{{price_html}}")),
            "Product price html template|Output template",
            "advanced-dynamic-pricing-for-woocommerce"
        );

        $this->formatter->setTemplate($template);
        $this->priceFunctions = new PriceFunctions();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function getFormatter()
    {
        return $this->formatter;
    }

    public function getPriceFunctions()
    {
        return $this->priceFunctions;
    }

    /**
     * @param ProcessedProductSimple|ProcessedVariableProduct $processedProduct
     *
     * @return bool
     */
    public function isNeeded($processedProduct)
    {
        if ($processedProduct instanceof ProcessedVariableProduct) {
            return false;
        }

        $index = $processedProduct->getQtyAlreadyInCart() + $processedProduct->getQty();

        $hasQtyTags = in_array(['Nth_item', 'qty_already_in_cart'], $this->formatter->getAvailableReplacements());

        return $this->context->getOption("enable_product_html_template", false) && (!$hasQtyTags || $index > 1);
    }

    /**
     * @param string $priceHtml
     * @param ProcessedProductSimple $processedProduct
     *
     * @return string
     */
    public function getHtml($priceHtml, ProcessedProductSimple $processedProduct)
    {
        $index   = (int)($processedProduct->getQtyAlreadyInCart() + $processedProduct->getQty());
        $product = $processedProduct->getProduct();

        $useRegularPrice = $this->context->getSettings()->getOption('regular_price_for_striked_price');
        $calcPrice = $processedProduct->calculateSubtotal();
        $origPrice = $useRegularPrice ? $product->get_regular_price('edit') : $processedProduct->getOriginalPriceToDisplay();

        $replacements = array(
            'price_html'            => $priceHtml,
            'Nth_item'              => $this->addSuffixOf($index),
            'qty_already_in_cart'   => $processedProduct->getQtyAlreadyInCart(),
            'price_suffix'          => get_option('woocommerce_price_display_suffix'),
            'regular_price_striked' => $calcPrice < $origPrice ? '<del>' . $this->priceFunctions->format(
                    $this->priceFunctions->getPriceToDisplay(
                        $product,
                        array("price" => $product->get_regular_price())
                    )) . '</del>' : '',

            'discounted_price_inclTax'  => $this->priceFunctions->format(
                $this->priceFunctions->getPriceIncludingTax($product, ['price' => $calcPrice])
            ),
            'discounted_price_exclTax' => $this->priceFunctions->format(
                $this->priceFunctions->getPriceExcludingTax($product, ['price' => $calcPrice])
            ),

            'price_inclTax'  => $calcPrice < $origPrice ? '<del>' . $this->priceFunctions->format(
                $this->priceFunctions->getPriceIncludingTax($product, ['price' => $origPrice]) 
            ) . '</del>' : '',
            'price_exclTax' => $calcPrice < $origPrice ? '<del>' . $this->priceFunctions->format(
                $this->priceFunctions->getPriceExcludingTax($product, ['price' => $origPrice])
            ). '</del>' : '',

            'calcPrice' => $calcPrice,
            'origPrice' => $origPrice,
        );

        $replacements = apply_filters("adp_default_formatter_replacements", $replacements, $processedProduct, $this);

        return $this->formatter->applyReplacements($replacements);
    }

    /**
     * Add ordinal indicator
     *
     * @param int $value
     *
     * @return string
     */
    protected function addSuffixOf($value)
    {
        if ( ! is_numeric($value)) {
            return $value;
        }

        $value = (string)$value;

        $mod10  = $value % 10;
        $mod100 = $value % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return $value . "st";
        }

        if ($mod10 === 2 && $mod100 !== 12) {
            return $value . "nd";
        }

        if ($mod10 === 3 && $mod100 !== 13) {
            return $value . "rd";
        }

        return $value . "th";
    }
}
