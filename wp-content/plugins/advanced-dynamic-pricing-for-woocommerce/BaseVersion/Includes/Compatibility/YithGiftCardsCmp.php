<?php

namespace ADP\BaseVersion\Includes\Compatibility;

use ADP\BaseVersion\Includes\Context;

defined('ABSPATH') or exit;

/**
 * Plugin Name: YITH WooCommerce Gift Cards
 * Author: YITH
 *
 * @see https://wordpress.org/plugins/yith-woocommerce-gift-cards/
 */
class YithGiftCardsCmp
{
    /**
     * @var Context
     */
    private $context;

    public function __construct($deprecated = null)
    {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function applyCompatibility()
    {
        if ( ! $this->isActive()) {
            return;
        }

        add_action('wdp_calculate_totals_hook_priority', function ($priority) {
            return $priority - 1;
        });

        $instance = function_exists('YITH_YWGC_Cart_Checkout') ? YITH_YWGC_Cart_Checkout() : \YITH_YWGC_Cart_Checkout::get_instance();
        if (false === ($priority = has_action('woocommerce_after_calculate_totals',
                [$instance, 'apply_gift_cards_discount']))) {
            return;
        }
        remove_action('woocommerce_after_calculate_totals', [$instance, 'apply_gift_cards_discount'], $priority);
        add_action('woocommerce_after_calculate_totals', [$instance, 'apply_gift_cards_discount'], PHP_INT_MAX);

        add_filter('adp_get_original_product_from_cart', function($product, $wcCartItem) {
            if ($product instanceof \WC_Product_Gift_Card) {
                $productExt = new \ADP\BaseVersion\Includes\ProductExtensions\ProductExtension($product);
                $cartItemData = $wcCartItem->getCartItemData();

                $price = $cartItemData['ywgc_amount'] ?? $product->get_price();

                $productExt->setCustomPrice($price);
                $product->set_price($price);
            }
            return $product;
        }, 10, 2);
    }

    public function isActive()
    {
        return defined('YITH_YWGC_PLUGIN_NAME');
    }
}
