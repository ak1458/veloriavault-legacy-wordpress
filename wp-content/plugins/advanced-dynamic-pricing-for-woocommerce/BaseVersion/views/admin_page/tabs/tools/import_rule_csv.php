<?php
defined('ABSPATH') or exit;
use ADP\BaseVersion\Includes\ImportExport\ImporterCSV;

/**
 * @var $import_data_types
 * @var string $security
 * @var string $security_param
 */
?>

<form method="post" enctype="multipart/form-data" class="wdp-import-tools-form">
    <input type="hidden" name="<?php echo esc_attr($security_param); ?>" value="<?php echo esc_attr($security); ?>"/>
        <?php if(!empty(ImporterCSV::$warnings)):; ?>
        <div class="wdp-import-data-exeptions">
            <?php foreach(ImporterCSV::$warnings as $warning): ?>
                <p style="color: red;"><?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $warning; ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div id="wdp-import-data-sample-csv" style="float: right; width: 70%">
            <a href="<?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo WC_ADP_PLUGIN_URL. '/BaseVersion/sample-data/sample.csv'; ?>"><?php esc_html_e('Download sample CSV', 'advanced-dynamic-pricing-for-woocommerce'); ?></a>
        </div>
    <div class="wdp-import-type-options-rules wdp-import-type-options">
        <div>
            <input type="radio" id="wdp-import-data-reset-rules" name="wdp-import-data-rule-import" value="reset">
            <label for="wdp-import-data-reset-rules">
                <?php esc_html_e('Delete rules before import', 'advanced-dynamic-pricing-for-woocommerce') ?>
            </label>
        </div>
        <div>
            <input type="radio" id="wdp-import-data-update-rules" name="wdp-import-data-rule-import" value="update" checked>
            <label for="wdp-import-data-update-rules">
                <?php esc_html_e('Update rules having same product filter, otherwise add new rule', 'advanced-dynamic-pricing-for-woocommerce'); ?>
            </label>
        </div>
        <div>
            <input type="radio" id="wdp-import-data-add-rules" name="wdp-import-data-rule-import" value="add">
            <label for="wdp-import-data-add-rules">
                <?php esc_html_e('Add new rules after existing', 'advanced-dynamic-pricing-for-woocommerce'); ?>
            </label>
        </div>
    </div>
    <div class="button" style="padding-right: 0; margin-top: 5px; margin-bottom: 5px;">
        <input type="file" name="rules-to-import" id="rules-to-import-csv" accept=".csv" style="margin-top: 3px;"/>
        <button
                id="wdp-import-csv"
                name="wdp-import-csv"
                class="button button-primary"
                type="submit"
                style="min-height: 36px"
                disabled
            >
                <?php esc_html_e( 'Import', 'advanced-dynamic-pricing-for-woocommerce' ); ?>
            </button>
    </div>
    <div>
        <input type="checkbox" id="wdp-import-data-optimize-rules" name="wdp-import-data-optimize-import" style="margin-top: 2px">
        <label for="wdp-import-data-add-rules">
            <?php esc_html_e('Don\'t optimize the rules during importing', 'advanced-dynamic-pricing-for-woocommerce'); ?>
        </label>
    </div>
</form>
