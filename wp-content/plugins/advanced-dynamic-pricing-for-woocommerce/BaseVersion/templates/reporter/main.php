<?php
defined('ABSPATH') or exit;

/**
 * @var array $tabs
 */

?>

<div id="wdp-report-window">
    <div id="wdp-report-control-bar">
        <div id="wdp-report-resizer"></div>

        <div class="tab-link icon-logo-report">
            <?php include(WC_ADP_PLUGIN_PATH."/BaseVersion/assets/images/pricing_logo.svg") ?>
        </div>

        <div id="wdp-report-main-tab-selector" class="tab-links-list">

            <div class="tab-link selected" data-tab-id="cart"><?php echo esc_html__('Cart',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></div>
            <div class="tab-link" data-tab-id="products"><?php echo esc_html__('Products',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></div>
            <div class="tab-link" data-tab-id="rules"><?php echo esc_html__('Rules',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></div>
            <div class="tab-link" data-tab-id="reports"><?php echo esc_html__('Get system report',
                    'advanced-dynamic-pricing-for-woocommerce'); ?></div>

            <div id="wdp-report-resizer"></div>
        </div>

        <div id="progress_div" style="margin-right: 10px;">
            <img class="spinner_img" alt="spinner">
        </div>

        <div id="wdp-report-goto-debug-settings" class="tab-link">
            <?php
            echo esc_html__('Only admins see this panel. ', 'advanced-dynamic-pricing-for-woocommerce');
            echo sprintf(
                wp_kses(
                        '<a href="%s" target="_blank">' .__('How to hide it.', 'advanced-dynamic-pricing-for-woocommerce') .'</a>',
                    array(
                        'a' => array(
                            'href' => array(),
                            'target' => array(),
                        ),
                    )
                ),
                esc_url(get_admin_url() . 'admin.php?page=wdp_settings&tab=options#section=debug')
            );
            ?>
        </div>

        <div id="wdp-report-window-refresh">
            <button>
                <?php echo esc_html__('Refresh', 'advanced-dynamic-pricing-for-woocommerce'); ?>
            </button>
        </div>

        <div id="wdp-arrow-report" style="padding-top: 0.85rem; margin-right: 0.3rem; cursor: pointer;">
            <span id="wdp-arrow-report-down" class="dashicons dashicons-arrow-down-alt2"></span>
        </div>

        <div id="wdp-report-window-close" style="padding-top: 0.2rem;">
            <span class="dashicons dashicons-no-alt"></span>
        </div>
    </div>

    <div id="wdp-report-tab-window"></div>

</div>

<div id="wdp-icon-report" class="wdp-icon-report-class">
    <div class="tab-link icon-logo-report">
        <?php include(WC_ADP_PLUGIN_PATH."/BaseVersion/assets/images/pricing_logo.svg") ?>
    </div>
</div>
