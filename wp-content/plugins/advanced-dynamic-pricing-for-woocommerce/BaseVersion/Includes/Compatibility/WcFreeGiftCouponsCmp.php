<?php

namespace ADP\BaseVersion\Includes\Compatibility;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

/**
 * Plugin Name: WooCommerce Free Gift Coupons
 * Author: Kathy Darling
 *
 * @see http://www.woo.com/products/free-gift-coupons/
 */
class WcFreeGiftCouponsCmp
{

    /**
     * @var Context
     */
    protected $context;

    public function __construct()
    {
        $this->context = adp_context();
    }

    public function isActive(): bool
    {
        return defined("WC_FGC_PLUGIN_NAME");
    }

    public function applyCompatibility()
    {

        add_filter('adp_get_original_product_from_cart', function($product, $wcCartItem) {

            if(!empty($wcCartItem->getData()['free_gift'])){
                $productExt = new \ADP\BaseVersion\Includes\ProductExtensions\ProductExtension($product);

                $productExt->setCustomPrice(0);
                $product->set_price(0);
            }
            
            return $product;
        }, 15, 2);

    }

}
