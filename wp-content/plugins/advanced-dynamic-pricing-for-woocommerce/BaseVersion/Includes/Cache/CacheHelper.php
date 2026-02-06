<?php

namespace ADP\BaseVersion\Includes\Cache;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\CartCalculator;
use ADP\BaseVersion\Includes\Core\ICartCalculator;
use ADP\BaseVersion\Includes\Core\Rule\Rule;
use ADP\BaseVersion\Includes\Database\Database;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepository;
use ADP\BaseVersion\Includes\Database\RulesCollection;
use ADP\BaseVersion\Includes\Database\RuleStorage;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedProductSimple;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedVariableProduct;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;
use ADP\BaseVersion\Includes\Enums\RuleTypeEnum;
use ADP\Factory;
use WC_Product;

defined('ABSPATH') or exit;

class CacheHelper
{
    const KEY_ACTIVE_RULES_COLLECTION = 'adp_active_rule_collection';
    const KEY_ALREADY_LOADED_VARIABLES = 'adp_already_loaded_variables';
    const GROUP_RULES_CACHE = 'adp_rules';
    const GROUP_VARIATION_PROD_DATA_CACHE = 'adp_variation_product_data';
    const GROUP_PROCESSED_PRODUCTS_TO_DISPLAY = 'adp_processed_products_to_display';
    const GROUP_WC_PRODUCT = 'adp_wc_product';

    const GROUP_COLLECTIONS = 'adp_collections';

    /**
     * @return true
     */
    public static function flush()
    {
        return wp_cache_flush();
    }

    public static function applyLanguageCurrency($key) {
        if( function_exists('get_locale'))
            $key .= "|".get_locale();
        if( function_exists('get_woocommerce_currency'))
            $key .= "|".get_woocommerce_currency();
        return $key;
    }

    public static function cacheGet($key, $group = '', $force = false, &$found = null)
    {
        return wp_cache_get(self::applyLanguageCurrency($key), $group, $force, $found);
    }

    public static function cacheSet($key, $data, $group = '', $expire = 0)
    {
        if( apply_filters("adp_cache_enabled",true) )
            return wp_cache_set( self::applyLanguageCurrency($key), $data, $group, (int)$expire);
        else
            return false;
    }

    public static function cacheDelete($key, $group = '')
    {
        return wp_cache_delete( self::applyLanguageCurrency($key), $group );
    }

    public static function cacheFlushGroup($group)
    {
        if ( wp_cache_supports( 'flush_group' ) ) {
            wp_cache_flush_group( $group );
        } else {
            wp_cache_flush();
        }
    }

    public static function flushRulesCache()
    {
        self::cacheDelete(self::KEY_ACTIVE_RULES_COLLECTION);
        self::cacheFlushGroup(self::GROUP_RULES_CACHE);
        self::cacheFlushGroup(self::GROUP_PROCESSED_PRODUCTS_TO_DISPLAY);

        Factory::callStaticMethod('Shortcodes_OnSaleProducts', 'clearCache');
        Factory::callStaticMethod('Shortcodes_BogoProducts', 'clearCache');
    }

    public static function flushCollectionsCache()
    {
        self::cacheFlushGroup(self::GROUP_COLLECTIONS);
        self::flushRulesCache();
    }


    /**
     * @param null $deprecated
     *
     * @return RulesCollection
     */
    public static function loadActiveRules($deprecated = null): RulesCollection
    {
        $rulesCollection = self::cacheGet(self::KEY_ACTIVE_RULES_COLLECTION);

        if ($rulesCollection instanceof RulesCollection) {
            return $rulesCollection;
        }

        /** @var RuleStorage $storage */
        $storage         = Factory::get("Database_RuleStorage");
        $ruleRepository = new RuleRepository();
        $rows            = $ruleRepository->getRules(array('active_only' => true, 'rule_types' => array(RuleTypeEnum::COMMON()->getValue(), RuleTypeEnum::EXCLUSIVE()->getValue())));
        $rulesCollection = $storage->buildRules($rows);

        self::cacheSet(self::KEY_ACTIVE_RULES_COLLECTION, $rulesCollection);
        self::addRulesToCache($rulesCollection->getRules());

        return $rulesCollection;
    }

