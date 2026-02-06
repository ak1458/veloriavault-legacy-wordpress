<?php

namespace ADP\BaseVersion\Includes\Compatibility\Container;

use ADP\BaseVersion\Includes\CartProcessor\CartProcessor;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPriceTypeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPartCartItem;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;
use AsanaPlugins\WooCommerce\ProductBundles\ProductBundle;
use Exception;

defined('ABSPATH') or exit;

/**
 * Plugin Name: Asana Easy Product Bundles
 * Author: Asana Plugins
 *
 * @see https://wordpress.org/plugins/easy-product-bundles/
 */
class AsanaEasyBundlesCmp extends AbstractContainerCompatibility
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

    public function prepareHooks(): void
    {

        add_filter('woocommerce_cart_item_price', function ($price, $cart_item) {
            try {
                if (!empty($cart_item['asnp_wepb_parent_key']) && !empty($cart_item["asnp_wepb_parent_is_fixed_price"]) && $cart_item["asnp_wepb_parent_is_fixed_price"] === true) {
                    return '';
                } elseif (!empty($cart_item['asnp_wepb_parent_key'])) {
                    if ($this->context->isUsingGlobalPriceSettings()) {
                        return wc_format_sale_price($cart_item["asnp_wepb_reg_price"], $cart_item["asnp_wepb_price"]);
                    }

                    $del = is_numeric($cart_item["asnp_wepb_reg_price"]) ? $this->format($cart_item["asnp_wepb_reg_price"]) : $cart_item["asnp_wepb_reg_price"];
                    $ins = is_numeric($cart_item["asnp_wepb_price"]) ? $this->format($cart_item["asnp_wepb_price"]) : $cart_item["asnp_wepb_price"];

                    return '<del>' . $del . '</del> <ins>' . $ins . '</ins>';
                }
            } catch (Exception $e) {
                wp_send_json_error($e->getMessage());
            }
            return $price;
        }, 10001, 2);

        add_filter('woocommerce_cart_item_subtotal', function ($product_subtotal, $cart_item) {
            try {
                if (!empty($cart_item['asnp_wepb_parent_key']) && !empty($cart_item["asnp_wepb_parent_is_fixed_price"]) && $cart_item["asnp_wepb_parent_is_fixed_price"] === true) {
                    return '';
                } elseif (!empty($cart_item['asnp_wepb_parent_key'])) {
                    $reg_sub = floatval($cart_item["asnp_wepb_reg_price"]) * $cart_item["quantity"];
                    if ($this->context->isUsingGlobalPriceSettings()) {
                        return wc_format_sale_price($reg_sub, $cart_item["line_subtotal"]);
                    }

                    $del = is_numeric($reg_sub) ? $this->format($reg_sub) : $reg_sub;
                    $ins = is_numeric($cart_item["line_subtotal"]) ? $this->format($cart_item["line_subtotal"]) : $cart_item["line_subtotal"];

                    return '<del>' . $del . '</del> <ins>' . $ins . '</ins>';
                }
            } catch (Exception $e) {
                wp_send_json_error($e->getMessage());
            }

            return $product_subtotal;
        }, 10001, 2);

    }

    public function isActive(): bool
    {
        return class_exists(ProductBundle::class);
    }

    public function isContainerFacade(WcCartItemFacade $facade): bool
    {
        return $facade->getProduct() instanceof ProductBundle;
    }

    public function isFacadeAPartOfContainer(WcCartItemFacade $facade): bool
    {
        $data = $facade->getThirdPartyData();
        return isset($data["asnp_wepb_parent_id"]);
    }

    public function isContainerProduct(\WC_Product $wcProduct): bool
    {
        return $wcProduct instanceof ProductBundle;
    }

    public function isFacadeAPartOfContainerFacade(WcCartItemFacade $partOfContainerFacade, WcCartItemFacade $bundle): bool
    {
        $thirdPartyData = $bundle->getThirdPartyData();
        return in_array($partOfContainerFacade->getKey(), $thirdPartyData["asnp_wepb_items_key"] ?? [], true);
    }

    public function isPartOfContainerFacadePricedIndividually(WcCartItemFacade $facade): ?bool
    {
        $data = $facade->getThirdPartyData();
        return $data['asnp_wepb_parent_is_fixed_price'];
    }

    public function getListOfPartsOfContainerFromContainerProduct(\WC_Product $product): array
    {
        if (!($product instanceof ProductBundle)) {
            return [];
        }

        $items = $product->get_items();
        if (empty($items)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) use ($product) {
            $bundledProduct = !empty($item['product']) ? wc_get_product((int)$item['product']) : null;
            if (!$bundledProduct) {
                return null;
            }

            $qty = !empty($item['quantity']) ? (int)$item['quantity'] : 1;
            $price = $bundledProduct->get_price();

            return ContainerPartProduct::of(
                $product,
                $bundledProduct,
                (float)$price,
                $qty,
                true
            );
        }, $items)));

    }

    public function calculatePartOfContainerPrice(WcCartItemFacade $facade): float
    {
        if (isset($facade->getThirdPartyData()['asnp_wepb_parent_is_fixed_price']) && $facade->getThirdPartyData()['asnp_wepb_parent_is_fixed_price']) {
            $facade->getProduct()->set_price(0.0);
        } elseif (!empty($facade->getProduct()) && isset($facade->getThirdPartyData()['asnp_wepb_price'])) {
            $facade->getProduct()->set_price($facade->getThirdPartyData()['asnp_wepb_price']);
        }
        return $facade->getProduct() ? (float)$facade->getProduct()->get_price() : 0.0;
    }

    public function calculateContainerPrice(WcCartItemFacade $facade, array $children): float
    {
        /** @var ProductBundle $bundleProduct */
        $bundleProduct = $facade->getProduct();
        if (!($bundleProduct instanceof ProductBundle)) {
            return (float)$facade->getOriginalPrice();
        }

        if ($bundleProduct->is_fixed_price()) {
            return (float)$bundleProduct->get_price();
        }

        $basePrice = $bundleProduct->get_include_parent_price() === 'true'
            ? (float)$bundleProduct->get_price()
            : 0.0;

        $containerQty = max(1, (float)$facade->getQty());

        $childItemsPrice = 0.0;

        if (!$bundleProduct->is_fixed_price()) {
            foreach ($children as $child) {

                $perContainerChildQty = ((float)$child->getQty() / $containerQty);
                $childItemsPrice += $this->calculatePartOfContainerPrice($child) * $perContainerChildQty;
            }
        }

        return $basePrice + $childItemsPrice;
    }


    public function calculateContainerBasePrice(WcCartItemFacade $facade, array $children): float
    {
        return floatval(CartProcessor::getProductPriceDependsOnPriceMode($facade->getProduct()));
    }

    public function getContainerPriceTypeByParentFacade(WcCartItemFacade $facade): ?ContainerPriceTypeEnum
    {
        /** @var ProductBundle $product */
        $product = $facade->getProduct();

        if (!($product instanceof ProductBundle)) {
            return null;
        }

        if (!$product->is_fixed_price()) {
            return ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS();
        } else {
            return ContainerPriceTypeEnum::FIXED();
        }
    }

    public function overrideContainerReferenceForPartOfContainerFacadeAfterPossibleDuplicates(
        WcCartItemFacade $partOfContainerFacade,
        WcCartItemFacade $containerFacade
    )
    {

    }

    public function adaptContainerCartItem(
        WcCartItemFacade $facade,
        array            $children,
        int              $pos
    ): ContainerCartItem
    {
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
        WcCartItemFacade      $parentFacade
    ): ContainerPartCartItem
    {
        if ($subContainerItem->isPricedIndividually()) {
            $subContainerItem->setQty($subContainerItem->getQty());
        }

        return $subContainerItem;
    }
}
