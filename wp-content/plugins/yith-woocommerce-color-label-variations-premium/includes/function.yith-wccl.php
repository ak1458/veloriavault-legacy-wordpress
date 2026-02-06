<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Common functions
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 * @version 1.0.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

if ( ! function_exists( 'yith_wccl_update_db_check' ) ) {
	/**
	 * Check DB version and trigger activation if needed.
	 *
	 * @return void
	 */
	function yith_wccl_update_db_check() {
		if ( YITH_WCCL_DB_VERSION !== get_option( 'yith_wccl_db_version', '' ) ) {

			if ( ! function_exists( 'yith_wccl_activation' ) ) {
				require_once 'function.yith-wccl-activation.php';
			}

			yith_wccl_activation();
		}
	}
}

if ( ! function_exists( 'ywccl_get_term_meta' ) ) {
	/**
	 * Get term meta.
	 *
	 * @param integer|string $term_id The term ID.
	 * @param string         $key The term meta key.
	 * @param boolean        $single Optional. Whether to return a single value.
	 * @param string         $taxonomy Optional. The taxonomy slug.
	 * @return mixed
	 */
	function ywccl_get_term_meta( $term_id, $key, $single = true, $taxonomy = '' ) {
		$value = get_term_meta( $term_id, $key, $single );

		// Compatibility with old format. To be removed on next version.
		/**
		 * APPLY_FILTERS: yith_wccl_get_term_meta
		 *
		 * Filter if get term meta.
		 *
		 * @param bool $get_term_meta conditional for get the term meta.
		 * @param int $term_id term id.
		 */
		if ( apply_filters( 'yith_wccl_get_term_meta', true, $term_id ) && ( false === $value || '' === $value ) && ! empty( $taxonomy ) ) {
			$value = get_term_meta( $term_id, $taxonomy . $key, $single );
			// If meta is not empty, save it with the new key.
			if ( false !== $value && '' !== $value ) {
				ywccl_update_term_meta( $term_id, $key, $value );
				// Delete old meta.
				// delete_term_meta( $term_id, $taxonomy . $key );.
			}
		}

		return $value;
	}
}

if ( ! function_exists( 'ywccl_update_term_meta' ) ) {
	/**
	 * Update term meta.
	 *
	 * @param integer|string $term_id The term ID.
	 * @param string         $key The term meta key.
	 * @param mixed          $meta_value Metadata value.
	 * @param mixed          $prev_value Optional. Previous value to check before updating.
	 * @return mixed
	 */
	function ywccl_update_term_meta( $term_id, $key, $meta_value, $prev_value = '' ) {
		if ( '' === $meta_value || false === $meta_value ) {
			return delete_term_meta( $term_id, $key );
		}

		return update_term_meta( $term_id, $key, $meta_value, $prev_value );
	}
}

if ( ! function_exists( 'ywccl_get_custom_tax_types' ) ) {
	/**
	 * Return custom product's attributes type
	 *
	 * @since  1.2.0
	 * @return mixed|void
	 */
	function ywccl_get_custom_tax_types() {
		/**
		 * APPLY_FILTER: yith_wccl_get_custom_tax_types
		 *
		 * Get custom taxonomy types
		 *
		 * @param array $args Custom taxonomy types
		 *
		 * @return array
		 */
		return apply_filters(
			'yith_wccl_get_custom_tax_types',
			array(
				'colorpicker' => __( 'Colorpicker', 'yith-woocommerce-color-label-variations' ),
				'image'       => __( 'Image', 'yith-woocommerce-color-label-variations' ),
				'label'       => __( 'Label', 'yith-woocommerce-color-label-variations' ),
			)
		);
	}
}

if ( ! function_exists( 'yith_wccl_hide_add_to_cart' ) ) {
	/**
	 * Check if catalog mode is active or if RAQ option "Hide add to cart" as enabled
	 *
	 * @since  1.6.0
	 * @param WC_Product $product Product instance.
	 * @return boolean
	 */
	function yith_wccl_hide_add_to_cart( $product = '' ) {

		$catalog_mode = defined( 'YWCTM_PREMIUM' ) && YWCTM_PREMIUM && $product && YITH_WCTM()->check_hide_add_cart( false, $product->get_id(), true );

		$raq = defined( 'YITH_YWRAQ_PREMIUM' ) && YITH_YWRAQ_PREMIUM && 'yes' === get_option( 'ywraq_hide_add_to_cart', 'no' );

		return $catalog_mode || $raq;
	}
}

