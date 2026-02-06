<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Main class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 * @version 1.0.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCCL' ) ) {
	/**
	 * YITH WooCommerce Color and Label Variations Premium
	 *
	 * @since 1.0.0
	 */
	class YITH_WCCL {


		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var YITH_WCCL
		 */
		protected static $instance;
		/**
		 * Single instance of the integrations class
		 *
		 * @since 2.0.0
		 * @var YITH_WCCL_Integrations
		 */
		public $integrations;
		/**
		 * Single instance of the blocks class
		 *
		 * @since 3.0.0
		 * @var YITH_WCCL_WC_Blocks
		 */
		public $wc_blocks;

		/**
		 * Single instance of the blocks class
		 *
		 * @since 3.0.0
		 * @var YITH_WCCL_WC_Endpoints/Null
		 */
		public $wc_endpoints = null;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 * @return YITH_WCCL
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function __construct() {

			// Load Plugin Framework.
			add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );

			/**
			 * APPLY_FILTERS: yith_wccl_require_class
			 *
			 * Filter the required files to be loaded in the plugin.
			 *
			 * @param array $files Required files
			 *
			 * @return array
			 */
			$require = apply_filters(
				'yith_wccl_require_class',
				array(
					'common' => array(
						'includes/integrations/class-yith-wccl-integrations.php',
						'includes/wc-blocks/src/class-yith-wccl-wc-endpoints.php',
					),
				)
			);

			$this->require( $require );
			$this->init_common_classes();

			// Class admin.
			if ( $this->is_admin() ) {
				// Require classes.
				require_once 'class.yith-wccl-admin.php';
				YITH_WCCL_Admin();
			} else {
				// Require classes.
				require_once 'class.yith-wccl-frontend.php';
				YITH_WCCL_Frontend();
			}

			add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );

			// Add new attribute types.
			add_filter( 'product_attributes_type_selector', array( $this, 'attribute_types' ), 10, 1 );
			// Delete transient on update stock.
			add_action( 'woocommerce_variation_set_stock', array( $this, 'delete_transient' ), 10, 1 );
			add_action( 'woocommerce_product_set_stock', array( $this, 'delete_transient' ), 10, 1 );
			// Delete transient on save product.
			add_action( 'woocommerce_before_product_object_save', array( $this, 'delete_transient' ), 10, 2 );
			add_action( 'woocommerce_before_product_variation_object_save', array( $this, 'delete_transient' ), 10, 2 );
			// Delete all transient on attribute save/update.
			add_action( 'woocommerce_attribute_updated', array( $this, 'delete_all_transient' ), 10 );
			// Compatibility with WP ALL IMPORT (import gallery for variations).
			add_action( 'pmxi_gallery_image', array( $this, 'wpai_import_gallery_images_variation' ), 10, 4 );
			// Process variation gallery for WC import/export products.
			add_filter( 'woocommerce_product_export_meta_value', array( $this, 'product_export_meta_value' ), 10, 4 );
			add_filter( 'woocommerce_product_import_process_item_data', array( $this, 'product_import_gallery' ), 10, 2 );
			// Init variations terms.
			add_action( 'init', array( $this, 'init_variations_term' ) );
			add_action( 'woocommerce_product_object_updated_props', array( $this, 'update_variations_terms' ), 10, 1 );
			add_action( 'woocommerce_before_delete_product_variation', array( $this, 'delete_variations_terms' ), 10, 1 );

			// AJAX Filter plugin compatibility.
			add_filter( 'yith_wcan_attribute_filter_item_args', array( $this, 'ajax_filter_compatibility' ), 10, 2 );
		}

		/**
		 * Add the main classes file
		 *
		 * Include the admin and frontend classes
		 *
		 * @param array $main_classes array The require classes file path.
		 *
		 * @since  2.0
		 *
		 * @return void
		 * @access protected
		 */
		protected function require( $main_classes ) {
			foreach ( $main_classes as $section => $classes ) {
				foreach ( $classes as $class ) {
					if ( 'common' === $section || ( 'frontend' === $section && ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) || ( 'admin' === $section && is_admin() ) && file_exists( YITH_WCCL_DIR . $class ) ) {
						include_once YITH_WCCL_DIR . $class;
					}
				}
			}
		}

		/**
		 * Init classes
		 */
		public function init_common_classes() {
			$this->integrations = YITH_WCCL_Integrations();
			$this->wc_endpoints = YITH_WCCL_WC_Endpoints();
		}

		/**
		 * Check if context is admin
		 *
		 * @since  1.2.2
		 * @return boolean
		 */
		public function is_admin() {
			/**
			 * APPLY_FILTER: yith_wccl_is_admin_actions_array
			 *
			 * Admin action list.
			 *
			 * @param array $args admin actions.
			 *
			 * @return array
			 */
			$actions = apply_filters(
				'yith_wccl_is_admin_actions_array',
				array(
					'prdctfltr_respond_550',
					'flatsome_quickview',
					'mwb_wvuc_get_product_html',
					'astra_shop_pagination_infinite',
					'facetwp_refresh',
					'ywapo_load_product_template',
				)
			);

			$is_frontend = isset( $_REQUEST['context'] ) && 'frontend' === sanitize_text_field( wp_unslash( $_REQUEST['context'] ) ); //phpcs:ignore WordPress.Security.NonceVerification
			$is_ajax     = defined( 'DOING_AJAX' ) && DOING_AJAX && ( $is_frontend || ( isset( $_REQUEST['action'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ), $actions, true ) ) ); //phpcs:ignore WordPress.Security.NonceVerification
			/**
			 * APPLY_FILTER: yith_wccl_load_admin_class
			 *
			 * Filter for check if load admin class.
			 *
			 * @param bool args load admin class
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_wccl_load_admin_class', ( is_admin() && ! $is_ajax ) );
		}


		/**
		 * Declare support for WooCommerce features.
		 *
		 * @since 2.0.0
		 */
		public function declare_wc_features_support() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				$init = defined( 'YITH_WCCL_INIT' ) ? YITH_WCCL_INIT : false;
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $init, true );
			}
		}

		/**
		 * Delete plugin transient on product save
		 *
		 * @since  1.5.0
		 * @param WC_Product $product The product object saved.
		 * @param array      $data An array of data saved.
		 */
		public function delete_transient( $product, $data = array() ) {
			// Get the ID.
			$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			// WPML support.
			$languages = apply_filters( 'wpml_active_languages', null );
			if ( ! empty( $languages ) ) {
				foreach ( $languages as $lang_code => $lang_opts ) {
					$translated_id = apply_filters( 'wpml_object_id', $id, 'product', true, $lang_code );
					if ( ! empty( $translated_id ) ) {
						delete_transient( 'yith_wccl_available_variations_' . $translated_id );
					}
				}
			} else {
				delete_transient( 'yith_wccl_available_variations_' . $id );
			}

			delete_transient( 'yith_wccl_variations_parent_product_id' );
			delete_transient( 'yith_wccl_loop_excluded_variations' );
			delete_transient( 'yith_wccl_loop_hidden_variable' );
		}

		/**
		 * Delete all transient on attribute save/update
		 *
		 * @since 1.14.1
		 * @return void
		 */
		public function delete_all_transient() {
			global $wpdb;

			$option         = '_transient_yith_wccl_available_variations_%';
			$option_timeout = '_transient_timeout_yith_wccl_available_variations_%';
			// @codingStandardsIgnoreStart
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $option));
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $option_timeout));
			// @codingStandardsIgnoreEnd
		}

		/**
		 * Add new attribute types to standard WooCommerce
		 *
		 * @since  1.5.0
		 * @param array $default_type An array of WooCommerce attribute types.
		 * @return array
		 */
		public function attribute_types( $default_type ) {
			$custom = ywccl_get_custom_tax_types();

			return is_array( $custom ) ? array_merge( $default_type, $custom ) : $default_type;
		}


		/**
		 * Import gallery images for single variation with WP ALL IMPORT
		 *
		 * @param integer|string $post_id The post ID.
		 * @param string         $att_id The attached ID.
		 * @param string         $filepath Imported file path.
		 * @param string         $is_keep_existing_images (Optional) Keep existing images.
		 * @return void
		 */
		public function wpai_import_gallery_images_variation( $post_id, $att_id, $filepath, $is_keep_existing_images = '' ) {
			// Get the ID of the featured image.
			$featured_image = get_post_meta( $post_id, '_thumbnail_id', true );

			$gallery = get_post_meta( $post_id, '_yith_wccl_gallery', true );
			if ( empty( $gallery ) ) {
				$gallery = array();
			}

			if ( ! in_array( absint( $att_id ), array_map( 'absint', $gallery ), true ) && ( ! empty( $featured_image ) && absint( $featured_image ) !== absint( $att_id ) ) ) {
				$gallery[] = $att_id;
				update_post_meta( $post_id, '_yith_wccl_gallery', $gallery );
			}
		}

		/**
		 * Filter gallery value on product export
		 *
		 * @since  1.8.15
		 * @param mixed      $value The meta value.
		 * @param string     $meta The meta key.
		 * @param WC_Product $product The product object.
		 * @param mixed      $row The exported row.
		 * @return mixed
		 */
		public function product_export_meta_value( $value, $meta, $product, $row ) {
			global $sitepress;

			if ( '_yith_wccl_gallery' !== $meta->key ) {
				return $value;
			}

			if ( function_exists( 'icl_object_id' ) && ! empty( $sitepress ) ) {
				$parent_translation_id = icl_object_id( $product->get_id(), 'post', true, $sitepress->get_default_language() );
				$product               = wc_get_product( $parent_translation_id );
			}

			$gallery = is_array( $value ) ? $value : $product->get_meta( '_yith_wccl_gallery', true ); // Make sure gallery is correct.
			if ( $gallery ) {
				$value = array();
				foreach ( $gallery as $attachment_id ) {
					$image = wp_get_attachment_url( $attachment_id );
					if ( $image ) {
						$value[] = $image;
					}
				}
				$value = implode( ',', $value );
			}

			return $value;
		}

		/**
		 * Import variation gallery
		 *
		 * @param array $data An array of product meta.
		 * @return array
		 */
		public function product_import_gallery( $data ) {
			if ( isset( $data['meta_data'] ) ) {
				foreach ( $data['meta_data'] as $index => $meta ) {
					if ( '_yith_wccl_gallery' === $meta['key'] && ! empty( $meta['value'] ) ) {
						$gallery   = explode( ',', $meta['value'] );
						$new_value = array();

						foreach ( $gallery as $image ) {
							$id = $this->get_attachment_id_from_url( $image );
							if ( $id ) {
								$new_value[] = $id;
							}
						}

						$data['meta_data'][ $index ]['value'] = $new_value;
					}
				}
			}

			return $data;
		}

		/**
		 * Get an attachment id from the url
		 *
		 * @param string $url The attachment source.
		 * @return int|mixed|WP_Post
		 */
		public function get_attachment_id_from_url( $url ) {
			if ( empty( $url ) ) {
				return 0;
			}

			$id  = 0;
			$ids = get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'any',
					'fields'      => 'ids',
					'meta_query'  => array( //phpcs:ignore slow query ok.
						array(
							'value' => $url,
							'key'   => '_wc_attachment_source',
						),
					),
				)
			);

			if ( $ids ) {
				$id = current( $ids );
			}

			// Upload if attachment does not exists.
			if ( ! $id && stristr( $url, '://' ) ) {
				$upload = wc_rest_upload_image_from_url( $url );

				if ( is_wp_error( $upload ) ) {
					return 0;
				}

				$id = wc_rest_set_uploaded_image_as_attachment( $upload, 0 );
				if ( ! wp_attachment_is_image( $id ) ) {
					return 0;
				}
				// Save attachment source for future reference.
				update_post_meta( $id, '_wc_attachment_source', $url );
			}

			return $id;
		}

		/**
		 * Init variations terms
		 *
		 * @since  1.9.3
		 * @return void
		 */
		public function init_variations_term() {

			$variation_enabled = get_option( 'yith-wccl-show-single-variations-loop', 'no' );
			$initialized       = get_option( 'yith_wccl_init_variation_terms_initialized', 'no' );
			$running           = get_option( 'yith_wccl_init_variation_terms_running', 'no' );
			if ( 'yes' !== $variation_enabled || 'yes' === $initialized || 'yes' === $running ) {
				return;
			}

			update_option( 'yith_wccl_init_variation_terms_running', 'yes' );
			$offset = get_option( 'yith_wccl_init_variation_terms_offset', 0 );
			$offset = absint( $offset );

			$products = wc_get_products(
				array(
					'status' => 'publish',
					'type'   => 'variable',
					'limit'  => 1,
					'offset' => $offset,
				)
			);

			if ( empty( $products ) ) {
				delete_option( 'yith_wccl_init_variation_terms_running' );
				delete_option( 'yith_wccl_init_variation_terms_offset' );
				update_option( 'yith_wccl_init_variation_terms_initialized', 'yes' );
			}

			foreach ( $products as $product ) {
				$product_id     = $product->get_id();
				$variations_ids = $product->get_children();
				if ( empty( $variations_ids ) ) {
					continue;
				}

				// Collect terms.
				$product_terms = array();
				foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
					$terms = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'ids' ) );
					if ( ! is_wp_error( $terms ) ) {
						$product_terms[ $taxonomy ] = $terms;
					}
				}

				foreach ( $variations_ids as $variation_id ) {
					// Add terms.
					foreach ( $product_terms as $taxonomy => $terms ) {
						wp_set_post_terms( $variation_id, $terms, $taxonomy );
					}
					// Add attributes.
					$attributes = wc_get_product_variation_attributes( $variation_id );

					if ( ! empty( $attributes ) ) {
						foreach ( $attributes as $taxonomy => $value ) {
							$taxonomy = str_replace( 'attribute_', '', $taxonomy );
							wp_set_object_terms( $variation_id, $value, $taxonomy );
						}
					}
				}
			}

			update_option( 'yith_wccl_init_variation_terms_running', 'no' );
			update_option( 'yith_wccl_init_variation_terms_offset', 1 + $offset );
		}

		/**
		 * Update variation terms on product update
		 *
		 * @since  1.9.3
		 * @param WC_Product $product The product object.
		 * @return void
		 */
		public function update_variations_terms( $product ) {

			$changes = $product->get_changes();

			if ( $product->is_type( 'variable' ) ) {
				$variation_ids = $product->get_children();
				$categories    = $product->get_category_ids( 'edit' );
				$tags          = $product->get_tag_ids( 'edit' );

				if ( ! empty( $variation_ids ) ) {
					foreach ( $variation_ids as $variation_id ) {
						wp_set_post_terms( $variation_id, $categories, 'product_cat', false );
						wp_set_post_terms( $variation_id, $tags, 'product_tag', false );
						if ( class_exists( 'YITH_WCBR' ) ) {
							$product_brands = wc_get_product_term_ids( $product->get_id(), YITH_WCBR::$brands_taxonomy );
							wp_set_post_terms( $variation_id, $product_brands, YITH_WCBR::$brands_taxonomy, false );
						}

						if ( array_key_exists( 'attributes', $changes ) ) {
							// Reset attribute relationship.

							$attributes_taxonomies = $this->get_attribute_taxonomies();

							wp_delete_object_term_relationships( $variation_id, $attributes_taxonomies );

							$variation = wc_get_product( $variation_id );
							if ( ! $variation ) {
								continue;
							}
							/**
							 * APPLY_FILTER: yith_wccl_variation_attributes
							 *
							 * filter variation attributes.
							 *
							 * @param array $attributes Variation attributes
							 * @param object  $product    Current product object
							 * @param object  $class    Current class object
							 *
							 * @return array
							 */
							$attributes = apply_filters( 'yith_wccl_variation_attributes', $variation->get_attributes(), $product, $this );
							foreach ( $attributes as $taxonomy => $value ) {
								wp_set_object_terms( $variation_id, $value, $taxonomy );
							}
						}
					}
				}
			}

			if ( $product->is_type( 'variation' ) && array_key_exists( 'attributes', $changes ) ) {
				// As we don't know attributes changed, remove all attribute taxonomies from variation.
				wp_delete_object_term_relationships( $product->get_id(), $this->get_attribute_taxonomies() );

				$attributes = $product->get_attributes();
				foreach ( $attributes as $taxonomy => $value ) {
					wp_set_object_terms( $product->get_id(), $value, $taxonomy );
				}
			}
		}

		/**
		 * Delete variation taxonomies on delete variation
		 *
		 * @since  1.9.3
		 * @param integer $product_id The product ID.
		 * @return void
		 */
		public function delete_variations_terms( $product_id ) {
			$taxonomies = array_merge( array( 'product_cat', 'product_tag' ), $this->get_attribute_taxonomies() );
			wp_delete_object_term_relationships( $product_id, $taxonomies );
		}

		/**
		 * Get an array of attribute taxonomies
		 *
		 * @since  1.9.3
		 * @return array
		 */
		protected function get_attribute_taxonomies() {

			$taxonomies = wp_cache_get( 'yith_wccl_attribute_taxonomies', 'yith_wccl' );
			if ( false === $taxonomies ) {
				$attribute_taxonomies = is_array( wc_get_attribute_taxonomies() ) ? wc_get_attribute_taxonomies() : array();
				$taxonomies           = array_filter(
					array_map(
						function ( $tax ) {
							$taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
							// Check if taxonomy exists
							return taxonomy_exists( $taxonomy_name ) ? $taxonomy_name : null;
						},
						$attribute_taxonomies
					)
				);

				wp_cache_set( 'yith_wccl_attribute_taxonomies', $taxonomies, 'yith_wccl' );
			}

			return $taxonomies;
		}

		/**
		 * AJAX Filter compatibility method
		 *
		 * @since 1.15.1
		 * @param array   $data The data array to filter.
		 * @param integer $term_id The term id to process.
		 * @return array
		 */
		public function ajax_filter_compatibility( $data, $term_id ) {

			// Check for execute the code on color and label compatibility class.
			if ( method_exists( YITH_WCAN_Color_Label_Compatibility::instance(), 'filter_items_args' ) ) {
				return $data;
			}

			$term = get_term( $term_id );
			if ( ! $term instanceof WP_Term ) {
				return $data;
			}

			$attribute_id = wc_attribute_taxonomy_id_by_name( str_replace( 'pa_', '', $term->taxonomy ) );
			if ( empty( $attribute_id ) ) {
				return $data;
			}

			$tooltip = ywccl_get_term_meta( $term_id, '_yith_wccl_tooltip' );
			if ( ! empty( $tooltip ) ) {
				$data['tooltip'] = $tooltip;
			}

			$value = ywccl_get_term_meta( $term_id, '_yith_wccl_value' );
			if ( ! empty( $value ) ) {
				$attribute = wc_get_attribute( $attribute_id );
				switch ( $attribute->type ) {
					case 'colorpicker':
						// ColorPicker is an image.
						if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
							$media_id      = attachment_url_to_postid( $value );
							$data['image'] = $media_id;
							$data['mode']  = 'image';
						} else {
							$colors          = ! is_array( $value ) ? explode( ',', $value ) : $value;
							$data['color_1'] = $colors[0];
							if ( isset( $colors[1] ) ) {
								$data['color_2'] = $colors[1];
							}
						}
						break;
					case 'image':
						$media_id = attachment_url_to_postid( $value );
						if ( $media_id ) {
							$data['image'] = $media_id;
							$data['mode']  = 'image';

							// Replace tooltip placeholder if any.
							$thumb_src       = wp_get_attachment_image_url( $media_id, 'thumbnail' );
							$image           = '<img src="' . $thumb_src . '" />';
							$data['tooltip'] = str_replace( '{show_image}', $image, $data['tooltip'] );
						}
						break;
					default:
						$data['label'] = $value;
						break;
				}
			}

			return $data;
		}
	}
}

/**
 * Unique access to instance of YITH_WCCL class
 *
 * @since 1.0.0
 * @return YITH_WCCL
 */
function YITH_WCCL() {
 // phpcs:ignore WordPress.NamingConventions
	return YITH_WCCL::get_instance();
}
