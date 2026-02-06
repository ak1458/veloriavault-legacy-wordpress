<?php

namespace ADP\BaseVersion\Includes\Compatibility\Container;

use ADP\BaseVersion\Includes\CartProcessor\CartProcessor;
use ADP\BaseVersion\Includes\CartProcessor\OriginalPriceCalculation;
use ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter\ToPricingAddonsAdapter;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPartCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPriceTypeEnum;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;
use ADP\Factory;

defined('ABSPATH') or exit;

/**
 * Plugin Name: WooCommerce Composite Products
 * Author: SomewhereWarm
 *
 * @see https://woocommerce.com/products/composite-products/
 */
class SomewhereWarmCompositesCmp extends AbstractContainerCompatibility
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    protected function getContext(): Context
    {
        return $this->context;
    }

    public function addFilters()
    {
        add_filter('adp_product_get_price', function ($price, $product, $variation, $qty, $trdPartyData, $facade) {
            if ($facade === null) {
                return $price;
            }

            if ($this->isContainerFacade($facade)) {
                if ($facade->getOriginalPrice() !== null) {
                    $price = $facade->getOriginalPrice();
                } else {
                    $price = \WC_CP_Display::instance()->get_container_cart_item_price_amount(
                        $facade->getData(),
                        'price'
                    );
                }
            } elseif ($this->isFacadeAPartOfContainer($facade)) {
                $price = 0.0;
            }

            return $price;
        }, 10, 6);
    }

    public function isActive(): bool
    {
        return class_exists("WC_Composite_Products");
    }

    public function isContainerFacade(WcCartItemFacade $facade): bool
    {
        return function_exists('wc_cp_is_composite_container_cart_item') && wc_cp_is_composite_container_cart_item($facade->getThirdPartyData());
    }

    public function isFacadeAPartOfContainer(WcCartItemFacade $facade): bool
    {
        return function_exists('wc_cp_maybe_is_composited_cart_item') && wc_cp_maybe_is_composited_cart_item($facade->getThirdPartyData());
    }

    public function isContainerProduct(\WC_Product $product): bool
    {
        return false;
    }

    public function isFacadeAPartOfContainerFacade(WcCartItemFacade $partOfContainerFacade, WcCartItemFacade $bundle): bool
    {
        $thirdPartyData = $bundle->getThirdPartyData();

        return in_array($partOfContainerFacade->getKey(), $thirdPartyData['composite_children'] ?? [], true);
    }

    public function getListOfPartsOfContainerFromContainerProduct(\WC_Product $product): array
    {
        return [];
    }

    public function calculatePartOfContainerPrice(WcCartItemFacade $facade): float
    {
        /*$compositedProduct = $facade->getProduct();
        $this->probablySetCompositeItem($compositedProduct, $facade);

        if ( isset($compositedProduct->composited_item) ) {
            $childItemPrice = floatval($compositedProduct->composited_item->get_price());
        } else {
            $childItemPrice = floatval($compositedProduct->get_price());
        }*/

        return 0;
    }

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    public function calculateContainerPrice(WcCartItemFacade $facade, array $children): float
    {
        $compositeProduct = $facade->getProduct();
        $basePrice = floatval($compositeProduct->get_price());
        $childItemsPrice = 0.0;
        foreach ($children as $child) {
            $childProduct = $child->getProduct();
            $childItemQty = $childProduct->is_sold_individually() ? 1 : $child->getQty() / $facade->getQty();
            $childItemPrice = $this->calculatePartOfContainerPrice($child) * $childItemQty;
            $childItemsPrice += $childItemPrice;
        }

        return $basePrice + $childItemsPrice;
    }

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    public function calculateContainerBasePrice(WcCartItemFacade $facade, array $children): float
    {
        return floatval(CartProcessor::getProductPriceDependsOnPriceMode($facade->getProduct()));
    }

    public function getContainerPriceTypeByParentFacade(WcCartItemFacade $facade): ?ContainerPriceTypeEnum
    {
        $product = $facade->getProduct();

        if (!($product instanceof \WC_Product_Composite)) {
            return null;
        }

        return ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS();
    }

    public function isPartOfContainerFacadePricedIndividually(WcCartItemFacade $facade): ?bool
    {
        $product = $facade->getProduct();
        $thirdPartyData = $facade->getThirdPartyData();
        $compositeItemId = $thirdPartyData['composite_item'];
        $this->probablySetCompositeItem($product, $facade);

        if (isset($product->composited_item)) {
            $composited_data = $product->composited_item->get_composite_data();

            return $composited_data[$compositeItemId]['priced_individually'] === 'yes';
        }

        return true;
    }

    public function adaptContainerPartCartItem(WcCartItemFacade $facade): ContainerPartCartItem
    {
        $origPriceCalc = new OriginalPriceCalculation();
        $origPriceCalc->withContext($this->getContext());

        Factory::callStaticMethod(
            'PriceDisplay_PriceDisplay',
            'processWithout',
            array($origPriceCalc, 'process'),
            $facade
        );


        $product = $facade->getProduct();
        $reflection = new \ReflectionClass($product);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);
        $basePrice = $property->getValue($product)['price'];

        $qty = floatval(apply_filters('wdp_get_product_qty', $facade->getQty(), $facade));

        $initialPrice = $this->calculatePartOfContainerPrice($facade);
        $initialPrice = (new ToPricingAddonsAdapter())->addAddonsToInitialPriceWithFacade($initialPrice, $facade);
        $pricedIndividually = $this->isPartOfContainerFacadePricedIndividually($facade);

        if (!$pricedIndividually) {
            $basePrice = 0;
            $pricedIndividually = !$pricedIndividually;
        }

        return new ContainerPartCartItem(
            $facade,
            floatval($basePrice),
            false,
            $basePrice,
            $qty
        );
    }

    public function overrideContainerReferenceForPartOfContainerFacadeAfterPossibleDuplicates(
        WcCartItemFacade $partOfContainerFacade,
        WcCartItemFacade $containerFacade
    ) {
        $partOfContainerFacade->setThirdPartyData('composited_by', $containerFacade->getKey());

        $parentFacadeThirdPartyData = $containerFacade->getThirdPartyData();
        $bundledItems = $parentFacadeThirdPartyData['composited_items'] ?? null;
        if ( $bundledItems === null ) {
            return;
        }

        $i = array_search($partOfContainerFacade->getOriginalKey(), $bundledItems);
        if ( $i !== false ) {
            $bundledItems = array_replace(
                $bundledItems,
                [$i => $partOfContainerFacade->getKey()]
            );

            $containerFacade->setThirdPartyData('composited_items', $bundledItems);
        }
    }
    public function probablySetCompositeItem(&$product, $facade) {
        $data = $facade->getThirdPartyData();
        if($product AND !empty($data['composite_parent']) AND $child = wc_cp_get_composited_cart_item_container($data)) {
            $product->composited_item = $child["data"];
        }
    }
}