if ( ! function_exists( 'yith_wccl_get_variation_gallery' ) ) {
	/**
	 * Get gallery images for given variation
	 *
	 * @since  1.8.0
	 * @param WP_Post|WC_Product_Variation $variation Instance WP_Post or WC_Product_Variation.
	 * @return array
	 */
	function yith_wccl_get_variation_gallery( $variation ) {

		global $sitepress;

		if ( ! ( $variation instanceof WC_Product ) ) {
			$variation = wc_get_product( $variation->ID );
		}

		if ( ! $variation ) {
			return array();
		}

		$gallery = $variation->get_meta( '_yith_wccl_gallery', true );
		/**
		 * APPLY_FILTERS: yith_wccl_use_parent_gallery_for_translated_products
		 *
		 * Filter if use parent gallery for translated products.
		 *
		 * @param bool $use_parent_gallery conditional for use parent gallery.
		 */
		if ( empty( $gallery ) && function_exists( 'wpml_object_id_filter' ) && ! empty( $sitepress ) && apply_filters( 'yith_wccl_use_parent_gallery_for_translated_products', true ) ) {
			$parent_id = wpml_object_id_filter( $variation->get_id(), 'product_variation', false, $sitepress->get_default_language() );
			if ( ! empty( $parent_id ) ) {
				$variation = wc_get_product( $parent_id );
				if ( $variation ) {
					$gallery = $variation->get_meta( '_yith_wccl_gallery', true );
				}
			}
		}

		return $gallery;
	}
}

if ( ! function_exists( 'yith_wccl_get_frontend_selectors' ) ) {
	/**
	 * Return correct selectors to use in frontend JS based on theme installed
	 *
	 * @since 1.10.2
	 * @param string $section Current section.
	 * @return string
	 */
	function yith_wccl_get_frontend_selectors( $section ) {

		// Get the current theme.
		$theme     = yith_wccl_get_current_theme();
		$selectors = array();

		switch ( $section ) {
			case 'single_gallery_selector':
				// Search for theme.
				if ( 'flatsome' === $theme ) {
					$selectors[] = '.product-gallery';
				} elseif ( 'salient' === $theme ) {
					$selectors[] = '.single-product-main-image > .images';
				} else {
					$selectors[] = '.woocommerce-product-gallery';
				}
				break;
			case 'wrapper_container_shop':
				if ( 'flatsome' === $theme ) {
					$selectors[] = 'div.product.product-small';
				} else {
					$selectors[] = apply_filters('yith_wccl_wrapper_container_selector', 'li.product');
				}

				// Append YITH Wishlist container.
				if ( defined( 'YITH_WCWL' ) ) {
					$selectors[] = '.wishlist-items-wrapper .product-add-to-cart';
				}

				break;
			case 'image_selector':
				$selectors = array( 'img.wp-post-image', 'img.attachment-woocommerce_thumbnail' );
				break;
			default:
				break;
		}

		return implode( ',', $selectors );
	}
}

if ( ! function_exists( 'yith_wccl_get_current_theme' ) ) {
	/**
	 * Return current active theme
	 *
	 * @since 1.10.2
	 * @return string
	 */
	function yith_wccl_get_current_theme() {

		// Get the installed theme.
		$theme = wp_cache_get( 'yith_wccl_current_theme', 'yith_wccl' );
		if ( false === $theme ) {
			$theme = '';
			if ( function_exists( 'wp_get_theme' ) ) {
				if ( is_child_theme() ) {
					$temp_obj  = wp_get_theme();
					$theme_obj = wp_get_theme( $temp_obj->get( 'Template' ) );
				} else {
					$theme_obj = wp_get_theme();
				}

				$theme = $theme_obj->get( 'TextDomain' );
				if ( empty( $theme ) ) {
					$theme = $theme_obj->get( 'Name' );
				}
			}

			wp_cache_set( 'yith_wccl_current_theme', $theme, 'yith_wccl' );
		}

		return $theme;
	}
}

if ( ! function_exists( 'yith_wccl_hex2rgba' ) ) {
	/**
	 * Convert value from hex to rgba
	 *
	 * @param string $color The Hex color.
	 * @param number $opacity The opacity value.
	 * @return string
	 * @since 2.0.0
	 */
	function yith_wccl_hex2rgba( $color, $opacity ) {
		$default = 'rgb(0,0,0)';

		// Return default if no color provided.
		if ( empty( $color ) ) {
			return $default;
		}

		// Sanitize $color if "#" is provided.
		if ( '#' === $color[0] ) {
			$color = substr( $color, 1 );
		}

		// Check if color has 6 or 3 characters and get values.
		if ( strlen( $color ) === 6 ) {
			$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) === 3 ) {
			$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return $default;
		}

		// Convert hexadec to rgb.
		$rgb = array_map( 'hexdec', $hex );

		// Check if opacity is set(rgba or rgb).
		if ( $opacity ) {
			$output = 'rgba(' . implode( ',', $rgb ) . ',' . $opacity . ')';
		} else {
			$output = 'rgb(' . implode( ',', $rgb ) . ')';
		}

		// Return rgb(a) color string.
		return $output;
	}
}

