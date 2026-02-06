<?php
/**
 * @var string $security
 * @var string $security_param
 */

use ADP\BaseVersion\Includes\Database\Repository\RuleRepository;

if ( ! defined( 'ABSPATH' ) ) exit;

$repo = new RuleRepository();
$items = $repo->getRulesWithBulk();
?>

<div>
    <?php if (count($items) > 0) { ?>
        <h3 class="tools-h3-title"><?php esc_html_e( 'Export rules with non-empty bulk ranges as CSV', 'advanced-dynamic-pricing-for-woocommerce' ); ?></h3>
        <button
            id="wdp-export-bulk-ranges"
            name="wdp-export-bulk-ranges"
            class="button button-primary wdp-export-bulk-ranges"
            type="submit"
        >
            <?php esc_html_e( 'Export into CSV', 'advanced-dynamic-pricing-for-woocommerce' ); ?>
        </button>
        <form method="post" enctype="multipart/form-data" class="wdp-import-tools-form">
            <input type="hidden" name="<?php echo esc_attr($security_param); ?>" value="<?php echo esc_attr($security); ?>"/>
            <h3 class="tools-h3-title"><?php esc_html_e( 'Re-import CSV to update ranges for EXISTING rules', 'advanced-dynamic-pricing-for-woocommerce' ); ?></h3>
            <input type="file" name="rules-to-import" id="rules-to-import" class="button"/>
            <button
                id="wdp-import-bulk-ranges"
                name="wdp-import-bulk-ranges"
                class="button button-primary"
                type="submit"
                style="min-height: 36px"
            >
                <?php esc_html_e( 'Import', 'advanced-dynamic-pricing-for-woocommerce' ); ?>
            </button>
        </form>
    <?php } else {?>
        <h3 class="tools-h3-title">
            <?php esc_html_e( 'Please, create some bulk rules at first', 'advanced-dynamic-pricing-for-woocommerce' ); ?>
        </h3>
    <?php } ?>
</div>

