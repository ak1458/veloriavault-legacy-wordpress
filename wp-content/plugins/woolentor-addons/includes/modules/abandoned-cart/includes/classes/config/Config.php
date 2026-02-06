<?php
namespace Woolentor\Modules\AbandonedCart\Config;

class Config {
    /**
     * Default values
     */
    const DEFAULT_ABANDONED_TIME = 60; // minutes
    const DEFAULT_CLEANUP_DAYS = 30;
    const DEFAULT_EMAIL_SUBJECT = 'Complete your purchase at {site_name}';
    const DEFAULT_EMAIL_HEADING = 'Complete your purchase';

    /**
     * Get abandoned cart time threshold
     */
    public static function get_abandoned_time() {
        return absint( woolentor_get_option( 
            'abandoned_time', 
            'woolentor_abandoned_cart_settings', 
            self::DEFAULT_ABANDONED_TIME 
        ) );
    }

    /**
     * Get cleanup days
     */
    public static function get_cleanup_days() {
        return absint( woolentor_get_option( 
            'cleanup_days', 
            'woolentor_abandoned_cart_settings', 
            self::DEFAULT_CLEANUP_DAYS 
        ) );
    }

    /**
     * Get email settings
     * @todo: remove this function if not needed
     */
    public static function get_email_settings() {
        return array(
            'enable' => woolentor_get_option( 
                'enable_email', 
                'woolentor_abandoned_cart_settings', 
                'yes' 
            ),
            'subject' => woolentor_get_option( 
                'email_subject', 
                'woolentor_abandoned_cart_settings', 
                self::DEFAULT_EMAIL_SUBJECT 
            ),
            'heading' => woolentor_get_option( 
                'email_heading', 
                'woolentor_abandoned_cart_settings', 
                self::DEFAULT_EMAIL_HEADING 
            ),
            'button_text' => woolentor_get_option( 
                'btn_text', 
                'woolentor_abandoned_cart_settings', 
                __( 'Recover Your Cart', 'woolentor' ) 
            )
        );
    }

    public static function get_from_email_options() {
        return array(
            'from_name' => woolentor_get_option( 
                'from_name', 
                'woolentor_abandoned_cart_settings', 
                get_bloginfo( 'name' ) 
            ),
            'from_email_address' => woolentor_get_option( 
                'from_email_address', 
                'woolentor_abandoned_cart_settings', 
                get_option( 'admin_email' ) 
            ),
            'from_reply_to_email_address' => woolentor_get_option( 
                'from_reply_to_email_address', 
                'woolentor_abandoned_cart_settings', 
                get_option( 'admin_email' ) 
            )
        );
    }

    public static function get_recovery_report_notify_to_admin_options() {
        return array(
            'enable' => woolentor_get_option( 
                'recovery_report_notify_to_admin', 
                'woolentor_abandoned_cart_settings', 
                'off' 
            ),
            'email' => woolentor_get_option( 
                'recovery_report_notify_to_admin_email', 
                'woolentor_abandoned_cart_settings', 
                get_option( 'admin_email' ) 
            )
        );
    }

    /**
     * Get database tables
     */
    public static function get_db_tables() {
        global $wpdb;
        
        return array(
            'carts' => $wpdb->prefix . 'woolentor_abandoned_cart',
            'email_logs' => $wpdb->prefix . 'woolentor_abandoned_cart_email_logs',
            'email_templates' => $wpdb->prefix . 'woolentor_abandoned_cart_email_templates',
        );
    }

    /**
     * Get cart statuses
     */
    public static function get_cart_statuses() {
        return array(
            'pending',    // Cart is active but not yet abandoned
            'abandoned',  // Cart has been abandoned
            'recovered',  // Cart has been recovered
            'completed'   // Order has been completed
        );
    }

    /**
     * Get order statuses
     */
    public static function get_order_statuses() {
        return array( 'completed', 'processing' );
    }

    /**
     * Get rule list
     */
    public static function get_rule_list() {

        $enable_reminder_email = woolentor_get_option( 
            'enable_reminder_email', 
            'woolentor_abandoned_cart_settings', 
            'on' 
        );

        if( $enable_reminder_email !== 'on' ) {
            return array();
        }

        $rule_list = woolentor_get_option( 
            'rule_list', 
            'woolentor_abandoned_cart_settings', 
            array()
        );

        if( empty( $rule_list ) ) {
            return array();
        }

        if( !woolentor_is_pro() && is_array( $rule_list ) && count( $rule_list ) > 1 ){
            return isset( $rule_list[1] ) ? [ $rule_list[0], $rule_list[1] ] : [ $rule_list[0] ];
        }else{
            return $rule_list;
        }
    }

}