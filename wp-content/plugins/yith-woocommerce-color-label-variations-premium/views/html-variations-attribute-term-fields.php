<?php
/** Variations attribute term fields.
 *
 * @var WC_Product         $product
 * @var string             $type
 * @var array              $fields
 * @var WC_Product_Attribute    $attribute
 * @var WP_Term             $attr_term
 *
 * @package YITH\ColorsAndLabels\Views
 */

foreach ( $fields as $field ) {
    // Change the fields name for use on edit product page.
    $name = $field['fields']['name'];
	$field['fields']['name'] = '_yith_wccl_product_terms[' . $attribute->get_name() . '][' . $attr_term->term_id . '][' . $name . ']';
    $field['fields']['value'] = isset( $attr_term_value[$attribute->get_name()][$attr_term->term_id][$name] ) ? $attr_term_value[$attribute->get_name()][$attr_term->term_id][$name] : $field['fields']['value'];
    $field['class'] = strpos( $field['class'], 'yith-wccl-override-global-term-field' ) === false ? $field['class'].' ywccl_show_if_override_global' : $field['class'];
    yith_wccl_get_field( $field );
}
