<?php

namespace ADP\BaseVersion\Includes\Core;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPartCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\Core\RuleProcessor\Listener;
use ADP\BaseVersion\Includes\Core\RuleProcessor\RuleProcessor;
use ADP\BaseVersion\Includes\Core\RuleProcessor\PersistentRuleProcessor;
use ADP\BaseVersion\Includes\Database\Repository\PersistentRuleRepository;
use ADP\BaseVersion\Includes\Database\Repository\PersistentRuleRepositoryInterface;
use ADP\BaseVersion\Includes\Database\RulesCollection;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;
use ADP\BaseVersion\Includes\SpecialStrategies\CompareStrategy;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceAdjustment;

defined('ABSPATH') or exit;

class CartCalculator implements ICartCalculator
{
    /**
     * @var RulesCollection
     */
    protected $ruleCollection;
    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var Listener
     */
    public $listener;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var PersistentRuleRepositoryInterface
     */
    protected $persistentRuleRepository;

    /**
     * @var CompareStrategy
     */
    protected $compareStrategy;

    /**
     * @var float
     */
    protected $maxDiscountRate;
    /**
     * @var float
     */
    protected $maxDiscountAmount;

    /**
     * @param Context|RulesCollection $contextOrRuleCollection
     * @param RulesCollection|Listener|null $ruleCollectionOrListener
     * @param Listener|null $deprecated
     */
    final public function __construct($contextOrRuleCollection, $ruleCollectionOrListener = null, $deprecated = null)
    {
        $this->context                  = adp_context();
        $this->ruleCollection           = $contextOrRuleCollection instanceof RulesCollection ? $contextOrRuleCollection : $ruleCollectionOrListener;
        $this->persistentRuleRepository = new PersistentRuleRepository();
        $this->listener                 = $ruleCollectionOrListener instanceof Listener ? $ruleCollectionOrListener : $deprecated;
        $this->compareStrategy          = new CompareStrategy();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
        $this->compareStrategy->withContext($context);
    }

    public function withPersistentRuleRepository(PersistentRuleRepositoryInterface $repository)
    {
        $this->persistentRuleRepository = $repository;
    }

    /**
     * @param Context|Listener|null $context
     * @param Listener|null $listener
     *
     * @return self
     */
    public static function make($contextOrListener, $deprecated = null)
    {
        $listener = $contextOrListener instanceof Listener ? $contextOrListener : $deprecated;

        return new static(CacheHelper::loadActiveRules(), $listener);
    }

    /**
     * @param Listener $listener
     *
     * @return self
     */
    public static function makeWithoutConditions($listener)
    {
        $rules = [];

        foreach ( CacheHelper::loadActiveRules()->getRules() as $rule ) {
            $newRule = clone $rule;
            $newRule->setConditions([]);
            $rules[] = $newRule;
        }

        return new static(new RulesCollection($rules), $listener);
    }

    public function getRulesCollection()
    {
        return $this->ruleCollection;
    }

