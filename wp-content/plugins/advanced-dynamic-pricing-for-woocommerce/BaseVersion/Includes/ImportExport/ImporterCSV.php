<?php

namespace ADP\BaseVersion\Includes\ImportExport;

use ADP\BaseVersion\Includes\Database\Models\Rule;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepository;
use ADP\BaseVersion\Includes\Helpers\Helpers;
use ADP\Factory;
use ADP\BaseVersion\Includes\Enums\RuleTypeEnum;

class ImporterCSV {

    public static $warnings = array();

    public static function importRules($rules, $importOption)
    {
        if ( ! empty(self::$warnings)) {
            return;
        }
        $ruleRepository = new RuleRepository();
        $ruleStorage    = Factory::get("Database_RuleStorage");
        if ($importOption === 'reset') {
            self::$warnings[] = __('All rules were deleted', 'advanced-dynamic-pricing-for-woocommerce');
            $ruleRepository->deleteAllRules();
        }

        if( apply_filters("adp_import_merge_rules", true) ) {
            $newRulesHash = array();
            foreach ($rules as $rule) {
                foreach ($rule as $key => $value) {
                    if ($key !== 'filter') {
                        $filteredData[$key] = $value;
                    }
                }
                $newRulesHash[] = array (md5(json_encode($filteredData)), $rule);
            }
            usort($newRulesHash, function($a, $b) {
                return strcmp($a[0], $b[0]);
            });

            $newRules= array();
            $previosHash = null;
            for($i = 0; $i < count($newRulesHash); $i++) {
                if($newRulesHash[$i][0] == $previosHash){
                    $newRules[count($newRules)-1]['filter']['value'][0] .= '|' . $newRulesHash[$i][1]['filter']['value'][0];
                }else {
                    $newRules[] = $newRulesHash[$i][1];
                    $previosHash = $newRulesHash[$i][0];
                }
            }
        } else //don't merge rules during import
            $newRules = $rules;
        self::createRules($newRules);

        $ruleObjects = array();
        foreach ($newRules as $pos=>$rawRule) {
            /** Do not allow importing data that does not fit. E.g.: collections */
            if ( ! isset($rawRule['rule_type'])) {
                continue;
            }

            if ($rawRule[KeyKeeperDB::RULE_TYPE] === RuleTypeEnum::PERSISTENT()->getValue()) {
                $rulesCol = $ruleStorage->buildPersistentRules(array(Rule::fromArray($rawRule)));
            } else {
                $rulesCol = $ruleStorage->buildRules(array(Rule::fromArray($rawRule)));
            }

            if ($rulesCol->getRules()) {
                $ruleObjects[] = $rulesCol->getFirst();
            }
        }

        if (count($ruleObjects) === 0) {
            return;
        }
        self::$warnings[] = sprintf(
            /* translators: Message about the imported rule*/
            _n(
                '%s rule were imported',
                '%s  rules were imported',
                count($ruleObjects),
                'advanced-dynamic-pricing-for-woocommerce'
            ),
            count($ruleObjects)
        );
        $exporter         = Factory::get("ImportExport_Exporter");
        $ruleCounter      = $ruleRepository->getRulesCount() + 1;
        foreach ($ruleObjects as &$ruleObject) {
            $rule        = $exporter->convertRule($ruleObject);
            if ($importOption == 'update')
                self::setId($rule, $ruleRepository);
            $ruleObj     = Rule::fromArray($rule);
            $ruleRepository->storeRule($ruleObj);
        }
    }

    protected static function convertSupportedValueToType($type){
        if (in_array(strtolower($type), array('product', 'category', 'sku', 'products'))) {
            $type = strtolower($type);
            $type = ('category' == $type)? 'categories': $type;
            if ($type == 'product' || $type == 'products') {
                $type .= ($type == 'products') ? '' : 's';
            } else {
                $type = 'product_' . $type;
            }

            return $type;
        } elseif (in_array(strtolower($type), array('fixeddiscount', 'fixedcost', 'percentage', 'amount'))) {
            $type = strtolower($type);
            if ($type == 'fixedcost') {
                $type = 'price__fixed';
            } else {
                $type = str_replace('discount', '', $type);
                $type = 'discount__' . (($type == 'fixed') ? 'amount' : $type);
            }

            return $type;
        } else {
            self::$warnings[] = __('Unknown type ', 'advanced-dynamic-pricing-for-woocommerce') . $type;

            return '';
        }
    }

