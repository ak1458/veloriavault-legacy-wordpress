<?php

namespace ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl;

use ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl\AbstractCondition;
use ADP\BaseVersion\Includes\Core\Rule\CartCondition\Interfaces\ValueComparisonCondition;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Rule\CartCondition\ConditionsLoader;

defined('ABSPATH') or exit;

class CartWeight extends AbstractCondition implements ValueComparisonCondition
{
    const LT = '<';
    const LTE = '<=';
    const MT = '>';
    const MTE = '>=';

    const AVAILABLE_COMP_METHODS = array(
        self::LT,
        self::LTE,
        self::MT,
        self::MTE,
    );

    /**
     * @var string
     */
    protected $comparisonMethod;
    /**
     * @var float
     */
    protected $comparisonValue;

    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function check($cart)
    {
        $weight = 0;
        foreach ($cart->getItems() as $itemKey => $item) {
            $wrapper = $item->getWcItem();

            $weight += (float)$wrapper->getProduct()->get_weight() * $item->getQty();
        }

        $comparisonValue  = (float)$this->comparisonValue;
        $comparisonMethod = $this->comparisonMethod;

        return $this->compareValues($weight, $comparisonValue, $comparisonMethod);
    }

    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function match($cart)
    {
        return $this->check($cart);
    }

    public static function getType()
    {
        return 'cart_weight';
    }

    public static function getLabel()
    {
        return __('Total weight', 'advanced-dynamic-pricing-for-woocommerce');
    }

    public static function getTemplatePath()
    {
        return WC_ADP_PLUGIN_VIEWS_PATH . 'conditions/cart/weight.php';
    }

    public static function getGroup()
    {
        return ConditionsLoader::GROUP_CART;
    }

    public function setValueComparisonMethod($comparisonMethod)
    {
        in_array(
            $comparisonMethod,
            self::AVAILABLE_COMP_METHODS
        ) ? $this->comparisonMethod = $comparisonMethod : $this->comparisonMethod = null;
    }

    /**
     * @param float|null $comparisonValue
     */
    public function setComparisonValue($comparisonValue)
    {
        is_numeric($comparisonValue) ? $this->comparisonValue = (float)$comparisonValue : $this->comparisonValue = null;
    }

    public function getValueComparisonMethod()
    {
        return $this->comparisonMethod;
    }

    /**
     * @return float|null
     */
    public function getComparisonValue()
    {
        return $this->comparisonValue;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return ! is_null($this->comparisonMethod) && ! is_null($this->comparisonValue);
    }

    /**
     * @param Cart $cart
     *
     * @return float
     */
    public function getCartComparisonValue($cart)
    {
        $weight = 0;
        foreach ($cart->getItems() as $itemKey => $item) {
            $wrapper = $item->getWcItem();

            $weight += (float)$wrapper->getProduct()->get_weight() * $item->getQty();
        }
        return $weight;
    }
}
