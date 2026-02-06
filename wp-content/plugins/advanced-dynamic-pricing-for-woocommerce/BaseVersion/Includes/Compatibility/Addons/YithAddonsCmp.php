<?php

namespace ADP\BaseVersion\Includes\Compatibility\Addons;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddon;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

/**
 * Plugin Name: YITH WooCommerce Product Add-ons & Extra Options
 * Author: YITH
 *
 * @see https://wordpress.org/plugins/yith-woocommerce-product-add-ons/
 */
class YithAddonsCmp
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
        return defined('YITH_WAPO');
    }

    /**
     * @param WcCartItemFacade $wcCartItemFacade
     *
     * @return array<int, CartItemAddon>
     * @see \YITH_WAPO_Cart::add_cart_item
     */
    public function getAddonsFromCartItem(WcCartItemFacade $wcCartItemFacade)
    {
        $thirdPartyData = $wcCartItemFacade->getThirdPartyData();
        $addonsData = $thirdPartyData['yith_wapo_options'] ?? [];
        $_product = $wcCartItemFacade->getProduct();
        // WooCommerce Measurement Price Calculator (compatibility).
        if (isset($thirdPartyData['pricing_item_meta_data']['_price'])) {
            $product_price = $thirdPartyData['pricing_item_meta_data']['_price'];
        } else {
            $product_price = \yit_get_display_price($_product);
        }

        $addon_id_check = '';
        $addons = [];
        foreach ($addonsData as $index => $addonData) {
            foreach ($addonData as $key => $value) {
                if ($key && '' !== $value) {
                    if (is_string($value)) {
                        $value = stripslashes($value);
                    }
                    $explode = explode('-', $key);
                    if (isset($explode[1])) {
                        $addon_id = $explode[0];
                        $option_id = $explode[1];
                    } else {
                        $addon_id = $key;
                        $option_id = $value;
                    }

                    if ($addon_id != $addon_id_check) {
                        $first_free_options_count = 0;
                        $addon_id_check = $addon_id;
                    }

                    $info = \yith_wapo_get_option_info($addon_id, $option_id);

                    $option_price = 0;

                    $addon_type             = $info['addon_type'] ?? '';
                    $first_options_selected = $info['addon_first_options_selected'] ?? '';
                    $first_options_qty      = intval( $info['addon_first_free_options'] ) ?? 0;
                    $price_method           = $info['price_method'] ?? '';
                    $sell_individually      = $info['sell_individually'] ?? '';

                    $is_empty_select = 'select' === $addon_type && 'default' === $option_id;

                    if ( $is_empty_select ) {
                        continue;
                    }

                    $calculate_taxes = false;

                    if ( wc_string_to_bool( $sell_individually ) ) {
                        $calculate_taxes = true;
                    }

                    $addon_prices = YITH_WAPO_Cart()->calculate_addon_prices_on_cart( $addon_id, $option_id, $key, $value, $thirdPartyData, $product_price, $calculate_taxes );

                    $option_price     = 0;
                    $addon_price      = floatval( $addon_prices['price'] );
                    $addon_sale_price = floatval( $addon_prices['price_sale'] );

                    // First X free options check.
                    if ( 'yes' === $first_options_selected && 0 < $first_options_qty && $first_free_options_count < $first_options_qty ) {
                        $first_free_options_count ++;
                    } else {
                        if ( $addon_price !== 0 || $addon_sale_price !== 0 ) {
                            if ( $addon_sale_price ) {
                                $option_price = $addon_sale_price;
                            } else {
                                $option_price = $addon_price;
                            }
                        }
                    }

                    $addon = new CartItemAddon($key, $value, $option_price);
                    $addon->currency = $wcCartItemFacade->getCurrency();
                    $addons[] = $addon;
                }
            }
        }
        return $addons;
    }
}
