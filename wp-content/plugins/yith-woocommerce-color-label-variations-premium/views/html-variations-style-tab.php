<?php
/** Variations style tab view.
 *
 * @var WC_Product         $product
 * @var int                $tab_id
 *
 * @package YITH\ColorsAndLabels\Views
 */

global $wc_product_attributes;

echo '<div id="' . esc_attr( $tab_id ) . '" class="panel woocommerce_options_panel">';

if ( $product && 'variable' === $product->get_type() ) {

    $attr_term_value = $product->get_meta('_yith_wccl_product_terms', true );

    ?>
					<div class="yith-wccl-title-section">
						<h3><?php esc_html_e( 'Attributes Style', 'yith-woocommerce-color-label-variations' ); ?></h3>
						<span class="yith-wccl-panel-description"><?php esc_html_e( 'Customize the display of this product\'s attributes.', 'yith-woocommerce-color-label-variations' ); ?> </span>
					</div>
					<?php
					$attributes = $product->get_attributes();
					?>
					<div class="yith-wccl-admin-attributes-section">
					<?php
					foreach ( $attributes as $attribute_name => $attribute ) {

						if ( ! $attribute->is_taxonomy() ) {
							continue;
						}

						?>
							<div class="yith-wccl-attribute-box-section">
								<div class="yith-wccl-attribute-row-box yith-wccl-closed" >
									<div class="yith-wccl-section-row-title">
										<h4>
											<strong class="attribute_name<?php echo esc_attr( $attribute->get_name() === '' ? ' placeholder' : '' ); ?>"><?php echo esc_html( $attribute->get_name() !== '' ? wc_attribute_label( $attribute->get_name() ) : __( 'Custom attribute', 'woocommerce' ) ); ?></strong>
										</h4>
										<div class="yith-wccl-box-toggle">
											<span class="yith-wccl-plus yith-wccl-icon">
												<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
													<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"></path>
												</svg>
											</span>
											<span class="yith-wccl-minus yith-wccl-icon yith-wccl-toggle-disable">
												<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
													<path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6"></path>
												</svg>
											</span>
										</div>
									</div>
									<div class="yith-wccl-attribute-section-row-content">
										<?php foreach ( $attribute->get_terms() as $attr_term ) : ?>
											<div class="yith-wccl-term-row-box yith-wccl-closed" >
												<div class="yith-wccl-term-section-row-title">
													<h4>
														<strong class="term_name<?php echo esc_attr( $attribute->get_name() === '' ? ' placeholder' : '' ); ?>"><?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $attr_term->name, $attr_term, $attribute->get_name(), $product_object ) ); ?></strong>
													</h4>
													<div class="yith-wccl-box-toggle-term">
														<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
															<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"></path>
														</svg>
													</div>
												</div>
												<div class="yith-wccl-term-section-row-content">
													<?php

														$attr_type = isset( $wc_product_attributes[ $attribute_name ] ) ? $wc_product_attributes[ $attribute_name ]->attribute_type : 'select';
														$fields    = yith_wccl_get_term_field( $attr_type, $attr_term, $attribute->get_name(), $wc_product_attributes );
														$override_global = array(
                                                            'override_global'   => array(
                                                                'label'  => __( 'Override attribute options', 'yith-woocommerce-color-label-variations' ),
                                                                'class'  => 'yith-wccl-override-global-term-field ywccl-product-section',
                                                                'fields' => array(
                                                                    'class'       => 'yith-wccl-override-global-term',
                                                                    'type'        => 'onoff',
                                                                    'value'       => '',
                                                                    'id'          => 'yith_wccl_term_override_global',
                                                                    'name'        => 'override_global',
                                                                    'default'     => 'no',
                                                                ),
                                                                'desc' => sprintf( esc_html_x( 'Enable to set custom display options for the "%s" attributes in this specific product.','Term name, for example Pink', 'yith-woocommerce-color-label-variations' ), $attr_term->name ),
                                                            ),
                                                        );
                                                        $fields = array_merge( $override_global, $fields );
                                                        include YITH_WCCL_VIEW_PATH . '/html-variations-attribute-term-fields.php';
													?>

												</div>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
														<?php
					}
					?>
					</div>
					<?php
} else {

    ?>
    <div class="yith-wccl-title-section">
        <h3><?php esc_html_e( 'Attributes Style', 'yith-woocommerce-color-label-variations' ); ?></h3>
        <span class="yith-wccl-panel-description"><?php esc_html_e( 'Customize the display of this product\'s attributes.', 'yith-woocommerce-color-label-variations' ); ?> </span>
    </div>

    <div id="" class="yith-wccl-blank-variation-style">
        <i class="yith-wccl-blank-state-icon"></i>
        <div>
            <b><?php esc_html_e( 'No attributes set for this product', 'yith-woocommerce-color-label-variations' ); ?></b></br>
            <div>
                <?php echo sprintf( __( 'Before you can customize the variations style, you need to add some variation attributes on the %s', 'yith-woocommerce-color-label-variations' ), sprintf('<a class="yith-wccl-attributes-link" href="">%s</a>', 'Attributes tab')  ); ?>
            </div>
        </div>
    </div>
    <?php

}

echo '</div>';