    public static function prepareCSV($file){
        $separator = apply_filters("adp_import_rules_separator", ",");
        $enclosure = apply_filters("adp_import_rules_enclosure", "\"");
        $escape = apply_filters("adp_import_rules_escape", "\\");

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        if (($handle = fopen($file, "r")) !== false) {
            $filterType = '';
            if (($data = fgetcsv($handle, null, $separator, $enclosure, $escape)) !== false && is_array($data)) {
                if (str_contains(strtolower($data[0]), 'type')) {
                    $filterType = self::convertSupportedValueToType($data[1]);
                }
            }
            $discountType = '';
            if (($data = fgetcsv($handle, null, $separator, $enclosure, $escape)) !== false && is_array($data)) {
                if (str_contains(strtolower($data[0]), 'type')) {
                    $discountType = self::convertSupportedValueToType($data[1]);
                }
            }
            $ruleBlocksSet = array();
            while (($data = fgetcsv($handle, null, $separator, $enclosure, $escape)) !== false) {
                $data = array_map('strtolower', $data);
                if (in_array($data[0], array('filter', 'discountedprice', 'fromqty', 'toqty', 'role'))) {
                    foreach ($data as $name) {
                        if (empty($name)) {
                            break;
                        }
                        $ruleBlocksSet[] = $name;
                    }
                    break;
                }
            }
            if (empty($ruleBlocksSet)) {
                self::$warnings[] = __(
                    'File must contain one rule or more.',
                    'advanced-dynamic-pricing-for-woocommerce'
                );
            }
            if ( ! empty(self::$warnings)) {
                return array();
            }
            $ruleBlocksSetLength = count($ruleBlocksSet);
            $rules               = array();
            $newRule             = array();
            while (($data = fgetcsv($handle, null, $separator, $enclosure, $escape)) !== false) {
                if (empty($data[0])) {
                    for ($setIter = 1; $setIter < $ruleBlocksSetLength; $setIter++) {
                        if (empty($data[$setIter])) {
                            continue;
                        }
                        $newRule[$ruleBlocksSet[$setIter]]['value'][] = $data[$setIter];
                    }
                } else {
                    if ( ! empty($newRule)) {
                        $rules[] = $newRule;
                    }
                    for ($setIter = 0; $setIter < $ruleBlocksSetLength; $setIter++) {
                        if (empty($data[$setIter]) && ! isset($data[$setIter])) {
                            continue;
                        }
                        $type = '';
                        if ($ruleBlocksSet[$setIter] == 'filter') {
                            $type = $filterType;
                        } elseif ($ruleBlocksSet[$setIter] == 'discountedprice') {
                            $type = $discountType;
                        }
                        $newRule[$ruleBlocksSet[$setIter]] = array(
                            'value' => empty($data[$setIter]) ? null : array($data[$setIter]),
                            'type'  => $type,
                        );
                    }
                }
            }
            $rules[] = $newRule;
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            fclose($handle);

            return $rules;
        }
        self::$warnings[] = __('Can\'t open file.', 'advanced-dynamic-pricing-for-woocommerce');

        return array();
    }

    protected static function setRuleTitle(&$rule, $filter_name, $pos){
        $rule['title'] = array();
        if($filter_name)
            $rule['title'][] = $filter_name;
        if ( ! empty($rule['bulk_adjustments'])) {
            if( $filter_name )
                $rule['title'][] = "-";
            $rule['title'][] = __('Bulk', 'advanced-dynamic-pricing-for-woocommerce');
        }
        if ( ! empty($rule['conditions'])) {
            $rule['title'][] = __('for Roles', 'advanced-dynamic-pricing-for-woocommerce');
        }
        if ( ! empty($rule['role_discounts'])) {
            if( $filter_name ) {
                $rule['title'][] = "-" . __('Roles', 'advanced-dynamic-pricing-for-woocommerce');
            } else {
                $rule['title'][] = __('Roles', 'advanced-dynamic-pricing-for-woocommerce');
            }
        }
        if (empty($rule['role_discounts']) && empty($rule['bulk_adjustments'])) {
            //nothing!just product/sku $rule['title'][] = __('Discount', 'advanced-dynamic-pricing-for-woocommerce');
        }
        if( empty($rule['title']) )
            $rule['title'][] = __('Imported Rule', 'advanced-dynamic-pricing-for-woocommerce') . ' #' . $pos;

        $char = array_sum(array_map(fn($str) => mb_strlen($str, 'UTF-8'), $rule['title'])) + count($rule['title']) - 1;
        if ($char > 20) {
            $excess = $char - 20;
            $rule['title'][0] = mb_substr($rule['title'][0], 0, -$excess, 'UTF-8');
        }

        $rule['title'] = join(" ", $rule['title']);
        $rule['title'] = apply_filters("adp_import_rules_rule_title", $rule['title'], $rule, $pos);
    }

    protected static function setId(&$rule, &$ruleRepository){
        $rulesLikeFilter = $ruleRepository->getRules(array('filter_types' => array($rule['filters'][0]['type'], 'active_only' => true)));
        foreach ($rulesLikeFilter as $ruleLikeFilter) {
            if (isset($ruleLikeFilter->filters[0])
                && $rule['filters'][0]['type'] == $ruleLikeFilter->filters[0]['type']
                && count($ruleLikeFilter->filters) === 1
                && empty(
                array_diff(
                    $ruleLikeFilter->filters[0]['value'],
                    $rule['filters'][0]['value']
                )
                )) {
                $rule['id']       = $ruleLikeFilter->id;
                $rule['priority'] = $ruleLikeFilter->priority;
                $rule['title']    = $ruleLikeFilter->title;
                break;
            }
        }
    }

