<?php
defined('ABSPATH') or exit;

/**
 * @var $groups
 */
$items = array();
foreach ($groups as $group) {
    foreach ($group['items'] as $key => $item) {
        $items[$key] = $item;
    }
}

?>

<div>
    <h3 style="margin-top: 0;"><?php esc_html_e('Export settings', 'advanced-dynamic-pricing-for-woocommerce') ?></h3>
    <p>
        <label for="wdp-export-select">
            <?php esc_html_e('Copy these settings and use it to migrate plugin to another WordPress install.',
                'advanced-dynamic-pricing-for-woocommerce') ?>
        </label>
        <select id="wdp-export-select">
            <?php foreach ($groups as $group_key => $group): ?>
                <optgroup label="<?php echo esc_attr($group['label']); ?>">
                    <?php foreach ($group['items'] as $key => $item): ?>
                        <option
                            value="<?php echo esc_attr($key) ?>" <?php selected($group_key === 'rules' and $key === 'all') ?> ><?php echo esc_html($item['label']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <textarea id="wdp-export-data" name="wdp-export-data" class="large-text" rows="15"></textarea>
    </p>
    <p>
        <button id="wdp-export-json-settings" name="export-json-settings" class="button button-primary wdp-export-json-settings" type="submit">
            <?php esc_html_e('Export JSON', 'advanced-dynamic-pricing-for-woocommerce') ?>
        </button>
    <p>
</div>

<div>
    <h3><?php esc_html_e('Import settings', 'advanced-dynamic-pricing-for-woocommerce') ?></h3>
    <form method="post" class="wdp-import-tools-form">
        <input type="hidden" name="<?php echo esc_attr($security_param); ?>" value="<?php echo esc_attr($security); ?>"/>
        <div>
            <div>
                <?php
                    $importResultMsg = get_transient('import-result');
                    if ($importResultMsg !== false) {
                        $msgClass = strpos($importResultMsg, 'success') !== false ? 'import-notice notice-ok' : 'import-notice notice-fail';
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo "<p class='$msgClass'>" . $importResultMsg . '</p>';
                        delete_transient('import-result');
                    }
                ?>
                <p>
                    <label for="wdp-import-data">
                        <?php esc_html_e('Paste text into this field to import settings into the current WordPress install.',
                            'advanced-dynamic-pricing-for-woocommerce') ?>
                    </label>
                </p>
                <p>
                    <textarea id="wdp-import-data" name="wdp-import-data" class="large-text" rows="15"></textarea>
                </p>
                <p class="wdp-import-type-options-rules wdp-import-type-options">
                    <span>
                        <?php esc_html_e('Or import JSON backup file:', 'advanced-dynamic-pricing-for-woocommerce') ?>
                    </span>
                    <input type="file" name="json-to-import" id="json-to-import" accept="application/json" hidden>
                    <button id="wdp-import-json" class="button button-primary">
                        <?php esc_html_e('Import JSON', 'advanced-dynamic-pricing-for-woocommerce') ?>
                    </button>
                </p>
                <p class="wdp-import-type-options-rules wdp-import-type-options">
                    <input type="hidden" name="wdp-import-data-reset-rules" value="0">
                    <input type="checkbox" id="wdp-import-data-reset-rules" name="wdp-import-data-reset-rules" value="1">
                    <label for="wdp-import-data-reset-rules">
                        <?php esc_html_e('Clear all rules before import', 'advanced-dynamic-pricing-for-woocommerce') ?>
                    </label>
                </p>
                <?php do_action('wdp_import_tools_options') ?>
            </div>
        </div>
        <p>
            <button type="submit" id="wdp-import" name="wdp-import" class="button button-primary">
                <?php esc_html_e('Import', 'advanced-dynamic-pricing-for-woocommerce') ?></button>
        </p>
    </form>
</div>

