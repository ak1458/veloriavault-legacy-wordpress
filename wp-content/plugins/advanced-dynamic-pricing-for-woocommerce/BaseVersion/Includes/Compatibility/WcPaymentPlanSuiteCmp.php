<?php

namespace ADP\BaseVersion\Includes\Compatibility;

use ADP\BaseVersion\Includes\Context;

defined('ABSPATH') or exit;

/**
 * Plugin Name: Payment Plan Suite
 * Author: Flintop
 *
 * @see
 */
class WcPaymentPlanSuiteCmp
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param null $deprecated
     */
    public function __construct($deprecated = null)
    {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return defined("PPN_PLUGIN_FILE");
    }

    public function applyCompatibility() {

        add_action('wdp_calculate_totals_hook_priority', function ($priority) {
            return $priority - 1;
        });

        if (class_exists('\PPN_Cart_Manager')) {
            remove_filter( 'woocommerce_cart_item_subtotal', 'PPN_Cart_Manager::render_plan_item_subtotal', 10, 3 );
            add_filter( 'woocommerce_cart_item_subtotal', 'PPN_Cart_Manager::render_plan_item_subtotal', PHP_INT_MAX, 3 );

            remove_action( 'woocommerce_after_calculate_totals', 'PPN_Cart_Manager::adjust_cart_totals', 100, 1 );
            add_action( 'woocommerce_after_calculate_totals', 'PPN_Cart_Manager::adjust_cart_totals', PHP_INT_MAX, 1 );

            remove_action( 'woocommerce_cart_totals_after_order_total', 'PPN_Cart_Manager::render_installment_details', 10 );
            add_action( 'woocommerce_cart_totals_after_order_total', 'PPN_Cart_Manager::render_installment_details', PHP_INT_MAX );

            remove_action( 'woocommerce_review_order_after_order_total', 'PPN_Cart_Manager::render_installment_details', 10 );
            add_action( 'woocommerce_review_order_after_order_total', 'PPN_Cart_Manager::render_installment_details', PHP_INT_MAX );

        }

    }

}
