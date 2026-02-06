<?php

namespace ADP\BaseVersion\Includes\WC;

use ADP\BaseVersion\Includes\CartProcessor\CartCouponsProcessorMerge\MergeCoupon\ExternalWcCoupon;
use ADP\BaseVersion\Includes\WC\WcAdpMergedCoupon\InMemoryAdpMergedCouponStorage;
use ADP\BaseVersion\Includes\WC\WcAdpMergedCoupon\WcAdpMergedCoupon;

class WcAdpMergedCouponHelper
{
    public static function loadOfCoupon($wcCoupon)
    {
        if (!$wcCoupon instanceof \WC_Coupon) {
            return null;
        }

        $couponCode = $wcCoupon->get_code("edit");
        $storage = InMemoryAdpMergedCouponStorage::getInstance();
        $coupon = $storage->getByCodeOrNull($couponCode);

        if ($coupon === null) {
            $coupon = new WcAdpMergedCoupon($couponCode);
            $coupon->setParts([new ExternalWcCoupon($wcCoupon, [])]);
        }

        return $coupon;
    }

    public static function loadOfCouponCode($couponCode)
    {
        global $wp_filter;
        if (is_numeric($couponCode)) {
            $couponCode = (string)$couponCode;
        }

        if (!is_string($couponCode)) {
            return null;
        }

        if ( $couponCode === '' ) {
            return null;
        }

        $storage = InMemoryAdpMergedCouponStorage::getInstance();
        $coupon = $storage->getByCodeOrNull($couponCode);

        if ($coupon === null) {
            $coupon = new WcAdpMergedCoupon($couponCode);

            $oldHooks = $wp_filter['woocommerce_get_shop_coupon_data'];
            unset($wp_filter['woocommerce_get_shop_coupon_data']);
            $wcCoupon = new \WC_Coupon();
            $wp_filter['woocommerce_get_shop_coupon_data'] = $oldHooks;

            $wcCoupon->set_code($couponCode);
            if ($id = self::getCouponIdByCode($couponCode)) {
                $wcCoupon->set_id($id);
                $dataStore = \WC_Data_Store::load('coupon');
                $dataStore->read($wcCoupon);
            }

            $coupon->setParts([new ExternalWcCoupon($wcCoupon, [])]);
            self::store($coupon);
        }

        return $coupon;
    }

    /**
     * Wrapper method for `wc_get_coupon_id_by_code` method.
     * If you get "virtual" coupon code which does not exist in DB, the query repeats over and over again with every call,
     * because we miss the cache.
     * So, we update the cache manually.
     *
     * @param string $couponCode
     * @return int
     */
    private static function getCouponIdByCode(string $couponCode): int
    {
        $id = wc_get_coupon_id_by_code($couponCode);

        if ($id === 0) {
            $cacheKey = \WC_Cache_Helper::get_cache_prefix('coupons') . 'coupon_id_from_code_' . md5($couponCode);
            wp_cache_set($cacheKey, [], 'coupons');
        }

        return $id;
    }

    public static function store(WcAdpMergedCoupon $wcAdpMergedCoupon)
    {
        InMemoryAdpMergedCouponStorage::getInstance()->insertOrUpdate($wcAdpMergedCoupon);
    }
}
