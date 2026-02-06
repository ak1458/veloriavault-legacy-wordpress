<?php
namespace WoolentorPro\Modules\AdvancedCoupon;
use WooLentorPro\Traits\ModuleBase;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Advanced_Coupon{
    use ModuleBase;

    /**
     * Class Constructor
     */
    public function __construct(){

        // Definded Constants
        $this->define_constants();

        // Include Nessary file
        $this->include();

        // initialize
        $this->init();

    }

    /**
     * Defined Required Constants
     *
     * @return void
     */
    public function define_constants(){
        define( 'WoolentorPro\Modules\AdvancedCoupon\MODULE_FILE', __FILE__ );
        define( 'WoolentorPro\Modules\AdvancedCoupon\MODULE_PATH', __DIR__ );
        define( 'WoolentorPro\Modules\AdvancedCoupon\ENABLED', self::$_enabled );
    }

    /**
     * Load Required File
     *
     * @return void
     */
    public function include(){
        require_once( MODULE_PATH. "/includes/classes/Admin.php" );
        require_once( MODULE_PATH. "/includes/classes/Frontend.php" );

    }

    /**
     * Module Initilize
     *
     * @return void
     */
    public function init(){
        // For Admin
        if ( $this->is_request( 'admin' ) || $this->is_request( 'rest' ) ) {
            Admin::instance();
        }
        
        if( self::$_enabled ){
            if( woolentor_get_option_pro('url_coupon','woolentor_advanced_coupon_settings', 'off') == 'on'){
                $this->for_coupon_url();
            }
            // For Frontend
            if ( $this->is_request( 'frontend' ) ) {
                Frontend::instance();
            }
        }
    }

    /**
     * For Coupon URL
     * @return void
     */
    public function for_coupon_url(){
        add_filter( 'woocommerce_register_post_type_shop_coupon', [ $this, 'add_rewrite_in_wc_coupon' ], 10, 1 );
        add_action( 'update_option_woolentor_advanced_coupon_settings', [ $this, 'flush_rewrite_rules_for_coupon' ] );
    }

    /**
     * Add rewrite and queryable in WC Coupon.
     * @param mixed $args
     * @return mixed
     */
    public function add_rewrite_in_wc_coupon( $args ) {
        $url_slug = woolentor_get_option_pro('url_coupon_slug','woolentor_advanced_coupon_settings', 'discount');
        $args['publicly_queryable'] = true;
        $args['rewrite'] = [
            'slug'       => $url_slug,
            'pages'      => false,
            'with_front' => false, // Exclude any custom structures added in the permalink settings.
        ];

        // Flush rewrite rules
        if( get_option('woolentor_coupon_url_flush_rules', 'no' ) === 'yes' ) {
            flush_rewrite_rules( false );
            delete_option(  'woolentor_coupon_url_flush_rules' );
        }

        return $args;
    }

    /**
     * Flash rewrite rules for coupon slug
     * @return void
     */
    public function flush_rewrite_rules_for_coupon() {
        update_option( 'woolentor_coupon_url_flush_rules', 'yes' );
    }


}
Advanced_Coupon::instance(\Woolentor\Modules\AdvancedCoupon\ENABLED);