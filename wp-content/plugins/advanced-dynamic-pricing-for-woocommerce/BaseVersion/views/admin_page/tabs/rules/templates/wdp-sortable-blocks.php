<?php
use ADP\BaseVersion\Includes\Helpers\Helpers;

defined('ABSPATH') or exit;
?>

<div class="wdp-sortable-blocks wdp-block-content">
    <div class="sortable-apply-mode-block" style="display: none;">
        <div class="wdp-column"></div>
        <div class="wdp-column" style="flex:20">
            <div style="width:400px">
                <label>
                    <?php esc_html_e('Role discounts and bulk discounts applied',
                        'advanced-dynamic-pricing-for-woocommerce'); ?>
                    <select class="sortable-apply-mode" name="rule[additional][sortable_apply_mode]"
                            style="width:150px; display: inline-block">
                        <option value="consistently"><?php esc_html_e('Sequentially',
                                'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                        <option value="min_price_between"><?php esc_html_e('Use min price',
                                'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                        <option value="max_price_between"><?php esc_html_e('Use max price',
                                'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                    </select>
                </label>
            </div>
        </div>
    </div>
    <!--            data-readonly="1" to prevent purge by "flushInputs"-->
    <div class="wdp-block wdp-role-discounts wdp-sortable-block" style="display: none;">
        <input data-readonly="1" type="hidden" class="priority_block_name"
                name="rule[sortable_blocks_priority][]" value="roles">
        <div class="wdp-column wdp-drag-handle">
            <span class="dashicons dashicons-menu"></span>
        </div>
        <div class="wdp-row">
            <div class="wdp-column wdp-column-help">
                <label><?php Helpers::ruleFilterLabel('Role discounts', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <p class="wdp-rule-help">
                <?php
                    echo sprintf(
                        wp_kses(
                                __('Choose a user role, which can get a discount, the discount type and amount.', 'advanced-dynamic-pricing-for-woocommerce')
                                .'<br><a href="%s" target="_blank">' .__('Read docs','advanced-dynamic-pricing-for-woocommerce') .'</a>',
                            array('br' => array(), 'a' => array('href' => array(), 'target' => array()), )
                        ),
                        esc_url('https://docs.algolplus.com/algol_pricing/rules/rules-sections/role-discounts/')
                    );
                    ?>
                </p>
            </div>
            <div class="wdp-wrapper wdp-column">
                <div class="wdp-role-discounts-container"></div>
                <div class="wdp-add-condition">
                    <button type="button" class="button add-role-discount"><?php esc_html_e('Add role discount',
                            'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                    <div>
                        <label class="dont-apply-bulk-if-roles-matched-check">
                            <input type="checkbox" name="rule[role_discounts][dont_apply_bulk_if_roles_matched]"
                                value="1">
                            <?php esc_html_e('Skip bulk rules if role rule was applied',
                                'advanced-dynamic-pricing-for-woocommerce'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wdp-block wdp-bulk-adjustments wdp-sortable-block" style="display: none;">
        <input data-readonly="1" type="hidden" class="priority_block_name"
                name="rule[sortable_blocks_priority][]" value="bulk-adjustments">
        <div class="wdp-column wdp-drag-handle">
            <span class="dashicons dashicons-menu"></span>
        </div>
        <div class="wdp-row">
            <div class="wdp-column wdp-column-help">
                <label><?php Helpers::ruleFilterLabel('Bulk mode', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <p class="wdp-rule-help">
                <?php
                    echo sprintf(
                        wp_kses(
                                __('Enter the discount amount based on the number of items in the cart. Put the product quantity in the range  and choose the type of bulk and discount.', 'advanced-dynamic-pricing-for-woocommerce')
                                .'<br><a href="%s" target="_blank">' .__('Read docs', 'advanced-dynamic-pricing-for-woocommerce') .'</a>',
                            array('br' => array(), 'a' =>array('href' => array(), 'target' => array()), )
                        ),
                        esc_url('https://docs.algolplus.com/algol_pricing/rules/rules-sections/bulk-discount/')
                    );
                    ?>
                </p>
            </div>
            <div class="wdp-wrapper wdp-column">
                <div class="wdp-row">
                    <span class="wdp-product-adjustments-type-value-note">
                        <?php
                            echo sprintf(
                                wp_kses(
                                    '<a href="%s" target="_blank">' .__('Please, read about difference between Tier and Bulk modes',
                                        'advanced-dynamic-pricing-for-woocommerce') .'</a>',
                                    array('a' => array('href' => array(), 'target' => array()), 'br' => array())
                                ),
                                esc_url('https://docs.algolplus.com/algol_pricing/rules/rules-sections/bulk-discount/#bulk-tier')
                            );
                        ?>
                    </span>
                </div>
                <div class="wdp-row">
                    <div class="smaller-width">
                        <div class="wdp-column">
                            <select name="rule[bulk_adjustments][type]" class="bulk-adjustment-type">
                                <option value="bulk"><?php esc_html_e('Bulk',
                                        'advanced-dynamic-pricing-for-woocommerce') ?></option>
                                <option value="tier"><?php esc_html_e('Tier',
                                        'advanced-dynamic-pricing-for-woocommerce') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="smaller-width-column">
                        <div class="wdp-column">
                            <select name="rule[bulk_adjustments][measurement]" class="bulk-measurement-type"></select>
                        </div>
                    </div>

                    <div class="wdp-column">
                        <select name="rule[bulk_adjustments][qty_based]" class="bulk-qty_based-type"></select>
                    </div>

                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="all" data-tip='<?php esc_attr_e( "bulk qty counts only for the products matched with the Product Filter", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="total_qty_in_cart" data-tip='<?php esc_attr_e( "bulk qty counts all products in the cart even if the products don’t match to the Product Filter", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="product_categories" data-tip='<?php esc_attr_e( "bulk qty counts by the each products from the selected category, e.g., separately for the Product A, separately for the Product B", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="product_selected_categories" data-tip='<?php esc_attr_e( "bulk qty counts all the products from the selected category, e.g., both Product A + Product B qty", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="selected_products" data-tip='<?php esc_attr_e( "bulk qty counts only for the selected products from this field", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="sets" data-tip='<?php esc_attr_e( "for cases when the Product filter qty > 1, or for multiple Product filters, the bulk qty will be counted only for the product sets", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="product" data-tip='<?php esc_attr_e( "bulk qty counts by each product separately", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="variation" data-tip='<?php esc_attr_e( "bulk qty counts by each variation separately, e.g., separately for the Green variation, and for the Red variation", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="cart_position" data-tip='<?php esc_attr_e( "bulk qty counts from the first range for every cart line separately", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
                    <span class="wdp-help-tip" style="margin-top:0.4rem; margin-left: 0.5rem; display: none;" data-qty-based="meta_data" data-tip='<?php esc_attr_e( "bulk qty counts separately for each product’s meta data", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>

                    <div class="wdp-column bulk-selected_categories-type">
                        <select multiple
                                data-list="product_categories"
                                data-field="autocomplete"
                                data-placeholder="<?php esc_attr_e("Select values",
                                    "advanced-dynamic-pricing-for-woocommerce") ?>"
                                name="rule[bulk_adjustments][selected_categories][]">
                        </select>
                    </div>

                    <div class="wdp-column bulk-selected_products-type">
                        <select multiple
                                data-list="products"
                                data-field="autocomplete"
                                data-placeholder="<?php esc_attr_e("Select values",
                                    "advanced-dynamic-pricing-for-woocommerce") ?>"
                                name="rule[bulk_adjustments][selected_products][]">
                        </select>
                    </div>

                    <div class="wdp-column">
                        <select name="rule[bulk_adjustments][discount_type]"
                                class="bulk-discount-type"></select>
                    </div>

                    <div class="wdp-column wdp-btn-remove wdp_bulk_adjustment_remove">
                        <div class="wdp-btn-remove-handle">
                            <span class="dashicons dashicons-no-alt"></span>
                        </div>
                    </div>
                </div>

                <div class="wdp-adjustment-ranges">
                    <div class="wdp-ranges wdp-sortable">
                        <div class="wdp-ranges-empty"><?php esc_html_e('No ranges',
                                'advanced-dynamic-pricing-for-woocommerce') ?></div>
                    </div>

                    <div class="wdp-add-condition">
                        <button type="button" class="button add-range"><?php esc_html_e('Add range',
                                'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                    </div>
                </div>

                <div class="wdp-bulk-adjustment-options">
                    <div class="wdp-column">
                        <label>
                            <?php esc_html_e('Bulk table message', 'advanced-dynamic-pricing-for-woocommerce') ?>
                            <input type="text" name="rule[bulk_adjustments][table_message]"
                                    class="bulk-table-message"
                                    placeholder="<?php esc_attr_e('If you leave this field empty, we will show default bulk description',
                                        'advanced-dynamic-pricing-for-woocommerce') ?>"/>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
