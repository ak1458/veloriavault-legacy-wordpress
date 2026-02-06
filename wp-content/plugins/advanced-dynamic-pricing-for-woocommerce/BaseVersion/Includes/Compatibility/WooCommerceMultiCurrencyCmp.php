<?php

namespace ADP\BaseVersion\Includes\Compatibility;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Context\Currency;
use ADP\BaseVersion\Includes\Context\CurrencyController;
use ADP\HighLander\HighLanderShortcuts;

defined('ABSPATH') or exit;

/**
 * Plugin Name: WooCommerce Payments
 * Author: Automattic
 *
 * Compatibility ONLY for multi currency module
 *
 * @see https://woocommerce.com/payments/
 * @see https://woocommerce.com/document/woocommerce-payments/currencies/
 */
class WooCommerceMultiCurrencyCmp
{
    /**
     * @var Context
     */
    protected $context;

    protected $multi_currency = null;

    public function __construct($context = null)
    {
        add_action('init',function() use($context) {
            $this->loadRequirements();
            if ($this->isActive()) {
                $this->modifyContext($context);
                $this->prepareHooks();
            }
        }, 9999);
    }

    public function loadRequirements()
    {
        if ( ! did_action('plugins_loaded')) {
            /* translators: Message about the load order*/
            _doing_it_wrong(__FUNCTION__, sprintf(esc_html__('%1$s should not be called earlier the %2$s action.',
                'advanced-dynamic-pricing-for-woocommerce'), 'load_requirements', 'plugins_loaded'), esc_html(WC_ADP_VERSION));
        }

        if (class_exists('WC_Payments_Features') && function_exists('WC_Payments_Multi_Currency')) {
            if ( \WC_Payments_Features::is_customer_multi_currency_enabled() ) {
                $this->multi_currency = WC_Payments_Multi_Currency();
            }
        }
    }

    public function prepareHooks() {
        $frontend_prices = $this->multi_currency->get_frontend_prices();
        remove_filter( 'woocommerce_shipping_method_add_rate_args', [ $frontend_prices, 'convert_shipping_method_rate_cost' ], 99 );
        $hooks = array(
            'woocommerce_product_get_price',
            'woocommerce_product_variation_get_price',
            'woocommerce_product_variation_get_regular_price',
            'woocommerce_product_get_regular_price',
            'woocommerce_product_get_sale_price',
            'woocommerce_get_variation_regular_price',
            'woocommerce_get_variation_sale_price'
        );
        foreach ($hooks as $hook) {
            $returnFalseCallback = function () {
                return false;
            };
            add_filter($hook, function ($price, $product) use ($returnFalseCallback) {
                if ($product->get_meta('adp_price_converted')) {
                    add_filter(\WCPay\MultiCurrency\MultiCurrency::FILTER_PREFIX . 'should_convert_product_price', $returnFalseCallback);
                }
                return $price;
            }, 10, 2);
            add_filter($hook, function ($price) use ($returnFalseCallback) {
                HighLanderShortcuts::removeFilters(
                    [
                        \WCPay\MultiCurrency\MultiCurrency::FILTER_PREFIX . 'should_convert_product_price' => [$returnFalseCallback]
                    ]
                );
                return $price;
            }, PHP_INT_MAX);
        }
    }

    public function isActive()
    {
        if ( ! $this->multi_currency ) {
            return false;
        }

        if( ! $this->multi_currency->is_initialized()) {
            return false;
        }

        if ( ! $this->multi_currency->has_additional_currencies_enabled() ) {
            return false;
        }

        return true;
    }

    /**
     * @return Currency|null
     * @throws \Exception
     */
    protected function getDefaultCurrency()
    {
        return $this->getCurrency($this->multi_currency->get_default_currency());
    }

    /**
     * @param \WCPay\MultiCurrency\Currency $currencyData
     *
     * @return Currency|null
     * @throws \Exception
     */
    protected function getCurrency($currencyData) {
        return new Currency($currencyData->get_code(), $currencyData->get_symbol(), $currencyData->get_rate());
    }

    /**
     * @return Currency|null
     * @throws \Exception
     */
    protected function getCurrentCurrency()
    {
        return $this->getCurrency($this->multi_currency->get_selected_currency());
    }

    public function modifyContext(Context $context)
    {
        $this->context = $context;

        $this->context->currencyController = new CurrencyController($this->context, $this->getDefaultCurrency());
        $this->context->currencyController->setCurrentCurrency($this->getCurrentCurrency());
    }
}
