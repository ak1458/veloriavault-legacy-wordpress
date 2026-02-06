<?php

namespace ADP\BaseVersion\Includes\Shortcodes;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\Core\Rule\Rule;
use ADP\BaseVersion\Includes\Core\Rule\SingleItemRule;
use ADP\BaseVersion\Includes\Database\Database;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepository;
use ADP\BaseVersion\Includes\Database\RuleStorage;
use ADP\BaseVersion\Includes\Enums\RuleTypeEnum;
use ADP\Factory;

use WC_Shortcode_Products;

defined('ABSPATH') or exit;

abstract class Products extends WC_Shortcode_Products
{
    const NAME = '';
    const STORAGE_WITH_RULES_KEY = '';

    /**
     * @var Context
     */
    protected $context;

    public static function register()
    {
        add_shortcode(static::NAME, function ($atts) {
            return static::create($atts);
        });
    }

    final public function __construct($attributes = array(), $type = 'products')
    {
        $this->context = adp_context();
        parent::__construct($attributes, $type);
    }

    /**
     * @param array|string $atts
     * @param Context $context
     *
     * @return string
     */
    public static function create($atts)
    {
        // apply legacy [sale_products] attributes
        $atts = array_merge(array(
            'limit'        => '12',
            'columns'      => '4',
            'orderby'      => 'title',
            'order'        => 'ASC',
            'category'     => '',
            'cat_operator' => 'IN',
        ), (array)$atts);

        $shortcode = new static($atts, static::NAME);

        return $shortcode->get_content();
    }

    /**
     * Parse attributes.
     *
     * @since  3.2.0
     * @param  array $attributes Shortcode attributes.
     * @return array
     */
    protected function parse_attributes( $attributes ) {
        $this->type = 'products'; //required
        $parsed_attributes = parent::parse_attributes( $attributes );
        $this->type = static::NAME;

        //parse own attrubutes
        $parsed_attributes['show_wc_onsale_products'] = false;
        if ( isset($attributes['show_wc_onsale_products']) ) {
            $parsed_attributes['show_wc_onsale_products'] = wc_string_to_bool($attributes['show_wc_onsale_products']);
        }
        $parsed_attributes['rule_id'] = false;
        if (isset($attributes['rule_id'])) {
            $parsed_attributes['rule_id'] = explode(',', $attributes['rule_id']);
        }
        return $parsed_attributes;
    }

    /**
	 * Parse query args.
	 *
	 * @since  3.2.0
	 * @return array
	 */
    protected function parse_query_args() {
        if ($this->attributes['rule_id'] !== false) {
            $productIds = static::getCachedProductsIdsByRule($this->attributes['rule_id']);
        } else {
            $productIds = static::getCachedProductsIdsByRule(); //get all
        }
        if ($this->attributes["show_wc_onsale_products"]) {
            $post__in = array_unique(array_merge(array(0), $productIds, wc_get_product_ids_on_sale()));
        } else {
            $post__in = array_merge(array(0), $productIds);
        }

        add_filter( 'woocommerce_shortcode_products_query', function($query_args, $attributes, $type ) use ($post__in){
                $query_args['post__in'] = $post__in;
                return $query_args;
        },0,4);
        $queryArgs = parent::parse_query_args();
        $queryArgs['post__in'] = $post__in;//force own ids only

        return $queryArgs;
    }

    public static function getActiveRules()
    {
        $context         = adp_context();
        if ($context->isRuleSuppressed() || $context->getOption('rules_apply_mode') === "none") {
            return [];
        }

        $rulesCollection = CacheHelper::loadActiveRules($context);
        $rulesArray = $rulesCollection->getRules();

        /** @var RuleStorage $storage */
        $storage        = Factory::get("Database_RuleStorage");
        $ruleRepository = new RuleRepository();
        $rows           = $ruleRepository->getRules([
            'active_only' => true,
            'rule_types'  => array(RuleTypeEnum::PERSISTENT()->getValue())
        ]);
        $persistentRulesCollection = $storage->buildPersistentRules($rows);

        $persistentRulesArray = $persistentRulesCollection->getRules();

        $rulesArray = array_merge($rulesArray, $persistentRulesArray);

        $rulesArray = array_filter($rulesArray, function($rule) {
            return static::filterRule($rule);
        });

        $rulesIds = array_map(function($rule) {
            return $rule->getId();
        }, $rulesArray);

        return array_values($rulesIds);
    }

    public static function getProductIdsByRuleId($ruleId)
    {
        $ruleStorage    = Factory::get("Database_RuleStorage");
        $ruleRepository = new RuleRepository();
        $rows           = $ruleRepository->getRules(['id' => $ruleId]);
        $rawRule        = reset($rows);

        if ($rawRule->rule_type === RuleTypeEnum::PERSISTENT()->getValue()) {
            $rulesCollection = $ruleStorage->buildPersistentRules([$rawRule]);
        } else {
            $rulesCollection = $ruleStorage->buildRules([$rawRule]);
        }

        $rule = $rulesCollection->getFirst();

        $sqlGenerator = Factory::get("Shortcodes_SqlGeneratorPersistent");
        $sqlGenerator->applyRuleToQuery($rule);
        $productIds = $sqlGenerator->getProductIds('on_sale');

        return $productIds;
    }

    public static function updateCachedProductsIdsByRuleId($ruleId) {
        $productIds = static::getProductIdsByRuleId($ruleId);
        $productIdsPerRule = get_transient(static::STORAGE_WITH_RULES_KEY);

        if(!$productIdsPerRule) {
            $productIdsPerRule = [];
        }

        $productIdsPerRule[$ruleId] = $productIds;
        set_transient(static::STORAGE_WITH_RULES_KEY, $productIdsPerRule, DAY_IN_SECONDS * 30);

        return $productIds;
    }

    /**
     * @param array $ruleId
     * @return array
     */
    public static function getCachedProductsIdsByRule($ruleIds = null)
    {
        $productIdsPerRule = static::getCachedProductsIdsPerRule();

        if(!$ruleIds) {
            $ruleIds = array_keys($productIdsPerRule);
        }

        $productIdsByRuleIds = [];
        foreach ($ruleIds as $ruleId) {
            $productIdsByRuleIds = array_merge($productIdsByRuleIds, $productIdsPerRule[$ruleId] ?? []);
        }

        return array_unique($productIdsByRuleIds);
    }

    public static function getCachedProductsIds($deprecated = null)
    {
        return static::getCachedProductsIdsByRule();
    }

    /**
     * @return mixed
     */
    public static function getCachedProductsIdsPerRule()
    {
        // Load from cache.
        $productIdsPerRule = get_transient(static::STORAGE_WITH_RULES_KEY);

        // Valid cache found.
        if (false !== $productIdsPerRule) {
            return $productIdsPerRule;
        }

        return static::updateCachedProductsIdsPerRule();
    }

    /**
     * @return mixed
     */
    public static function updateCachedProductsIdsPerRule()
    {
        $productIdsPerRule = [];

        $rules = static::getActiveRules();
        foreach ($rules as $ruleId) {
            $productIdsPerRule[$ruleId] = static::getProductIdsByRuleId($ruleId);
        }

        set_transient(static::STORAGE_WITH_RULES_KEY, $productIdsPerRule, DAY_IN_SECONDS * 30);

        return $productIdsPerRule;
    }

    public static function clearCache()
    {
        delete_transient(static::STORAGE_WITH_RULES_KEY);
    }

    /**
     * @param Rule $rule
     *
     * @return bool
     */
    protected static function filterRule($rule)
    {
        return false;
    }
}