    private static function createRules(&$rules){
        foreach ($rules as $pos=>&$rule) {
            $filter_name  = $rule['filter']['value'][0]; // required only to make correct rule title!
            $rule['rule_type'] = 'common';
            $rule['enabled']   = 'on';
            $rule['filters']   = array(
                array(
                    'qty'    => 1,
                    'type'   => $rule['filter']['type'],
                    'method' => 'in_list',
                    'value'  => self::convertProductNameToId($rule['filter']['type'], explode('|', $rule['filter']['value'][0])),
                ),
            );
            if (isset($rule['filters'][0]['value'][0]) && $rule['filters'][0]['value'][0] == 'undefined') {
                self::$warnings[] = __(
                                        'Can not find ',
                                        'advanced-dynamic-pricing-for-woocommerce'
                                    ) . $rule['filter']['type'] . ' ' . $rule['filter']['value'][0];
                $rule             = null;
                continue;
            }
            if (isset($rule['fromqty']['value']) AND is_array($rule['fromqty']['value'])) {
                $rule['bulk_adjustments'] = array(
                    'type'              => 'bulk',
                    'discount_type'     => $rule['discountedprice']['type'],
                    'ranges'            => array(),
                    "qty_based"         => 'not',
                    'split_discount_by' => 'cost',
                    'total'             => array(
                        'type'  => $rule['discountedprice']['type'],
                        'value' => 0,
                    ),
                );
                foreach (
                    array_map(
                        null,
                        $rule['fromqty']['value'],
                        $rule['toqty']['value'] ?? [],
                        $rule['discountedprice']['value']
                    ) as $range
                ) {
                    $rule['bulk_adjustments']['ranges'][] = array(
                        'from'  => $range[0],
                        'to'    => $range[1],
                        'value' => $range[2],
                    );
                }
            }
            if (is_array($rule['role']['value'])) {
                if(is_array($rule['role']['value'])){
                    $role = array();
                    foreach($rule['role']['value'] as $rowRole){
                        $role = array_merge($role, explode('|', $rowRole));
                    }
                    $rule['role']['value'] = $role;
                }else{
                    $rule['role']['value'] = explode('|', $rule['role']['value']);
                }
                if (isset($rule['fromqty']['value']) AND is_array($rule['fromqty']['value'])) {
                    $rule['conditions'] = array(
                        array(
                            'type'    => 'customer_role',
                            'options' => array(
                                'comparison_list_method' => 'in_list',
                                'comparison_list'        => $rule['role']['value'],
                            ),
                        ),
                    );
                    $rule['additional'] = array(
                        'conditions_relationship' => 'and',
                    );
                } else {
                    $rows = array();
                    if(count($rule['discountedprice']['value']) ==  count($rule['role']['value']) ) {
                        for($i=0;$i<count($rule['discountedprice']['value']);$i++ )
                            $rows[] = array(
                                'discount_type'  => $rule['discountedprice']['type'],
                                'discount_value' => $rule['discountedprice']['value'][$i],
                                'roles'          => array($rule['role']['value'][$i]),
                            );
                    } else {
                        // one discount for all roles
                        $rows[] = array(
                                'discount_type'  => $rule['discountedprice']['type'],
                                'discount_value' => $rule['discountedprice']['value'][0],
                                'roles'          => $rule['role']['value'],
                        );
                    }
                    $rule['role_discounts'] = array('rows' =>$rows);
                }
            }
            if ( ! isset($rule['bulk_adjustments']) && ! isset($rule['role_discounts'])) {
                if ( ! is_array($rule['discountedprice']['value'])) {
                    throw new \RuntimeException('Discount price must be set.');
                }
                $rule['product_adjustments'] = array(
                    'type'              => 'total',
                    'split_discount_by' => 'cost',
                    'total'             => array(
                        'type'  => $rule['discountedprice']['type'],
                        'value' => $rule['discountedprice']['value'][0],
                    ),
                );
            }
            self::setRuleTitle($rule,$filter_name,$pos+1);
            unset($rule['discountedprice'], $rule['fromqty'], $rule['toqty'], $rule['filter'], $rule['role']);
        }
    }

    protected static function convertProductNameToId($type, $items)
    {
        if (empty($items)) {
            return $items;
        }
        foreach ($items as &$value) {
            if ('products' === $type) {
                $value = Helpers::getProductId($value);
            } elseif ('product_categories' === $type) {
                if(str_contains($value, '>')){
                    $parent = '';
                    foreach(explode('>', $value) as $category){
                        $parent = get_terms(array(
                            'taxonomy' => 'product_cat',
                            'parent' => $parent,
                            'name' => $category
                        ));
                        if(is_array($parent) && $parent[0] instanceof \WP_Term){
                            $parent = $parent[0]->term_id;
                        }else{
                            $parent = 0;
                            break;
                        }
                    }
                    $value = $parent;
                }
                $value = Helpers::getCategoryId($value);
            } elseif ('product_tag' === $type) {
                $value = Helpers::getTagId($value);
            } elseif ('product_attribute' === $type) {
                $value = Helpers::getAttributeId($value);
            }

            if (empty($value)) {
                $value = 'undefined';
            }
        }

        return $items;
    }
}
