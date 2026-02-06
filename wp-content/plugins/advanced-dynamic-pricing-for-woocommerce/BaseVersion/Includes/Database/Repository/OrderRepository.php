<?php

namespace ADP\BaseVersion\Includes\Database\Repository;

use ADP\BaseVersion\Includes\Database\Models\Order;
use ADP\BaseVersion\Includes\Database\Models\Rule;

class OrderRepository implements OrderRepositoryInterface {
    public function addOrderStats($order)
    {
        global $wpdb;

        $table = $wpdb->prefix . Order::TABLE_NAME;
        $data = $order->getData();

        $data = array_merge(array(
            'order_id'         => 0,
            'rule_id'          => 0,
            'amount'           => 0.0,
            'extra'            => 0,
            'shipping'         => 0,
            'is_free_shipping' => 0,
            'gifted_amount'    => 0.0,
            'gifted_qty'       => 0,
            'date'             => current_time('mysql'),
        ), $data);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->replace($table, $data);
    }

    /**
     * @param $orderId
     *
     * @return array<int, array{order: Order, rule: Rule}>
     */
    public function getAppliedRulesForOrder($orderId)
    {
        global $wpdb;

        $table_order_rules = $wpdb->prefix . Order::TABLE_NAME;
        //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $wpdb->prepare("SELECT $table_order_rules.* FROM $table_order_rules WHERE order_id = %d ORDER BY amount DESC", $orderId);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results($sql, ARRAY_A);

        if ( count($rows) === 0 ) {
            return [];
        }

        $ruleIds = [];
        $orderRules = array_map(function ($orderRule) use (&$ruleIds) {
            $ruleId = (int)($orderRule["rule_id"]);
            $ruleIds[] = $ruleId;

            return [
                'order' => Order::fromArray($orderRule),
                'rule' => $ruleId
            ];
        }, $rows);
        $rules = [];
        foreach ((new RuleRepository())->getRules(["id" => $ruleIds]) as $rule) {
            $rules[$rule->id] = $rule;
        }
        $orderRules = array_map(function ($orderRule) use (&$rules) {
            return [
                'order' => $orderRule['order'],
                'rule' => $rules[$orderRule["rule"]] ?? null,
            ];
        }, $orderRules);

        return $orderRules;
    }

    public function getCountOfRuleUsages($ruleId)
    {
        global $wpdb;

        $tableOrderRules = $wpdb->prefix . Order::TABLE_NAME;
        //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $wpdb->prepare("SELECT COUNT(*) FROM {$tableOrderRules} WHERE rule_id = %d", $ruleId);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $value = $wpdb->get_var($sql);

        return (integer)$value;
    }

    public function getCountOfRuleUsagesPerCustomer($ruleId, $customerId)
    {
        global $wpdb;

        $tableOrderRules = $wpdb->prefix . Order::TABLE_NAME;

        $customerOrdersIds = wc_get_orders(array(
            'return'      => 'ids',
            'numberposts' => -1,
            'customer_id' => $customerId,
            'post_type'   => wc_get_order_types(),
            'post_status' => array_keys(wc_get_order_statuses()),
        ));
        if (empty($customerOrdersIds)) {
            return 0;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $value = $wpdb->get_var("SELECT COUNT(*) FROM {$tableOrderRules} WHERE rule_id = $ruleId  AND order_id IN (" . implode(',', $customerOrdersIds) . ")");

        return (integer)$value;
    }

    /**
     * @param int $ruleId
     * @param array $data
     * @return int
     */
    public function getCountOfRuleUsagesPerCustomerData(int $ruleId, array $data): int
    {
        global $wpdb;

        $tableOrderRules = $wpdb->prefix . Order::TABLE_NAME;

        // must ignore draft orders created by blocks-based cart
        $existing_orders_statuses = wc_get_order_statuses();
        if(isset($existing_orders_statuses['wc-checkout-draft']))
            unset($existing_orders_statuses['wc-checkout-draft']);

        $customerOrdersIds = [];

        //email ?
        if( !empty($data['customer_email']) ) {
            $args = array(
                'billing_email' => $data['customer_email'],
                'return'      => 'ids',
                'numberposts' => -1,
                'post_type'   => wc_get_order_types(),
                'post_status' => array_keys($existing_orders_statuses),
            );
            $customerOrdersIds = wc_get_orders($args);
        }

        //got full address?
        $args = array_filter( array(
            'billing_first_name' => $data['customer_first_name'] ?? null,
            'billing_last_name' => $data['customer_last_name'] ?? null,
            'billing_address_1' => $data['customer_address_1'] ?? null,
            'billing_city' => $data['customer_city'] ?? null,
            'billing_state' => $data['customer_state'] ?? null,
            'billing_postcode' => $data['customer_zip'] ?? null,
        ));
        if( count($args) == 6) {
            $args = array_merge( $args, array(
            'billing_address_2' => $data['customer_address_2'] ?? null,
            'return'      => 'ids',
            'numberposts' => -1,
            'post_type'   => wc_get_order_types(),
            'post_status' => array_keys($existing_orders_statuses),
            ));
            $customerOrdersIds = array_merge( $customerOrdersIds, wc_get_orders($args) );
        }

        if (empty($customerOrdersIds)) {
            return 0;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $value = $wpdb->get_var("SELECT COUNT(*) FROM {$tableOrderRules} WHERE rule_id = $ruleId  AND order_id IN (" . implode(',', $customerOrdersIds) . ")");

        return (integer)$value;
    }
}
