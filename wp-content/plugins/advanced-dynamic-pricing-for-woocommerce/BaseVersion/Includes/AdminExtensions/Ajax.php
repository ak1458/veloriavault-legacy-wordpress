<?php

namespace ADP\BaseVersion\Includes\AdminExtensions;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\Database\Models\Rule;
use ADP\BaseVersion\Includes\Database\Repository\PersistentRuleRepository;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepository;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepositoryInterface;
use ADP\BaseVersion\Includes\Helpers\Helpers;
use ADP\BaseVersion\Includes\WC\WcProductCustomAttributesCache;
use ADP\BaseVersion\Includes\Database\Repository\PersistentRuleRepositoryInterface;
use ADP\Factory;
use WC_Data_Store;
use ADP\BaseVersion\Includes\Enums\RuleTypeEnum;

defined('ABSPATH') or exit;

class Ajax
{
    const ACTION_PREFIX = 'wdp_ajax';
    const SECURITY_QUERY_ARG = 'security';
    const SECURITY_ACTION = 'wdp-request';
    protected $limit = null;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var PersistentRuleRepositoryInterface
     */
    protected $persistentRuleRepository;

    /**
     * @param null $deprecated
     */
    public function __construct($deprecated = null)
    {
        $this->context                  = adp_context();
        $this->ruleRepository           = new RuleRepository();
        $this->persistentRuleRepository = new PersistentRuleRepository();

        $this->limit = $this->context->getOption('limit_results_in_autocomplete');
        if (empty($this->limit)) {
            $this->limit = 25;
        }
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function withRuleRepository(RuleRepositoryInterface $repository)
    {
        $this->ruleRepository = $repository;
    }

    public function withPersistentRuleRepository(PersistentRuleRepositoryInterface $repository)
    {
        $this->persistentRuleRepository = $repository;
    }

    public function register()
    {
        add_action('wp_ajax_' . self::ACTION_PREFIX, array($this, 'ajaxRequests'));
    }

    public function ajaxRequests()
    {
        $result = null;

        check_ajax_referer(self::SECURITY_ACTION, self::SECURITY_QUERY_ARG);
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $method = htmlspecialchars($_POST['method'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);

        $methodName = 'ajax_' . $method;

        if (method_exists($this, $methodName)) {
            $result = $this->$methodName();
        }
        $result = apply_filters('wdp_ajax_' . $method, $result);

        wp_send_json_success($result);
    }

    public function ajax_products()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);

        /** @var \WC_Product_Data_Store_CPT $dataStore */
        $dataStore = WC_Data_Store::load('product');
        $ids       = $dataStore->search_products($query, '', true, false, $this->limit);

        return array_values(array_map(function ($postId) {
            return array(
                'id'   => (string)$postId,
                'text' => '#' . $postId . ' ' . Helpers::getProductTitle($postId),
                'link' => $this->context->getOption("products_as_links_in_the_product_filter", false) ? Helpers::getProductLink($postId) : '',
            );
        }, array_filter($ids)));
    }

