<?php
defined('ABSPATH') or exit;

/**
 * @var array $tilesHelpInfo
 */
?>
<div id="wp-tab-help">
    <div class="wdp-help-list-container">
        <div class="wdp-row wdp-title-wrapper">
            <h3 class="wdp-column wdp-help-title"><?php esc_html_e('Help', 'advanced-dynamic-pricing-for-woocommerce'); ?></h3>
            <div class="wdp-column">
                <a class="wdp_docs_links" href="<?php echo esc_url('https://docs.algolplus.com/category/algol_pricing/');?>" target="_blank"><?php esc_html_e('Docs site', 'advanced-dynamic-pricing-for-woocommerce'); ?></a>
            </div>
            <div class="wdp-column">
                <a class="wdp_docs_links" href="<?php echo esc_url('https://docs.algolplus.com/category/algol_pricing/faq/');?>" target="_blank"><?php esc_html_e('FAQ', 'advanced-dynamic-pricing-for-woocommerce'); ?></a>
            </div>
            <div class="wdp-column">
                <a class="wdp_docs_links" href="<?php echo esc_url('https://docs.algolplus.com/support/');?>" target="_blank"><?php esc_html_e('Support', 'advanced-dynamic-pricing-for-woocommerce'); ?></a>
            </div>
        </div>
        <div class="wdp-tiles-grid">
            <?php foreach($tilesHelpInfo as $tile): ?>
                <div class="wdp-cell">
                    <a href="<?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $tile['link']; ?>" class="wdp-title-help-info" target="_blank">
                        <h2 class="wdp-tile-title"><?php echo esc_html($tile['title']); ?></h2>
                        <p><?php echo esc_html($tile['description']) ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
