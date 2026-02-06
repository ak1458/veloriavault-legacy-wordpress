<?php
/** Variations style tab view.
 *
 * @var WC_Product         $product
 * @var int                $tab_id
 *
 * @package YITH\ColorsAndLabels\Views
 */

global $wc_product_attributes;
$admin_url = admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=' . YITH_WCCL_SLUG );
echo '<div id="' . esc_attr( $tab_id ) . '" class="panel woocommerce_options_panel">';

    echo yith_plugin_fw_get_component(
        array(
            'type' => 'list-table-blank-state',
            'icon' => 'info-squared',
            'message' => sprintf( __('Translate the attributes %s', 'yith-woocommerce-color-label-variations' ) , sprintf( '<a href="%s" target="_blank">%s</a>', $admin_url, _x( 'in the following section', 'Translate the attributes in the following section', 'yith-woocommerce-color-label-variations' ) ) ),
        )
    );
echo '</div>';