<?php

namespace ADP\BaseVersion\Includes\Compatibility;

use ADP\BaseVersion\Includes\Context;

defined('ABSPATH') or exit;
/**
 * Plugin Name: WooCommerce Chained Products
 * Author: StoreApps
 *
 * @see https://woocommerce.com/products/chained-products/
 */
class WcChainedProductsCmp
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
        return defined('WC_CP_PLUGIN_DIRNAME');
    }

    public function applyCompatibility()
    {
        add_filter("adp_get_original_product_initial_price_from_cart", array($this, 'getChainedPrice'), 10, 3);
        add_filter("adp_get_original_product_regular_price_from_cart", array($this,'getChainedPrice'), 10, 3);
        add_filter("adp_get_original_product_sale_price_from_cart", array($this,'getChainedPrice'), 10, 3);
    }
    public function getChainedPrice($result, $product, $wcCartItem) {
        $thirdPartyData = $wcCartItem->getThirdPartyData();
        if(isset($thirdPartyData['chained_item_of']) && ($thirdPartyData['priced_individually'] ?? 'yes') == 'no') {
            return 0;
        }
        return $result;
    }
}
