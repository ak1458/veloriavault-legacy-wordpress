<?php

namespace ADP\BaseVersion\Includes\Compatibility\Container;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPriceTypeEnum;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;
use YITH_WCPB_Frontend_Premium;

defined('ABSPATH') or exit;

/**
 * Plugin Name: YITH WooCommerce Product Bundles
 * Author: YITH
 *
 * @see https://wordpress.org/plugins/yith-woocommerce-product-bundles/
 */
class YithBundlesCmp extends AbstractContainerCompatibility
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var YITH_WCPB_Frontend_Premium
     */
    private $yithWcpbFrontendPremium = null;

    public function __construct(Context $context)
    {
        $this->context = $context;
        if(class_exists('YITH_WCPB_Frontend_Premium')){
            $this->yithWcpbFrontendPremium = yith_wcpb_frontend();
        }
    }

    protected function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @param WcCartItemFacade $facade
     *
     * @return bool
     */
    public function isFacadeAPartOfContainer(WcCartItemFacade $facade): bool
    {
        $trdPartyData = $facade->getThirdPartyData();

        return isset($trdPartyData['bundled_by']);
    }

    /**
     * @param WcCartItemFacade $facade
     *
     * @return bool
     */
    public function isContainerFacade(WcCartItemFacade $facade): bool
    {
        $trdPartyData = $facade->getThirdPartyData();

        return isset($trdPartyData['yith_parent']) && isset($trdPartyData['bundled_items']);
    }

    public function isActive(): bool
    {
        return defined('YITH_WCPB_VERSION');
    }

    public function isContainerProduct(\WC_Product $wcProduct): bool
    {
        return $wcProduct instanceof \WC_Product_Yith_Bundle;
    }

    public function isFacadeAPartOfContainerFacade(
        WcCartItemFacade $partOfContainerFacade,
        WcCartItemFacade $bundle
    ): bool {
        $thirdPartyData = $bundle->getThirdPartyData();

        return in_array($partOfContainerFacade->getKey(), $thirdPartyData['bundled_items'] ?? [], true);
    }

    public function getListOfPartsOfContainerFromContainerProduct(\WC_Product $product): array
    {
        if (!($product instanceof \WC_Product_Yith_Bundle)) {
            return [];
        }

        return array_map(
            function ($bundleItem) use ($product) {
                /** @var \YITH_WC_Bundled_Item $bundleItem */
                $bundledProduct = $bundleItem->get_product();

                $price = $bundledProduct->get_price('edit');

                return ContainerPartProduct::of(
                    $product,
                    $bundledProduct,
                    (float)$price,
                    (float)$bundleItem->get_quantity(),
                    false
                );
            },
            $product->get_bundled_items()
        );
    }

    public function calculatePartOfContainerPrice(WcCartItemFacade $facade): float
    {
        $product = $facade->getProduct();
        $reflection = new \ReflectionClass($product);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);
        $basePrice = $property->getValue($product)['price'];

        return floatval($basePrice);
    }

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    public function calculateContainerPrice(WcCartItemFacade $facade, array $children): float
    {

        if (!is_null($this->yithWcpbFrontendPremium) && $this->yithWcpbFrontendPremium->show_item_prices_in_cart_and_checkout) {
            $containerPrice = 0.0;

            foreach ($children as $child) {
                $containerPrice += floatval($child->getProduct()->get_price());
            }
            return $containerPrice;
        }

        return floatval($facade->getProduct()->get_price());
    }

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    public function calculateContainerBasePrice(WcCartItemFacade $facade, array $children): float
    {
        if (!is_null($this->yithWcpbFrontendPremium) && $this->yithWcpbFrontendPremium->show_item_prices_in_cart_and_checkout) {
            return 0.0;
        }

        return floatval($facade->getProduct()->get_regular_price());
    }

    public function getContainerPriceTypeByParentFacade(WcCartItemFacade $facade): ?ContainerPriceTypeEnum
    {
        $product = $facade->getProduct();

        if (!($product instanceof \WC_Product_Yith_Bundle)) {
            return null;
        }

        if (!is_null($this->yithWcpbFrontendPremium) && !$this->yithWcpbFrontendPremium->show_item_prices_in_cart_and_checkout) {
            return ContainerPriceTypeEnum::FIXED();
        } else {
            return ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS();
        }
    }

    public function isPartOfContainerFacadePricedIndividually(WcCartItemFacade $facade): ?bool
    {
        if (!is_null($this->yithWcpbFrontendPremium) && $this->yithWcpbFrontendPremium->show_item_prices_in_cart_and_checkout) {
            return true;
        }else {
            return false;
        }
    }

    public function overrideContainerReferenceForPartOfContainerFacadeAfterPossibleDuplicates(
        WcCartItemFacade $partOfContainerFacade,
        WcCartItemFacade $containerFacade
    ) {
        $partOfContainerFacade->setThirdPartyData('bundled_by', $containerFacade->getKey());

        $parentFacadeThirdPartyData = $containerFacade->getThirdPartyData();
        $bundledItems = $parentFacadeThirdPartyData['bundled_items'] ?? null;
        if ($bundledItems === null) {
            return;
        }

        $i = array_search($partOfContainerFacade->getOriginalKey(), $bundledItems);
        if ($i !== false) {
            $bundledItems = array_replace(
                $bundledItems,
                [$i => $partOfContainerFacade->getKey()]
            );

            $containerFacade->setThirdPartyData('bundled_items', $bundledItems);
        }
    }
}
