<?php

namespace ADP\BaseVersion\Includes\Compatibility;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
* Plugin Name: German Market
* Author: MarketPress
 *
* @see https://marketpress.com/shop/plugins/woocommerce-german-market/
*/

class GermanMarketCmp {
    /**
     * @return bool
     */
    public function isActive()
    {
        return class_exists( 'Woocommerce_German_Market' );
    }

    public function prepareHooks()
    {
        add_filter("adp_price_qty_changed_external_plugins", function ($external_plugins, $new_price, $product) {
            if (class_exists('WGM_Price_Per_Unit')) {
                //overide via filter
                add_filter("german_market_get_price_per_unit_data_complete_product_price", function ($price, $product) use($new_price){
                    return $new_price;
                }, 10, 2);
                //depends on product type
                if ( $product->get_type() == 'variation' )
                    $ppu= wcppufv_get_price_per_unit_string_by_product($product);
                else
                    $ppu = \WGM_Price_Per_Unit::get_price_per_unit_string($product);
                //done
                $external_plugins[] = array(
                    'destination' => '.wgm-info.price-per-unit.price-per-unit-loop.ppu-variation-wrap',
                    'html' => $ppu,
                );
            }

            return $external_plugins;
        }, 10, 3);
    }
}
