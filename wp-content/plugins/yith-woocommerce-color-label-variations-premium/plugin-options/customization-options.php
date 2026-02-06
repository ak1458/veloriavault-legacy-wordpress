<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Customization ARRAY OPTIONS
 *
 * @since   2.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

$customization = array(

	'customization' => array(
		array(
			'title' => __( 'Tooltip', 'yith-woocommerce-color-label-variations' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith-wccl-customization-tooltip-options',
		),

		array(
			'id'        => 'yith-wccl-enable-tooltip',
			'title'     => __( 'Enable tooltip on attributes', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable to show a tooltip with the attributes\' label.', 'yith-woocommerce-color-label-variations' ),
			'default'   => 'yes',
		),

		array(
			'id'        => 'yith-wccl-tooltip-position',
			'title'     => __( 'Tooltip position', 'yith-woocommerce-color-label-variations' ),
			// 'desc'      => __( 'Select the tooltip position', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'top'    => __( 'Top', 'yith-woocommerce-color-label-variations' ),
				'bottom' => __( 'Bottom', 'yith-woocommerce-color-label-variations' ),
			),
			'default'   => 'top',
			'deps'      => array(
				'id'    => 'yith-wccl-enable-tooltip',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),

		array(
			'id'        => 'yith-wccl-tooltip-animation',
			'title'     => __( 'Tooltip animation', 'yith-woocommerce-color-label-variations' ),
			// 'desc'      => __( 'Select the tooltip animation', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'fade'  => __( 'Fade in', 'yith-woocommerce-color-label-variations' ),
				'slide' => __( 'Slide in', 'yith-woocommerce-color-label-variations' ),
			),
			'default'   => 'fade',
			'deps'      => array(
				'id'    => 'yith-wccl-enable-tooltip',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),

		array(
			'id'           => 'yith-wccl-tooltip-colors',
			'title'        => __( 'Tooltip colors', 'yith-woocommerce-color-label-variations' ),
			'type'         => 'yith-field',
			'yith-type'    => 'multi-colorpicker',
			'colorpickers' => array(
				array(
					'id'      => 'background',
					'name'    => __( 'Background', 'yith-woocommerce-color-label-variations' ),
					'default' => get_option( 'yith-wccl-tooltip-background', '#448a85'),
                    'alpha_enabled'     => false,
				),
				array(
					'id'      => 'text-color',
					'name'    => __( 'Text', 'yith-woocommerce-color-label-variations' ),
					'default' => get_option( 'yith-wccl-tooltip-text-color', '#ffffff' ),
                    'alpha_enabled'     => false,
				),
			),
			'deps'         => array(
				'id'    => 'yith-wccl-enable-tooltip',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),

		array(
			'type' => 'sectionend',
			'id'   => 'yith-wccl-customization-tooltip-options-end',
		),
		array(
			'title' => __( 'Style options', 'yith-woocommerce-color-label-variations' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith-wccl-customization-style-options',
		),

		array(
			'id'           => 'yith-wccl-form-colors',
			'title'        => __( 'Form colors', 'yith-woocommerce-color-label-variations' ),
			'type'         => 'yith-field',
			'yith-type'    => 'multi-colorpicker',
			'colorpickers' => array(
				array(
					'id'      => 'border',
					'name'    => __( 'Border', 'yith-woocommerce-color-label-variations' ),
					'default' => '#ffffff',
                    'alpha_enabled'     => false,
				),
				array(
					'id'      => 'accent',
					'name'    => __( 'Accent', 'yith-woocommerce-color-label-variations' ),
					'default' => '#448a85',
                    'alpha_enabled'     => false,
				),
			),
		),
		array(
			'title'     => __( 'When a variation is out of stock,', 'yith-woocommerce-color-label-variations' ),
			'id'        => 'yith-wccl-attributes-style',
			'default'   => 'hide',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'hide'       => __( 'Hide it', 'yith-woocommerce-color-label-variations' ),
				'grey'       => __( 'Blur it', 'yith-woocommerce-color-label-variations' ),
				'blur_cross' => __( 'Blur it and cross it out', 'yith-woocommerce-color-label-variations' ),
			),
		),
		array(
			'id'        => 'yith-wccl-customization-color-swatches-size',
			'title'     => __( 'Color swatches size (px)', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'number',
			'default'   => 25,
            'min'       => 0,
		),
		array(
			'id'        => 'yith-wccl-customization-color-swatches-border-radius',
			'title'     => __( 'Border radius of color swatches (px)', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'number',
			'default'   => 25,
            'min'       => 0,

        ),
		array(
			'id'        => 'yith-wccl-customization-option-border-radius',
			'title'     => __( 'Border radius of labels and images swatches (px)', 'yith-woocommerce-color-label-variations' ),
			'type'      => 'yith-field',
			'yith-type' => 'number',
			'default'   => 25,
            'min'       => 0,

        ),
		array(
			'type' => 'sectionend',
			'id'   => 'yith-wccl-customization-style-options-end',
		),

	),
);
/**
 * APPLY_FILTER: yith_wccl_panel_customization_options
 *
 * Customization options
 *
 * @param array $customization Customization options
 *
 * @return array
 */
return apply_filters( 'yith_wccl_panel_customization_options', $customization );
