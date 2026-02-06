<?php

namespace ADP\BaseVersion\Includes\Database\Repository;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\CartProcessor\CartBuilder;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\Core\CartCalculatorPersistent;
use ADP\BaseVersion\Includes\Core\Rule\PersistentRule;
use ADP\BaseVersion\Includes\Core\Rule\Structures\Discount;
use ADP\BaseVersion\Includes\Core\Rule\Structures\RangeDiscount;
use ADP\BaseVersion\Includes\Database\Models\PersistentRuleCache;
use ADP\BaseVersion\Includes\Database\Models\PersistentRuleCache as PersistentRuleModel;
use ADP\BaseVersion\Includes\Database\PersistentRuleCacheObject;
use ADP\BaseVersion\Includes\Database\RuleStorage;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedGroupedProduct;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedVariableProduct;
use ADP\BaseVersion\Includes\PriceDisplay\Processor;
use ADP\BaseVersion\Includes\Shortcodes\SqlGenerator;
use ADP\Factory;

defined('ABSPATH') or exit;

class PersistentRuleRepository implements PersistentRuleRepositoryInterface
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct() {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param ICartItem $item
     * @param float|null $qty
     * @param array|null $roles
     *
     * @return array<int, PersistentRuleCacheObject>
     * @throws \Exception
     */
    public function getCache($item, $qty = null, $roles = null)
    {
        $cacheKey = $this->calculateCacheHash($item, $qty, $roles);

        $objects = CacheHelper::cacheGet($cacheKey, CacheHelper::GROUP_RULES_CACHE);

        if ( ! is_array($objects) ) {
            $objects = $this->calculate($item, $qty, $roles);
            CacheHelper::cacheSet($cacheKey, $objects, CacheHelper::GROUP_RULES_CACHE);
        }

        return $objects;
    }

    /**
     * @param \WC_Product $product
     *
     * @return array<int, PersistentRuleCacheObject>
     * @throws \Exception
     */
    public function getCacheWithProduct($product)
    {
        $cacheKey = $this->calculateCacheHashWithProduct($product);

        $objects = CacheHelper::cacheGet($cacheKey, CacheHelper::GROUP_RULES_CACHE);

        if ( ! is_array($objects) ) {
            $objects = $this->calculate($product);
            CacheHelper::cacheSet($cacheKey, $objects, CacheHelper::GROUP_RULES_CACHE);
        }

        return $objects;
    }

    public function addRule($rows, $ruleId)
    {

        global $wpdb;
        $table = $wpdb->prefix . PersistentRuleModel::TABLE_NAME;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query('START TRANSACTION');

        if ( ! empty($ruleId)) {
            $where  = array('rule_id' => $ruleId);
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $result = $wpdb->delete($table, $where);
        }

        /**
         * @var PersistentRuleCache $cache
         */
        foreach ($rows as $cache) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $result = $wpdb->insert($table, $cache->getData());
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query('COMMIT');
    }

    public function getAddRuleData($ruleId, Context $context)
    {
        global $wpdb;

        /** @var $sqlGenerator SqlGeneratorPersistent */
        $sqlGenerator = Factory::get("Shortcodes_SqlGeneratorPersistent");

        /** @var RuleStorage $storage */
        $storage         = Factory::get("Database_RuleStorage");
        $storage->withContext($context);
        $ruleRepository = new RuleRepository();
        $rows            = $ruleRepository->getRules(array('id' => $ruleId));
        $rulesCollection = $storage->buildPersistentRules($rows);

        foreach ($rulesCollection->getRules() as $rule) {
            /** @var PersistentRule $rule */
            $sqlGenerator->applyRuleToQuery($context, $rule);
        }

        $productIds = $sqlGenerator->getProductIds();
        if(!$productIds) {
            return [];
        }

        $data = array();
        /** @var PersistentRule $rule */
        $rule             = $rulesCollection->getFirst();
        $cartCalculator   = new CartCalculatorPersistent($context, $rule);
        $productProcessor = new Processor($context, $cartCalculator);
        $cartBuilder      = new CartBuilder($context);
        $cart             = $cartBuilder->create(WC()->customer, WC()->session);
        $productProcessor->withCart($cart);

        foreach ($productIds as $productId) {
            $variationId = 0;

            if ('product_variation' === get_post_type($productId)) {
                $variationId = $productId;
                $productId   = wp_get_post_parent_id($variationId);
            }

            $product = CacheHelper::getWcProduct($variationId ? $variationId : $productId);
            $persistentRuleCaches = $this->calculateCacheForProductWithRule($context, $productProcessor, $rule, $product);
            foreach ($persistentRuleCaches as $cache) {
                $data[] = PersistentRuleCache::fromArray($cache);

                $this->saveCacheInProductMetaData($product, $cache);
            }
        }

        return $data;
    }

    public function removeRule($ruleId)
    {

        global $wpdb;
        $table = $wpdb->prefix .  PersistentRuleModel::TABLE_NAME;

        $where = array('rule_id' => $ruleId);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->delete($table, $where);
    }

    /**
     * @param Context $context
     * @param \WC_Product $product
     * @param array $cartItemData
     */
    public function recalculateCacheForProduct($context, $product, $cartItemData = array())
    {
        $objects = $this->getCacheWithProduct($product);

        global $wpdb;
        $tableCache = $wpdb->prefix . PersistentRuleModel::TABLE_NAME;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query('START TRANSACTION');

        foreach ( $objects as $object ) {
            if ($object === null || $object->rule === null) {
                continue;
            }

            $rule = $object->rule;
            $hash = $this->calculateDbHashWithProduct($product);
            $where  = array('rule_id' => $rule->getId(), 'product' => $hash);
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $result = $wpdb->delete($tableCache, $where);

            $cartCalculator   = new CartCalculatorPersistent($context, $rule);
            $productProcessor = new Processor($context, $cartCalculator);
            $cartBuilder      = new CartBuilder($context);
            $cart             = $cartBuilder->create(WC()->customer, WC()->session);
            $productProcessor->withCart($cart);
            foreach ($this->calculateCacheForProductWithRule($context, $productProcessor, $rule, $product, $cartItemData) as $data) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $result = $wpdb->insert($tableCache, $data);
            }
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query('COMMIT');
    }

    /**
     * @param Context $context
     * @param \WC_Cart $wcCart
     *
     * @return array<int, PersistentRule>
     */
    public function getRulesFromWcCart($context, $wcCart)
    {
        $rules = array();

        $cartBuilder = new CartBuilder($context);
        $cart        = $cartBuilder->create(WC()->customer, WC()->session);
        $cartBuilder->populateCart($cart, $wcCart);

        foreach ($cart->getItems() as $item) {
            $objects = $this->getCache($item);

            $object = null;
            foreach ( $objects as $tmpObject ) {
                $tmpProcessor = $tmpObject->rule->buildProcessor($context);

                if ( $tmpProcessor->isRuleMatchedCart($cart) ) {
                    $object = $tmpObject;
                }
            }

            if ($object !== null && $object->rule !== null) {
                $rules[] = $object->rule;
            }
        }

        return $rules;
    }

    public function truncate() {
        global $wpdb;
        $tableCache = $wpdb->prefix . PersistentRuleModel::TABLE_NAME;
        //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("TRUNCATE TABLE $tableCache");
    }

    /**
     * @param Context $context
     * @param Processor $productProcessor
     * @param PersistentRule $rule
     * @param \WC_Product $product
     * @param array $cartItemData
     */
    protected function calculateCacheForProductWithRule(
        $context,
        $productProcessor,
        $rule,
        $product,
        $cartItemData = array()
    ) {
        $data = array();
        $hash = $this->calculateDbHashWithProduct($product, $cartItemData);

        if($rolesDiscounts = $rule->getRoleDiscounts()) {
            $customer = $productProcessor->getCart()->getContext()->getCustomer();
            $initialRoles = $customer->getRoles();

            foreach ($rolesDiscounts as $rolesDiscount) {
                foreach ($rolesDiscount->getRoles() as $role) {
                    $customer->setRoles([$role]);

                    $customHash = function($key) use($role) {
                        $key[] = "C{$role}";
                        return $key;
                    };
                    add_filter("adp_calculate_persistent_rule_product_hash", $customHash);

                    $hash = $this->calculateDbHashWithProduct($product, $cartItemData);

                    $processedProduct = $productProcessor->calculateProduct($product, 1.0, $cartItemData);

                    remove_filter("adp_calculate_persistent_rule_product_hash", $customHash);
                    $customer->setRoles($initialRoles);

                    if ($processedProduct === null || $processedProduct instanceof ProcessedVariableProduct || $processedProduct instanceof ProcessedGroupedProduct) {
                        continue;
                    }

                    $row = [
                        'product'        => $hash,
                        'rule_id'        => $rule->getId(),
                        'qty_start'      => 1.0,
                        'qty_finish'     => null,
                        'original_price' => $processedProduct->getOriginalPrice(),
                        'price'          => $processedProduct->getCalculatedPrice(),
                    ];

                    $data[] = $row;
                }
            }
        } else if ($rule->hasProductRangeAdjustment()) {
            $handler = $rule->getProductRangeAdjustmentHandler();
            $ranges  = $handler->getRanges();

            if ( count($ranges) > 0 ) {
                $range = $ranges[0];
                if ( $range->getFrom() !== INF && $range->getFrom() > 1.0 ) {
                    $ranges = array_merge(
                        array(
                            new RangeDiscount(
                                1,
                                $range->getFrom() - 1.0,
                                new Discount($context, Discount::TYPE_PERCENTAGE, 0)
                            )
                        ),
                        $ranges
                    );
                }
            }

            foreach ($ranges as $range) {
                $processedProduct = $productProcessor->calculateProduct($product, $range->getFrom(), $cartItemData);

                if ($processedProduct === null || $processedProduct instanceof ProcessedVariableProduct || $processedProduct instanceof ProcessedGroupedProduct) {
                    return $data;
                }

                $data[] = array(
                    'product'        => $hash,
                    'rule_id'        => $rule->getId(),
                    'qty_start'      => $range->getFrom(),
                    'qty_finish'     => $range->getTo() === INF ? null : $range->getTo(),
                    'original_price' => $processedProduct->getOriginalPrice(),
                    'price'          => $processedProduct->getCalculatedPrice(),
                );
            }
        } else {
            $processedProduct = $productProcessor->calculateProduct($product, 1.0, $cartItemData);

            if ($processedProduct === null || $processedProduct instanceof ProcessedVariableProduct || $processedProduct instanceof ProcessedGroupedProduct) {
                return $data;
            }

            $data[] = array(
                'product'        => $hash,
                'rule_id'        => $rule->getId(),
                'qty_start'      => 1.0,
                'qty_finish'     => null,
                'original_price' => $processedProduct->getOriginalPrice(),
                'price'          => $processedProduct->getCalculatedPrice(),
            );
        }

        return $data;
    }

    protected function calculateDbHashWithRoles($item, $roles)
    {
        $hash = [];
        if(!$roles) {
            return $hash;
        }
        foreach ($roles as $role) {
            $customHash = function($key) use($role) {
                $key[] = "C{$role}";
                return $key;
            };
            add_filter("adp_calculate_persistent_rule_product_hash", $customHash);

            $hash[] = $this->calculateDbHash($item);

            remove_filter("adp_calculate_persistent_rule_product_hash", $customHash);
        }
        return $hash;
    }

    /**
     * @param ICartItem|\WC_Product $item
     * @param float|null $qty
     *
     * @return array<int, PersistentRuleCacheObject>
     * @throws \Exception
     */
    protected function calculate($item, $qty = null, $roles=null)
    {
        $context = $this->context;

        if ($item instanceof ICartItem) {
            $hash[] = $this->calculateDbHash($item);
            $hash = \array_merge($hash, $this->calculateDbHashWithRoles($item, $roles));
            $qty  = ($qty !== null ? (float)$qty : $item->getQty());
        } elseif ($item instanceof \WC_Product) {
            $hash[] = $this->calculateDbHashWithProduct($item);
            $hash = \array_merge($hash, $this->calculateDbHashWithRoles($item, $roles));
            $qty  = ($qty !== null ? (float)$qty : 1.0);
        } else {
            return array();
        }

        $hash = implode(',', array_map(function($v) {
            return "'" . esc_sql($v) . "'";
        }, $hash));

        global $wpdb;

        $tableCache = $wpdb->prefix . PersistentRuleModel::TABLE_NAME;
        //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare("SELECT persistent_rules_cache.rule_id, persistent_rules_cache.price FROM {$tableCache} AS persistent_rules_cache WHERE persistent_rules_cache.product IN ({$hash})
            AND persistent_rules_cache.qty_start <= %s
            AND (persistent_rules_cache.qty_finish IS NULL OR persistent_rules_cache.qty_finish >= %s)",
            array($qty, $qty)
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $rows  = $wpdb->get_results($query, ARRAY_A);

        if (count($rows) === 0) {
            return array();
        }

        $objects = [];
        foreach ($rows as $row) {
            $price = $row['price'];

            $rules = CacheHelper::loadProductOnlyRules([$row['rule_id']]);
            if (count($rules) === 0) {
                return [];
            }

            $rule = reset($rules);
            if ($rule === null) {
                continue;
            }

            $objects[] = new PersistentRuleCacheObject($rule, $price);
        }

        return $objects;
    }


    /**
     * @param ICartItem $item
     */
    protected function calculateDbHash($item)
    {
        $productId           = $item->getWcItem()->getProductId();
        $variationId         = $item->getWcItem()->getVariationId();
        $cartItemData        = array();
        $product             = $item->getWcItem()->getProduct();
        $variationAttributes = $product instanceof \WC_Product_Variation ? $product->get_variation_attributes() : array();

        return CacheHelper::calcHashPersistentRuleProduct(
            $productId,
            $variationId,
            $variationAttributes,
            $cartItemData
        );
    }

    /**
     * @param \WC_Product $product
     * @param array $cartItemData
     */
    protected function calculateDbHashWithProduct($product, $cartItemData = array())
    {
        $parentId            = $product->get_parent_id('edit');
        $productId           = $parentId ?: $product->get_id();
        $variationId         = $parentId ? $product->get_id() : 0;
        $variationAttributes = $product instanceof \WC_Product_Variation ? $product->get_variation_attributes() : array();

        return CacheHelper::calcHashPersistentRuleProduct(
            (string)$productId,
            (string)$variationId,
            $variationAttributes,
            $cartItemData
        );
    }

    /**
     * @param ICartItem $item
     * @param float|null $qty
     * @param array|null $roles
     */
    protected function calculateCacheHash($item, $qty = null, $roles = null)
    {
        return join('_', array_filter([
            $this->calculateDbHash($item),
            $qty !== null ? (float) $qty : $item->getQty(),
            $roles !== null ? join(',', $roles) : ''
        ]));
    }

    /**
     * @param \WC_Product $product
     */
    protected function calculateCacheHashWithProduct($product)
    {
        $qty = (string)(1.0);

        return $this->calculateDbHashWithProduct($product) . '_' . $qty;
    }

    /**
     * @param \WC_Product $product
     * @param array $cache
     * @return void
     */
    protected function saveCacheInProductMetaData($product, $cache) {
        static $last_parent_id = false, $last_parent_min_price=0;
        global $wpdb;

        $price = $cache['price'];

        // save calculated price in product meta
        update_post_meta($product->get_id(), '_sale_price_adp', $price);
        //update lookup table too
        // phpcs:ignore WordPress.DB
        $wpdb->update( $wpdb->wc_product_meta_lookup, ['min_price' => $price, 'onsale'=>1],  ['product_id' => $product->get_id()] );

        if($product instanceof \WC_Product_Variation) {
            $parent_id = $product->get_parent_id('edit');
            if( $last_parent_id == $parent_id){ // variations of same product processed still ?
                if($last_parent_min_price > $price) //use min price of variation
                    $last_parent_min_price = $price;
            } else {
                $last_parent_id = $parent_id;
                $last_parent_min_price = $price;
            }

            update_post_meta($last_parent_id, '_sale_price_adp', $last_parent_min_price);
            // phpcs:ignore WordPress.DB
            $wpdb->update( $wpdb->wc_product_meta_lookup, ['min_price' => $last_parent_min_price, 'onsale'=>1],  ['product_id' => $last_parent_id] );
        }
    }

    public function clearCacheInProductMetaData() {
        delete_post_meta_by_key('_sale_price_adp');
    }

    public function installHooksForProductLookupTable() {
        add_action( 'wc_update_product_lookup_tables_column', function($column){
            global $wpdb;

            if($column == 'min_max_price') {
                // phpcs:ignore WordPress.DB
                $sql = "UPDATE {$wpdb->wc_product_meta_lookup} lookup_table INNER JOIN {$wpdb->postmeta} meta ON lookup_table.product_id = meta.post_id AND meta.meta_key = '_sale_price_adp' SET lookup_table.min_price = meta.meta_value";
                // phpcs:ignore  WordPress.DB
                $wpdb->query($sql);
            }
            if($column == 'onsale') {
                // phpcs:ignore WordPress.DB
                $sql = "UPDATE {$wpdb->wc_product_meta_lookup} lookup_table INNER JOIN {$wpdb->postmeta} meta ON lookup_table.product_id = meta.post_id AND meta.meta_key = '_sale_price_adp' SET lookup_table.onsale = 1";
                // phpcs:ignore  WordPress.DB
                $wpdb->query($sql);
            }
        } , 100 );

        add_filter( 'woocommerce_get_catalog_ordering_args', function ($sort_args) {
            $orderby_value = null;

            if ( isset( $_GET['orderby'] ) ) {
                $orderby_value = wc_clean( $_GET['orderby'] );
            }

            if (!empty($orderby_value) && $orderby_value === 'on_sale_first') {
                add_filter(
                    'woocommerce_product_query_meta_query',
                    function ($meta_query) {

                        $meta_query = [
                            'relation' => 'OR',
                            [
                                'key'     => '_sale_price_adp',
                                'compare' => 'NOT EXISTS',
                            ],
                            [
                                'relation' => 'OR',
                                [
                                    'key'     => '_sale_price_adp',
                                    'value'   => 0,
                                    'compare' => '>=',
                                    'type'    => 'NUMERIC',
                                ],
                                [
                                    'key'     => '_sale_price_adp',
                                    'value'   => '',
                                    'compare' => '=',
                                ],
                            ],
                        ];

                        return $meta_query;
                    },
                    100
                );
            }
            return $sort_args;
        }, 100);
    }
}
