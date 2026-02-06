<?php

namespace ADP\BaseVersion\Includes\Compatibility\Container;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPartCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPriceTypeEnum;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;


defined('ABSPATH') or exit;

/**
 * Plugin Name: WPC Composite Products
 * Author: WPClever
 *
 * @see https://wordpress.org/plugins/wpc-composite-products/
 */
class WpcCompositeCmp extends AbstractContainerCompatibility
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

    public function isActive(): bool
    {
        return class_exists("WPCleverWooco");
    }

    public function isFacadeAPartOfContainer(WcCartItemFacade $facade): bool
    {
        $trdPartyData = $facade->getThirdPartyData();

        return isset($trdPartyData['wooco_parent_id']);
    }

    /**
     * @param WcCartItemFacade $facade
     *
     * @return bool
     */
    public function isSmartComposite(WcCartItemFacade $facade)
    {
        $trdPartyData = $facade->getThirdPartyData();

        return isset($trdPartyData["wooco_key"]) && !isset($trdPartyData["wooco_parent_id"]);
    }

    public function isContainerFacade(WcCartItemFacade $facade): bool
    {
        return $this->isSmartComposite($facade);
    }

    public function isContainerProduct(\WC_Product $wcProduct): bool
    {
        return $wcProduct instanceof \WC_Product_Composite;
    }

    public function isFacadeAPartOfContainerFacade(WcCartItemFacade $partOfContainerFacade, WcCartItemFacade $bundle): bool
    {
        $thirdPartyData = $bundle->getThirdPartyData();

        return in_array($partOfContainerFacade->getKey(), $thirdPartyData['wooco_keys'] ?? [], true);
    }

    public function calculatePartOfContainerPrice(WcCartItemFacade $facade): float
    {
        return floatval(0);
    }

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    public function calculateContainerPrice(WcCartItemFacade $facade, array $children): float
    {
        $thirdPartyData = $facade->getThirdPartyData();

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

        if ($parentProduct instanceof \WC_Product_Composite && $parentProduct->get_pricing() === 'exclude') {
            return 0.0;
        }

        if ($this->isComponentsHidden($facade) && isset($thirdPartyData['wooco_price'])) {
            return $thirdPartyData['wooco_price'];
        }

        if($parentProduct->get_sale_price()){
            return floatval($parentProduct->get_sale_price());
        }

        return floatval($parentProduct->get_regular_price());
    }

    public function getListOfPartsOfContainerFromContainerProduct(\WC_Product $product): array
    {
        if (!($product instanceof \WC_Product_Composite)) {
            return [];
        }
       return [];
    }

    public function getContainerPriceTypeByParentFacade(WcCartItemFacade $facade): ?ContainerPriceTypeEnum
    {
        $product = $facade->getProduct();

        if (!($product instanceof \WC_Product_Composite)) {
            return null;
        }

        return $this->isComponentsHidden($facade) ? ContainerPriceTypeEnum::FIXED() : ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS();
    }

    public function isPartOfContainerFacadePricedIndividually(WcCartItemFacade $facade): ?bool
    {
        return false;
    }

    public function overrideContainerReferenceForPartOfContainerFacadeAfterPossibleDuplicates(
        WcCartItemFacade $partOfContainerFacade,
        WcCartItemFacade $containerFacade
    ) {

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
     * @param ContainerPartCartItem $subContainerItem
     * @param WcCartItemFacade $parentFacade
     * @return ContainerPartCartItem
     */
    protected function modifyPartOfContainerItemQty(
        ContainerPartCartItem $subContainerItem,
        WcCartItemFacade $parentFacade
    ): ContainerPartCartItem {

        return $subContainerItem;
    }

    protected function isComponentsHidden(WcCartItemFacade $facade): bool {
        if (!class_exists('WPCleverWooco')) {
            return false;
        }

        if (!apply_filters('adp_wpc_composite_full_price', false)) {
            return false;
        }

        $settingsHideComponents = WPCleverWooco()::get_setting( 'hide_component', 'no' );

        return strpos($settingsHideComponents, 'yes') !== false;
    }
}
