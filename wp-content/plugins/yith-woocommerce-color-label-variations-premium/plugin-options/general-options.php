<?php // phpcs:ignore WordPress.NamingConventions
/**
 * GENERAL ARRAY OPTIONS
 *
 * @since   2.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

$general = array(

	'general' => array(
		array(
			'title' => __( 'Variations on shop & archive pages', 'yith-woocommerce-color-label-variations' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith-wccl-single-variations-options',
		),
		array(
			'title'     => __( 'Show variations as separate products', 'yith-woocommerce-color-label-variations' ),
			'desc'      => __( 'Enable to show each single variation as a separate product on the shop and archive pages.', 'yith-woocommerce-color-label-variations' ),
			'id'        => 'yith-wccl-show-single-variations-loop',
			'default'   => 'no',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
		),

		array(
			'title'     => __( 'Hide Variable Products', 'yith-woocommerce-color-label-variations' ),
			'desc'      => __( 'Hide variable products in loop when their variations are displayed.', 'yith-woocommerce-color-label-variations' ),
			'id'        => 'yith-wccl-hide-parent-products-loop',
			'default'   => 'yes',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'deps'      => array(
				'id'    => 'yith-wccl-show-single-variations-loop',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),

		array(
			'title'     => __( 'Order products by ID', 'yith-woocommerce-color-label-variations' ),
			'desc'      => __( 'Order products by ID in all WooCommerce loop. This option alters the main WordPress query so you may notice changes in different sections of your site. We recommend enabling this option if you see your variations sequentially', 'yith-woocommerce-color-label-variations' ),
			'id'        => 'yith-wccl-order-products-by-id',
			'default'   => 'no',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'deps'      => array(
				'id'    => 'yith-wccl-show-single-variations-loop',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),
		array(
			'id'        => 'yith-wccl-enable-in-loop',
            'class'     => 'yith-wccl-enable-in-loop',
			'title'     => __( 'Allow attributes selection on shop and archive pages', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable to allow users to choose variations on the shop and archive pages.', 'yith-woocommerce-color-label-variations' ),
			'default'   => 'yes',
		),

        array(
            'id'        => 'yith-wccl-enable-specific-attributes-in-loop',
            'class'     => 'yith-wccl-enable-specific-attributes-in-loop',
            'title'     => __( 'Show only specific attributes on shop archive pages', 'yith-woocommerce-color-label-variations' ),
            'type'      => 'yith-field',
            'yith-type' => 'onoff',
            'desc'      =>  implode(
                        '<br />',
                        array(
                            esc_html__( 'Enable to choose which attributes to show on the shop archive pages.', 'yith-woocommerce-color-label-variations' ),
                            esc_html__( 'If disabled, all attributes will be shown.', 'yith-woocommerce-color-label-variations' ),
                        )
            ),
            'default'   => 'no',
            'deps'      => array(
                'id'    => 'yith-wccl-enable-in-loop',
                'value' => 'yes',
                'type'  => 'hide',
            ),
        ),
        array(
            'id'        => 'yith-wccl-attributes-in-loop',
            'title'     => __( 'Attributes to show:', 'yith-woocommerce-color-label-variations' ),
            'type'      => 'yith-field',
            'yith-type' => 'select',
            'class'     => 'wc-enhanced-select yith-wccl-attributes-in-loop',
            'multiple'  => true,
            'options'   => yith_wccl_get_supported_taxonomies(),
            //'default'   => '',
            'deps'      => array(
                'id'    => 'yith-wccl-enable-specific-attributes-in-loop',
                'value' => 'yes',
                'type'  => 'hide',
            ),
        ),



		array(
			'id'        => 'yith-wccl-position-in-loop',
			'title'     => __( 'Variations form position on archive pages', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'before' => __( 'Before the "Add to Cart" button', 'yith-woocommerce-color-label-variations' ),
				'after'  => __( 'After the "Add to Cart" button', 'yith-woocommerce-color-label-variations' ),
			),
			'default'   => 'after',
			'deps'      => array(
				'id'    => 'yith-wccl-enable-in-loop',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),
		array(
			'id'        => 'yith-wccl-ajax-in-loop',
			'title'     => __( 'Enable AJAX form on archive pages', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable AJAX handle for variations form on archive shop pages.', 'yith-woocommerce-color-label-variations' ),
			'default'   => 'no',
			'deps'      => array(
				'id'    => 'yith-wccl-enable-in-loop',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),

		array(
			'id'        => 'yith-wccl-override-add-to-cart-label',
			'title'     => __( 'Customize the "Add to cart" button label', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable to set a custom label for the "Add to cart" button when a variation is selected.', 'yith-woocommerce-color-label-variations' ),
			'default'   => 'yes',
			'deps'      => array(
				'id'    => 'yith-wccl-enable-in-loop',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),

		array(
			'id'        => 'yith-wccl-add-to-cart-label',
			'title'     => __( 'Label for the "Add to cart" button', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'desc'      => __( 'Label for the "Add to cart" button when a variation is selected from an archive page.', 'yith-woocommerce-color-label-variations' ),
			'default'   => __( 'Add to cart', 'yith-woocommerce-color-label-variations' ),
			'deps'      => array(
				'id'    => 'yith-wccl-override-add-to-cart-label',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'yith-wccl-single-variations-options',
		),

		array(
			'title' => __( 'Variations on product pages', 'yith-woocommerce-color-label-variations' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith-wccl-variations-in-product-pages',
		),
		array(
			'title'     => __( 'Variations layout', 'yith-woocommerce-color-label-variations' ),
			'id'        => 'yith-wccl-variations-layout-single-page',
			'default'   => 'inline',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'inline'   => __( 'Inline', 'yith-woocommerce-color-label-variations' ),
				'separate' => __( 'Separate lines', 'yith-woocommerce-color-label-variations' ),
			),
		),
		array(
			'id'        => 'yith-wccl-enable-description',
			'title'     => __( 'Show attribute description', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Choose to show the description below each attribute on the single product page.', 'yith-woocommerce-color-label-variations' ),
			'default'   => 'yes',
		),
		array(
			'id'        => 'yith-wccl-change-image-hover',
			'title'     => __( 'Replace product image on hover', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __(
				'Enable to replace the default product image when the mouse hovers over  
a specific attribute.',
				'yith-woocommerce-color-label-variations'
			),
			'default'   => 'no',
		),

		array(
			'id'        => 'yith-wccl-show-custom-on-tab',
			'title'     => __( 'Show custom attributes on "Additional Information" Tab', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable to add the custom attributes style to the info shown on the "Additional Information" tab.', 'yith-woocommerce-color-label-variations' ),
			'default'   => 'no',
		),


		array(
			'type' => 'sectionend',
			'id'   => 'yith-wccl-general-options',
		),

        array(
            'title' => __( 'Variations on Cart page', 'yith-woocommerce-color-label-variations' ),
            'type'  => 'title',
            'desc'  => '',
            'id'    => 'yith-wccl-variations-in-cart-page',
        ),
        array(
            'id'        => 'yith-wccl-edit-attributes-on-cart-page',
            'title'     => __( 'Allow editing of attributes on the Cart page ', 'yith-woocommerce-color-label-variations' ),
            'type'      => 'yith-field',
            'yith-type' => 'onoff',
            'desc'      => __( 'Enable to allow users to edit attributes selection in a modal window on the Cart page', 'yith-woocommerce-color-label-variations' ),
            'default'   => 'yes',
        ),
        array(
            'type' => 'sectionend',
            'id'   => 'yith-wccl-variations-in-cart-page-end',
        ),

	),
);
/**
 * APPLY_FILTER: yith_wccl_panel_general_options
 *
 * General options
 *
 * @param array $general General options
 *
 * @return array
 */
return apply_filters( 'yith_wccl_panel_general_options', $general );
