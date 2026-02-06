<?php
namespace WooLentorPro;
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Installer class
 */
class Installer {

    private static $instance = null;
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Class Constructor
     */
    private function __construct(){
        $this->run();
    }

    /**
     * Run the installer
     *
     * @return void
     */
    public function run() {
        $this->add_version();
        $this->add_redirection_flag();
    }

    /**
     * Add time and version on DB
     */
    public function add_version() {
        $installed = get_option( 'woolentorpro_installed' );

        if ( ! $installed ) {
            update_option( 'woolentorpro_installed', time() );
        }

        update_option( 'woolentorpro_version', WOOLENTOR_VERSION_PRO );
    }

    /**
     * [add_redirection_flag] redirection flug
     */
    public function add_redirection_flag(){
        add_option( 'woolentor_do_activation_library_cache', TRUE );
    }


}