    /**
     * @param array<int, int> $ruleIds
     * @param Context $context
     *
     * @return array<int, Rule>
     */
    public static function loadRules($ruleIds, ?Context $context = null)
    {
        $ruleIds = (array)$ruleIds;
        $ruleIds = array_map('intval', $ruleIds);

        if (count($ruleIds) === 0) {
            return array();
        }

        $rules            = array();
        $notCachedRuleIds = array();

        foreach ($ruleIds as $ruleId) {
            $rule = self::cacheGet($ruleId, self::GROUP_RULES_CACHE);

            if ($rule instanceof Rule) {
                $rules[$rule->getId()] = $rule;
            } else {
                $notCachedRuleIds[] = $ruleId;
            }
        }

        if (count($notCachedRuleIds) === 0) {
            return $rules;
        }

        if (is_null($context)) {
            $context = new Context();
        }

        /** @var RuleStorage $storage */
        $storage         = Factory::get("Database_RuleStorage");
        $storage->withContext($context);
        $ruleRepository = new RuleRepository();
        $rows            = $ruleRepository->getRules(array('id' => $notCachedRuleIds));
        $rulesCollection = $storage->buildRules($rows);
        $rules           = $rulesCollection->getRules();
        self::addRulesToCache($rules);

        return $rules;
    }

    /**
     * @param array<int, int> $ruleIds
     * @param Context $context
     *
     * @return array<int, Rule>
     */
    public static function loadProductOnlyRules($ruleIds, ?Context $context = null)
    {
        $ruleIds = (array)$ruleIds;
        $ruleIds = array_map('intval', $ruleIds);

        if (count($ruleIds) === 0) {
            return array();
        }

        $rules            = array();
        $notCachedRuleIds = array();

        foreach ($ruleIds as $ruleId) {
            $rule = self::cacheGet($ruleId, self::GROUP_RULES_CACHE);

            if ($rule instanceof Rule) {
                $rules[$rule->getId()] = $rule;
            } else {
                $notCachedRuleIds[] = $ruleId;
            }
        }

        if (count($notCachedRuleIds) === 0) {
            return $rules;
        }

        if (is_null($context)) {
            $context = new Context();
        }

        /** @var RuleStorage $storage */
        $storage         = Factory::get("Database_RuleStorage");
        $storage->withContext($context);
        $ruleRepository = new RuleRepository();
        $rows            = $ruleRepository->getRules(array('id' => $notCachedRuleIds));
        $rulesCollection = $storage->buildPersistentRules($rows);
        $rules           = $rulesCollection->getRules();
        self::addRulesToCache($rules);

        return $rules;
    }

    /**
     * @param array<int, Rule> $rules
     */
    protected static function addRulesToCache($rules)
    {
        foreach ($rules as $rule) {
            self::cacheSet($rule->getId(), $rule, self::GROUP_RULES_CACHE);
        }
    }

    /**
     * @param int $variableProductId
     */
    public static function loadVariationsPostMeta($variableProductId)
    {
        $loadedIds = self::cacheGet(self::KEY_ALREADY_LOADED_VARIABLES);

        if ($loadedIds === false) {
            $loadedIds = array();
        }

        if (in_array($variableProductId, $loadedIds)) {
            return;
        }

        $productsData = Database::getOnlyRequiredChildPostMetaData($variableProductId);

        foreach ($productsData as $productId => $data) {
            self::cacheSet($productId, $data, self::GROUP_VARIATION_PROD_DATA_CACHE);
        }

        $loadedIds[] = $variableProductId;
        self::cacheSet(self::KEY_ALREADY_LOADED_VARIABLES, $loadedIds);
    }

