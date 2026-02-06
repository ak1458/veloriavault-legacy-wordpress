<?php // phpcs:ignore WordPress.NamingConventions
/**
 * WPML integration class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 * @version 1.0.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCCL_WPML' ) ) {
    /**
     * WPML class.
     * The class manage all the WPML behaviors.
     *
     * @since 2.0.0
     */
    class YITH_WCCL_WPML
    {

        /**
         * Single instance of the class
         *
         * @since 2.0.0
         * @var YITH_WCCL_WPML
         */
        protected static $instance;

        /**
         * Returns single instance of the class
         *
         * @return YITH_WCCL_WPML
         * @since 2.0.0
         */
        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor
         *
         * @access public
         * @since  2.0.0
         */
        public function __construct()
        {
            add_filter( 'yith_wccl_get_product', array( $this, 'retrieve_original_product' ) );
            add_filter( 'yith_wccl_get_term_id', array( $this, 'retrieve_original_term_id' ),10,2 );
            add_filter('yith_wccl_before_save_custom_product_terms', array( $this, 'register_strings_for_translations' ),10,2 );
            add_filter( 'yith_wccl_custom_attr_product', array( $this, 'get_strings_for_translations' ), 10, 4 );
            add_filter( 'yith_wccl_product_tabs', array( $this, 'change_variations_style_tab_for_translated_product' ), 10, 2 );

        }
        /**
         * Get parent product
         * @param WC_Product $product The variable product.
         * @access public
         * @since  2.0.0
         *
         * @return WC_Product
         */
        public function retrieve_original_product( WC_Product $product ) {
            global $wpml_post_translations;

            $id        = $product->get_id();
            $parent_id = $wpml_post_translations->get_original_element( $id );

            if ( $wpml_post_translations && $parent_id ) {
                $product = wc_get_product($parent_id);
            }
            return $product;
        }
        /**
         * Get original term
         * @param int $term_id The term id.
         * @access public
         * @since  2.0.0
         *
         * @return WP_Term
         */
        public function retrieve_original_term_id( $term_id, $taxonomy ) {
            global $sitepress;
            $default_language = ! is_null( $sitepress ) ? $sitepress->get_default_language() : false;

            if( $default_language ) {
                $term_id = apply_filters('wpml_object_id',$term_id, $taxonomy, false, $default_language );
            }

            return $term_id;
        }
        /**
         * Register strings for translations
         * @param array $product_attributes The custom attributes.
         * @param WC_Product $variable The variable product.
         * @access public
         * @since  2.0.0
         *
         * @return array
         */
        public function register_strings_for_translations( $product_attributes, $variable ) {

            $id = $variable->get_id();
            foreach ( $product_attributes as $taxonomy_name => $attribute ) {
                foreach ( $attribute as $term_key => $term ) {
                    $actual_term = get_term_by( 'id', $term_key, $taxonomy_name );
                    if( 'yes' === $term['override_global'] ) {
                        $labels_translated = $this->translated_labels_by_attr_type($term['term_attribute_type'] );
                        foreach ( $labels_translated as $key => $value ) {
                            yit_wpml_register_string(YITH_WCCL_SLUG, $value . ' for term ' . $actual_term->name . ' on product id ' . $id, $term[$key] );
                        }
                    }
                }
            }
            return $product_attributes;
        }
        /**
         * Retrieve strings for translations
         * @param array $custom_attr_product The custom attributes.
         * @param string $taxonomy_name The taxonomy name.
         * @param int $term_id The term id.
         * @param WC_Product $product The product.
         * @access public
         * @since  2.0.0
         *
         * @return array
         */
        public function get_strings_for_translations( $custom_attr_product, $taxonomy_name, $term_id, $product ) {

            if( !empty( $custom_attr_product ) ) {
                global $sitepress;

                //get original term.
                $has_filter = remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );
                $actual_term = get_term_by( 'id', $term_id, $taxonomy_name );
                if ( $has_filter ) {
                    add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
                }

                $id = $product->get_id();
                $labels_translated = $this->translated_labels_by_attr_type($custom_attr_product['term_attribute_type'] );
                foreach( $labels_translated as $key => $value ) {
                    $name_wpml = $value . ' for term ' . $actual_term->name . ' on product id ' . $id;
                    $custom_attr_product[$key] = yit_wpml_string_translate(YITH_WCCL_SLUG, $name_wpml, $custom_attr_product[$key]);
                }

            }

            return $custom_attr_product;
        }
        /**
         * Retrieve the labels allowed for translation
         * @param string $attr_type The attribute type.
         * @access public
         * @since  2.0.0
         *
         * @return array
         */
        private function translated_labels_by_attr_type( $attr_type ) {

            $attribute_type = apply_filters( 'yith_wccl_wpml_translated_labels', array(
                'colorpicker' => array(
                    'term_tooltip' => 'Tooltip'
                ),
                'image' => array(
                    'term_tooltip' => 'Tooltip'
                ),
                'label' => array(
                    'term_value' => 'Label',
                    'term_tooltip' => 'Tooltip'
                ),
            ), $attr_type );

            return $attribute_type[$attr_type] ?? array();
        }
        /**
         * Change the variations style tab for translated products
         * @param string $attr_type The attribute type.
         * @access public
         * @since  2.0.0
         *
         * @return array
         */
        public function change_variations_style_tab_for_translated_product( $tabs, $product ) {
            global $wpml_post_translations;

            $id        = $product->get_id();
            $parent_id = $wpml_post_translations->get_original_element( $id );

            if ( $wpml_post_translations && $parent_id ) {
                $claves = array_keys($tabs);
                $position = array_search('variations-style', $claves);
                $claves[$position] = 'wpml-variations-style';
                $tabs = array_combine($claves, $tabs);
            }



            return $tabs;
        }
    }
}
/**
 * Unique access to instance of YITH_WCCL_WPML class
 *
 * @return YITH_WCCL_WPML
 * @since 2.0.0
 */
function YITH_WCCL_WPML()
{ // phpcs:ignore WordPress.NamingConventions
    return YITH_WCCL_WPML::get_instance();
}