if ( ! function_exists( 'yith_wccl_get_field' ) ) {
	/**
	 * Print a form field for an attribute field
	 *
	 * @param array $field The field.
	 * @since 2.0.0
	 */
	function yith_wccl_get_field( $field ) {
		$defaults = array(
			'class'     => '',
			'title'     => '',
			'label_for' => '',
			'desc'      => '',
			'data'      => array(),
			'fields'    => array(),
		);

		/**
		 * APPLY_FILTERS: yith_wccl_form_field_args
		 *
		 * Filter the array of the arguments for the fields in the product metabox.
		 *
		 * @param array $args  Array of arguments.
		 * @param array $field Field.
		 *
		 * @return array
		 */
		$field = apply_filters( 'yith_wccl_form_field_args', wp_parse_args( $field, $defaults ), $field );

		/**
		 * Variable information for extract
		 *
		 * @var string $class
		 * @var string $title
		 * @var string $label_for
		 * @var string $desc
		 * @var array  $data
		 * @var array  $fields
		 */
		extract( $field ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		if ( ! $label_for && $fields ) {
			$first_field = current( $fields );

			if ( isset( $first_field['id'] ) ) {
				$label_for = $first_field['id'];
			}
		}

		$data_html = '';

		foreach ( $data as $key => $value ) {
			$data_html .= "data-{$key}='{$value}' ";
		}

		$html  = '';
		$html .= "<div class='yith-wccl-form-field yith-plugin-ui {$class}' {$data_html}>";
		$html .= "<label class='yith-wccl-form-field__label' for='{$label_for}' style='display: none'>{$label}</label>";

		$html .= "<div class='yith-wccl-form-field__container'>";
		ob_start();
		yith_plugin_fw_get_field( $fields, true ); // Print field using plugin-fw.
		$html .= ob_get_clean();
		if ( $desc ) {
			$html .= "<div class='yith-wccl-form-field__description'>{$desc}</div>";
		}
		$html .= '</div><!-- yith-wccl-form-field__container -->';

		$html .= '</div><!-- yith-wccl-form-field -->';

		/**
		 * APPLY_FILTERS: yith_wccl_form_field_html
		 *
		 * Filter the HTML for the fields in the product metabox.
		 *
		 * @param string $html  Field HTML
		 * @param array  $field Field
		 *
		 * @return string
		 */
        echo apply_filters( 'yith_wccl_form_field_html', $html, $field ); // phpcs:ignore
	}
}


if ( ! function_exists( 'yith_wccl_get_term_field' ) ) {
	/**
	 * Get the fields for each attribute.
	 *
	 * @param string $type Attribute type.
	 * @param object $term the term taxonomy.
	 * @param string $taxonomy The taxonomy slug.
	 * @param array  $custom_types The custom attribute type added by the plugin.
	 * @return array
	 *
	 * @since 2.0.0
	 */
	function yith_wccl_get_term_field( $type, $term, $taxonomy, $custom_types ) {

		$fields = array(
			// Colorpicker fields.
			'colorpicker' => array(
				'use_for_tooltip' => array(
					'label'  => '',
					'class'  => 'yith-wccl-hide-if-product',
					'fields' => array(
						'class'       => 'ywccl_use_tooltip',
						'type'        => 'checkbox',
						'value'       => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_use_for_tooltip', true, $taxonomy ) : '',
						'id'          => 'yith_wccl_term_use_for_tooltip',
						'name'        => 'term_use_for_tooltip',
						'desc-inline' => __( 'Use for tooltip', 'yith-woocommerce-color-label-variations' ),
					),
				),
				'swatch_type'     => array(
					'label'  => __( 'Swatch type', 'yith-woocommerce-color-label-variations' ),
					'class'  => '',
					'fields' => array(
						'class'   => 'ywccl_swatch_type wc-enhanced-select',
						'type'    => 'select',
						'options' => array(
							'single_color' => __( 'Single color', 'yith-woocommerce-color-label-variations' ),
							'dual_color'   => __( 'Bicolor', 'yith-woocommerce-color-label-variations' ),
							'image_color'  => __( 'Image', 'yith-woocommerce-color-label-variations' ),
						),
						'value'   => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_swatch_type', true, $taxonomy ) : '',
						'id'      => 'yith_wccl_term_swatch_type',
						'name'    => 'term_swatch_type',
						'default' => 'single_color',
					),
				),
				'value'           => array(
					'label'  => isset( $custom_types[ $type ] ) ? $custom_types[ $type ] : __( 'Value', 'yith-woocommerce-color-label-variations' ),
					'desc'   => '',
					'class'  => 'ywccl_show_if_no_image_color',
					'fields' => array(
						'class'  => 'ywccl',
						'type'   => 'custom',
						'value'  => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_value', true, $taxonomy ) : '',
						'id'     => 'term_value',
						'name'   => 'term_value',
						'action' => 'yith_wccl_colorpicker_attribute',
					),
				),
				'attribute_image' => array(
					'label'  => '',
					'desc'   => '',
					'class'  => 'ywccl_show_if_image_color',
					'fields' => array(
						'class' => '',
						'type'  => 'media',
						'value' => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_attribute_image', true, $taxonomy ) : '',
						'id'    => 'attribute_image',
						'name'  => 'attribute_image',
                        'allow_custom_url' => false,
					),
				),
				'tooltip'         => array(
					'label'  => __( 'Tooltip text', 'yith-woocommerce-color-label-variations' ),
					'class'  => 'ywccl_show_if_use_for_tooltip',
					'fields' => array(
						'class' => 'ywccl_tooltip_text',
						'type'  => 'text',
						'value' => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip', true, $taxonomy ) : '',
						'id'    => 'term_tooltip',
						'name'  => 'term_tooltip',
					),
					'desc'   => '',

				),
				'tooltip_image'   => array(
					'label'  => __( 'Tooltip image', 'yith-woocommerce-color-label-variations' ),
					'class'  => '',
					'fields' => array(
						'class' => '',
						'type'  => 'media',
						'value' => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip_image', true, $taxonomy ) : '',
						'id'    => 'tooltip_image',
						'name'  => 'tooltip_image',
                        'allow_custom_url' => false,
                        'data'  => array(
							'type' => 'colorpicker',
						),
					),
					'desc'   => '',

				),
			),
			// Image fields.
			'image'       => array(
				'use_for_tooltip'    => array(
					'label'  => '',
					'class'  => 'yith-wccl-hide-if-product',
					'fields' => array(
						'class'       => 'ywccl_use_tooltip',
						'type'        => 'checkbox',
						'value'       => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_use_for_tooltip', true, $taxonomy ) : '',
						'id'          => 'yith_wccl_term_use_for_tooltip',
						'name'        => 'term_use_for_tooltip',
						'desc-inline' => __( 'Use for tooltip', 'yith-woocommerce-color-label-variations' ),
					),
				),
				'value'              => array(
					'label'  => isset( $custom_types[ $type ] ) ? $custom_types[ $type ] : __( 'Value', 'yith-woocommerce-color-label-variations' ),
					'desc'   => '',
					'class'  => '',
					'fields' => array(
						'class' => 'ywccl',
						'type'  => 'media',
						'value' => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_value', true, $taxonomy ) : '',
						'id'    => 'term_value',
						'name'  => 'term_value',
                        'allow_custom_url' => false,
                        'data'  => array(
							'type' => 'image',
						),
					),
				),
				'tooltip'            => array(
					'class'  => 'ywccl_show_if_use_for_tooltip',
					'label'  => __( 'Tooltip text', 'yith-woocommerce-color-label-variations' ),
					'fields' => array(
						'class' => 'ywccl_tooltip_text',
						'type'  => 'text',
						'value' => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip', true, $taxonomy ) : '',
						'id'    => 'yith_wccl_term_tooltip',
						'name'  => 'term_tooltip',
					),
					'desc'   => '',
				),
				'tooltip_image_type' => array(
					'label'  => __( 'Tooltip image', 'yith-woocommerce-color-label-variations' ),
					'class'  => '',
					'fields' => array(
						'class'   => 'ywccl_tooltip_image_type wc-enhanced-select',
						'type'    => 'select',
						'options' => array(
							'no_image'        => __( 'No image', 'yith-woocommerce-color-label-variations' ),
							'attribute_image' => __( 'Use attribute image', 'yith-woocommerce-color-label-variations' ),
							'upload_image'    => __( 'Upload image', 'yith-woocommerce-color-label-variations' ),
						),
						'value'   => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip_image_type', true, $taxonomy ) : '',
						'id'      => 'yith_wccl_term_tooltip_image_type',
						'name'    => 'term_tooltip_image_type',
						'default' => 'no_image',
					),
				),
				'tooltip_image'      => array(
					'class'  => 'ywccl_show_if_upload_image',
					'label'  => '',
					'fields' => array(
						'class' => '',
						'type'  => 'media',
						'value' => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip_image', true, $taxonomy ) : '',
						'id'    => 'tooltip_image',
						'name'  => 'tooltip_image',
                        'allow_custom_url' => false,
                        'data'  => array(
							'type' => 'colorpicker',
						),
					),
					'desc'   => '',
				),
			),
			// Label fields.
			'label'       => array(
				'use_for_tooltip' => array(
					'label'  => '',
					'class'  => 'yith-wccl-hide-if-product',
					'fields' => array(
						'class'       => 'ywccl_use_tooltip',
						'type'        => 'checkbox',
						'value'       => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_use_for_tooltip', true, $taxonomy ) : '',
						'id'          => 'yith_wccl_term_use_for_tooltip',
						'name'        => 'term_use_for_tooltip',
						'desc-inline' => __( 'Use for tooltip', 'yith-woocommerce-color-label-variations' ),
					),
				),
				'use_for_label'   => array(
					'label'  => '',
					'class'  => 'yith-wccl-hide-if-product',
					'fields' => array(
						'class'       => 'ywccl_use_label',
						'type'        => 'checkbox',
						'value'       => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_use_for_label', true, $taxonomy ) : '',
						'id'          => 'yith_wccl_term_use_for_label',
						'name'        => 'term_use_for_label',
						'desc-inline' => __( 'Use for label', 'yith-woocommerce-color-label-variations' ),
					),
				),
				'value'           => array(
					'label'  => isset( $custom_types[ $type ] ) ? $custom_types[ $type ] : __( 'Value', 'yith-woocommerce-color-label-variations' ),
					'desc'   => '',
					'class'  => 'ywccl_show_if_use_for_label',
					'fields' => array(
						'class' => 'ywccl',
						'type'  => 'text',
						'value' => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_value', true, $taxonomy ) : '',
						'id'    => 'term_value',
						'name'  => 'term_value',
						'data'  => array(
							'type' => 'label',
						),
					),
				),
				'tooltip'         => array(
					'label'  => __( 'Tooltip text', 'yith-woocommerce-color-label-variations' ),
					'class'  => 'ywccl_show_if_use_for_tooltip',
					'fields' => array(
						'class' => 'ywccl_tooltip_text',
						'type'  => 'text',
						'value' => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip', true, $taxonomy ) : '',
						'id'    => 'term_tooltip',
						'name'  => 'term_tooltip',
					),
					'desc'   => '',
				),
				'tooltip_image'   => array(
					'label'  => __( 'Tooltip image', 'yith-woocommerce-color-label-variations' ),
					'class'  => '',
					'fields' => array(
						'class' => '',
						'type'  => 'media',
						'value' => $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip_image', true, $taxonomy ) : '',
						'id'    => 'tooltip_image',
						'name'  => 'tooltip_image',
                        'allow_custom_url' => false,
                        'data'  => array(
							'type' => 'image',
						),
					),
					'desc'   => '',

				),
			),
			// Select fields.
			'select'      => array(),
		);

		$fields_type = $fields[ $type ] ?? array();

		if ( ! empty( $fields_type ) ) {
			$fields_type['hidden'] = array(
				'label'  => '',
				'class'  => '',
				'fields' => array(
					'type'  => 'hidden',
					'value' => $type,
					'id'    => 'term_attribute_type',
					'name'  => 'term_attribute_type',
				),
			);
		}

		return apply_filters( 'yith_wccl_gel_fields_type', $fields_type, $type, $term, $taxonomy, $custom_types );

	}
}

if( !function_exists('yith_wccl_get_supported_taxonomies') ) {
    /**
     * Retrieve product taxonomies list
     *
     * @return array
     * @since 3.0.0
     */
    function yith_wccl_get_supported_taxonomies()
    {
        $product_taxonomies = get_object_taxonomies('product', 'object' );
        $supported_taxonomies = array();

        if ( !empty( $product_taxonomies ) ) {
            foreach ($product_taxonomies as $taxonomy_slug => $taxonomy) {
                if ( in_array( $taxonomy_slug, array('product_cat', 'product_tag'), true ) || 0 !== strpos( $taxonomy_slug, 'pa_' ) ) {
                    continue;
                }

                $supported_taxonomies[$taxonomy_slug] = $taxonomy->label;
            }
        }
        return $supported_taxonomies;
    }
}