<?php

namespace ADP\BaseVersion\Includes\Compatibility;

defined('ABSPATH') or exit;

/**
 * Plugin Name: Points and Rewards for WooCommerce
 * Author: WP Swings
 *
 * @see https://wordpress.org/plugins/points-and-rewards-for-woocommerce/
 */
class PointsAndRewardsForWoocommerceCmp
{
    /**
     * @return bool
     */
    public function isActive()
    {
        return defined("REWARDEEM_WOOCOMMERCE_POINTS_REWARDS_VERSION");
    }

    public function isPointsAndRewardsCoupon($code, $coupon)
    {
        //phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
        return $code === __('Cart Discount', 'points-and-rewards-for-woocommerce');
    }

    public function getPointsAndRewardsCoupon($code, $coupon)
    {
        //phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
        return __('Cart Discount', 'points-and-rewards-for-woocommerce');
    }
}