    /**
     * @param int $productId
     *
     * @return array|object
     */
    public static function getVariationProductData($productId)
    {
        $productMeta = self::cacheGet($productId, self::GROUP_VARIATION_PROD_DATA_CACHE);

        if (false === $productMeta) {
            $productMeta = Database::getOnlyRequiredProductPostMetaData((int)$productId);

            if ( $productMeta ) {
                self::cacheSet($productId, $productMeta, self::GROUP_VARIATION_PROD_DATA_CACHE);
            }
        }

        return $productMeta;
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    public static function getVariationProductMeta($productId)
    {
        $product_data = self::getVariationProductData($productId);

        return $product_data ? $product_data->meta : array();
    }

    /**
     * @param int $productId
     * @param array $variationAttributes
     * @param float $qty
     * @param array $cartItemData
     * @param Cart $cart
     * @param CartCalculator $calc
     *
     * @return ProcessedProductSimple|ProcessedVariableProduct|null
     */
    public static function maybeGetProcessedProductToDisplay(
        $productId,
        $variationAttributes,
        $qty,
        $cartItemData,
        $cart,
        $calc
    ) {
        $hash      = self::calcHashProcessedProduct($productId, $variationAttributes, $qty, $cartItemData, $cart,
            $calc);
        $processed = self::cacheGet($hash, self::GROUP_PROCESSED_PRODUCTS_TO_DISPLAY);

        return $processed !== false ? $processed : null;
    }

    /**
     * @param WcCartItemFacade $cartItem
     * @param float $qty
     * @param ProcessedProductSimple|null $processed
     * @param Cart $cart
     * @param ICartCalculator $calc
     */
    public static function addProcessedProductToDisplay(
        WcCartItemFacade $cartItem,
        float $qty,
        ProcessedProductSimple $processed,
        Cart $cart,
        ICartCalculator $calc
    ) {
        $productId           = $cartItem->getVariationId() ? $cartItem->getVariationId() : $cartItem->getProductId();
        $cartItemData        = $cartItem->getThirdPartyData();
        $product             = $cartItem->getProduct();
        $variationAttributes = $product instanceof \WC_Product_Variation ? $product->get_variation_attributes() : array();
        $hash                = self::calcHashProcessedProduct($productId, $variationAttributes, $qty, $cartItemData,
            $cart, $calc);
        self::cacheSet($hash, $processed, self::GROUP_PROCESSED_PRODUCTS_TO_DISPLAY, 60 * 10 );
    }

    /**
     * @param int $productId
     * @param array $variationAttributes
     * @param float $qty
     * @param array $cartItemData
     * @param Cart $cart
     * @param ICartCalculator $calc
     *
     * @return string
     */
    public static function calcHashProcessedProduct(
        $theProduct,
        $variationAttributes,
        $qty,
        $cartItemData,
        $cart,
        $calc
    ) {
        if ($theProduct instanceof WC_Product) {
            $productId = $theProduct->get_id();
        } elseif (is_numeric($theProduct)) {
            $productId = $theProduct;
        } elseif ($theProduct instanceof \WP_Post) {
            $productId = $theProduct->ID;
        } else {
            $productId = $theProduct;
        }

        $parts = array($productId, $qty);

        foreach ($variationAttributes as $key => $value) {
            $parts[] = trim($key) . trim($value);
        }

        if (is_array($cartItemData) && ! empty($cartItemData)) {
            $cartItemDataKey = '';
            foreach ($cartItemData as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = http_build_query($value);
                }
                $cartItemDataKey .= trim($key) . trim($value);

            }
            $parts[] = $cartItemDataKey;
        }

        foreach ($cart->getItems() as $item) {
            $parts[] = $item->getHash();
        }

        foreach ($calc->getRulesCollection()->getRules() as $rule) {
            $parts[] = md5(serialize($rule));
        }

        $parts[]= md5( json_encode( $cart->getContext()->getCustomer()->getJson() ) );

        $parts = apply_filters("adp_calculate_processed_product_hash", $parts,
                    $theProduct, $variationAttributes, $qty, $cartItemData, $cart, $calc);

        $hash = md5(implode('_', $parts));

        return $hash;
    }