    /**
     * @param Cart $cart
     * @param ICartItem $item
     *
     * @return bool
     */
    public function processItem(&$cart, $item)
    {
        return $this->processCart($cart);
    }

    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function processCart(&$cart)
    {
        if ($cart->isEmpty()) {
            return false;
        }

        if ( ! $this->isCalculationAllowed()) {
            return false;
        }

        if ($this->listener) {
            $this->listener->calcProcessStarted();
        }

        $appliedRules = 0;

        if ($this->context->getOption('support_persistence_rules')) {
            $appliedRules = $this->applyPersistentRules($cart);
        }

        foreach ($this->ruleCollection->getRules() as $rule) {
            $proc = $rule->buildProcessor($this->context);
            if ($proc->applyToCart($cart) && $proc->getStatus() === $proc::STATUS_SUCCESSFULLY_COMPLETED) {
                $appliedRules++;
            }

            if ($this->context->getOption('show_debug_bar'))
                $this->announceRuleCalculated($proc);
        }

        $prodPropsWithFilters = $this->context->getOption('initial_price_context') === 'view';

        $result = boolval($appliedRules);

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

                $minDiscountRangePrice = $item->prices()->getMinDiscountRangePrice();
                if (!is_null($wcSalePrice) && $wcSalePrice < $productPrice) {
                    $newItem = self::recreateItem($item, $wcSalePrice);
                    $item->copyAttributesTo($newItem);

                    $priceAdjustments = array_map(function($adj) use($wcSalePrice) {
                        if($wcSalePrice > $adj->getNewPrice()) {
                            return $adj;
                        }

                        return new CartItemPriceAdjustment(
                            $adj->getType(),
                            $adj->getSource(),
                            $adj->getOriginalPrice(),
                            0,
                            $wcSalePrice,
                            $adj->getRuleId()
                        );
                    }, $item->getPriceAdjustments());

                    $newItem->setPriceAdjustments($priceAdjustments);

                    if ($minDiscountRangePrice !== null) {
                        $minPrice = min(array_filter([$minDiscountRangePrice, $wcSalePrice]));
                        $newItem->prices()->setMinDiscountRangePrice($minPrice);
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
                        $newItem = self::recreateItem($item, $wcSalePrice);
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
                    $newItem = self::recreateItem($item, $wcSalePrice);
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

        if ($this->listener) {
            $this->listener->processResult($result);
        }

        return $result;
    }

    protected function getWcSalePrice($product, $item, $prodPropsWithFilters) {
        $wcSalePrice = null;
        if ($product->is_on_sale('edit') && $product->get_sale_price('edit') !== '') {
            $wcSalePrice = floatval($product->get_sale_price('edit'));
            if ( count($item->getAddons()) > 0 ) {
                $wcSalePrice += $item->getAddonsAmount();
            }
        }
        return apply_filters('adp_get_wc_sale_price', $wcSalePrice, $product, $item, $prodPropsWithFilters);

        //Failed code, caused infinite loop  -  some plugins add hook for 'view' context
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

    public static function recreateItem(ICartItem $item, $wcSalePrice): ICartItem
    {
        if ($item instanceof ContainerCartItem) {
            $subItems = array_map(function ($item) {
                $newItem = new ContainerPartCartItem(
                    $item->getWcItem(),
                    $item->getBasePrice(),
                    $item->isPricedIndividually(),
                    $item->getOriginalPrice(),
                    $item->getQty(),
                    $item->getInitialCartPosition()
                );

                $item->copyAttributesTo($newItem);

                return $newItem;
            }, $item->getItems());

            $newItem = new ContainerCartItem(
                $item->getWcItem(),
                $item->getCompatibility(),
                $item->getContainerPriceTypeEnum(),
                $wcSalePrice,
                $wcSalePrice,
                $subItems,
                $item->getQty(),
                $item->getInitialCartPosition()
            );
        } else {
            $newItem = new BasicCartItem(
                $item->getWcItem(),
                $wcSalePrice,
                $item->getQty(),
                $item->getInitialCartPosition()
            );
        }

        return $newItem;
    }

    /**
     * @return bool
     */
    protected function isCalculationAllowed()
    {
        return ! $this->context->isRuleSuppressed();
    }

    /**
     * @param RuleProcessor $proc
     */
    protected function announceRuleCalculated($proc)
    {
        if ($this->listener) {
            $this->listener->ruleCalculated($proc);
        }
    }

    /**
     * @param Cart $cart
     *
     * @return int
     */
    protected function applyPersistentRules(&$cart)
    {
        $appliedRules = 0;
        $context = $this->context;

        /**
         * Accumulating non-temporary quantity
         *
         * The temporary and 'in cart' items are defined as different in the cart ( mean does not merge with each other ).
         * But we need to get 'persistent' price based on quantity as if they were merged.
         * E.g. we calculate price for 6th apple and the other 5 is already in the cart. So, if we do not 'accumulate'
         * quantity ( as you see below ), we will receive the price of first item, because they are split in the cart.
         *
         * Without a doubt, we should divide cart items based on quantity from persistent cache storage,
         * but it is too complicated for now.
         */
        $nonTempQtyCounter = [];
        $mappingQty        = [];
        foreach ($cart->getItems() as $item) {
            $nonTempHash                     = $item->calculateNonTemporaryHash();
            $nonTempQtyCounter[$nonTempHash] = isset($nonTempQtyCounter[$nonTempHash]) ? $nonTempQtyCounter[$nonTempHash] + $item->getQty() : $item->getQty();
            $mappingQty[$item->getHash()]    = $nonTempQtyCounter[$nonTempHash];
        }
        /** Finish accumulating non-temporary quantity */

        /**
         * @var array<int, ICartItem> $newItems
         * Create the list of cloned items with modified prices, so modifications do not affect the work of conditions.
         */
        $newItems = [];

        $initialItems = $cart->getItems();
        $roles = $cart->getContext()->getCustomer()->getRoles();

        foreach ($cart->getItems() as $item) {
            $newItem = clone $item;
            $newItems[] = $newItem;

            $persistentQty = $mappingQty[$item->getHash()] ?? 1.0;
            $objects = $this->persistentRuleRepository->getCache($item, $persistentQty, $roles);

            $object = null;
            $processor = null;
            foreach ($objects as $tmpObject) {
                $tmpProcessor = $tmpObject->rule->buildProcessor($context);

                if ( $tmpProcessor->isRuleMatchedCart($cart) ) {
                    $object = $tmpObject;
                    $processor = $tmpProcessor;
                }
            }

            if ( ! $object || ! $object->rule || ! $object->price || !($processor instanceof PersistentRuleProcessor) ) {
                continue;
            }

            $price = $object->price;

            $currencySwitcher = $this->context->currencyController;
            if ($currencySwitcher->isCurrencyChanged()) {
                $price = $price * $this->context->currencyController->getRate();
            }

            /** Replace cloned item in $cart */
            $tmpItems = $cart->getItems();
            foreach ( $tmpItems as $index => $tmpItem ) {
                if ( $tmpItem === $item ) {
                    $tmpItems[$index] = $newItem;
                }
            }
            $cart->setItems($tmpItems);
            $processor->applyPriceToCartItem($cart, $newItem, $price);
            $cart->setItems($initialItems);

            $appliedRules++;
        }

        $cart->setItems($newItems);

        return $appliedRules;
    }

    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function findPossibleMaxDiscountsForProducts(&$maxDiscountRate,&$maxDiscountAmount)
    {
        //has cached values ?
        if( isset($this->maxDiscountRate) AND isset($this->maxDiscountAmount) ) {
            $maxDiscountRate = $this->maxDiscountRate;
            $maxDiscountAmount = $this->maxDiscountAmount;
            return;
        }
        $maxDiscountRate = $maxDiscountAmount = 0;
        foreach ($this->ruleCollection->getRules() as $rule) {
            $rule->findPossibleMaxDiscountsForProducts($maxRuleDiscountRate,$maxRuleDiscountAmount);
            if($maxRuleDiscountRate>$maxDiscountRate) $maxDiscountRate = $maxRuleDiscountRate;
            if($maxRuleDiscountAmount>$maxDiscountAmount) $maxDiscountAmount = $maxRuleDiscountAmount;
        }
        $this->maxDiscountRate = $maxDiscountRate;
        $this->maxDiscountAmount = $maxDiscountAmount;
    }

    /**
     * @param Cart $cart
     *
     * @return array
     */
    public function getApplicableRulesForCart($cart)
    {
        $context = $this->context;
        return array_filter(array_map(function($rule) use($context, $cart) {
            $proc = $rule->buildProcessor($context);
            if ($proc->isRuleMatchedCart($cart)) {
                return $rule;
            }
            return null;
        }, $this->ruleCollection->getRules()));
    }
}
