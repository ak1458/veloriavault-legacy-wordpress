<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Admin class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 * @version 1.0.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCCL_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCCL_Admin {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var YITH_WCCL_Admin
		 */
		protected static $instance;

		/**
		 * Plugin option
		 *
		 * @since  1.0.0
		 * @var array
		 */
		public $option = array();

		/**
		 * Plugin custom taxonomy
		 *
		 * @since  1.0.0
		 * @var array
		 */
		public $custom_types = array();

		/**
		 * The admin panel instance
		 *
		 * @var object $panel
		 */
		protected $panel;

		/**
		 * Color Labes panel page
		 *
		 * @var string $panel_page
		 */
		protected $panel_page = 'yith_wccl_panel';

		/**
		 * Various links
		 *
		 * @since  1.0.0
		 * @var string
		 * @access public
		 */
		public $doc_url = 'http://yithemes.com/docs-plugins/yith-woocommerce-color-label-variations';

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 * @return YITH_WCCL_Admin
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
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {

			$this->custom_types = ywccl_get_custom_tax_types();

			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

			// Add action links.
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_WCCL_DIR . '/' . basename( YITH_WCCL_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );

			// Register plugin to licence/update system.
			add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
            add_action( 'wp_loaded', array( $this, 'register_plugin_for_updates' ), 99 );

			// Enqueue style and scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'dequeue_scripts_styles' ), 20 );

			// Add description field to products attribute.
			add_action( 'admin_footer', array( $this, 'add_description_field' ) );
			add_action( 'woocommerce_attribute_added', array( $this, 'attribute_add_description_field' ), 10, 2 );
			add_action( 'woocommerce_attribute_updated', array( $this, 'attribute_update_description_field' ), 10, 3 );
			add_action( 'woocommerce_attribute_deleted', array( $this, 'attribute_delete_description_field' ), 10, 3 );

			// Product attribute taxonomies.
			add_action( 'init', array( $this, 'attribute_taxonomies' ) );

			// Print attribute field type.
			add_action( 'yith_wccl_print_attribute_field', array( $this, 'print_attribute_type' ), 10, 3 );

			// Choose variations in product page.
			add_action( 'woocommerce_product_option_terms', array( $this, 'product_option_terms' ), 10, 2 );

			// Add term directly from product variation.
			add_action( 'admin_footer', array( $this, 'product_option_add_terms_form' ) );

			// Save new term.
			add_action( 'created_term', array( $this, 'attribute_save' ), 10, 3 );
			add_action( 'edit_term', array( $this, 'attribute_save' ), 10, 3 );

			// AJAX add attribute.
			add_action( 'wp_ajax_yith_wccl_add_new_attribute', array( $this, 'yith_wccl_add_new_attribute_ajax' ) );
			add_action( 'wp_ajax_nopriv_yith_wccl_add_new_attribute', array( $this, 'yith_wccl_add_new_attribute_ajax' ) );

			// Add gallery for variations.
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'gallery_variation_html' ), 10, 3 );
			add_action( 'admin_footer', array( $this, 'gallery_variation_template_js' ) );
			// Add option to show/hide variable in loop.
			add_filter( 'product_type_options', array( $this, 'show_variable_in_loop_opt' ), 10, 1 );
			// Add option to show/hide single variation in loop.
			add_action( 'woocommerce_variation_options', array( $this, 'show_variation_in_loop_opt' ), 10, 3 );
			// Save custom meta.
			add_action( 'woocommerce_process_product_meta_variable', array( $this, 'save_variable_custom_meta' ), 10, 1 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_custom_meta' ), 10, 2 );

			add_action( 'yith_wccl_print_attributes_tab', array( $this, 'print_attribute_tab' ) );

			// Colorpicker attribute.
			add_action( 'yith_wccl_colorpicker_attribute', array( $this, 'print_colorpicker_attribute' ) );

			// Variation Style tab.
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'variation_style_tab' ), 99 ); // Use high priority since we need to filter all tabs, also the ones added by other plugins.
			add_action( 'woocommerce_product_data_panels', array( $this, 'add_product_data_panels' ) );

		}

		/**
		 * Action Links
		 * add the action links to plugin admin page
		 *
		 * @since    1.0
		 * @param array $links Links plugin array.
		 * @return   mixed
		 * @use      plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, $this->panel_page, true, YITH_WCCL_SLUG );

			return $links;
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @since    1.0
		 * @use      /Yit_Plugin_Panel class
		 * @return   void
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->panel ) ) {
				return;
			}

			$admin_tabs = array(
				'general'       => array(
					'title'       => __( 'General Options', 'yith-woocommerce-color-label-variations' ),
					'icon'        => 'settings',
					'description' => __( 'Set the general options for the plugin behavior.', 'yith-woocommerce-color-label-variations' ),
				),
				'customization' => array(
					'title'       => __( 'Customization', 'yith-woocommerce-color-label-variations' ),
					'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 11.25l1.5 1.5.75-.75V8.758l2.276-.61a3 3 0 10-3.675-3.675l-.61 2.277H12l-.75.75 1.5 1.5M15 11.25l-8.47 8.47c-.34.34-.8.53-1.28.53s-.94.19-1.28.53l-.97.97-.75-.75.97-.97c.34-.34.53-.8.53-1.28s.19-.94.53-1.28L12.75 9M15 11.25L12.75 9"></path>
                                      </svg>',
					'description' => __( 'Set the customization options for the plugin behavior.', 'yith-woocommerce-color-label-variations' ),
				),
			);

			$args = array(
				'ui_version'       => 2,
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => 'YITH Color, Image & Label Variation Swatches',
				'menu_title'       => 'Color, Image & Label Variation Swatches',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yith_plugin_panel',
				'page'             => $this->panel_page,
				'is_premium'       => defined( YITH_WCCL_PREMIUM ),
				/**
				 * APPLY_FILTERS: yith-wccl-admin-tabs
				 *
				 * Filter the available tabs in the plugin panel.
				 *
				 * @param array $admin_tabs Admin tabs.
				 */
				'admin-tabs'       => apply_filters( 'yith-wccl-admin-tabs', $admin_tabs ), // phpcs:ignore WordPress.NamingConventions
				'options-path'     => YITH_WCCL_DIR . '/plugin-options',
				'class'            => yith_set_wrapper_class(),
				'plugin_slug'      => YITH_WCCL_SLUG,
                'your_store_tools' => array(
                    'items' => array(
                        'wishlist'               => array(
                            'name'           => 'Wishlist',
                            'icon_url'       => YITH_WCCL_ASSETS_URL . '/images/plugins/wishlist.svg',
                            'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-wishlist/',
                            'description'    => _x(
                                'Allow your customers to create lists of products they want and share them with family and friends.',
                                '[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Wishlist',
                                'yith-woocommerce-color-label-variations'
                            ),
                            'is_active'      => defined( 'YITH_WCWL_PREMIUM' ),
                            'is_recommended' => true,
                        ),
                        'gift-cards'             => array(
                            'name'           => 'Gift Cards',
                            'icon_url'       => YITH_WCCL_ASSETS_URL . '/images/plugins/gift-cards.svg',
                            'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-gift-cards/',
                            'description'    => _x(
                                'Sell gift cards in your shop to increase your earnings and attract new customers.',
                                '[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Gift Cards',
                                'yith-woocommerce-color-label-variations'
                            ),
                            'is_active'      => defined( 'YITH_YWGC_PREMIUM' ),
                            'is_recommended' => true,
                        ),
                        'request-a-quote'        => array(
                            'name'           => 'Request a Quote',
                            'icon_url'       => YITH_WCCL_ASSETS_URL . '/images/plugins/request-a-quote.svg',
                            'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-request-a-quote/',
                            'description'    => _x(
                                'Hide prices and/or the "Add to cart" button and let your customers request a custom quote for every product.',
                                '[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Request a Quote',
                                'yith-woocommerce-color-label-variations'
                            ),
                            'is_active'      => defined( 'YITH_YWRAQ_PREMIUM' ),
                            'is_recommended' => false,
                        ),
                        'ajax-product-filter'    => array(
                            'name'           => 'Ajax Product Filter',
                            'icon_url'       => YITH_WCCL_ASSETS_URL . '/images/plugins/ajax-product-filter.svg',
                            'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-ajax-product-filter/',
                            'description'    => _x(
                                'Help your customers to easily find the products they are looking for and improve the user experience of your shop.',
                                '[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Ajax Product Filter',
                                'yith-woocommerce-color-label-variations'
                            ),
                            'is_active'      => defined( 'YITH_WCAN_PREMIUM' ),
                            'is_recommended' => false,
                        ),
                        'product-addons'         => array(
                            'name'           => 'Product Add-Ons & Extra Options',
                            'icon_url'       => YITH_WCCL_ASSETS_URL . '/images/plugins/product-add-ons.svg',
                            'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-product-add-ons/',
                            'description'    => _x(
                                'Add paid or free advanced options to your product pages using fields like radio buttons, checkboxes, drop-downs, custom text inputs, and more.',
                                '[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Product Add-Ons',
                                'yith-woocommerce-color-label-variations'
                            ),
                            'is_active'      => defined( 'YITH_WAPO_PREMIUM' ),
                            'is_recommended' => false,
                        ),
                        'dynamic-pricing'        => array(
                            'name'           => 'Dynamic Pricing and Discounts',
                            'icon_url'       => YITH_WCCL_ASSETS_URL . '/images/plugins/dynamic-pricing-and-discounts.svg',
                            'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-dynamic-pricing-and-discounts/',
                            'description'    => _x(
                                'Increase conversions through dynamic discounts and price rules, and build powerful and targeted offers.',
                                '[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Dynamic Pricing and Discounts',
                                'yith-woocommerce-color-label-variations'
                            ),
                            'is_active'      => defined( 'YITH_YWDPD_PREMIUM' ),
                            'is_recommended' => false,
                        ),
                        'customize-my-account'   => array(
                            'name'           => 'Customize My Account Page',
                            'icon_url'       => YITH_WCCL_ASSETS_URL . '/images/plugins/customize-myaccount-page.svg',
                            'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-customize-my-account-page/',
                            'description'    => _x(
                                'Customize the My Account page of your customers by creating custom sections with promotions and ad-hoc content based on your needs.',
                                '[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Customize My Account',
                                'yith-woocommerce-color-label-variations'
                            ),
                            'is_active'      => defined( 'YITH_WCMAP_PREMIUM' ),
                            'is_recommended' => false,
                        ),
                        'recover-abandoned-cart' => array(
                            'name'           => 'Recover Abandoned Cart',
                            'icon_url'       => YITH_WCCL_ASSETS_URL . '/images/plugins/recover-abandoned-cart.svg',
                            'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-recover-abandoned-cart/',
                            'description'    => _x(
                                'Contact users who have added products to the cart without completing the order and try to recover lost sales.',
                                '[YOUR STORE TOOLS TAB] Description for plugin Recover Abandoned Cart',
                                'yith-woocommerce-color-label-variations'
                            ),
                            'is_active'      => defined( 'YITH_YWRAC_PREMIUM' ),
                            'is_recommended' => false,
                        ),
                    ),
                ),
				'help_tab'         => array(
					'hc_url' => '',
                    'main_video' => array(
                        'url'  => array(
                            'en' => 'https://www.youtube.com/embed/yD7KN-cGi6g',
                            'it' => 'https://www.youtube.com/embed/MEjT9Je3Yiw',
                            'es' => 'https://www.youtube.com/embed/8GfRXAzucz8',
                        ),
                    ),
                    'playlists'  => array(
                        'en' => 'https://youtube.com/playlist?list=PLDriKG-6905mouVNImHoDY7aFjYufsYtd',
                        'it' => 'https://youtube.com/playlist?list=PL9c19edGMs091bRDzPgZPIo5ENlE0BDpF',
                        'es' => 'https://youtube.com/playlist?list=PL9Ka3j92PYJOIaBu6qG-iZKB6GcKRV_Kc',
                    ),
				),
                'welcome_modals' => array(
                    'on_close'    => function () {
                        update_option( 'yith-wccl-plugin-welcome-modal', 'no' );
                    },
                    'modals'  => array(
                        'welcome' => array(
                            'type'        => 'welcome',
                            'description' => __( 'The most effective way to show your product variations (size, colors, shapes, etc.)', 'yith-woocommerce-color-label-variations' ),
                            'show'        => get_option( 'yith-wccl-plugin-welcome-modal', 'welcome' ) === 'welcome',
                            'items'       => array(
                                'documentation'  => array(),
                                'how-to-video'   => array(
                                    'url' => array(
                                        'en' => 'https://www.youtube.com/embed/yD7KN-cGi6g',
                                        'it' => 'https://www.youtube.com/embed/MEjT9Je3Yiw',
                                        'es' => 'https://www.youtube.com/embed/8GfRXAzucz8',
                                    ),
                                ),
                                'create-product' => array(
                                    'title'       => __( 'Create or customize your products\' attributes', 'yith-woocommerce-color-label-variations' ),
                                    'description' => __( 'and start a new adventure!', 'yith-woocommerce-color-label-variations' ),
                                    'url'         => '',
                                ),
                            ),
                        ),
                    ),
                ),
			);

			// Fixed: not updated theme.
			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once YITH_WCCL_DIR . '/plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );

		}

		/**
		 * Plugin_row_meta
		 * add the action links to plugin admin page
		 *
		 * @since    1.0
		 * @use      plugin_row_meta
		 * @param array    $new_row_meta_args An array of plugin row meta.
		 * @param string[] $plugin_meta An array of the plugin's metadata,
		 *                                    including the version, author,
		 *                                    author URI, and plugin URI.
		 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
		 * @param array    $plugin_data An array of plugin data.
		 * @param string   $status Status of the plugin. Defaults are 'All', 'Active',
		 *                                    'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
		 *                                    'Drop-ins', 'Search', 'Paused'.
		 * @return   Array
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status ) {

			if ( defined( 'YITH_WCCL_INIT' ) && YITH_WCCL_INIT === $plugin_file ) {
				$new_row_meta_args['slug'] = YITH_WCCL_SLUG;

				$new_row_meta_args['live_demo'] = array(
					'url' => 'https://plugins.yithemes.com/yith-woocommerce-color-and-label-variations/',
				);

				if ( defined( 'YITH_WCCL_PREMIUM' ) ) {
					$new_row_meta_args['is_premium'] = true;
				}
			}

			return $new_row_meta_args;
		}

		/**
		 * Register plugins for activation tab
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function register_plugin_for_activation() {
			if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
				require_once YITH_WCCL_DIR . 'plugin-fw/licence/lib/yit-licence.php';
				require_once YITH_WCCL_DIR . 'plugin-fw/licence/lib/yit-plugin-licence.php';
			}

			YIT_Plugin_Licence()->register( YITH_WCCL_INIT, YITH_WCCL_SECRET_KEY, YITH_WCCL_SLUG );
		}

		/**
		 * Register plugins for update tab
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function register_plugin_for_updates() {
			if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
				require_once YITH_WCCL_DIR . 'plugin-fw/lib/yit-upgrade.php';
			}

			YIT_Upgrade()->register( YITH_WCCL_SLUG, YITH_WCCL_INIT );
		}

		/**
		 * Enqueue scripts
		 *
		 * @since  1.0.0
		 */
		public function enqueue_scripts() {
			global $pagenow;

			/**
			 * APPLY_FILTERS: yith_wccl_enqueue_admin_scripts
			 *
			 * Filter enqueue admin script.
			 *
			 * @param bool $status enqueue script.
			 */
			if ( ( ( 'edit-tags.php' === $pagenow || 'edit.php' === $pagenow || 'term.php' === $pagenow ) && isset( $_GET['post_type'] ) && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) //phpcs:ignore WordPress.Security.NonceVerification
				|| ( 'post.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) //phpcs:ignore WordPress.Security.NonceVerification
				|| ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) //phpcs:ignore WordPress.Security.NonceVerification
				|| ( isset( $_GET['tab'] ) && 'single-variations' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) //phpcs:ignore WordPress.Security.NonceVerification
				|| apply_filters( 'yith_wccl_enqueue_admin_scripts', false ) ) {

				$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				wp_enqueue_media();

				wp_register_style( 'yith-wccl-icon-font', YITH_WCCL_URL . '/assets/fonts/icons-font/style.css', array(), YITH_WCCL_VERSION );

				wp_enqueue_style( 'yith-wccl-admin', YITH_WCCL_URL . '/assets/css/yith-wccl-admin.css', array( 'wp-color-picker', 'yith-plugin-fw-fields', 'yith-wccl-icon-font' ), YITH_WCCL_VERSION );
				wp_enqueue_script(
					'yith-wccl-admin',
					YITH_WCCL_URL . '/assets/js/yith-wccl-admin' . $min . '.js',
					array(
						'jquery',
						'wp-color-picker',
						'jquery-ui-dialog',
						'yith-plugin-fw-fields',
					),
					YITH_WCCL_VERSION,
					true
				);

				wp_localize_script(
					'yith-wccl-admin',
					'yith_wccl_admin',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
					)
				);
			}
		}

		/**
		 * Dequeue wp-color-picker-alpha from FUSION Avada theme to fix issue with plugin colorpicker
		 *
		 * @since 1.10.3
		 * @return void
		 */
		public function dequeue_scripts_styles() {
			global $pagenow;

			if ( ( ( 'edit-tags.php' === $pagenow || 'edit.php' === $pagenow || 'term.php' === $pagenow ) && isset( $_GET['post_type'] ) && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) //phpcs:ignore WordPress.Security.NonceVerification
				|| ( 'post.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) //phpcs:ignore WordPress.Security.NonceVerification
				|| ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) //phpcs:ignore WordPress.Security.NonceVerification
				|| ( isset( $_GET['tab'] ) && isset( $_GET['post_type'] ) && 'single-variations' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification

				if ( defined( 'FUSION_LIBRARY_URL' ) ) {
					wp_dequeue_script( 'wp-color-picker-alpha' );
				}
			}
		}

		/**
		 * Add description field to add/edit products attribute
		 *
		 * @since  1.0.0
		 */
		public function add_description_field() {
			global $pagenow, $wpdb;

			if ( ! ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) //phpcs:ignore WordPress.Security.NonceVerification
					&& isset( $_GET['page'] ) && 'product_attributes' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				return;
			}

			$edit            = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : false; //phpcs:ignore WordPress.Security.NonceVerification
			$att_description = false;

			if ( $edit ) {
				$attribute_to_edit = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->prefix}yith_wccl_meta WHERE wc_attribute_tax_id = '$edit'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$att_description   = isset( $attribute_to_edit ) ? $attribute_to_edit : false;
			}

			ob_start();
			include YITH_WCCL_DIR . 'templates/admin/description-field.php';
			$html = ob_get_clean();

			wp_localize_script( 'yith-wccl-admin', 'yith_wccl_admin', array( 'html' => $html ) );
		}

		/**
		 * Maybe sanitize a field
		 *
		 * @since  1.8.4
		 * @param string $field The field to sanitize.
		 * @param mixed  $value The field value.
		 * @return string
		 */
		protected function maybe_sanitize_field( $field, $value ) {
			/**
			 * APPLY_FILTERS: yith_wccl_sanitize_field_. $field
			 *
			 * Filter prevent sanitize a field.
			 *
			 * @param bool $status sanitize field type.
			 */
			if ( ! apply_filters( 'yith_wccl_sanitize_field_' . $field, '__return_true' ) ) {
				return $value;
			}

			return wc_clean( $value );
		}

		/**
		 * Add new product attribute description
		 *
		 * @since  1.0.0
		 * @param int   $id Added attribute ID.
		 * @param array $attribute Attribute data.
		 */
		public function attribute_add_description_field( $id, $attribute ) {
			global $wpdb;

			// Get attribute description.
			$descr = isset( $_POST['attribute_description'] ) ? $this->maybe_sanitize_field( 'attribute_description', wp_unslash( $_POST['attribute_description'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

			// Insert db value.
			if ( $descr ) {
				$attr = array();

				$attr['wc_attribute_tax_id'] = $id;
				// Add description.
				$attr['meta_key']   = '_wccl_attribute_description'; //phpcs:ignore slow query ok.
				$attr['meta_value'] = $descr; //phpcs:ignore slow query ok.

				$wpdb->insert( $wpdb->prefix . 'yith_wccl_meta', $attr ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}

		/**
		 * Update product attribute description
		 *
		 * @since  1.0.0
		 * @param int    $id Added attribute ID.
		 * @param array  $attribute Attribute data.
		 * @param string $old_attributes Attribute old name.
		 */
		public function attribute_update_description_field( $id, $attribute, $old_attributes ) {
			global $wpdb;

			$descr = isset( $_POST['attribute_description'] ) ? $this->maybe_sanitize_field( 'attribute_description', wp_unslash( $_POST['attribute_description'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

			// Get meta value.
			$meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}yith_wccl_meta WHERE wc_attribute_tax_id = %d", $id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery

			if ( ! isset( $meta ) ) {
				$this->attribute_add_description_field( $id, $attribute );
			} elseif ( $meta->meta_value !== $descr ) {
				$attr               = array();
				$attr['meta_value'] = $descr; //phpcs:ignore slow query ok.

				$wpdb->update( $wpdb->prefix . 'yith_wccl_meta', $attr, array( 'meta_id' => $meta->meta_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}

		/**
		 * Delete product attribute description
		 *
		 * @since  1.0.0
		 * @param int    $attribute_id Attribute ID.
		 * @param string $attribute_name Attribute name.
		 * @param string $taxonomy Attribute taxonomy name.
		 */
		public function attribute_delete_description_field( $attribute_id, $attribute_name, $taxonomy ) {
			global $wpdb;

			$meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}yith_wccl_meta WHERE wc_attribute_tax_id = %d", $attribute_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery

			if ( $meta_id ) {
				$wpdb->query( "DELETE FROM {$wpdb->prefix}yith_wccl_meta WHERE wc_attribute_tax_id = $attribute_id" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}

		/**
		 * Init product attribute taxonomies
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function attribute_taxonomies() {

			$attribute_taxonomies = wc_get_attribute_taxonomies();

			if ( $attribute_taxonomies ) {
				foreach ( $attribute_taxonomies as $tax ) {

					// Check if tax is custom.
					/**
					 * APPLY_FILTERS: yith_wccl_check_for_custom_types
					 *
					 * Filter check for custom attribute types.
					 *
					 * @param bool $status custom types.
					 */
					if ( apply_filters( 'yith_wccl_check_for_custom_types', true ) && ! array_key_exists( $tax->attribute_type, $this->custom_types ) ) {
						continue;
					}

					$name = wc_attribute_taxonomy_name( $tax->attribute_name );
					add_action( $name . '_add_form_fields', array( $this, 'add_attribute_field' ) );
					add_action( $name . '_edit_form_fields', array( $this, 'edit_attribute_field' ), 10, 2 );

					add_filter( 'manage_edit-' . $name . '_columns', array( $this, 'product_attribute_columns' ) );
					add_filter( 'manage_' . $name . '_custom_column', array( $this, 'product_attribute_column' ), 10, 3 );
				}
			}
		}

		/**
		 * Add field for each product attribute taxonomy
		 *
		 * @access public
		 * @since  1.0.0
		 * @param string $taxonomy Attribute taxonomy name.
		 * @return void
		 */
		public function add_attribute_field( $taxonomy ) {
			global $wpdb;

			$attribute = substr( $taxonomy, 3 );
			$attribute = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = '$attribute'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			$values = yith_wccl_get_term_field( $attribute->attribute_type, '', $taxonomy, $this->custom_types );

			/**
			 * DO_ACTION: yith_wccl_print_attribute_field
			 *
			 * @param string $type Attribute type.
			 * @param array  $values Attribute data.
			 */
			do_action( 'yith_wccl_print_attribute_field', $attribute->attribute_type, $values );
		}

		/**
		 * Edit field for each product attribute taxonomy
		 *
		 * @access public
		 * @since  1.0.0
		 * @param WP_Term $term Current taxonomy term object.
		 * @param string  $taxonomy Current taxonomy slug.
		 * @return void
		 */
		public function edit_attribute_field( $term, $taxonomy ) {
			global $wpdb;

			$attribute = substr( $taxonomy, 3 );
			$attribute = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = '$attribute'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			$values = yith_wccl_get_term_field( $attribute->attribute_type, $term, $taxonomy, $this->custom_types );

			/**
			 * DO_ACTION: yith_wccl_print_attribute_field
			 *
			 * @param string $type Attribute type.
			 * @param array  $values Attribute data.
			 * @param bool   $table True if is in table, false otherwise.
			 */
			do_action( 'yith_wccl_print_attribute_field', $attribute->attribute_type, $values, true );
		}

		/**
		 * Print Attribute Tax Type HTML
		 *
		 * @access public
		 * @since  1.0.0
		 * @param string $type Attribute type.
		 * @param array  $args Attribute data.
		 * @param bool   $table True if is in table, false otherwise.
		 */
		public function print_attribute_type( string $type, array $args, bool $table = false ) {

			foreach ( $args as $key => $arg ) :
				$class = $arg['class'] ?? '';
				if ( $table ) : ?>
					<tr class="form-field <?php echo 'term-' . esc_attr( $key ) . '-wrap yith-wccl-term-type-' . esc_attr( $type ) . ' ' . esc_attr( $class ); ?>">
					<th scope="row">
						<label for="term_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $arg['label'] ); ?></label>
					</th>
					<td>
				<?php else : ?>
					<div class="form-field <?php echo 'term-' . esc_attr( $key ) . '-wrap yith-wccl-term-type-' . esc_attr( $type ) . ' ' . esc_attr( $class ); ?>">
					<label for="term_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $arg['label'] ); ?></label>
				<?php endif ?>

				<?php echo yith_wccl_get_field( $arg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php if ( $table ) : ?>
				</td>
				</tr>
			<?php else : ?>
				</div>
				<?php
			endif;
			endforeach;
		}

		/**
		 * Save attribute field
		 *
		 * @access public
		 * @since  1.0.0
		 * @param int    $term_id Term ID.
		 * @param int    $tt_id Term taxonomy ID.
		 * @param string $taxonomy Taxonomy slug.
		 */
		public function attribute_save( $term_id, $tt_id, $taxonomy ) {

			if ( isset( $_POST['term_attribute_type'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification

				$attribute_type = sanitize_text_field( wp_unslash( $_POST['term_attribute_type'] ) );

				switch ( $attribute_type ) {

					case 'colorpicker':
						$this->save_colorpicker_attribute( $term_id );
						break;

					case 'image':
						$this->save_image_attribute( $term_id );
						break;

					case 'label':
						$this->save_label_attribute( $term_id );
						break;
					case 'radio':
						$this->save_radio_attribute( $term_id );
						break;

					default:
						do_action( 'yith_wccl_attribute_save', $term_id, $tt_id, $taxonomy, $attribute_type );
				}
			}
		}
		/**
		 * Save colorpicker attribute fields
		 *
		 * @access public
		 * @since  2.0.0
		 * @param int $term_id Term ID.
		 */
		public function save_colorpicker_attribute( $term_id ) {

			$name_used_for_tooltip = isset( $_POST['term_use_for_tooltip'] ) ? wc_clean( wp_unslash( $_POST['term_use_for_tooltip'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification
			ywccl_update_term_meta( $term_id, '_yith_wccl_use_for_tooltip', $name_used_for_tooltip );

			$term_swatch_type = isset( $_POST['term_swatch_type'] ) ? wc_clean( wp_unslash( $_POST['term_swatch_type'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification
			ywccl_update_term_meta( $term_id, '_yith_wccl_swatch_type', $term_swatch_type );

			if ( isset( $_POST['term_value'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification

				switch ( $term_swatch_type ) {
					case 'image_color':
						// Set value as image and also save the attribute image on it own meta.
						$value = isset( $_POST['attribute_image'] ) ? wc_clean( wp_unslash( $_POST['attribute_image'] ) ) : '';
						ywccl_update_term_meta( $term_id, '_yith_wccl_attribute_image', sanitize_text_field( wp_unslash( $value ) ) );

						break;

					case 'dual_color':
						$array_values = wc_clean( wp_unslash( array_filter( $_POST['term_value'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
						if ( empty( $array_values ) ) {
							$value = '';
						} else {
							$value = implode( ',', $array_values );
						}
						break;
					default: // Single color.
						$term_value = wc_clean( wp_unslash( $_POST['term_value'] ) );
						if ( isset( $term_value[1] ) ) { // Remove dual color for security.
							unset( $term_value[1] );
						}
                        //Prevent errors with save colors.
						$value = wc_clean( wp_unslash( implode( ',', $term_value ) ) ); //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

						break;
				}

				ywccl_update_term_meta( $term_id, '_yith_wccl_value', $value );
			}
            if( $name_used_for_tooltip ){
                $tooltip = $_POST['tag-name'] ? $_POST['tag-name'] : $_POST['name'];
            }else{
	            $tooltip = $_POST['term_tooltip'];
            }
			ywccl_update_term_meta( $term_id, '_yith_wccl_tooltip', sanitize_text_field( wp_unslash( $tooltip ) ) ); // phpcs:ignore WordPress.Security.NonceVerification

            $tooltip_image = isset( $_POST['tooltip_image'] ) ? wc_clean( wp_unslash( $_POST['tooltip_image'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification
			ywccl_update_term_meta( $term_id, '_yith_wccl_tooltip_image', sanitize_text_field( wp_unslash( $tooltip_image ) ) );

			/**
			 * DO_ACTION: yith_wccl_after_save_colorpicker_attribute
			 *
			 * @param int $term_id Term id.
			 */
			do_action( 'yith_wccl_after_save_colorpicker_attribute', $term_id );
		}
		/**
		 * Save image attribute fields
		 *
		 * @access public
         *
		 * @since  2.0.0
		 * @param int $term_id Term ID.
		 */
		public function save_image_attribute( $term_id ) {

			$term_use_for_tooltip = $_POST['term_use_for_tooltip'] ?? '';
			$term_value           = $_POST['term_value'] ?? '';
			$tooltip_image_type   = $_POST['term_tooltip_image_type'] ?? 'no_image';

            if( $term_use_for_tooltip ){
                $term_tooltip = $_POST['tag-name'] ? $_POST['tag-name'] : $_POST['name'];
            }else{
                $term_tooltip = $_POST['term_tooltip'];
            }

			switch ( $tooltip_image_type ) {
				case 'upload_image':
					$tooltip_image = $_POST['tooltip_image'];
					break;
				case 'attribute_image':
					$tooltip_image = $term_value;
					break;
				default:
					$tooltip_image = '';
			}


			ywccl_update_term_meta( $term_id, '_yith_wccl_value', $term_value );
			ywccl_update_term_meta( $term_id, '_yith_wccl_tooltip', $term_tooltip );
			ywccl_update_term_meta( $term_id, '_yith_wccl_use_for_tooltip', $term_use_for_tooltip );
			ywccl_update_term_meta( $term_id, '_yith_wccl_tooltip_image_type', $tooltip_image_type );
			ywccl_update_term_meta( $term_id, '_yith_wccl_tooltip_image', $tooltip_image );

			/**
			 * DO_ACTION: yith_wccl_after_save_image_attribute
			 *
			 * @param int $term_id Term id.
			 */
			do_action( 'yith_wccl_after_save_image_attribute', $term_id );
		}
		/**
		 * Save label attribute fields
		 *
		 * @access public
		 * @since  2.0.0
		 * @param int $term_id Term ID.
		 */
		public function save_label_attribute( $term_id ) {

			$term_use_for_tooltip = $_POST['term_use_for_tooltip'] ?? '';
			$term_use_for_label   = $_POST['term_use_for_label'] ?? '';

			$tooltip_image = $_POST['tooltip_image'] ?? '';

            if ($term_use_for_tooltip){
                $term_tooltip  = $_POST['tag-name']  ? $_POST['tag-name'] : $_POST['name'];
            } else {
                $term_tooltip  = $_POST['term_tooltip'];
            }

            if ($term_use_for_label){
                $term_value = $_POST['tag-name']  ? $_POST['tag-name'] : $_POST['name'];
            } else {
                $term_value = $_POST['term_value'];
            }


			ywccl_update_term_meta( $term_id, '_yith_wccl_value', $term_value );
			ywccl_update_term_meta( $term_id, '_yith_wccl_tooltip', $term_tooltip );
			ywccl_update_term_meta( $term_id, '_yith_wccl_use_for_label', $term_use_for_label );
			ywccl_update_term_meta( $term_id, '_yith_wccl_use_for_tooltip', $term_use_for_tooltip );
			ywccl_update_term_meta( $term_id, '_yith_wccl_tooltip_image', $tooltip_image );

			/**
			 * DO_ACTION: yith_wccl_after_save_label_attribute
			 *
			 * @param int $term_id Term id.
			 */
			do_action( 'yith_wccl_after_save_label_attribute', $term_id );
		}
		/**
		 * Save radio attribute fields
		 *
		 * @access public
		 * @since  2.0.0
		 * @param int $term_id Term ID.
		 */
		public function save_radio_attribute( $term_id ) {
			/**
			 * DO_ACTION: yith_wccl_after_save_radio_attribute
			 *
			 * @param int $term_id Term id.
			 */
			do_action( 'yith_wccl_after_save_radio_attribute', $term_id );
		}


		/**
		 * Create new column for product attributes
		 *
		 * @access public
		 * @since  1.0.0
		 * @param array $columns The data table column.
		 * @return mixed
		 */
		public function product_attribute_columns( $columns ) {
            global $taxonomy, $wpdb;

			if ( empty( $columns ) ) {
				return $columns;
			}

            $attribute = substr( $taxonomy, 3 );
            $attribute = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", $attribute ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $att_type  = $attribute->attribute_type;

            $temp_cols                    = array();
            $temp_cols['cb']              = $columns['cb'];

            switch ( $att_type ) {

                case 'colorpicker':
                    $temp_cols['yith_wccl_value'] = esc_html__( 'Color', 'yith-woocommerce-color-label-variations' );

                    break;
                case 'image' :
                    $temp_cols['yith_wccl_value'] = esc_html__( 'Image', 'yith-woocommerce-color-label-variations' );
                    break;

                case 'label' :
                    $temp_cols['yith_wccl_value'] = esc_html__( 'Label', 'yith-woocommerce-color-label-variations' );

                    break;

                default:
                    $temp_cols['yith_wccl_value'] = esc_html__( 'Value', 'yith-woocommerce-color-label-variations' );
            }

			unset( $columns['cb'] );
			$columns = array_merge( $temp_cols, $columns );

			return $columns;
		}

		/**
		 * Print the column content
		 *
		 * @access public
		 * @since  1.0.0
		 * @param string $columns Blank string.
		 * @param string $column Name of the column.
		 * @param int    $id Term ID.
		 * @return mixed
		 */
		public function product_attribute_column( $columns, $column, $id ) {
			global $taxonomy, $wpdb;

			if ( 'yith_wccl_value' === $column ) {

				$attribute = substr( $taxonomy, 3 );
				$attribute = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", $attribute ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$att_type  = $attribute->attribute_type;
				$value     = ywccl_get_term_meta( $id, '_yith_wccl_value', true, $taxonomy );

				if ( 'colorpicker' === $att_type ) {
					$swap_type = ywccl_get_term_meta( $id, '_yith_wccl_swatch_type', true, $taxonomy );
					if ( 'image_color' === $swap_type ) {
						$value    = ywccl_get_term_meta( $id, '_yith_wccl_attribute_image', true, $taxonomy );
						$att_type = 'image';
					}
				}

				$columns .= $this->print_attribute_column( $value, $att_type );
			}

			return $columns;
		}


		/**
		 * Print the column content according to attribute type
		 *
		 * @access public
		 * @since  1.0.0
		 * @param string $value Column value.
		 * @param string $type Attribute type.
		 * @return string
		 */
		protected function print_attribute_column( $value, $type ) {
			$output = '';

			if ( 'colorpicker' === $type ) {

				$values = is_string( $value ) ? explode( ',', $value ) : $value;
				if ( isset( $values[1] ) && $values[1] ) {
					$style  = "border-bottom-color:{$values[1]};border-left-color:{$values[0]}";
					$output = '<span class="yith-wccl-color"><span class="yith-wccl-bicolor" style="' . $style . '"></span></span>';
				} else {
					$output = '<span class="yith-wccl-color" style="background-color:' . $values[0] . '"></span>';
				}
			} elseif ( 'label' === $type ) {
				$output = '<span class="yith-wccl-label">' . esc_attr( $value ) . '</span>';
			} elseif ( 'image' === $type ) {
				$output = '<img class="yith-wccl-image" src="' . esc_url( $value ) . '" alt="" />';
			}

			return $output;
		}

		/**
		 * Print select for product variations
		 *
		 * @since  1.0.0
		 * @param object  $taxonomy Attribute taxonomy.
		 * @param integer $i Variation index.
		 */
		public function product_option_terms( $taxonomy, $i ) {

			if ( ! array_key_exists( $taxonomy->attribute_type, $this->custom_types ) ) {
				return;
			}

			global $thepostid;
			if ( is_null( $thepostid ) && ! empty( $_REQUEST['post_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				$thepostid = absint( $_REQUEST['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			$attribute_taxonomy_name = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
			?>

			<select multiple="multiple" data-placeholder="<?php esc_html_e( 'Select terms', 'woocommerce' ); ?>"
					class="multiselect attribute_values wc-enhanced-select" name="attribute_values[<?php echo intval( $i ); ?>][]">
				<?php
				$all_terms = $this->get_terms( $attribute_taxonomy_name );
				if ( $all_terms ) {
					foreach ( $all_terms as $term ) {
						echo '<option value="' . esc_attr( $term['value'] ) . '" ' . selected( has_term( absint( $term['id'] ), $attribute_taxonomy_name, $thepostid ), true, false ) . '>' . esc_html( $term['name'] ) . '</option>';
					}
				}
				?>
			</select>
			<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'yith-woocommerce-color-label-variations' ); ?></button>
			<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'yith-woocommerce-color-label-variations' ); ?></button>
			<button class="button fr plus yith_wccl_add_new_attribute" data-type_input="<?php echo esc_attr( $taxonomy->attribute_type ); ?>"><?php esc_html_e( 'Add new', 'yith-woocommerce-color-label-variations' ); ?></button>

			<?php
		}

		/**
		 * Get terms attributes array
		 *
		 * @since  1.3.0
		 * @param string $tax_name Taxonomy name.
		 * @return array
		 */
		protected function get_terms( $tax_name ) {

			$args = array(
				'taxonomy'   => $tax_name,
				'orderby'    => 'name',
				'hide_empty' => '0',
			);
			// Get terms.
			$terms = get_terms( $args );

			$all_terms = array();
			foreach ( $terms as $term ) {
				$all_terms[] = array(
					'id'    => $term->term_id,
					'value' => $term->term_id,
					'name'  => $term->name,
				);
			}

			return $all_terms;
		}

		/**
		 * Add form in footer to add new attribute from edit product page
		 *
		 * @since  1.0.0
		 */
		public function product_option_add_terms_form() {

			global $pagenow, $post;

			/**
			 * APPLY_FILTERS: yith_wccl_add_product_add_terms_form
			 *
			 * Filter if pagenow is available for add the terms form.
			 *
			 * @param bool $in_array page is in array.
			 */
			if ( apply_filters( 'yith_wccl_add_product_add_terms_form', ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) || ( isset( $post ) && 'product' !== get_post_type( $post->ID ) ) ) ) {
				return;
			}

			ob_start();

			?>

			<div id="yith_wccl_dialog_form"
					title="<?php esc_html_e( 'Create new attribute term', 'yith-woocommerce-color-label-variations' ); ?>"
					style="display:none;">
				<span class="dialog_error"></span>
				<form>
					<fieldset>
						<label for="term_name"><?php esc_html_e( 'Name', 'yith-woocommerce-color-label-variations' ); ?>:
							<input type="text" name="term_name" id="term_name" value="">
						</label>
						<label for="term_slug"><?php esc_html_e( 'Slug', 'yith-woocommerce-color-label-variations' ); ?>:
							<input type="text" name="term_slug" id="term_slug" value="">
						</label>
						<div class="label-input">
							<?php esc_html_e( 'Value', 'yith-woocommerce-color-label-variations' ); ?>:
							<input type="text" class="ywccl" name="term_value[]" id="term_value" value="" data-type="label">
							<span class="ywccl_add_color_icon" data-content="-">+</span><br>
							<input type="text" class="ywccl hidden_empty" name="term_value[]" id="term_value_2" value="" data-type="label">
						</div>
						<label for="term_tooltip"><?php esc_html_e( 'Tooltip', 'yith-woocommerce-color-label-variations' ); ?>:
							<input type="text" name="term_tooltip" id="term_tooltip" value="">
						</label>
					</fieldset>
				</form>
			</div>

			<?php

			echo ob_get_clean(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Ajax action to add new attribute terms
		 *
		 * @since  1.0.0
		 */
		public function yith_wccl_add_new_attribute_ajax() {
			if ( ! isset( $_POST['taxonomy'] ) || ! isset( $_POST['term_name'] ) || ! isset( $_POST['term_value'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				die();
			}

			$tax     = esc_attr( $_POST['taxonomy'] ); //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			$term    = wc_clean( $_POST['term_name'] ); //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			$slug    = wc_clean( $_POST['term_slug'] ); //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			$value   = wc_clean( implode( ',', array_filter( $_POST['term_value'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			$tooltip = wc_clean( $_POST['term_tooltip'] );  //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			$args    = array();

			if ( empty( $value ) ) {
				wp_send_json(
					array(
						'error' => __( 'A value is required for this term', 'yith-woocommerce-color-label-variations' ),
					)
				);
			}

			if ( taxonomy_exists( $tax ) ) {

				if ( $slug ) {
					$args['slug'] = $slug;
				}

				$result = wp_insert_term( $term, $tax, $args );
				if ( is_wp_error( $result ) ) {
					wp_send_json(
						array(
							'error' => $result->get_error_message(),
						)
					);
				} else {
					$term = get_term_by( 'id', $result['term_id'], $tax );
					ywccl_update_term_meta( $term->term_id, '_yith_wccl_value', $value );
					if ( $tooltip ) {
						ywccl_update_term_meta( $term->term_id, '_yith_wccl_tooltip', $tooltip );
					}

					wp_send_json(
						array(
							'id'    => $term->term_id,
							'value' => $term->term_id,
							'name'  => $term->name,
						)
					);
				}
			}

			die();
		}

		/**
		 * Variation gallery template
		 *
		 * @since  1.8.0
		 * @param int     $loop           Position in the loop.
		 * @param array   $variation_data Variation data.
		 * @param WP_Post $variation      Post data.
		 */
		public function gallery_variation_html( $loop, $variation_data, $variation ) {
			$gallery = yith_wccl_get_variation_gallery( $variation );
			if ( ! is_array( $gallery ) ) {
				$gallery = array();
			}

			include YITH_WCCL_DIR . 'templates/admin/variation-gallery.php';
		}

		/**
		 * Variation gallery single image template js
		 *
		 * @since  1.8.0
		 */
		public function gallery_variation_template_js() {
			?>
			<script type="text/html" id="tmpl-yith-wccl-variation-gallery-image">
				<li class="image" data-value="{{data.id}}">
					<a href="#" class="remove"
							title="<?php echo esc_html_x( 'Remove image', 'label for remove single image from variation gallery', 'yith-woocommerce-color-label-variations' ); ?>"></a>
					<img src="{{data.url}}">
				</li>
			</script>
			<?php
		}

		/**
		 * Show option to enable/disable single variation in loop
		 *
		 * @since  1.9.4
		 * @param int     $loop           Position in the loop.
		 * @param array   $variation_data Variation data.
		 * @param WP_Post $variation      Post data.
		 * @return void
		 */
		public function show_variation_in_loop_opt( $loop, $variation_data, $variation ) {

			if ( 'yes' !== get_option( 'yith-wccl-show-single-variations-loop', 'no' ) ) {
				return;
			}

			$value = ! isset( $variation_data['_yith_wccl_in_loop'] )

			?>
			<label class="tips"
					data-tip="<?php esc_attr_e( 'Enable this option to show this variation in archive pages', 'yith-woocommerce-color-label-variations' ); ?>">
				<?php esc_html_e( 'Show in archive pages?', 'yith-woocommerce-color-label-variations' ); ?>
				<input type="checkbox" class="checkbox"
						name="yith_wccl_variation_in_loop[<?php echo esc_attr( $loop ); ?>]" <?php checked( $value, true ); ?> />
			</label>
			<?php
		}

		/**
		 * Add option to enable/disable variable in loop
		 *
		 * @since  1.9.4
		 * @param array $opts An array of product type options.
		 * @return array
		 */
		public function show_variable_in_loop_opt( $opts ) {
			if ( 'yes' !== get_option( 'yith-wccl-show-single-variations-loop', 'no' ) || 'yes' !== get_option( 'yith-wccl-hide-parent-products-loop', 'no' ) ) {
				return $opts;
			}

			$opts['yith_wccl_variable_in_loop'] = array(
				'id'            => '_yith_wccl_variable_in_loop',
				'wrapper_class' => 'show_if_variable',
				'label'         => __( 'Hide in archive pages?', 'yith-woocommerce-color-label-variations' ),
				'description'   => '',
				'default'       => 'yes',
			);

			return $opts;
		}

		/**
		 * Save variation custom meta
		 *
		 * @since  1.8.0
		 * @param integer $variation_id Variation ID.
		 * @param int     $index Variation loop index.
		 * @return void
		 */
		public function save_variation_custom_meta( $variation_id, $index ) {

			$gallery = isset( $_POST['yith_wccl_variation_gallery'][ $index ] ) ? wc_clean( wp_unslash( $_POST['yith_wccl_variation_gallery'][ $index ] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			$in_loop = isset( $_POST['yith_wccl_variation_in_loop'][ $index ] ); //phpcs:ignore WordPress.Security.NonceVerification
			// get variation.
			$variation = wc_get_product( $variation_id );

			if ( $variation instanceof WC_Product ) {
				empty( $gallery ) ? $variation->delete_meta_data( '_yith_wccl_gallery' ) : $variation->update_meta_data( '_yith_wccl_gallery', array_map( 'intval', explode( ',', $gallery ) ) );
                if ( 'yes' === get_option( 'yith-wccl-show-single-variations-loop', 'no' ) ) {
                    $in_loop ? $variation->delete_meta_data( '_yith_wccl_in_loop' ) : $variation->update_meta_data( '_yith_wccl_in_loop', 'no' );
                }
				$variation->save();
			}
		}

		/**
		 * Save variable custom meta
		 *
		 * @since  1.9.6
		 * @param integer $post_id The post ID.
		 * @return void
		 */
		public function save_variable_custom_meta( $post_id ) {

			// Save custom attribute terms.
			if ( isset( $_POST['_yith_wccl_product_terms'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				$product_attributes = $_POST['_yith_wccl_product_terms'];
				foreach ( $product_attributes as $pa_attribute_id => $pa_attribute ) {
					foreach ( $pa_attribute as $pa_term_id => $pa_term ) {
						if ( ! isset( $pa_term['override_global'] ) ) {
							$product_attributes[ $pa_attribute_id ][ $pa_term_id ] = array();
						} else {
							if ( is_array( $product_attributes[ $pa_attribute_id ][ $pa_term_id ]['term_value'] ) ) {
								$value = implode( ',', $product_attributes[ $pa_attribute_id ][ $pa_term_id ]['term_value'] );
								$product_attributes[ $pa_attribute_id ][ $pa_term_id ]['term_value'] = $value;
							}
						}
					}
				}

				$variable           = wc_get_product( $post_id );

				if ( $variable instanceof WC_Product ) {
                    /**
                     * APPLY_FILTERS: yith_wccl_before_save_custom_product_terms
                     *
                     * Filter the available tabs in the plugin panel.
                     *
                     * @param array $product_attributes The custom attributes.
                     * @param WC_Product $variable The variable product.
                     */
                    $product_attributes = apply_filters( 'yith_wccl_before_save_custom_product_terms', $product_attributes, $variable );
					$variable->update_meta_data( '_yith_wccl_product_terms', $product_attributes );
					$variable->save();
				}
			}

			if ( 'yes' !== get_option( 'yith-wccl-show-single-variations-loop', 'no' ) || 'yes' !== get_option( 'yith-wccl-hide-parent-products-loop', 'no' ) ) {
				return;
			}

			if ( isset( $_POST['_yith_wccl_variable_in_loop'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				delete_post_meta( $post_id, '_yith_wccl_variable_in_loop' );
			} else {
				update_post_meta( $post_id, '_yith_wccl_variable_in_loop', 'no' );
			}
		}
		/**
		 * Print Colorpicker attribute
		 *
		 * @param array $field Colorpicker parameters.
		 * @since  2.0.0
		 * @return void
		 */
		public function print_colorpicker_attribute( $field ) {

			$values        = is_string( $field['value'] ) ? explode( ',', $field['value'] ) : $field['value'];
			$value         = $values[0];
			$value_2       = $values[1] ?? '';
			$name          = isset( $field['name'] ) ? $field['name'] . '[]' : 'term_value[]';
			$color_picker1 = array(
				'id'            => '0',
				'name'          => $name,
				'value'         => ! empty( $value ) ? $value : '#ffffff',
				'alpha_enabled' => false,
				'type'          => 'colorpicker',
				'default'       => ! empty( $value_2 ) ? $value_2 : '#ffffff',

			);
			$color_picker2 = array(
				'id'            => '1',
				'name'          => $name,
				'default'       => ! empty( $value_2 ) ? $value_2 : '#ffffff',
				'alpha_enabled' => false,
				'type'          => 'colorpicker',
				'value'         => ! empty( $value_2 ) ? $value_2 : '#ffffff',
			);

			?>
			<div class="yith-colorpicker-group">
				<div class="yith-single-colorpicker colorpicker">
					<?php yith_plugin_fw_get_field( $color_picker1, true, false ); ?>
				</div>
				<div class="yith-single-colorpicker colorpicker ywccl_show_if_dual_color">
					<?php yith_plugin_fw_get_field( $color_picker2, true, false ); ?>
				</div>
			</div>
			<?php
		}
		/**
		 * Variation style tab on single product page for variable products
		 *
		 * @param array $tabs Products tabs.
		 * @since  2.0.0
		 * @return array
		 */
		public function variation_style_tab( $tabs ) {
			$variable_tabs = array(
				'yith_wccl_variations_style' => array(
					'label'    => _x( 'Attributes Style', 'Product tab title', 'yith-woocommerce-color-label-variations' ),
					'target'   => 'yith_wccl_variations_style_tab',
					'class'    => array( 'show_if_variable' ),
					'priority' => 65,
				),
			);
			$tabs          = array_merge( $tabs, $variable_tabs );
			return $tabs;
		}

		/**
		 * Add data panels to products
		 */
		public function add_product_data_panels() {
			/**
			 * Product object.
			 *
			 * @var WC_Product $product_object
			 */
			global $post, $product_object;

            $product = wc_get_product( $post );

            $tabs    = apply_filters('yith_wccl_product_tabs', array(
				'variations-style' => 'yith_wccl_variations_style_tab',
			), $product);

			$args = array(
				'attributes' => $product && 'variable' === $product->get_type() ? $product->get_variation_attributes() : '',
				'product'    => $product,
			);

			foreach ( $tabs as $key => $tab_id ) {
				extract( $args );
				include YITH_WCCL_VIEW_PATH . '/html-' . $key . '-tab.php';

			}
		}
	}
}
/**
 * Unique access to instance of YITH_WCCL_Admin class
 *
 * @since 1.0.0
 * @return YITH_WCCL_Admin
 */
function YITH_WCCL_Admin() { // phpcs:ignore WordPress.NamingConventions
	return YITH_WCCL_Admin::get_instance();
}
