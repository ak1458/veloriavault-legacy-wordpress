<?php
namespace WooLentor\MultiLanguage;
use WooLentor\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Languages {
    use Singleton;

    /**
     * [$language_code]
     * @var string
     */
    public static $language_code;

    /**
     * Translator Name
     * @var
     */
    public static $translator_name;

    /**
     * [__construct] Class constructor
    */
    public function __construct() {
        $this->set_language_code();
        add_filter( 'woolentor_current_language_code', [$this, 'get_language_code'] );
    }

    /**
     * [set_language_code]
     * @return [void]
    */
    public static function set_language_code() {
        // Check for WPML - use defined constant which works on both admin and frontend
        if ( defined( 'ICL_SITEPRESS_VERSION' ) || class_exists( 'SitePress' ) ) {
            self::$language_code = apply_filters( 'wpml_current_language', 'en' );
            self::$translator_name = 'wpml';

        } elseif ( function_exists( 'pll_current_language' ) ) {
            self::$language_code = pll_current_language();
            self::$translator_name = 'polylang';
        }

    }

    /**
     * [get_language_code]
     * @var $language_code
     * @return [string]
    */
    public static function get_language_code( $language_code ) {
        if ( self::$language_code ) {
            return self::$language_code;
        }
        return $language_code;
    }

    /**
     * Manage Single Text translate
     * @param mixed $name String identifier
     * @param mixed $value Default value
     * @param string $context Translation context/group (must match registration context)
     * @return mixed Translated string or original value
     */
    public static function translator( $name, $value, $context = 'ShopLentor' ){
        // Re-check translator name in case it wasn't set during init
        if ( empty( self::$translator_name ) ) {
            self::set_language_code();
        }

        if ( 'polylang' === self::$translator_name && function_exists('pll_translate_string') ) {
            return pll_translate_string( $value, pll_current_language() );
        } elseif ( 'wpml' === self::$translator_name ) {
            return apply_filters( 'wpml_translate_single_string', $value, $context, $name );
        }
        return $value;
    }

    /**
     * Register a string for translation with WPML/Polylang
     * This must be called before translator() can find translations
     *
     * @param string $name Unique identifier for the string
     * @param string $value The default string value to register
     * @param string $group Group name for organization in translation interface
     * @return void
     */
    public static function register_string( $name, $value, $group = 'ShopLentor' ){
        if ( empty( $value ) || ! is_string( $value ) ) {
            return;
        }

        // Re-check translator name in case it wasn't set during init
        if ( empty( self::$translator_name ) ) {
            self::set_language_code();
        }

        if ( 'wpml' === self::$translator_name ) {
            do_action( 'wpml_register_single_string', $group, $name, $value );
        } elseif ( 'polylang' === self::$translator_name && function_exists( 'pll_register_string' ) ) {
            pll_register_string( $name, $value, $group, false );
        }
    }

    /**
     * Register multiple strings at once
     *
     * @param array $strings Array of ['name' => 'value'] pairs
     * @param string $group Group name for organization
     * @return void
     */
    public static function register_strings( $strings, $group = 'ShopLentor' ){
        if ( ! is_array( $strings ) ) {
            return;
        }

        foreach ( $strings as $name => $value ) {
            self::register_string( $name, $value, $group );
        }
    }

}
Languages::instance();