    /**
     * @param $theProduct int|WC_Product|\WP_Post
     *
     * @return false|WC_Product
     */
    public static function getWcProduct($theProduct)
    {
        $clone_products = apply_filters("adp_cache_clone_products",true);

        if ($theProduct instanceof WC_Product) {
            $product = $clone_products ? clone $theProduct : $theProduct;

            try {
                $reflection = new \ReflectionClass($product);
                $property   = $reflection->getProperty('changes');
                $property->setAccessible(true);
                $property->setValue($product, array());
            } catch (\ReflectionException $exception) {
                return false;
            }

            self::cacheSet($product->get_id(), $product, self::GROUP_WC_PRODUCT);

        } elseif (is_numeric($theProduct)) {
            $productId = $theProduct;

            $product = self::cacheGet($productId, self::GROUP_WC_PRODUCT);

            if ($product === false && ! empty($productById = wc_get_product($productId))) {
                $product = $clone_products ? clone $productById : $productById;
                self::cacheSet($productId, $product, self::GROUP_WC_PRODUCT);
            }


        } elseif ($theProduct instanceof \WP_Post) {
            $productId = $theProduct->ID;

            $product = self::cacheGet($productId, self::GROUP_WC_PRODUCT);

            if ($product === false && ! empty($productById = wc_get_product($productId))) {
                $product = $clone_products ? clone $productById : $productById;
                self::cacheSet($productId, $product, self::GROUP_WC_PRODUCT);
            }
        } else {
            return false;
        }

        return $product;
    }

    /**
     * @param int $productId
     * @param int $variationId
     * @param array $variationAttributes
     * @param array $cartItemData
     *
     * @return string
     */
    public static function calcHashPersistentRuleProduct(
        $productId,
        $variationId,
        $variationAttributes,
        $cartItemData
    ) {
        /**
         * We do not calculate cache for variation attributes for now
         * TODO calculate cache for every any variation attribute
         */
        $variationAttributes = array();

        $parts = array($productId, $variationId);

        foreach ($variationAttributes as $key => $value) {
            $parts[] = trim($key) . trim($value);
        }

        if (is_array($cartItemData) && ! empty($cartItemData)) {
            $cartItemDataKey = '';
            foreach ($cartItemData as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = http_build_query($value);
                }
                $cartItemDataKey .= trim($key) . trim($value);

            }
            $parts[] = $cartItemDataKey;
        }

        $parts = apply_filters("adp_calculate_persistent_rule_product_hash", $parts);

        return md5(implode('_', $parts));
    }


    public static function getCollectionsById($collectionIds)
    {
        $collectionIds = (array)$collectionIds;
        $collectionIds = array_map('intval', $collectionIds);

        if (count($collectionIds) === 0) {
            return [];
        }

        $collections = [];
        $notCachedCollectionsIds = [];

        foreach ($collectionIds as $collectionId) {
            $collection = self::cacheGet($collectionId, self::GROUP_COLLECTIONS);

            if ($collection === false) {
                $notCachedCollectionsIds[] = $collectionId;
            } else {
                $collections[$collection->id] = $collection;
            }
        }

        if (count($notCachedCollectionsIds) === 0) {
            return $collections;
        }

        if (!class_exists("\ADP\ProVersion\Includes\Database\Repository\CollectionRepository")) {
            return $collections;
        }

        $newCollections = \ADP\ProVersion\Includes\Database\Repository\CollectionRepository::getProductCollectionsByIds(
            $notCachedCollectionsIds
        );

        foreach ($newCollections as $collection) {
            self::cacheSet($collection->id, $collection, self::GROUP_COLLECTIONS);
        }

        return array_merge($collections, $newCollections);
    }
}
