<?php
namespace WoolentorOptions;

/**
 * Scripts and Styles Class
 */
class Assets {

    function __construct() {

        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'register' ], 5 );
        } else {
            add_action( 'wp_enqueue_scripts', [ $this, 'register' ], 5 );
        }
    }

    /**
     * Register our app scripts and styles
     *
     * @return void
     */
    public function register() {
        $this->register_scripts( $this->get_scripts() );
        $this->register_styles( $this->get_styles() );
    }

    /**
     * Register scripts
     *
     * @param  array $scripts
     *
     * @return void
     */
    private function register_scripts( $scripts ) {
        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : false;
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : false;
            $version   = isset( $script['version'] ) ? $script['version'] : '1.0.0';

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
        }
    }

    /**
     * Register styles
     *
     * @param  array $styles
     *
     * @return void
     */
    public function register_styles( $styles ) {
        foreach ( $styles as $handle => $style ) {
            $deps = isset( $style['deps'] ) ? $style['deps'] : false;

            wp_register_style( $handle, $style['src'], $deps, '1.0.0' );
        }
    }

    /**
     * Get all registered scripts
     *
     * @return array
     */
    public function get_scripts() {
        $prefix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.min' : '';
        $is_dev = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'plugindev.test';

        $scripts = [
            'woolentoropt-element-plus' => [
                'src'       => WOOLENTOROPT_ASSETS . '/js/element-plus.js',
                'version'   => WOOLENTOR_VERSION,
                'in_footer' => true
            ],
            'woolentoropt-vendor' => [
                'src'       => WOOLENTOROPT_ASSETS . '/js/vendor.js',
                'version'   => WOOLENTOR_VERSION,
                'in_footer' => true,
                'deps'      => [ 'woolentoropt-element-plus' ]
            ],
            'woolentoropt-vite-client' => [
                'src'       => 'http://localhost:5173/@vite/client',
                'version'   => null,
                'in_footer' => true
            ],
            'woolentoropt-admin' => [
                'src'       => $is_dev && $this->is_vite_running() ? 'http://localhost:5173/src/main.js' : WOOLENTOROPT_ASSETS . '/js/admin.js',
                'deps'      => $is_dev && $this->is_vite_running() ? [ 'woolentoropt-vite-client' ] : [ 'jquery', 'woolentoropt-vendor' ],
                'version'   => WOOLENTOR_VERSION,
                'in_footer' => true
            ]
        ];

        return $scripts;
    }

    /**
     * Get registered styles
     *
     * @return array
     */
    public function get_styles() {

        $styles = [
            'woolentoropt-main' => [
                'src' =>  WOOLENTOROPT_ASSETS . '/css/main.css'
            ],
            'woolentoropt-admin' => [
                'src' =>  WOOLENTOROPT_ASSETS . '/css/admin.css'
            ]
        ];

        return $styles;
    }

    /**
     * Check if Vite dev server is running
     */
    private function is_vite_running() {
        $handle = curl_init('http://localhost:5173');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);

        curl_exec($handle);
        $error = curl_errno($handle);
        curl_close($handle);

        return !$error;
    }

}