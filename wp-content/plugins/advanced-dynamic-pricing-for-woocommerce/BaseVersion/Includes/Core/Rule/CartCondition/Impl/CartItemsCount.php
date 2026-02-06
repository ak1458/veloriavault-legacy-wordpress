<?php

namespace ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl;

use ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl\AbstractCondition;
use ADP\BaseVersion\Includes\Core\Rule\CartCondition\Interfaces\ValueComparisonCondition;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Rule\CartCondition\ConditionsLoader;

defined('ABSPATH') or exit;

class CartItemsCount extends AbstractCondition implements ValueComparisonCondition
{
    const LT = '<';
    const LTE = '<=';
    const MT = '>';
    const MTE = '>=';
    const EQ = '=';

    const AVAILABLE_COMP_METHODS = array(
        self::LT,
        self::LTE,
        self::MT,
        self::MTE,
        self::EQ,
    );

    /**
     * @var string
     */
    protected $comparisonMethod;
    /**
     * @var int
     */
    protected $comparisonValue;

    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function check($cart)
    {
        $qty = array_sum(array_map(function ($item) use ($cart) {
            // todo replace?
            if ($cart->getContext()->getGlobalContext()->getContainerCompatibilityManager()->isPartOfContainer($item->getWcItem())) {
                return 0.0;
            }

            return $item->getQty();
        }, $cart->getItems()));

        $comparisonValue  = (float)$this->comparisonValue;
        $comparisonMethod = $this->comparisonMethod;

        return $this->compareValues($qty, $comparisonValue, $comparisonMethod);
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
        return 'cart_items_count';
    }

    public static function getLabel()
    {
        return __('Items count', 'advanced-dynamic-pricing-for-woocommerce');
    }

    public static function getTemplatePath()
    {
        return WC_ADP_PLUGIN_VIEWS_PATH . 'conditions/cart/items-count.php';
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

    public function setComparisonValue($comparisonValue)
    {
        is_numeric($comparisonValue) ? $this->comparisonValue = (int)$comparisonValue : $this->comparisonValue = null;
    }

    public function getValueComparisonMethod()
    {
        return $this->comparisonMethod;
    }

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
     * @return int
     */
    public function getCartComparisonValue($cart)
    {
        $qty = array_sum(array_map(function ($item) {
            return $item->getQty();
        }, $cart->getItems()));

        return $qty;
    }
}
