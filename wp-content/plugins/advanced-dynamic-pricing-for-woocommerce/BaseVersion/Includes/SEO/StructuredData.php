<?php

namespace ADP\BaseVersion\Includes\SEO;

use ADP\BaseVersion\Includes\Compatibility\YoastSEOCmp;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Engine;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedGroupedProduct;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedProductSimple;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedVariableProduct;
use ADP\BaseVersion\Includes\WC\PriceFunctions;
use ADP\Factory;

defined('ABSPATH') or exit;

class StructuredData
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Engine
     */
    protected $globalEngine;

    /**
     * @var DiscountRangeFormatter
     */
    protected $discountRangeFormatter;

    /**
     * @param Context|Engine $contextOrEngine
     * @param Engine|null    $deprecated
     */
    public function __construct($contextOrEngine, $deprecated = null)
    {
        $this->context      = adp_context();
        $this->globalEngine = $contextOrEngine instanceof Engine ? $contextOrEngine : $deprecated;
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function install()
    {
        add_filter('woocommerce_structured_data_product_offer', array($this, 'structuredProductData'), 10, 2);
        $this->discountRangeFormatter = Factory::get("PriceDisplay_PriceFormatters_DiscountRangeFormatter");
    }

    /**
     * @param array       $data
     * @param \WC_Product $product
     *
     * @return array
     */
    public function structuredProductData($data, $product)
    {
        if ( ! $this->globalEngine) {
            return $data;
        }

        if ( ! $this->context->getOption('is_calculate_based_on_wc_precision')) {
            $decimals = $this->context->getPriceDecimals() - 2;
        } else {
            $decimals = $this->context->getPriceDecimals();
        }

        if (is_object($product) && $product->get_price()) {
            $productProcessor = $this->globalEngine->getProductProcessor();
            $processedProduct = $productProcessor->calculateProduct($product, 1);
            $priceSpecification = null;

            if (is_null($processedProduct)) {
                return $data;
            }

            if ($processedProduct instanceof ProcessedVariableProduct || $processedProduct instanceof ProcessedGroupedProduct) {
                $lowestPrice = $processedProduct->getLowestPrice();
                $highestPrice = $processedProduct->getHighestPrice();

                if($this->discountRangeFormatter->isNeeded($processedProduct)) {
                    if ($discountRangeProcessed = $processedProduct->getLowestRangeDiscountPriceProduct()) {
                        $lowestPrice = $discountRangeProcessed->getMinDiscountRangePrice();
                    }
                }

                if ($lowestPrice === $highestPrice) {
                    unset($data['lowPrice']);
                    unset($data['highPrice']);
                    $data['@type'] = 'Offer';
                    $data['price'] = wc_format_decimal($lowestPrice, $decimals);
                    // Assume prices will be valid until the end of next year, unless on sale and there is an end date.
                    $data['priceValidUntil']    = gmdate('Y-12-31', time() + YEAR_IN_SECONDS);
                    $priceSpecification = [
                        'price'                 => wc_format_decimal($lowestPrice, $decimals),
                        'priceCurrency'         => $this->context->getCurrencyCode(),
                        'valueAddedTaxIncluded' => $this->context->getIsPricesIncludeTax() ? 'true' : 'false',
                    ];
                } else {
                    unset($data['price']);
                    unset($data['priceValidUntil']);
                    unset($data['priceSpecification']);
                    $data['@type'] = 'AggregateOffer';
                    $data['lowPrice']  = wc_format_decimal($lowestPrice, $decimals);
                    $data['highPrice'] = wc_format_decimal($highestPrice, $decimals);
                    $data['offerCount'] = count( $product->get_children() );
                }
            } elseif ($processedProduct instanceof ProcessedProductSimple) {
                $price = $processedProduct->getPrice();
                if($this->discountRangeFormatter->isNeeded($processedProduct)) {
                    $price = $processedProduct->getMinDiscountRangePrice();
                }

                $data['price']              = wc_format_decimal($price, $decimals);
                $priceSpecification = [
                    'price'                 => wc_format_decimal($price, $decimals),
                    'priceCurrency'         => $this->context->getCurrencyCode(),
                    'valueAddedTaxIncluded' => $this->context->getIsPricesIncludeTax() ? 'true' : 'false',
                ];
            }

            if (isset($priceSpecification)) {
                if (YoastSEOCmp::isNewPriceSpecification()) {
                    $priceSpecification["@type"] = "UnitPriceSpecification";
                    $priceSpecification['validThrough']  = gmdate('Y-12-31', time() + YEAR_IN_SECONDS);
                    $priceSpecification['valueAddedTaxIncluded'] = filter_var($priceSpecification['valueAddedTaxIncluded'], FILTER_VALIDATE_BOOLEAN);

                    $data['priceSpecification'] = [ $priceSpecification ];
                } else {
                    $data['priceSpecification'] = $priceSpecification;
                }
            }

            $data['priceCurrency'] = $this->context->getCurrencyCode();
        }

        do_action("adp_schema_data_ready", $data, $processedProduct ?? null, $decimals);

        return $data;
    }


}
