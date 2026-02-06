<?php
namespace Woolentor\Modules\AbandonedCart\Database;
use WooLentor\Modules\AbandonedCart\Config\Config;
use WooLentor\Traits\Singleton;

class DB_Schema {
    use Singleton;

    /**
     * Create tables
     */
    public function create_tables() {
        global $wpdb;
        $tables = Config::get_db_tables();
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Carts table
        $schema = "CREATE TABLE IF NOT EXISTS `{$tables['carts']}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) unsigned DEFAULT NULL,
            `user_email` varchar(100) DEFAULT NULL,
            `session_id` varchar(100) DEFAULT NULL,
            `cart_contents` longtext NOT NULL,
            `cart_total` decimal(10,2) NOT NULL,
            `cart_currency` varchar(10) NOT NULL,
            `customer_other_info` longtext DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `modified_at` datetime NOT NULL,
            `abandoned_at` datetime DEFAULT NULL,
            `recovered_at` datetime DEFAULT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `recovery_key` varchar(32) DEFAULT NULL,
            `unsubscribed` boolean DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `user_email` (`user_email`),
            KEY `session_id` (`session_id`),
            KEY `status` (`status`)
        ) $charset_collate;";

        dbDelta( $schema );

        // Email logs table
        $schema = "CREATE TABLE IF NOT EXISTS `{$tables['email_logs']}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `cart_id` bigint(20) unsigned NOT NULL,
            `template_id` bigint(20) unsigned DEFAULT NULL,
            `subject` varchar(255) NOT NULL,
            `scheduled_at` datetime DEFAULT NULL,
            `sent_at` datetime DEFAULT NULL,
            `status` varchar(20) DEFAULT 'scheduled',
            `email_data` longtext DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `cart_id` (`cart_id`),
            KEY `template_id` (`template_id`),
            KEY `status` (`status`),
            KEY `scheduled_at` (`scheduled_at`)
        ) $charset_collate;";

        dbDelta( $schema );

        // Email templates table
        $schema = "CREATE TABLE IF NOT EXISTS `{$tables['email_templates']}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `name` text NOT NULL,
            `subject` text NOT NULL,
            `body` longtext NOT NULL,
            `status` varchar(20) DEFAULT 'inactive',
            `coupon_data` longtext DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `modified_at` datetime NOT NULL,
            PRIMARY KEY (`id`)
        ) $charset_collate;";

        dbDelta( $schema );

        $this->insert_default_email_templates($wpdb, $tables);

    }

    public function insert_default_email_templates($wpdb, $tables) {
        $templates = $this->get_default_email_templates();
        foreach( $templates as $template ) {
            $wpdb->insert( $tables['email_templates'], $template );
        }
    }

    /**
     * Drop tables
     */
    public function drop_tables() {
        global $wpdb;

        $tables = Config::get_db_tables();

        foreach( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        }
    }

    /**
     * Check if tables exist
     */
    public function tables_exist() {
        global $wpdb;

        $tables = Config::get_db_tables();

        foreach( $tables as $table ) {
            if( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get default email templates
     */
    public function get_default_email_templates() {
        $templates = array();

        $templates[] = array(
            'name' => __( 'First Reminder', 'woolentor' ),
            'subject' => __( 'You left something in your cart!', 'woolentor' ),
            'body' => "Hi {customer_first_name}, 
                You left items in your cart on {cart_abandonment_date}. 

                Cart Total: {cart_total} 

                Your items: {cart_items} 

                <a href='{checkout_url}' target='_blank' rel='noopener'>Complete your purchase</a> 

                <a href='{unsubscribe_link}' target='_blank' rel='noopener'>Unsubscribe</a>

                Kindly,
                {admin_first_name}",
            'status' => 'active',
            'created_at' => current_time( 'mysql' )
        );

        $templates[] = array(
            'name' => __( 'Second Reminder', 'woolentor' ),
            'subject' => __( 'Still interested? Your cart is waiting!', 'woolentor' ),
            'body' => "Hi {customer_first_name}, 
                You left items in your cart on {cart_abandonment_date}. 

                Cart Total: {cart_total}

                Your items: {cart_items}

                Use coupon: {coupon_code} (expires {coupon_expiry_date})

                <a href='{checkout_url}' target='_blank' rel='noopener'>Complete your purchase</a> 

                <a href='{unsubscribe_link}' target='_blank' rel='noopener'>Unsubscribe</a>

                Kindly,
                {admin_first_name}",
            'status' => 'active',
            'created_at' => current_time( 'mysql' )
        );

        $templates[] = array(
            'name' => __( 'Third Reminder', 'woolentor' ),
            'subject' => __( 'Your cart is still here!', 'woolentor' ),
            'body' => "Hi {customer_first_name}, 
                You left items in your cart on {cart_abandonment_date}. 

                Cart Total: {cart_total}

                Your items: {cart_items}

                Use coupon: {coupon_code} (expires {coupon_expiry_date})

                <a href='{checkout_url}' target='_blank' rel='noopener'>Complete your purchase</a> 

                <a href='{unsubscribe_link}' target='_blank' rel='noopener'>Unsubscribe</a>

                Kindly,
                {admin_first_name}",
            'status' => 'active',
            'created_at' => current_time( 'mysql' )
        );

        return $templates;
    }

}