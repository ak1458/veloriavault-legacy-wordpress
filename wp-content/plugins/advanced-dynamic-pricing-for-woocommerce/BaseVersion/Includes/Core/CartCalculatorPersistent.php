<?php

namespace ADP\BaseVersion\Includes\Core;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\Core\Rule\PersistentRule;
use ADP\BaseVersion\Includes\Database\RulesCollection;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;
use ADP\BaseVersion\Includes\SpecialStrategies\CompareStrategy;

class CartCalculatorPersistent implements ICartCalculator
{
    /**
     * @var PersistentRule
     */
    protected $rule;
    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var CompareStrategy
     */
    protected $compareStrategy;

    /**
     * @param Context|PersistentRule $contextOrRule
     * @param PersistentRule|null $deprecated
     */
    public function __construct($contextOrRule, $deprecated = null)
    {
        $this->context = adp_context();
        $this->rule    = $contextOrRule instanceof PersistentRule ? $contextOrRule : $deprecated;
        $this->compareStrategy = new CompareStrategy();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
        $this->compareStrategy->withContext($context);
    }

    /**
     * @param Cart $cart
     * @param ICartItem $item
     *
     * @return bool
     */
    public function processItem(&$cart, $item)
    {
        if ($cart->isEmpty()) {
            return false;
        }

        $appliedRules = 0;

        $proc = $this->rule->buildProcessor($this->context);
        if ($proc->applyToCartItem($cart, $item)) {
            $appliedRules++;
        }

        $prodPropsWithFilters = $this->context->getOption('initial_price_context') === 'view';

        $result = boolval($appliedRules);

        if ($result) {
            if ('compare_discounted_and_sale' === $this->context->getOption('discount_for_onsale')) {
                $newItems = array();
                foreach ($cart->getItems() as $item) {
                    $productPrice = $item->getOriginalPrice();
                    foreach ($item->getDiscounts(true) as $ruleId => $amounts) {
                        $productPrice -= array_sum($amounts);
                    }
                    if ($this->context->getOption('is_calculate_based_on_wc_precision')) {
                        $productPrice = round($productPrice, wc_get_price_decimals());
                    }

                    $product     = $item->getWcItem()->getProduct();
                    $wcSalePrice = $this->getWcSalePrice($product, $item, $prodPropsWithFilters);

                    if ( ! is_null($wcSalePrice) && $wcSalePrice < $productPrice) {
                        $newItem = new BasicCartItem($item->getWcItem(), $wcSalePrice, $item->getQty(), $item->getInitialCartPosition());

                        $item->copyAttributesTo($newItem);

                        $minDiscountRangePrice = $item->prices()->getMinDiscountRangePrice();
                        if ($minDiscountRangePrice !== null) {
                            $minDiscountRangePrice = $minDiscountRangePrice < $wcSalePrice ? $minDiscountRangePrice : $wcSalePrice;
                            $newItem->prices()->setMinDiscountRangePrice($minDiscountRangePrice);
                        }

                        $item = $newItem;
                    }

                    $newItems[] = $item;
                }

                $cart->setItems($newItems);
            } elseif ('discount_regular' === $this->context->getOption('discount_for_onsale')) {
                $newItems = array();
                foreach ($cart->getItems() as $item) {
                    $product     = $item->getWcItem()->getProduct();
                    $wcSalePrice = $this->getWcSalePrice($product, $item, $prodPropsWithFilters);

                    if ( ! is_null($wcSalePrice) && count($item->getHistory()) == 0) {
                        $newItem = new BasicCartItem($item->getWcItem(), $wcSalePrice, $item->getQty(), $item->getInitialCartPosition());

                        $item->copyAttributesTo($newItem);

                        $minDiscountRangePrice = $item->prices()->getMinDiscountRangePrice();
                        if ($minDiscountRangePrice !== null) {
                            $newItem->prices()->setMinDiscountRangePrice($minDiscountRangePrice);
                        }

                        $item = $newItem;
                    }

                    $newItems[] = $item;
                }

                $cart->setItems($newItems);
        } elseif ('sale_price' === $this->context->getOption('discount_for_onsale')) {
            $newItems = array();
            foreach ($cart->getItems() as $item) {
                $product     = $item->getWcItem()->getProduct();
                $wcSalePrice = $this->getWcSalePrice($product, $item, $prodPropsWithFilters);

                if ( ! is_null($wcSalePrice) ) {
                    $newItem = CartCalculator::recreateItem($item, $wcSalePrice);
                        $item->copyAttributesTo($newItem);

                        $minDiscountRangePrice = $item->prices()->getMinDiscountRangePrice();
                    if ($minDiscountRangePrice !== null) {
                        $newItem->prices()->setMinDiscountRangePrice($minDiscountRangePrice);
                    }

                    $item = $newItem;
                }

                $newItems[] = $item;
            }

            $cart->setItems($newItems);
            }
        }

        return $result;
    }

    protected function getWcSalePrice($product, $item, $prodPropsWithFilters) {
        $wcSalePrice = null;
        /** Always remember about scheduled WC sales */
        if( $prodPropsWithFilters
                && ! $this->compareStrategy->floatsAreEqual(
                    $product->get_price('edit'),
                    $product->get_price('view')
                )
        ) {
            if ($product->is_on_sale('view') && $product->get_sale_price('view') !== '') {
                $wcSalePrice = floatval($product->get_sale_price('view'));
                if ( count($item->getAddons()) > 0 ) {
                    $wcSalePrice += $item->getAddonsAmount();
                }
            }
        } else {
            if ($product->is_on_sale('edit') && $product->get_sale_price('edit') !== '') {
                $wcSalePrice = floatval($product->get_sale_price('edit'));
                if ( count($item->getAddons()) > 0 ) {
                    $wcSalePrice += $item->getAddonsAmount();
                }
            }
        }
        return $wcSalePrice;
    }


    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function processCart(&$cart)
    {
        return true;
    }


    public function getRulesCollection()
    {
        return new RulesCollection(array($this->rule));
    }
}
