<?php

namespace ADP\BaseVersion\Includes\Compatibility;

use HeidiPay\Classes\Widget;

defined('ABSPATH') or exit;

/**
 *
 * Plugin Name: HeyLight
 * Author: HeyLight
 *
 * @see https://heylight.com/
 */
class HeyLightCmp
{
    private Widget $instance;

    public function __construct()
    {
        
    }

    public function isActive(): bool
    {
        return class_exists("\WC_HeyLight");
    }

    public function prepareHooks(): void
    {
        if ($this->isActive()) {
            $this->instance = Widget::getInstance();
            remove_filter('woocommerce_get_price_html', [$this->instance, 'loadProductWidget']);
            add_filter('woocommerce_get_price_html', [$this, 'changePriceProductForWidget'], PHP_INT_MAX, 2);
        }
    }

    public function changePriceProductForWidget($price_html, $product): string
    {
        $salePrice = $product->get_sale_price();
        if(!empty($salePrice)){
            $product->set_price($salePrice);
        }
        return $this->instance->loadProductWidget($price_html, $product);
    }
}
