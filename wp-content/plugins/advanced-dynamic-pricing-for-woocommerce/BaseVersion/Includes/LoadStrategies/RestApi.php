<?php

namespace ADP\BaseVersion\Includes\LoadStrategies;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Database\Repository\OrderItemRepository;
use ADP\BaseVersion\Includes\Database\Repository\OrderRepository;
use ADP\BaseVersion\Includes\Engine;
use ADP\BaseVersion\Includes\WC\WcAdpMergedCoupon\WcAdpMergedCoupon;
use ADP\BaseVersion\Includes\WC\WcCouponFacade;
use ADP\BaseVersion\Includes\WC\WcProductCustomAttributesCache;
use ADP\BaseVersion\Includes\StatsCollector\WcCartStatsCollector;
use ADP\Factory;

defined('ABSPATH') or exit;

class RestApi implements LoadStrategy
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct($deprecated = null)
    {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function start()
    {
        $settings = $this->context->getSettings();
        $settings->set('show_onsale_badge', true);

        add_filter('woocommerce_coupon_discount_types',function ($coupon_types) {
            if ( ! in_array(WcAdpMergedCoupon::COUPON_DISCOUNT_TYPE, $coupon_types) )
                $coupon_types[WcAdpMergedCoupon::COUPON_DISCOUNT_TYPE] = __('ADP Coupon', 'advanced-dynamic-pricing-for-woocommerce');
            if ( ! in_array(WcCouponFacade::TYPE_CUSTOM_PERCENT_WITH_LIMIT, $coupon_types) )
                $coupon_types[WcCouponFacade::TYPE_CUSTOM_PERCENT_WITH_LIMIT] = __('ADP Coupon (percent with limit)', 'advanced-dynamic-pricing-for-woocommerce');
            if ( ! in_array(WcCouponFacade::TYPE_ADP_FIXED_CART_ITEM, $coupon_types) )
                $coupon_types[WcCouponFacade::TYPE_ADP_FIXED_CART_ITEM] = __('ADP Coupon (fixed cart item)', 'advanced-dynamic-pricing-for-woocommerce');
            if ( ! in_array(WcCouponFacade::TYPE_ADP_RULE_TRIGGER, $coupon_types) )
                $coupon_types[WcCouponFacade::TYPE_ADP_RULE_TRIGGER] = __('ADP Coupon  (rule trigger)', 'advanced-dynamic-pricing-for-woocommerce');
            return $coupon_types;
        });
        if (!apply_filters("adp_wp_rest_api_strategy_load",
            $this->context->getOption('update_prices_while_doing_rest_api') OR $this->context->isWCStoreAPIRequest() )) {

            return false;
        }

        /**
         * We do not need this if "WooCommerce Blocks" < 2.6.0 is installed.
         * In future versions method "maybe_init_cart_session" has been removed.
         * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/commit/5a195cf105133e5b3ac232cfb469ed5c53a3d4bc#diff-17c1ab7a1ea1f97171811713b2a886c1
         *
         * @see WpCron::start() explanation here!
         */
        add_filter('woocommerce_apply_base_tax_for_local_pickup', "__return_false");

        /**
         * @var Engine $engine
         */
        $engine = Factory::get("Engine", WC()->cart);
        $engine->getCartProcessor()->installActionFirstProcess_Blocks(); //installs only cartCouponsProcessor

        // Should we install all price display hooks?
        $engine->installProductProcessorWithEmptyCart();

        $wcCartStatsCollector = new WcCartStatsCollector();
        $wcCartStatsCollector->setActionCheckoutOrderProcessedDuringRestApi();

        add_action('woocommerce_before_calculate_totals', function($wcCart) use ($engine){
            if(did_action('adp_rest_api_engine_reinitialized'))
                return ;// only once!
            $engine->getCartProcessor()->withCart($wcCart);
            $engine->getCartProcessor()->installActionFirstProcess($skip_cartCouponsProcessor=true);
            $engine->firstTimeProcessCart();
            do_action('adp_rest_api_engine_reinitialized');
        });

        /** @see Functions::install() */
        Factory::callStaticMethod("Functions", 'install');

        /** @var WcProductCustomAttributesCache $productAttributesCache */
        $productAttributesCache  = Factory::get("WC_WcProductCustomAttributesCache");
        $productAttributesCache->installHooks();

    }
}
