<?php

namespace ADP\BaseVersion\Includes\Compatibility;


defined('ABSPATH') or exit;

/**
 * 
 * Plugin Name: Klarna Payments for WooCommerce
 * Author: klarna
 *
 * @see https://krokedil.se/ 
 */

class KlarnaCmp
{
    public function __construct()
    {

    }

    public function isActive()
    {
        return class_exists("\WC_Klarna_Payments");
    }

    public function prepareHooks()
    {
        if ($this->isActive()) {
            add_filter('adp_get_payment_methods', [$this, 'getPaymentMethods'], 10, 1);
        }
    }

    function getPaymentMethods($methods)
    {
		$methods[] = [
			'id' => 'klarna_payments_pay_later',
			'text' => 'klarna payments pay later'
		];
        return $methods;
    }

}
