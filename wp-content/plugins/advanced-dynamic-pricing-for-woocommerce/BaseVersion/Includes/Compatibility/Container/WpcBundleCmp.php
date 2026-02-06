<?php

namespace ADP\BaseVersion\Includes\Compatibility\Container;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\CartProcessor\CartProcessor;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPartCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPriceTypeEnum;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

/**
 * Plugin Name: WPC Product Bundles for WooCommerce
 * Author: WPClever
 *
 * @see https://wpclever.net/downloads/product-bundles/
 */
class WpcBundleCmp extends AbstractContainerCompatibility
{
    /**
     * @var Context
     */
    protected $context;

    protected $settings;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->settings = (array) get_option( 'woosb_settings', [] );
    }

    protected function getContext(): Context
    {
        return $this->context;
    }

    public function addFilters()
    {
    }

    public function isActive(): bool
    {
        return class_exists("WPCleverWoosb") || class_exists("WC_Product_Woosb");
    }

    public function isFacadeAPartOfContainer(WcCartItemFacade $facade): bool
    {
        $trdPartyData = $facade->getThirdPartyData();

        return isset($trdPartyData['woosb_parent_id']);
    }

    /**
     * @param WcCartItemFacade $facade
     *
     * @return bool
     */
    public function isSmartBundle(WcCartItemFacade $facade)
    {
        $trdPartyData = $facade->getThirdPartyData();

        return isset($trdPartyData['woosb_key']) && !isset($trdPartyData['woosb_parent_id']);
    }

    public function isContainerFacade(WcCartItemFacade $facade): bool
    {
        return $this->isSmartBundle($facade);
    }

    public function isContainerProduct(\WC_Product $wcProduct): bool
    {
        return $wcProduct instanceof \WC_Product_Woosb;
    }

    public function isFacadeAPartOfContainerFacade(WcCartItemFacade $partOfContainerFacade, WcCartItemFacade $bundle): bool
    {
        $thirdPartyData = $bundle->getThirdPartyData();

        return in_array($partOfContainerFacade->getKey(), $thirdPartyData['woosb_keys'] ?? [], true);
    }

    public function calculatePartOfContainerPrice(WcCartItemFacade $facade): float
    {
        $product = $facade->getProduct();
        $reflection = new \ReflectionClass($product);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);

        if(isset($this->settings['bundled_price_from']) && $this->settings['bundled_price_from'] === 'regular_price') {
            $basePrice = (float)$property->getValue($product)['regular_price'];
        } else {
            $basePrice = (float)$property->getValue($product)['price'];
        }

        return floatval($basePrice);
    }

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    public function calculateContainerPrice(WcCartItemFacade $facade, array $children): float
    {
        $thirdPartyData = $facade->getThirdPartyData();

        if (isset($thirdPartyData['woosb_price'])) {
            return floatval($thirdPartyData['woosb_price']);
        }

        return $this->calculateContainerBasePrice($facade, $children);
    }

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    public function calculateContainerBasePrice(WcCartItemFacade $facade, array $children): float
    {
        $thirdPartyData = $facade->getThirdPartyData();
        $parentProduct = $facade->getProduct();
        if (!empty($thirdPartyData['woosb_discount'])) {
            $_price = floatval(CartProcessor::getProductPriceDependsOnPriceMode($parentProduct));
            $_price   *=  (float) $thirdPartyData['woosb_discount']  / 100;
            return -$_price;
        }

        if (!empty($thirdPartyData['woosb_discount_amount'])) {
            return -floatval($thirdPartyData['woosb_discount_amount']);
        }

        if (!empty($thirdPartyData['woosb_price'])) {
            return 0.0;
        }

        if ($parentProduct instanceof \WC_Product_Woosb && !$parentProduct->is_fixed_price()) {
            return 0.0;
        }

        return floatval(CartProcessor::getProductPriceDependsOnPriceMode($facade->getProduct()));
    }

    public function getListOfPartsOfContainerFromContainerProduct(\WC_Product $product): array
    {
        if (!($product instanceof \WC_Product_Woosb)) {
            return [];
        }

        return array_filter(array_map(
            function ($bundleItem) use ($product) {
                $bundledProduct = CacheHelper::getWcProduct($bundleItem['id']);

                if(!($bundledProduct instanceof \WC_Product)) {
                    return false;
                }

                $price = $bundledProduct->get_price('edit');

                return ContainerPartProduct::of(
                    $product,
                    $bundledProduct,
                    (float)$price,
                    (float)$bundleItem['qty'],
                    !$product->is_fixed_price()
                );
            },
            $product->get_items()
        ));
    }

    public function getContainerPriceTypeByParentFacade(WcCartItemFacade $facade): ?ContainerPriceTypeEnum
    {
        $product = $facade->getProduct();

        if (!($product instanceof \WC_Product_Woosb)) {
            return null;
        }

        if ($product->is_fixed_price()) {
            return ContainerPriceTypeEnum::FIXED();
        } else {
            return ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS();
        }
    }

    public function isPartOfContainerFacadePricedIndividually(WcCartItemFacade $facade): ?bool
    {
        $thirdPartyData = $facade->getThirdPartyData();

        return !($thirdPartyData['woosb_fixed_price'] ?? null);
    }

    public function overrideContainerReferenceForPartOfContainerFacadeAfterPossibleDuplicates(
        WcCartItemFacade $partOfContainerFacade,
        WcCartItemFacade $containerFacade
    ) {
        $partOfContainerFacade->setThirdPartyData('woosb_parent_key', $containerFacade->getKey());

        $parentFacadeThirdPartyData = $containerFacade->getThirdPartyData();
        $bundledItems = $parentFacadeThirdPartyData['woosb_keys'] ?? null;
        if ($bundledItems === null) {
            return;
        }

        $i = array_search($partOfContainerFacade->getOriginalKey(), $bundledItems);
        if ($i !== false) {
            $bundledItems = array_replace(
                $bundledItems,
                [$i => $partOfContainerFacade->getKey()]
            );

            $containerFacade->setThirdPartyData('woosb_keys', $bundledItems);
        }
    }

    public function adaptContainerCartItem(
        WcCartItemFacade $facade,
        array $children,
        int $pos
    ): ContainerCartItem {
        $containerItem = parent::adaptContainerCartItem($facade, $children, $pos);

        return $containerItem->setItems(
            array_map(
                function ($subContainerItem) use ($facade) {
                    /** @var ContainerPartCartItem $subContainerItem */
                    return $this->modifyPartOfContainerItemQty($subContainerItem, $facade);
                },
                array_map([$this, 'adaptContainerPartCartItem'], $children)
            )
        );
    }

    /**
     * Children are stored in the WC_Cart with total qty from ALL bundles.
     * Change to qty of a single container.
     * To get total qty in the future we will multiply it to qty of parent (container).
     *
     * @param ContainerPartCartItem $subContainerItem
     * @param WcCartItemFacade $parentFacade
     * @return ContainerPartCartItem
     */
    protected function modifyPartOfContainerItemQty(
        ContainerPartCartItem $subContainerItem,
        WcCartItemFacade $parentFacade
    ): ContainerPartCartItem {
        if (!$parentFacade->getProduct()->is_fixed_price()) {
            $subContainerItem->setQty($subContainerItem->getQty() / $parentFacade->getQty());
        }

        return $subContainerItem;
    }
}
