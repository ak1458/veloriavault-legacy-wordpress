<?php
defined('ABSPATH') or exit;

use ADP\BaseVersion\Includes\AdminExtensions\Ajax;
/**
 * @var boolean $hide_inactive
 * @var string $pagination Pagination HTML
 * @var string $tab current tab key
 * @var string $page current page slug
 * @var string $tabHandler current tab handler
 * @var \ADP\Settings\OptionsManager $options
 * @var string $ruleSearchQ rule search query
 * @var integer $rulesCount
 * @var integer $activeRulesCount
 * @var integer $inactiveRulesCount
 * @var string $tabUrl
 * @var string $active
 */
?>

<div id="poststuff">

    <div class="wdp-list-container" id="rules-action-controls">
        <div class="wdp-row">
        <div class="wdp-column wdp-column-max-content wdp-row" style="flex-direction: column">
                <ul class="subsubsub" style="margin-top: auto;">
                    <li>
                        <a class="<?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $active === "all" ? "current" : ""; ?>" href="<?php echo add_query_arg("active", "all", $tabUrl);?>">
                            <?php esc_html_e('All', 'advanced-dynamic-pricing-for-woocommerce'); ?>
                            <span class="count"><?php echo esc_html("($rulesCount)"); ?></span>
                        </a> |
                    </li>
                    <li>
                        <a class="<?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $active === "1" ? "current" : ""; ?>" href="<?php echo add_query_arg("active", "1", $tabUrl);?>">
                            <?php esc_html_e('Active', 'advanced-dynamic-pricing-for-woocommerce'); ?>
                            <span class="count"><?php echo esc_html("($activeRulesCount)"); ?></span>
                        </a> |
                    </li>
                    <li>
                        <a class="<?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $active === "0" ? "current" : ""; ?>" href="<?php echo add_query_arg("active", "0", $tabUrl);?>">
                            <?php esc_html_e('Inactive', 'advanced-dynamic-pricing-for-woocommerce'); ?>
                            <span class="count"><?php echo esc_html("($inactiveRulesCount)"); ?></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="wdp-wrapper wdp-column wdp-column-max-content" style="margin-left: auto;">
                <form id="search-rules" method="get">
                    <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>">
                    <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">
                    <input type="hidden" name="action" value="search_rules">
                    <input type="search" name="q" value="<?php echo esc_attr($ruleSearchQ); ?>">
                    <button type="submit" class="button wdp-btn-rule-action-controls"><?php esc_html_e('Search rules', 'advanced-dynamic-pricing-for-woocommerce') ?></button>
                </form>
            </div>
        </div>
    </div>

    <?php
    //phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_GET['product']) && isset($_GET['action_rules'])): ?>
        <div>
            <span class="tag-show-rules-for-product"><?php
                /* translators: Only the rules for a specific product are shown */
                printf(esc_html__('Only rules for product "%s" are shown',
                    'advanced-dynamic-pricing-for-woocommerce'),
                    //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    esc_html(\ADP\BaseVersion\Includes\Helpers\Helpers::getProductTitle(sanitize_key(wp_unslash($_GET['product']))))); ?></span>
        </div>
    <?php endif; ?>

    <div style="clear: both; margin: 5px 0;">
        <div style="float: left; margin: 5px 0; width: 39px; text-align: center;">
            <input type="checkbox" id="bulk-action-select-all">
        </div>

        <form id="bulk-action" method="post" style="display: inline-block; float: left; margin-right: 10px; ">
            <?php wp_nonce_field(Ajax::SECURITY_ACTION, Ajax::SECURITY_QUERY_ARG); ?>
            <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>"/>
            <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>"/>
            <select id="bulk-action-selector" name="bulk_action" style="width: 131px;">
                <option value=""><?php esc_html_e('Bulk actions', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                <option value="enable"><?php esc_html_e('Activate', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                <option value="disable"><?php esc_html_e('Deactivate', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                <option value="delete"><?php esc_html_e('Delete', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
            </select>
            <button type="submit" class="button"><?php esc_html_e('Apply', 'advanced-dynamic-pricing-for-woocommerce') ?></button>
        </form>

        <form id="rules-filter" method="get" style="float: right;">
            <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>"/>
            <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>"/>
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $pagination; ?>
        </form>
    </div>

    <div id="post-body" class="metabox-holder">
        <div id="postbox-container-2" class="postbox-container">
            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                <div id="rules-container"
                     class="sortables-container group-container loading wdp-list-container"></div>
                <p id="no-rules"
                   class="wdp-no-list-items loading"><?php esc_html_e('No rules defined',
                        'advanced-dynamic-pricing-for-woocommerce') ?></p>
                <p>
                    <button class="button add-rule wdp-add-list-item loading">
                        <?php esc_html_e('Add rule', 'advanced-dynamic-pricing-for-woocommerce') ?></button>
                </p>
                <div style="float: right; margin: 5px">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $pagination; ?>
                </div>
                <div id="progress_div" style="">
                    <div id="container"><span class="spinner is-active" style="float:none;"></span></div>
                </div>

            </div>
        </div>

        <div style="clear: both;"></div>
    </div>
</div>

<?php include 'rules/templates.php'; ?>
