<?php

namespace ADP\BaseVersion\Includes\Core\Rule\CartAdjustment\Interfaces;

defined('ABSPATH') or exit;

interface PaymentMethodCartAdj
{
    const PAYMENT_CARTADJ_METHOD = 'payment_cartadj_method';

    /**
     * @param $paymentCartAdjMethod
     */
    public function setPaymentCartAdjMethod($paymentCartAdjMethod);

    /**
     * @return 
     */
    public function getPaymentCartAdjMethod();
}
