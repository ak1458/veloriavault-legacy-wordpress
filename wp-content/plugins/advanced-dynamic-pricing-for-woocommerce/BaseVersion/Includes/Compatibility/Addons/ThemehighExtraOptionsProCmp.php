<?php

namespace ADP\BaseVersion\Includes\Compatibility\Addons;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddon;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

/**
 * Plugin Name: WooCommerce Extra Product Options Pro
 * Author: ThemeHigh
 *
 * @see https://themehigh.com/product/woocommerce-extra-product-options
 * @see https://wordpress.org/plugins/woo-extra-product-options/
 */
class ThemehighExtraOptionsProCmp
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct()
    {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function isActive()
    {
        return defined('THWEPO_FILE');
    }

    /**
     * @param WcCartItemFacade $wcCartItemFacade
     *
     * @return array<int, CartItemAddon>
     */
    public function getAddonsFromCartItem(WcCartItemFacade $wcCartItemFacade)
    {
        $thirdPartyData = $wcCartItemFacade->getThirdPartyData();
        $addonsData     = $thirdPartyData['thwepo_options'] ?? [];

        $addons = [];
        foreach ($addonsData as $addonData) {
            $key   = $addonData['name'] ?? null;
            $value = $addonData['value'] ?? null;
            $price = null;

            if ($addonData['price_flat_fee'] !== "yes") {
                $price = $addonData['price'] ?? null;
                if ($price === "") {
                    if($addonData['field_type'] === 'select'){
                        $price = $addonData['options'][$value]['price'] ?? null;
                    } elseif ($addonData['field_type'] === 'multiselect') {
                        $price = 0;
                        foreach($addonData['value'] as $item) {
                            $price += intval($addonData['options'][$item]['price']);
                        }
                    }

                }
            }

            if ($key === null || $value === null || $price === null) {
                continue;
            }

            if (is_string($price)) {
                if (apply_filters('adp_format_thwepo_price_corresponding_to_wc_price_settings', true)) {
                    $price = str_replace($this->context->priceSettings->getThousandSeparator(), "", $price);
                    $price = str_replace($this->context->priceSettings->getDecimalSeparator(), ".", $price);
                }

                $price = (float)$price;
            }

            $addon           = new CartItemAddon($key, $value, $price);
            $addon->currency = $wcCartItemFacade->getCurrency();

            $addons[] = $addon;
        }

        return $addons;
    }

    /**
     * @param array $cart_contents
     *
     * @return array
     */
    public function checkFeesFromCart($cart_contents) {
        $fees = [];
        
        foreach ($cart_contents as $cart_item) {
            if (isset($cart_item['thwepo_options'])) {
                foreach ($cart_item['thwepo_options'] as $option) {
                    $fee = null;
                    
                    if ($option['price_flat_fee'] === "yes" && $option['field_type'] === 'select') {
                        $name_thwepo = $option['name'];
                        $name = $option['label'];
                        $amount = $option['options'][$option['value']]['price'];
                        
                        $fee = ['name_thwepo' => $name_thwepo, 'name' => $name, 'amount' => $amount];
                        
                    } else if ($option['price_flat_fee'] === "yes" && $option['field_type'] === 'multiselect') {
                        $name_thwepo = $option['name'];
                        $name = $option['label'];
                        $amount = 0;
                        
                        foreach ($option['value'] as $item) {
                            $amount += intval($option['options'][$item]['price']);
                        }
                        
                        $fee = ['name_thwepo' => $name_thwepo, 'name' => $name, 'amount' => $amount];
                    }
                    
                    if (!empty($fee)) {
                        $found = false;
                        foreach ($fees as &$existing_fee) {
                            if ($existing_fee['name_thwepo'] === $fee['name_thwepo']) {
                                $existing_fee['amount'] += $fee['amount'];
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $fees[] = $fee;
                        }
                    }
                }
            }
        }
    
        return $fees;
    }
    
}