    public function ajax_giftable_products()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);

        /** @var \WC_Product_Data_Store_CPT $dataStore */
        $dataStore = WC_Data_Store::load('product');
        $ids       = $dataStore->search_products($query, '', true, false, $this->limit);

        return array_values(array_filter(array_map(function ($postId) {
            $product = CacheHelper::getWcProduct($postId);
            if ( ! $product) {
                return false;
            }

            $bundle = null;
            if ($product->is_type(array('variable', 'grouped'))) {
                $bundle = array_map(function ($postId) {
                    return array(
                        'id'   => (string)$postId,
                        'text' => '#' . $postId . ' ' . Helpers::getProductTitle($postId),
                        'link' => $this->context->getOption("products_as_links_in_the_product_filter", false) ? Helpers::getProductLink($postId) : '',
                    );
                }, $product->get_children());
            }

            $result = array(
                'id'   => (string)$postId,
                'text' => '#' . $postId . ' ' . Helpers::getProductTitle($postId),
                'link' => $this->context->getOption("products_as_links_in_the_product_filter", false) ? Helpers::getProductLink($postId) : '',
            );

            if ($bundle) {
                $result['bundle'] = $bundle;
            }

            return $result;
        }, $ids)));
    }

    public function ajax_auto_add_products() {
        return $this->ajax_giftable_products();
    }

    public function ajax_giftable_categories()
    {
        return $this->ajax_product_categories();
    }

    public function ajax_product_sku()
    {
        global $wpdb;
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $queryResults = $wpdb->get_results("SELECT DISTINCT meta_value, post_id FROM $wpdb->postmeta WHERE meta_key = '_sku' AND meta_value  like '%$query%' LIMIT $this->limit");

        $results = array_map(function ($result) {
            return array(
                'id'   => (string)$result->meta_value,
                'text' => 'SKU: ' . $result->meta_value,
            );
        }, $queryResults);

        return apply_filters('wdp_product_sku_autocomplete_items', $results, $queryResults);
    }

    public function ajax_product_category_slug()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        $terms = get_terms(array(
            'taxonomy'   => 'product_cat',
            'name__like' => $query,
            'hide_empty' => false,
            'number'     => $this->limit
        ));

        return array_map(function ($term) {
            return array(
                'id'   => $term->slug,
                'text' => __('Slug', 'advanced-dynamic-pricing-for-woocommerce') . ': ' . $term->slug,
                'link' => $this->context->getOption("products_as_links_in_the_product_filter", false) ? get_category_link($term->term_id) : ''
            );
        }, $terms);
    }

    public function ajax_product_categories()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        $terms = get_terms(array(
            'taxonomy'   => 'product_cat',
            'name__like' => $query,
            'hide_empty' => false,
            'number'     => $this->limit
        ));

        return array_map(function ($term) {
            $parent = $term;
            while ($parent->parent != '0') {
                $parent_id = $parent->parent;
                $parent    = get_term($parent_id, 'product_cat');
            }

            $id = (string)$term->term_id;

            return array(
                'id'   => $id,
                'text' => $parent == $term ? "#$id $term->name" : "#$id $parent->name>$term->name",
                'link' => $this->context->getOption("products_as_links_in_the_product_filter", false) ? get_category_link($term->term_id) : ''
            );
        }, $terms);
    }

    public function ajax_check_filter_priority()
    {
        try {
            if(!$this->context->getOption('show_select_filter_priority', false)) {
                $settings = $this->context->getSettings();
                $settings->set('show_select_filter_priority', true);
                $settings->save();
            }
            wp_send_json_success();
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_product_taxonomies()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query         = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $taxonomy_name = htmlspecialchars($_POST['taxonomy'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);

        $terms = get_terms(array(
            'taxonomy'   => $taxonomy_name,
            'name__like' => $query,
            'hide_empty' => false,
            'number'     => $this->limit,
        ));

        return array_map(function ($term) {
            return array(
                'id'   => (string)$term->term_id,
                'text' => $term->name,
            );
        }, $terms);
    }

    public function ajax_product_attributes()
    {
        global $wc_product_attributes, $wpdb;
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);

        $taxonomies = array_map(function ($item) {
            return "'$item'";
        }, array_keys($wc_product_attributes));
        $taxonomies = implode(', ', $taxonomies);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $items = $wpdb->get_results("SELECT $wpdb->terms.term_id, $wpdb->terms.name, taxonomy FROM $wpdb->term_taxonomy INNER JOIN $wpdb->terms USING (term_id) WHERE taxonomy in ($taxonomies) AND $wpdb->terms.name  like '%$query%' LIMIT $this->limit");


        return array_map(function ($term) use ($wc_product_attributes) {
            $attribute = $wc_product_attributes[$term->taxonomy]->attribute_label;

            return array(
                'id'   => (string)$term->term_id,
                'text' => $attribute . ': ' . $term->name,
                'link' => $this->context->getOption("products_as_links_in_the_product_filter", false) ? get_edit_term_link( $term->term_id ) : '',
            );
        }, $items);
    }

    public function ajax_product_custom_attributes()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = strtolower(htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401));

        $results = array();
        /** @var WcProductCustomAttributesCache $productAttributesCache */
        $productAttributesCache = Factory::get("WC_WcProductCustomAttributesCache");
        $attributes             = $productAttributesCache->findCustomAttributes($query);

        foreach ($attributes as $attribute) {
            $pieces        = explode(":", $attribute);
            $attributeName = wc_clean(strtolower(trim(array_shift($pieces))));
            $option        = strtolower(implode(":", $pieces));

            $results[] = array(
                'id'   => "$attributeName:$option",
                'text' => $attribute,
            );
        }

        return $results;
    }

    public function ajax_product_tags()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        $terms = get_terms(array(
            'taxonomy'   => 'product_tag',
            'name__like' => $query,
            'hide_empty' => false,
            'number'     => $this->limit
        ));


        return array_map(function ($term) {
            $id = (string)$term->term_id;
            return array(
                'id'   => $id,
                'text' => "#$id $term->name",
                'link' => $this->context->getOption("products_as_links_in_the_product_filter", false) ? get_category_link($term->term_id) : ''
            );
        }, $terms);
    }

    public function ajax_product_brand()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        $terms = get_terms(array(
            'taxonomy'   => 'product_brand',
            'name__like' => $query,
            'hide_empty' => false,
            'number'     => $this->limit
        ));


        return array_map(function ($term) {
            $id = (string)$term->term_id;
            return array(
                'id'   => $id,
                'text' => "#$id $term->name",
                'link' => $this->context->getOption("products_as_links_in_the_product_filter", false) ? get_category_link($term->term_id) : ''
            );
        }, $terms);
    }

    public function ajax_product_custom_fields()
    {
        global $wpdb;
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        $like  = $wpdb->esc_like($query);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpFields = $wpdb->get_col("SELECT DISTINCT CONCAT(fields.meta_key,'=',fields.meta_value) FROM {$wpdb->postmeta} AS fields JOIN {$wpdb->posts} AS products ON products.ID = fields.post_id WHERE products.post_type IN ('product','product_variation') AND CONCAT(fields.meta_key,'=',fields.meta_value) LIKE '%{$like}%' ORDER BY meta_key LIMIT $this->limit");

        return array_map(function ($custom_field) {
            return array(
                'id'   => $custom_field,
                'text' => $custom_field,
            );
        }, $wpFields);
    }

    public function ajax_coupons()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);

        $postsRaw = get_posts(array(
            's'              => $query,
            'posts_per_page' => $this->limit,
            'post_type'      => 'shop_coupon',
            'post_status'    => array('publish'),
            'fields'         => 'ids',
        ));

        $items = array_map(function ($postId) {
            $code = get_the_title($postId);

            return array(
                'id'   => $code,
                'text' => $code
            );
        }, $postsRaw);

        return array_values($items);
    }

    public function ajax_rules_list()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        $rulesList = $this->ruleRepository->getRules();

        $rulesList = array_values(array_filter($rulesList, function ($rule) use ($query) {
            //phpcs:ignore WordPress.Security
            return (isset($_POST["current_rule"]) && $rule->id === $_POST["current_rule"]) ? false : stripos($rule->title,
                    $query) !== false;
        }));

        return array_map(function ($el) {
            return array(
                "id"   => $el->id,
                "text" => $el->title
            );
        }, $rulesList);
    }

    public function ajax_users_list()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        $query = "*$query*";
        $users = get_users(array(
            'fields'  => array('ID', 'user_nicename'),
            'search'  => $query,
            'orderby' => 'user_nicename',
            'number'  => $this->limit
        ));

        return array_map(function ($user) {
            return array(
                'id'   => (string)$user->ID,
                'text' => $user->user_nicename,
                'link' => $this->context->getOption("products_as_links_in_the_product_filter", false) ? get_author_posts_url($user->ID) : '',
            );
        }, $users);
    }

    public function ajax_skip_discount_type() {
        $settings = $this->context->getSettings();
        $settings->set('create_blank_rule', true);
        $settings->save();

        wp_send_json_success();
    }

    public function ajax_save_rule()
    {
        //phpcs:ignore WordPress.Security
        if ( ! isset($_POST['rule'])) {
            return;
        }
        //phpcs:ignore WordPress.Security, WordPress.Security.ValidatedSanitizedInput
        $rule = $_POST['rule'];

        $title = htmlspecialchars(
            $rule['title'] ? stripcslashes($rule['title']) : "",
            ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
        );

        // prepare data to store each rule in db
        $data = array(
            'deleted'                  => 0,
            'enabled'                  => (isset($rule['enabled']) && $rule['enabled'] === 'on') ? 1 : 0,
            'exclusive'                => (isset($rule['exclusive']) && $rule['exclusive']) ? 1 : 0,
            'title'                    => $title,
            'rule_type'                => isset($rule['rule_type']) ? sanitize_text_field($rule['rule_type']) : sanitize_text_field($rule['type']),
            'type'                     => sanitize_text_field($rule['type']),
            'priority'                 => isset($rule['priority']) ? (int)$rule['priority'] : 0,
            'options'                  => isset($rule['options']) ? $rule['options'] : array(),
            'conditions'               => array_values(isset($rule['conditions']) ? $rule['conditions'] : array()),
            'filters'                  => isset($rule['filters']) ? $rule['filters'] : array(),
            'limits'                   => array_values(isset($rule['limits']) ? $rule['limits'] : array()),
            'cart_adjustments'         => array_values(isset($rule['cart_adjustments']) ? $rule['cart_adjustments'] : array()),
            'product_adjustments'      => isset($rule['product_adjustments']) ? $rule['product_adjustments'] : array(),
            'sortable_blocks_priority' => isset($rule['sortable_blocks_priority']) ? $rule['sortable_blocks_priority'] : array(),
            'bulk_adjustments'         => isset($rule['bulk_adjustments']) ? $rule['bulk_adjustments'] : array(),
            'role_discounts'           => isset($rule['role_discounts']) ? $rule['role_discounts'] : array(),
            'get_products'             => isset($rule['get_products']) ? $rule['get_products'] : array(),
            'auto_add_products'        => $rule['auto_add_products'] ?? array(),
            'additional'               => isset($rule['additional']) ? $rule['additional'] : array(),
            'advertising'              => isset($rule['advertising']) ? $rule['advertising'] : array(),
            'condition_message'        => isset($rule['condition_message']) ? $rule['condition_message'] : array(),
        );

        if (isset($data['additional']['disabled_by_plugin'])) {
            unset($data['additional']['disabled_by_plugin']);
        }

        $data['id'] = empty($rule['id']) ? null : (int)$rule['id'];

        $rule = Rule::fromArray($data);

        // insert or update
        $id = $this->ruleRepository->storeRule($rule);

        if ($data['rule_type'] === RuleTypeEnum::PERSISTENT()->getValue()) {
            if ( $data['enabled'] === 1 ) {
                $this->persistentRuleRepository->addRule($this->persistentRuleRepository->getAddRuleData($id, $this->context), $id);
            } else {
                $this->persistentRuleRepository->removeRule($id);
            }
        } else {
            $this->persistentRuleRepository->removeRule($id);
        }

        return $id;
    }


    function sanitize_array_text_fields($array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = $this->sanitize_array_text_fields($value);
            } else {
                $value = sanitize_text_field($value);
            }
        }

        return $array;
    }

    public function ajax_remove_rule()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $ruleId = absint(wp_unslash($_POST['rule_id']));
        if ($ruleId) {
            $this->ruleRepository->markRuleAsDeleted($ruleId);
            $this->persistentRuleRepository->removeRule($ruleId);
        }
        wp_send_json_success();
    }

    public function ajax_reorder_rules()
    {
        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
        $items = $_POST['items'];

        foreach ($items as $item) {
            $id = (int)$item['id'];
            if ( ! empty($id)) {
                $this->ruleRepository->changeRulePriority($id, (int)$item['priority']);
            }
        }
    }

    public function ajax_subscriptions()
    {
        if (get_option('woocommerce_subscriptions_is_active', false)) {
            //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
            $query = htmlspecialchars($_POST['query'] ?? "", ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);

            $posts = wc_get_products(array(
                'type'  => array('subscription', 'subscription_variation', 'variable-subscription'),
                's'     => $query,
                'limit' => $this->limit
            ));

            $result = array();
            foreach ($posts as $post) {
                $result[] = array(
                    'id'   => $post->get_id(),
                    'text' => $post->get_name(),
                );
            }

            return $result;

        } else {
            return null;
        }
    }

    public function ajax_rebuild_onsale_list()
    {
        Factory::callStaticMethod("Shortcodes_OnSaleProducts", 'updateCachedProductsIds', $this->context);
    }

    public function ajax_rebuild_bogo_list()
    {
        Factory::callStaticMethod("Shortcodes_BogoProducts", 'updateCachedProductsIds', $this->context);
    }

    public function ajax_recalculate_persistence_cache()
    {
        global $wpdb;
        $table = $wpdb->prefix . Rule::TABLE_NAME;

        $this->persistentRuleRepository->truncate();
        $this->persistentRuleRepository->clearCacheInProductMetaData();

        $sql = "SELECT id FROM $table WHERE (rule_type = 'persistent' ) AND enabled = 1 AND deleted = 0";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        foreach ($wpdb->get_col($sql) as $id) {
            $this->persistentRuleRepository->addRule(
                $this->persistentRuleRepository->getAddRuleData($id, $this->context),
                $id
            );
        }
    }

    public function ajax_start_partial_recalculate_persistence_cache()
    {
        global $wpdb;
        $table = $wpdb->prefix . Rule::TABLE_NAME;

        $this->persistentRuleRepository->truncate();
        $this->persistentRuleRepository->clearCacheInProductMetaData();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $sql = "SELECT COUNT(*) FROM $table WHERE (rule_type = 'persistent' ) AND enabled = 1 AND deleted = 0";
        // phpcs:ignore WordPress.DB
        $totalCount = (int)($wpdb->get_var($sql));
        CacheHelper::flush();
        wp_send_json_success(
            [
                'count' => $totalCount,
            ]
        );
    }

    public function ajax_partial_recalculate_persistence_cache()
    {
        global $wpdb;
        $table = $wpdb->prefix . Rule::TABLE_NAME;
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
        $from = (absint(wp_unslash($_REQUEST['from'])) ?? 0);
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
        $count = (absint(wp_unslash($_REQUEST['count'])) ?? 0);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $sql = "SELECT id FROM $table WHERE (rule_type = 'persistent' ) AND enabled = 1 AND deleted = 0 LIMIT $from, $count";
        // phpcs:ignore WordPress.DB
        $list = $wpdb->get_col($sql);

        if ( $wpdb->last_error ) {
            wp_send_json_error($wpdb->last_error);
        }

        foreach ($list as $id) {
            $this->persistentRuleRepository->addRule(
                $this->persistentRuleRepository->getAddRuleData($id, $this->context),
                $id
            );
        }

        wp_send_json_success(count($list));
    }

    public function ajax_start_partial_rebuild_bogo_list()
    {
        $this->start_partial_rebuild_list('Shortcodes_BogoProducts');
    }

    public function ajax_start_partial_rebuild_onsale_list()
    {
        $this->start_partial_rebuild_list('Shortcodes_OnSaleProducts');
    }

    public function ajax_partial_rebuild_bogo_list()
    {
        $this->partial_rebuild_list('Shortcodes_BogoProducts');
    }

    public function ajax_partial_rebuild_onsale_list()
    {
        $this->partial_rebuild_list('Shortcodes_OnSaleProducts');
    }

    public function ajax_admin_footer_text_rated()
    {
        $settings = $this->context->getSettings();
        $settings->set('admin_footer_text_rated', true);
        $settings->save();

        wp_send_json_success();
    }

    protected function start_partial_rebuild_list($name)
    {
        $rules = Factory::callStaticMethod(
            $name,
            'getActiveRules',
            $this->context
        );
        Factory::callStaticMethod(
            $name,
            'clearCache'
        );
        wp_send_json_success(
            [
                'count' => count($rules),
                'rules' => $rules
            ]
        );
    }

    protected function partial_rebuild_list($name)
    {
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $ruleId = sanitize_key(absint(wp_unslash($_REQUEST['ruleId']))) ?? null;

        if(!$ruleId) {
            wp_send_json_error('ruleId is required');
        }

        try {
            $ids = Factory::callStaticMethod(
                $name,
                'updateCachedProductsIdsByRuleId',
                $ruleId
            );
            wp_send_json_success([
                'count' => count($ids),
                'productIds' => $ids
            ]);
        }
        catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}
