<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Variable product add to cart in loop
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 * @version 1.0.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

?>

<div class="variations_form cart in_loop" data-product_id="<?php echo intval( $product_id ); ?>" data-active_variation=""
		data-product_variations="<?php echo $data_product_variations; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>" data-number-variation-attributes="<?php echo esc_attr( $variation_attributes_number ) ?>">
	<?php
	foreach ( $attributes as $name => $options ) :

		// check for default attribute.
		if ( isset( $selected_attributes[ sanitize_title( $name ) ] ) ) {
			$selected_value = $selected_attributes[ sanitize_title( $name ) ];
		} else {
			$selected_value = '';
		}

		$sanitized_name = esc_attr( sanitize_title( $name ) );

		$attribute_type = isset( $attributes_types[ $name ] ) ? 'data-type=' . esc_attr( $attributes_types[ $name ] )  : '';

		?>
		<div class="<?php echo 'variations ' . esc_attr( $sanitized_name ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>">
			<?php
			if ( isset( $attributes_types[ $name ] ) && apply_filters('yith_wccl_enable_label_in_loop', false ) ) {
				?>
				<label class="ywccl-loop-label" for="<?php echo esc_attr( $sanitized_name ); ?>"><?php echo wc_attribute_label( $name ); // phpcs:ignore. ?></label>
				<?php
			}
			?>
			<select id="<?php echo esc_attr( $sanitized_name ); ?>" name="attribute_<?php echo esc_attr( $sanitized_name ); ?>" data-attribute_name="attribute_<?php echo esc_attr( $sanitized_name ); ?>"
				<?php
					echo esc_attr( $attribute_type );
				?>
					data-default_value="<?php echo esc_attr( $selected_value ); ?>">
				<?php
				/**
				 * APPLY_FILTER: yith_wccl_empty_option_loop_label
				 *
				 * Empty option loop label.
				 *
				 * @param string $label the default label
				 * @param string $name  attribute name
				 *
				 * @return array
				 */
				?>
				<option value=""><?php echo esc_html( apply_filters( 'yith_wccl_empty_option_loop_label', __( 'Choose an option', 'yith-woocommerce-color-label-variations' ), $name ) ); ?></option>
				<?php

				if ( is_array( $options ) ) {

					// Get terms if this is a taxonomy - ordered.
					if ( taxonomy_exists( $name ) ) {

						$terms = wc_get_product_terms( $product_id, $name, array( 'fields' => 'all' ) );

						foreach ( $terms as $term ) { //phpcs:ignore
							if ( ! in_array( $term->slug, $options, true ) ) {
								continue;
							}
							$term_values   = apply_filters(
								'yith_wccl_create_custom_attributes_term_attr',
								array(
									'value'         => ywccl_get_term_meta( $term->term_id, '_yith_wccl_value', true, $name ),
									'tooltip'       => ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip', true, $name ),
									'tooltip_image' => ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip_image', true, $name ),
									'type'          => isset( $attributes_types[ $name ] ) ? $attributes_types[ $name ] : false,
								),
								$name,
								$term,
								wc_get_product( $product_id ),
							);
							$value         = is_array( $term_values['value'] ) ? implode( ',', $term_values['value'] ) : $term_values['value'];
							$tooltip       = $term_values['tooltip'];
							$tooltip_image = $term_values['tooltip_image'];

							$attribute_option_type = ( $term_values['type'] ) ? 'data-type="' . esc_attr( $term_values['type'] ) . '"' : $attribute_type;
							echo '<option value="' . esc_attr( $term->slug ) . '"' . selected( sanitize_title( $selected_value ), sanitize_title( $term->slug ), false ) . ' data-value="' . esc_attr( $value ) . '" data-tooltip="' . esc_attr( $tooltip ) . '" data-tooltip_image="' . esc_attr( $tooltip_image ) . '" ' . $attribute_option_type . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) ) . '</option>';  // phpcs:ignore.
						}
					} else {

						foreach ( $options as $option ) {
							echo '<option value="' . esc_attr( $option ) . '"' . selected( sanitize_title( $selected_value ), sanitize_title( $option ), false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
						}
					}
				}
				?>
			</select>
		</div>
	<?php endforeach; ?>
</div>
