<?php

use ADP\BaseVersion\Includes\Enums\RuleTypeEnum;
use ADP\BaseVersion\Includes\Helpers\Helpers;

defined('ABSPATH') or exit;

$isCouponEnabled = wc_coupons_enabled();

$pleaseEnableText = __("Please, enable coupons to use price replacements.", 'advanced-dynamic-pricing-for-woocommerce');

?>

<form class="wdp-ruleitem wdp-ruleitem-{rule_type} postbox closed not-initialized" data-index="{r}">
    <input type="hidden" name="action" value="wdp_ajax">
    <input type="hidden" name="method" value="save_rule">
    <input type="hidden" name="rule[priority]" value="{p}" class="rule-priority"/>
    <input type="hidden" value="" name="rule[id]" class="rule-id">
    <input type="hidden" name="rule[type]" value="persistent" class="rule-type">
    <input type="hidden" name="rule[exclusive]" value="0">

    <input type="hidden" name="rule[additional][blocks][productFilters][isOpen]" value="0">
    <input type="hidden" name="rule[additional][blocks][productDiscounts][isOpen]" value="0">
    <input type="hidden" name="rule[additional][blocks][roleDiscounts][isOpen]" value="0">
    <input type="hidden" name="rule[additional][blocks][bulkDiscounts][isOpen]" value="0">
    <input type="hidden" name="rule[additional][blocks][freeProducts][isOpen]" value="0">
    <input type="hidden" name="rule[additional][blocks][autoAddToCart][isOpen]" value="0">
    <input type="hidden" name="rule[additional][blocks][advertising][isOpen]" value="0">
    <input type="hidden" name="rule[additional][blocks][cartAdjustments][isOpen]" value="0">
    <input type="hidden" name="rule[additional][blocks][conditions][isOpen]" value="0">
    <input type="hidden" name="rule[additional][blocks][limits][isOpen]" value="0">

    <div class="wdp-ruleitem-row hndle ui-sortable-handle">
        <div class="rule-type-bage">
            <input type="checkbox" class="bulk-action-mark">
        </div>

        <h2>
            <div class="wdp-column wdp-field-enabled">
                <select name="rule[enabled]" data-role="flipswitch" data-mini="true">
                    <option value="off">Off</option>
                    <option value="on" selected>On</option>
                </select>
            </div>
            <div class="wdp-disabled-automatically-prefix">[disabled automatically]</div>
            <span data-wdp-title></span>
        </h2>

        <div class="rule-date-from-to">
            <span><?php esc_html_e('From', 'advanced-dynamic-pricing-for-woocommerce') ?></span>
            <input style="max-width: 100px;" class="datepicker" name="rule[additional][date_from]" type="text">
            <span><?php esc_html_e('To', 'advanced-dynamic-pricing-for-woocommerce') ?></span>
            <input style="max-width: 100px;" class="datepicker" name="rule[additional][date_to]" type="text" placeholder="<?php esc_html_e('include', 'advanced-dynamic-pricing-for-woocommerce') ?>">
            <span class="wdp-help-tip" data-tip='<?php esc_attr_e( "The sale will start at 00:00:00 of \"From\" date and end at 23:59:59 of \"To\" date.", "advanced-dynamic-pricing-for-woocommerce" ); ?>'></span>
        </div>

        <div class="rule-type">
            <span><?php esc_html_e('Rule type', 'advanced-dynamic-pricing-for-woocommerce') ?></span>
            <select name="rule[rule_type]">
                <option value="<?php echo esc_attr(RuleTypeEnum::PERSISTENT()->getValue()) ?>">
                    <?php esc_html_e('Product only', 'advanced-dynamic-pricing-for-woocommerce') ?>
                </option>
                <option value="<?php echo esc_attr(RuleTypeEnum::COMMON()->getValue()) ?>">
                    <?php esc_html_e('Common', 'advanced-dynamic-pricing-for-woocommerce') ?>
                </option>
            </select>
        </div>

        <div class="rule-id-badge wdp-list-item-id-badge">
            <label><?php esc_html_e('#', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
            <label class="rule-id"></label>
        </div>

        <button type="button" class="button-link wdp_remove_rule">
            <span class="screen-reader-text"><?php esc_html_e('Delete', 'advanced-dynamic-pricing-for-woocommerce') ?>
                </span>
            <span class="dashicons dashicons-no-alt"
                  title="<?php esc_html_e('Delete', 'advanced-dynamic-pricing-for-woocommerce') ?>"></span>
        </button>

        <button type="button" class="button-link wdp_copy_rule">
            <span class="screen-reader-text"><?php esc_html_e('Clone', 'advanced-dynamic-pricing-for-woocommerce') ?>
                </span>
            <span class="dashicons dashicons-admin-page"
                  title="<?php esc_html_e('Clone', 'advanced-dynamic-pricing-for-woocommerce') ?>"></span>
        </button>

        <button type="button" class="handlediv" aria-expanded="false">
            <span class="screen-reader-text"><?php esc_html_e('Expand', 'advanced-dynamic-pricing-for-woocommerce') ?></span>
            <span class="toggle-indicator" aria-hidden="true"
                title="<?php esc_html_e('Expand', 'advanced-dynamic-pricing-for-woocommerce') ?>"></span>
        </button>
    </div>
    <!-- <div style="clear: both;"></div> -->
    <div class="inside">
        <div class="wdp-row wdp-options">
            <div class="wdp-row wdp-column wdp-field-title">
                <label><?php esc_html_e('Title', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <input class="wdp-column wdp-title" type="text" name="rule[title]">
            </div>
        </div>

        <div class="wdp-row wdp-options">
            <div class="buffer"></div>
            <div class="replace-adjustments">
                <div style="float: right" <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $isCouponEnabled ? "" : "title='{$pleaseEnableText}'"; ?>>
                    <label>
                        <input type="checkbox"
                               name="rule[additional][is_replace]">
                        <?php esc_html_e("Don't change product price and show discount as coupon",
                            'advanced-dynamic-pricing-for-woocommerce') ?>
                    </label>
                    <input type="text" name="rule[additional][replace_name]" style="width: 110px"
                           placeholder="<?php esc_attr_e("coupon_name", 'advanced-dynamic-pricing-for-woocommerce') ?>"
                    >

                </div>
            </div>
        </div>

        <div class="wdp-block wdp-filter-block wdp-row" style="display: none;">
            <div class="wdp-column wdp-column-help">
                <label><?php Helpers::ruleFilterLabel('Filter by products', 'advanced-dynamic-pricing-for-woocommerce'); ?></label><br>
                <label class="wdp-filter-warning" style="color:red"><?php esc_html_e('If you add many lines to this section – you will create product bundle',
                        'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <p class="wdp-rule-help">
                <?php
                    echo sprintf(
                        wp_kses(
                            __('Select what to discount: any products, certain products, collections, categories, category slugs, attributes, custom attributes, tags, SKUs, custom fields, sellers.', 'advanced-dynamic-pricing-for-woocommerce')
                            .'<br><a href="%s">' .__('Read docs', 'advanced-dynamic-pricing-for-woocommerce') .'</a>',
                            array('br' => array(), 'a' => array('href' => array()))
                        ),
                        esc_url('https://docs.algolplus.com/algol_pricing/rules/rules-sections/product-filters/')
                    );
                ?>
                </p>
            </div>
            <div class="wdp-wrapper wdp_product_filter wdp-column">
                <div class="wdp-product-filter-container"></div>
            </div>
        </div>

        <div class="wdp-block wdp-product-adjustments wdp-row" style="display: none;">
            <div class="wdp-column wdp-column-help">
                <label><?php Helpers::ruleFilterLabel('Product discounts', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <p class="wdp-rule-help">
                <?php
                        echo sprintf(
                            wp_kses(
                                    __('Select the discount type and enter its value.', 'advanced-dynamic-pricing-for-woocommerce')
                                    .'<br><a href="%s">' .__('Read docs', 'advanced-dynamic-pricing-for-woocommerce') .'</a>',
                                array('a' => array('href' => array()), 'br' => array())
                            ),
                            esc_url('https://docs.algolplus.com/algol_pricing/rules/rules-sections/product-discounts/')
                        );
                    ?>
                </p>
            </div>
            <div class="wdp-wrapper wdp-column">
                <div class="wdp-row">
                    <div class="wdp-column">
                        <label>
                            <input type="radio" name="rule[product_adjustments][type]"
                                   class="adjustment-mode adjustment-mode-total"
                                   data-readonly="1"
                                   value="total"/><?php esc_html_e('Total', 'advanced-dynamic-pricing-for-woocommerce') ?>
                        </label>
                    </div>

                    <div class="wdp-column wdp-btn-remove wdp_product_adjustment_remove">
                        <div class="wdp-btn-remove-handle">
                            <span class="dashicons dashicons-no-alt"></span>
                        </div>
                    </div>
                </div>

                <div class="wdp-row" data-show-if="total">
                    <div class="wdp-column">
                        <select name="rule[product_adjustments][total][type]" class="adjustment-total-type">
                            <option value="discount__amount"><?php esc_html_e('Fixed discount',
                                    'advanced-dynamic-pricing-for-woocommerce') ?></option>
                            <option value="discount__percentage"><?php esc_html_e('Percentage discount',
                                    'advanced-dynamic-pricing-for-woocommerce') ?></option>
                            <option value="price__fixed"><?php esc_html_e('Fixed price',
                                    'advanced-dynamic-pricing-for-woocommerce') ?></option>
                        </select>
                    </div>

                    <div class="wdp-column">
                        <input name="rule[product_adjustments][total][value]" class="adjustment-total-value"
                               type="number" placeholder="0.00" min="0" step="any">
                        <span class="wdp-product-adjustments-total-value-note">
                            <?php esc_html_e('To increase the price, make a negative discount', 'advanced-dynamic-pricing-for-woocommerce') ?>
                        </span>
                    </div>
                </div>

                <div class="wdp-product-adjustments-split-container" data-show-if="split"></div>

                <div class="wdp-product-adjustments-options">
                    <div>
                        <div style="display: inline-block;margin: 0 10px 0 0;">
                            <label>
                                <?php esc_html_e('Limit discount to amount', 'advanced-dynamic-pricing-for-woocommerce') ?>
                                <input style="display: inline-block; width: 200px;"
                                       name="rule[product_adjustments][max_discount_sum]" type="number"
                                       class="product-adjustments-max-discount" placeholder="<?php esc_html_e('Unlimited', 'advanced-dynamic-pricing-for-woocommerce') ?>" min="0" step="any"/>
                            </label>
                        </div>

                        <div style="display: none;margin: 0 10px;width: 20rem;">
                            <div class="split-discount-controls">
                                <label>
                                    <?php esc_html_e('Split discount by:', 'advanced-dynamic-pricing-for-woocommerce') ?>
                                    <select name="rule[product_adjustments][split_discount_by]"
                                            style="display: inline-block; width: 200px;"
                                            class="adjustment-split-discount-type">
                                        <option class="split-discount-by-cost" value="cost"><?php esc_html_e('Item cost',
                                                'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                                    </select>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <?php include WC_ADP_PLUGIN_VIEWS_PATH."/admin_page/tabs/rules/templates/wdp-sortable-blocks.php" ?>

        <div class="wdp-block wdp-get-products-block wdp-get-products-options wdp-row" style="display: none;">
            <div class="wdp-column wdp-column-help">
                <label><?php Helpers::ruleFilterLabel('Free products', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <p class="wdp-rule-help">
                <?php
                    echo sprintf(
                        wp_kses(
                                __('Select products that would be gifted to the customers.', 'advanced-dynamic-pricing-for-woocommerce')
                                .'<br><a href="%s">' .__('Read docs', 'advanced-dynamic-pricing-for-woocommerce') .'</a>',
                            array('br' => array(), 'a' => array('href' => array()), )
                        ),
                        esc_url('https://docs.algolplus.com/algol_pricing/rules/rules-sections/free-products/')
                    );
                    ?>
                </p>
            </div>
            <div class="wdp-column">
                <div class="wdp-row wdp-get-products-repeat">
                    <div class="wdp-column">
                        <label><?php esc_html_e('Can be applied ', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>

                        <select name="rule[get_products][repeat]">
                            <optgroup label="<?php esc_attr_e('Can be applied', 'advanced-dynamic-pricing-for-woocommerce') ?>">
                                <option value="-1"><?php esc_attr_e('Unlimited',
                                        'advanced-dynamic-pricing-for-woocommerce') ?></option>
                                <option value="1"><?php esc_attr_e('Once', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e('Based on', 'advanced-dynamic-pricing-for-woocommerce') ?>">
                                <option value="based_on_subtotal"><?php esc_html_e('Subtotal (exc. VAT)',
                                        'advanced-dynamic-pricing-for-woocommerce') ?></option>
                                <option value="based_on_subtotal_inc"><?php esc_html_e('Subtotal (inc. VAT)',
                                        'advanced-dynamic-pricing-for-woocommerce') ?></option>
                            </optgroup>
                        </select>

                        <div class="repeat-subtotal" style="display: none">
                            <label><?php esc_html_e('Repeat counter = subtotal amount divided by',
                                    'advanced-dynamic-pricing-for-woocommerce'); ?>
                                <input class="repeat-subtotal-value" name="rule[get_products][repeat_subtotal]"
                                    placeholder="<?php esc_attr_e("amount", 'advanced-dynamic-pricing-for-woocommerce') ?>">
                            </label>
                        </div>
                    </div>
                    <div style="flex: 1;" class="replace-free-products">
                        <div
                            style="float: right;" <?php echo $isCouponEnabled ? "" : "title='Please, enable coupons to use price replacements.'"; ?>>
                            <label>
                                <input <?php echo $isCouponEnabled ? "" : "disabled"; ?> type="checkbox"
                                                                                        name="rule[additional][is_replace_free_products_with_discount]">
                                <?php esc_html_e("Add free items at regular price and show discount as coupon",
                                    'advanced-dynamic-pricing-for-woocommerce') ?>
                            </label>
                            <input <?php echo $isCouponEnabled ? "" : "disabled"; ?> type="text"
                                                                                    name="rule[additional][free_products_replace_name]"
                                                                                    style="width: 110px; display: inline-block;"
                                                                                    placeholder="<?php esc_attr_e("coupon_name",
                                                                                        'advanced-dynamic-pricing-for-woocommerce') ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="wdp-wrapper">
                    <div class="wdp-get-products"></div>

                    <div class="wdp-add-condition">
                        <button type="button" class="button add-filter-get-product"><?php esc_html_e('Add product',
                                'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="wdp-block wdp-filter-item-qty-block wdp-filter-item-qty-options" style="display: none;">
            <div class="wdp-row wdp-filter-item" data-index="{f}">

                <div class="wdp-filter-content-no-remove">

                    <div class="two-on-two">
                        <div class="two-on-two-column left-column">

                            <div style="display: flex;">
                                <div class="wdp-column wdp-condition-field-qty" style="display: none">
                                    <input type="number" placeholder="1" min="1" name="rule[{t}][{f}][qty]" value="1">
                                </div>
                                <?php

                                $productFilterTypeList = array(
                                    'products'                  => __(
                                        'Products',
                                        'advanced-dynamic-pricing-for-woocommerce'
                                    ),
                                    'product_sku'           => __(
                                        'SKUs',
                                        'advanced-dynamic-pricing-for-woocommerce'
                                    ),
                                    'product_categories'        => __(
                                        'Categories',
                                        'advanced-dynamic-pricing-for-woocommerce'
                                    ),
                                    'product_category_slug'     => __(
                                        'Category slugs',
                                        'advanced-dynamic-pricing-for-woocommerce'
                                    ),
                                    'product_attributes'        => __(
                                        'Attributes',
                                        'advanced-dynamic-pricing-for-woocommerce'
                                    ),
                                    'product_custom_attributes' => __(
                                        'Custom attributes',
                                        'advanced-dynamic-pricing-for-woocommerce'
                                    ),
                                    'product_tags'              => __(
                                        'Tags',
                                        'advanced-dynamic-pricing-for-woocommerce'
                                    ),
                                );

                                foreach (\ADP\BaseVersion\Includes\Helpers\Helpers::getCustomProductTaxonomies() as $tax) {
                                    $productFilterTypeList[$tax->name] = $tax->labels->menu_name;
                                }

                                $productFilterTypeList = array_merge($productFilterTypeList, array(
                                    'product_sellers'       => __(
                                        'Sellers',
                                        'advanced-dynamic-pricing-for-woocommerce'
                                    ),
                                ));

                                $productFilterTypeList = apply_filters(
                                    'wdp_select_product_filter_type_list',
                                    $productFilterTypeList
                                );
                                ?>

                                <div class="wdp-column wdp-filter-field-type">
                                    <select name="rule[{t}][{f}][type]" class="wdp-filter-type">
                                        <?php foreach ($productFilterTypeList as $value => $title): ?>
                                            <option value="<?php echo esc_attr($value) ?>"><?php echo esc_html($title) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>


                        </div>

                        <div class="two-on-two-column right-column">

                            <div>
                                <div class="wdp-column wdp-column-subfields wdp-condition-field-sub"></div>
                            </div>

                            <div>
                                <div class="wdp-product-filter-options">
                                    <div class="wdp-row">

                                        <div class="wdp-product-exclude wdp-column wdp-column-subfields">
                                            <div style="width: 100px"></div>
                                            <div class="wdp-column" style="flex: 1">
                                                <details>
                                                    <summary class="wdp-link">
                                                        <?php esc_html_e( 'Exclude products', 'advanced-dynamic-pricing-for-woocommerce' ); ?>
                                                    </summary>
                                                    <div style="display: flex">
                                                        <div>
                                                            <label>
                                                                <span class="wdp-exclude-title">
                                                                    <?php esc_html_e( 'Exclude products', 'advanced-dynamic-pricing-for-woocommerce' ); ?>
                                                                </span>
                                                            </label>
                                                        </div>
                                                        <div style="margin-left: 5px" class="wdp-exclude-on-wc-sale-container">
                                                            <label>
                                                                <input type="checkbox" class="wdp-exclude-on-wc-sale" name="rule[{t}][{f}][product_exclude][on_wc_sale]" value="1" >
                                                                <span class="wdp-exclude-on-wc-sale-title">
                                                                    <?php esc_html_e( 'on sale products', 'advanced-dynamic-pricing-for-woocommerce' ); ?>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <select multiple
                                                            data-list="products"
                                                            data-field="autocomplete"
                                                            data-placeholder="<?php esc_attr_e("Select values",
                                                                "advanced-dynamic-pricing-for-woocommerce") ?>"
                                                            name="rule[{t}][{f}][product_exclude][values][]">
                                                    </select>
                                                </details>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>


                    </div>


                </div>


                <div class="wdp-column wdp-btn-remove">
                    <div class="wdp-btn-remove-handle wdp_filter_remove">
                        <span class="dashicons dashicons-no-alt"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="wdp-block wdp-conditions wdp-sortable wdp-row" style="display: none;">
            <div class="wdp-column wdp-column-help">
                <label><?php Helpers::ruleFilterLabel('Conditions', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <p class="wdp-rule-help">
                <?php
                    echo sprintf(
                        wp_kses(
                                __('Select a cart condition that would trigger a rule execution.', 'advanced-dynamic-pricing-for-woocommerce')
                                .'<br><a href="%s" target="_blank">' .__('Read docs', 'advanced-dynamic-pricing-for-woocommerce') .'</a>',
                            array('br' => array(), 'a' => array('href' =>array(), 'target' => array()))
                        ),
                        esc_url('https://docs.algolplus.com/algol_pricing/rules/rules-sections/cart-conditions/')
                    );
                ?>
                </p>
                <h4 style="margin-bottom: 0px;"><?php esc_html_e('Popular conditions:',
                            'advanced-dynamic-pricing-for-woocommerce'); ?></h4>
                <div class="wdp-description ">
                    <div class="wdp-description-content">
                        <ul class="wdp-rule-help" style="column-count: 2;">
                            <?php
                            $mostPopularConditions = [
                                \ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl\CartSubtotal::class
                                    => __('Subtotal',
                                            'advanced-dynamic-pricing-for-woocommerce'), //(Cart Condition "Subtotal (excl. VAT)”)
                                \ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl\CustomerRole::class
                                    => __('Role',
                                            'advanced-dynamic-pricing-for-woocommerce'),
                                \ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl\Date::class
                                    => __('Date',
                                            'advanced-dynamic-pricing-for-woocommerce'),
                                \ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl\CustomerOrderCount::class
                                    => __('First Order',
                                            'advanced-dynamic-pricing-for-woocommerce'),
                                \ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl\ShippingCountry::class
                                    => __('Shipping Country',
                                            'advanced-dynamic-pricing-for-woocommerce'),
                                \ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl\ProductsAll::class
                                    => __('Product in the Cart',
                                            'advanced-dynamic-pricing-for-woocommerce'),
                            ];

                            foreach($mostPopularConditions as $impl => $name) {?>
                                <li>
                                    <span class="wdp-add-popular-condition wdp-link"
                                        data-condition-type="<?php echo esc_attr($impl::getType()) ?>"
                                        <?php if($impl === \ADP\BaseVersion\Includes\Core\Rule\CartCondition\Impl\CustomerOrderCount::class) {?>
                                            data-condition-value="1"
                                        <?php } ?>
                                    >
                                        <?php echo esc_html($name) ?>
                                </span>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                    <div class="wdp-description-cut">
                        <span class="wdp-description-cut-more wdp-link"><?php esc_html_e('More', 'advanced-dynamic-pricing-for-woocommerce')?></span>
                        <span class="wdp-description-cut-less wdp-link"><?php esc_html_e('Less', 'advanced-dynamic-pricing-for-woocommerce')?></span>
                    </div>
                </div>
                <a href="https://algolplus.com/plugins/downloads/advanced-dynamic-pricing-woocommerce-pro/"
                   target=_blank><?php esc_html_e('Need more conditions?', 'advanced-dynamic-pricing-for-woocommerce') ?></a>
            </div>
            <div class="wdp-wrapper wdp-column">
                <div class="wdp-conditions-relationship">
                    <label><?php esc_html_e('Conditions relationship', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                    <label><input type="radio" name="rule[additional][conditions_relationship]" value="and"
                                  checked><?php esc_html_e('Match All', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                    <label><input type="radio" name="rule[additional][conditions_relationship]"
                                  value="or"><?php esc_html_e('Match Any', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                </div>
                <div class="wdp-conditions-container"></div>
                <div class="add-condition">
                    <button type="button" class="button"><?php esc_html_e('Add condition',
                            'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                </div>
            </div>
        </div>

        <div class="wdp-block wdp-limits wdp-sortable wdp-row" style="display: none;">
            <div class="wdp-column wdp-column-help">
                <label><?php Helpers::ruleFilterLabel('Limits', 'advanced-dynamic-pricing-for-woocommerce'); ?></label>
                <p class="wdp-rule-help">
                <?php
                    echo sprintf(
                        wp_kses(
                                __('Configure how often the rule would be applied.', 'advanced-dynamic-pricing-for-woocommerce')
                                .'<br><a href="%s">' .__('Read docs', 'advanced-dynamic-pricing-for-woocommerce') .'</a>',
                            array('br' => array(), 'a' => array('href' => array()))
                        ),
                        esc_url('https://docs.algolplus.com/algol_pricing/rules/rules-sections/limits/')
                    );
                    ?>
                </p>
            </div>
            <div class="wdp-wrapper wdp-column">
                <div class="wdp-limits-container"></div>
                <div class="add-limit">
                    <button type="button" class="button"><?php esc_html_e('Add limit',
                            'advanced-dynamic-pricing-for-woocommerce'); ?></button>
                </div>
            </div>
        </div>

        <div class="wdp-add-condition">
            <button type="button" class="button wdp-btn-add-product-filter"><?php esc_html_e('Product filters',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-product-adjustment"><?php esc_html_e('Product discounts',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-role-discount"><?php esc_html_e('Role discounts',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-bulk"><?php esc_html_e('Bulk rules',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-getproduct"><?php esc_html_e('Free products',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-condition"><?php esc_html_e('Cart conditions',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="button" class="button wdp-btn-add-limit"><?php esc_html_e('Limits',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></button>
            <button type="submit" class="button button-primary save-rule"><?php esc_html_e('Save changes',
                    'advanced-dynamic-pricing-for-woocommerce') ?></button>
        </div>
    </div>
</form>
