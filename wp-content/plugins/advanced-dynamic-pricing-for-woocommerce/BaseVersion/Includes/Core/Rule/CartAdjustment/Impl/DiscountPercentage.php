<?php

namespace ADP\BaseVersion\Includes\Core\Rule\CartAdjustment\Impl;

use ADP\BaseVersion\Includes\Core\Cart\Coupon\CouponCart;
use ADP\BaseVersion\Includes\Core\Rule\CartAdjustment\CartAdjustment;
use ADP\BaseVersion\Includes\Core\Rule\CartAdjustment\Impl\AbstractCartAdjustment;
use ADP\BaseVersion\Includes\Core\Rule\CartAdjustment\Interfaces\CouponCartAdj;
use ADP\BaseVersion\Includes\Core\Rule\CartAdjustment\CartAdjustmentsLoader;

defined('ABSPATH') or exit;

class DiscountPercentage extends AbstractCartAdjustment implements CouponCartAdj, CartAdjustment
{
    /**
     * @var float
     */
    protected $couponValue;

    /**
     * @var string
     */
    protected $couponCode;

    /**
     * @var float|null
     */
    protected $couponMaxDiscount;

    public static function getType()
    {
        return 'discount__percentage';
    }

    public static function getLabel()
    {
        return __('Percentage discount', 'advanced-dynamic-pricing-for-woocommerce');
    }

    public static function getTemplatePath()
    {
        return WC_ADP_PLUGIN_VIEWS_PATH . 'cart_adjustments/discount.php';
    }

    public static function getGroup()
    {
        return CartAdjustmentsLoader::GROUP_DISCOUNT;
    }

    public function __construct()
    {
        $this->amountIndexes = array('couponValue', 'couponMaxDiscount');
    }

    /**
     * @param float|string $couponValue
     */
    public function setCouponValue($couponValue)
    {
        $this->couponValue = $couponValue;
    }

    /**
     * @param string $couponCode
     */
    public function setCouponCode($couponCode)
    {
        $this->couponCode = $couponCode;
    }

    public function getCouponValue()
    {
        return $this->couponValue;
    }

    public function getCouponCode()
    {
        return $this->couponCode;
    }

    /**
     * @param float $amount
     */
    public function setCouponMaxDiscount($amount)
    {
        if (is_numeric($amount)) {
            $this->couponMaxDiscount = floatval($amount);
        }
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return isset($this->couponValue) or isset($this->couponCode);
    }

    public function applyToCart($rule, $cart)
    {
        $context    = $cart->getContext()->getGlobalContext();
        $couponCode = ! empty($this->couponCode) ? $this->couponCode : $context->getOption('default_discount_name');

        $coupon = new CouponCart(
            $context,
            CouponCart::TYPE_PERCENTAGE,
            $couponCode,
            $this->couponValue,
            $rule->getId()
        );

        if (isset($this->couponMaxDiscount)) {
            $coupon->setMaxDiscount($this->couponMaxDiscount);
        }

        $cart->addCoupon($coupon);
    }
}
