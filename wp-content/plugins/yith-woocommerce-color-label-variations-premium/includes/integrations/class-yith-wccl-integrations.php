<?php // phpcs:ignore WordPress.NamingConventions
/**
 * WPML integration class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariationsPremium
 * @version 1.0.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCCL_Integrations' ) ) {
    /**
     * WPML class.
     * The class manage all the Integrations.
     *
     * @since 2.0.0
     */
    class YITH_WCCL_Integrations
    {

        /**
         * Single instance of the class
         *
         * @since 2.0.0
         * @var YITH_WCCL_Integrations
         */
        protected static $instance;

        /**
         * Returns single instance of the class
         *
         * @return YITH_WCCL_Integrations
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
         * Plugins added
         *
         * @var   \array
         * @since 2.0.0
         */
        protected $plugins = array();

        /**
         * Constructor
         *
         * @access public
         * @since  2.0.0
         */
        public function __construct()
        {
            $this->plugins = array(
                'wpml' => 'WPML',
            );

            $this->load();
        }

        /**
         * Load integration class.
         *
         * @since  2.0.0
         */
        private function load() {
            foreach ( $this->plugins as $slug => $class_slug ) {
                $filename  = YITH_WCCL_DIR . 'includes/integrations/' . $slug . '/class-yith-wccl-' . $slug . '.php';
                $classname = 'YITH_WCCL_' . $class_slug;

                $var = str_replace( '-', '_', $slug );

                if ( $this::has_plugin( $slug ) && file_exists( $filename ) && ! function_exists( $classname ) ) {
                    include_once $filename;
                }

                if ( function_exists( $classname ) ) {
                   $classname();
                }
            }
        }

        /**
         * Check if plugin exists and it's activated.
         *
         * @param string $slug plugin slug.
         * @since  2.0.0
         * @return bool
         */
        public static function has_plugin( $slug ) {

            switch ( $slug ) {
                case 'wpml':
                    global $sitepress;

                    $has_plugin = ! empty( $sitepress );
                    break;

                default:
                    $has_plugin = false;
            }

            return $has_plugin;
        }
    }
}

/**
 * Unique access to instance of YITH_WCCL_Integrations class
 *
 * @return YITH_WCCL_Integrations
 * @since 2.0.0
 */
function YITH_WCCL_Integrations()
{ // phpcs:ignore WordPress.NamingConventions
    return YITH_WCCL_Integrations::get_instance();
}
