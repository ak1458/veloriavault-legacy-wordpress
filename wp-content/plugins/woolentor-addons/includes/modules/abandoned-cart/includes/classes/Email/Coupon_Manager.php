<?php
namespace Woolentor\Modules\AbandonedCart\Email;
use WooLentor\Traits\Singleton;

class Coupon_Manager {
    use Singleton;

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Clean up expired auto-generated coupons
        add_action( 'woolentor_abandoned_cart_cleanup', array( $this, 'cleanup_expired_coupons' ) );
        
        // Track coupon usage
        add_action( 'woocommerce_applied_coupon', array( $this, 'track_coupon_usage' ) );
        
        // Delete coupon when cart is completed
        add_action( 'woocommerce_order_status_completed', array( $this, 'handle_order_completed' ) );
        add_action( 'woocommerce_order_status_processing', array( $this, 'handle_order_completed' ) );
    }

    /**
     * Create WooCommerce coupon for abandoned cart
     */
    public function create_coupon( $coupon_code, $coupon_data, $cart ) {
        // Don't create if coupon already exists
        if( $this->coupon_exists( $coupon_code ) ) {
            return false;
        }

        $coupon = new \WC_Coupon();
        $coupon->set_code( $coupon_code );
        
        // Set coupon type and amount
        if( $coupon_data['coupon_type'] === 'percentage' ) {
            $coupon->set_discount_type( 'percent' );
        } elseif( $coupon_data['coupon_type'] === 'fixed_cart' ) {
            $coupon->set_discount_type( 'fixed_cart' );
        } elseif( $coupon_data['coupon_type'] === 'fixed_product' ) {
            $coupon->set_discount_type( 'fixed_product' );
        } else {
            $coupon->set_discount_type( 'percent' ); // Default
        }
        
        $coupon->set_amount( floatval( $coupon_data['coupon_amount'] ) );
        
        // Set expiry date
        if( isset( $coupon_data['coupon_expiry_days'] ) && $coupon_data['coupon_expiry_days'] > 0 ) {
            $expiry_date = date( 'Y-m-d', strtotime( '+' . intval( $coupon_data['coupon_expiry_days'] ) . ' days' ) );
            $coupon->set_date_expires( $expiry_date );
        }
        
        // Set usage restrictions
        if( isset( $coupon_data['minimum_amount'] ) && $coupon_data['minimum_amount'] > 0 ) {
            $coupon->set_minimum_amount( floatval( $coupon_data['minimum_amount'] ) );
        }
        
        if( isset( $coupon_data['maximum_amount'] ) && $coupon_data['maximum_amount'] > 0 ) {
            $coupon->set_maximum_amount( floatval( $coupon_data['maximum_amount'] ) );
        }
        
        if( isset( $coupon_data['usage_limit'] ) && $coupon_data['usage_limit'] > 0 ) {
            $coupon->set_usage_limit( intval( $coupon_data['usage_limit'] ) );
        }
        
        // Set per-customer usage limit
        if( isset( $coupon_data['usage_limit_per_customer'] ) && $coupon_data['usage_limit_per_customer'] > 0 ) {
            $coupon->set_usage_limit_per_user( intval( $coupon_data['usage_limit_per_customer'] ) );
        }
        
        if( isset( $coupon_data['individual_use'] ) && $coupon_data['individual_use'] ) {
            $coupon->set_individual_use( true );
        }
        
        if( isset( $coupon_data['exclude_sale_items'] ) && $coupon_data['exclude_sale_items'] ) {
            $coupon->set_exclude_sale_items( true );
        }
        
        if( isset( $coupon_data['free_shipping'] ) && $coupon_data['free_shipping'] ) {
            $coupon->set_free_shipping( true );
        }
        
        // Product restrictions
        if( isset( $coupon_data['product_ids'] ) && !empty( $coupon_data['product_ids'] ) ) {
            $coupon->set_product_ids( $coupon_data['product_ids'] );
        }
        
        if( isset( $coupon_data['excluded_product_ids'] ) && !empty( $coupon_data['excluded_product_ids'] ) ) {
            $coupon->set_excluded_product_ids( $coupon_data['excluded_product_ids'] );
        }
        
        // Category restrictions
        if( isset( $coupon_data['product_categories'] ) && !empty( $coupon_data['product_categories'] ) ) {
            $coupon->set_product_categories( $coupon_data['product_categories'] );
        }
        
        if( isset( $coupon_data['excluded_product_categories'] ) && !empty( $coupon_data['excluded_product_categories'] ) ) {
            $coupon->set_excluded_product_categories( $coupon_data['excluded_product_categories'] );
        }
        
        // Restrict to specific customer email
        if( !empty( $cart->user_email ) ) {
            $coupon->set_email_restrictions( array( $cart->user_email ) );
        }
        
        // Set description
        $coupon->set_description( 
            sprintf( 
                __( 'Abandoned cart recovery coupon for %s (Cart ID: %d)', 'woolentor' ),
                $cart->user_email ?: __( 'Guest', 'woolentor' ),
                $cart->id
            )
        );
        
        // Save coupon
        $coupon_id = $coupon->save();
        
        // Add meta to track this as abandoned cart coupon
        if( $coupon_id ) {
            update_post_meta( $coupon_id, '_woolentor_abandoned_cart_id', $cart->id );
            update_post_meta( $coupon_id, '_woolentor_template_id', $coupon_data['template_id'] ?? 0 );
            update_post_meta( $coupon_id, '_woolentor_auto_generated', 1 );
            update_post_meta( $coupon_id, '_woolentor_created_at', current_time( 'mysql' ) );
            
            // Store the original coupon data for reference
            update_post_meta( $coupon_id, '_woolentor_coupon_data', maybe_serialize( $coupon_data ) );
        }
        
        return $coupon_id;
    }

    /**
     * Generate unique coupon code
     */
    public function generate_coupon_code( $coupon_data, $cart ) {
        $prefix = isset( $coupon_data['coupon_prefix'] ) ? $coupon_data['coupon_prefix'] : 'SAVE';
        $length = isset( $coupon_data['coupon_length'] ) ? intval( $coupon_data['coupon_length'] ) : 8;
        
        // Include cart ID or user identifier for uniqueness
        $identifier = $cart->id;
        if( $cart->user_id ) {
            $identifier .= '_' . $cart->user_id;
        }
        
        // Generate unique code
        $max_attempts = 10;
        $attempts = 0;
        
        do {
            $suffix = $this->generate_random_string( $length );
            $coupon_code = strtoupper( $prefix . $suffix );
            $attempts++;
        } while( $this->coupon_exists( $coupon_code ) && $attempts < $max_attempts );

        // If we couldn't generate a unique code, add timestamp
        if( $this->coupon_exists( $coupon_code ) ) {
            $coupon_code = strtoupper( $prefix . time() . $this->generate_random_string( 4 ) );
        }

        return $coupon_code;
    }

    /**
     * Generate random string for coupon
     */
    private function generate_random_string( $length ) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        
        for( $i = 0; $i < $length; $i++ ) {
            $string .= $characters[wp_rand(0, strlen($characters) - 1)];
        }
        
        return $string;
    }

    /**
     * Check if coupon code already exists
     */
    public function coupon_exists( $coupon_code ) {
        $coupon = new \WC_Coupon( $coupon_code );
        return $coupon->get_id() > 0;
    }

    /**
     * Get coupon by cart ID
     */
    public function get_coupon_by_cart_id( $cart_id ) {
        global $wpdb;
        
        $coupon_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_woolentor_abandoned_cart_id' 
            AND meta_value = %d",
            $cart_id
        ));
        
        if( $coupon_id ) {
            return new \WC_Coupon( $coupon_id );
        }
        
        return false;
    }

    /**
     * Track coupon usage
     */
    public function track_coupon_usage( $coupon_code ) {
        $coupon = new \WC_Coupon( $coupon_code );
        
        if( $coupon->get_id() ) {
            $cart_id = get_post_meta( $coupon->get_id(), '_woolentor_abandoned_cart_id', true );
            
            if( $cart_id ) {
                // Mark cart as recovered if it's not already
                $db = \Woolentor\Modules\AbandonedCart\Database\DB_Handler::instance();
                $cart = $db->get_cart( $cart_id );
                
                if( $cart && $cart->status === 'abandoned' ) {
                    $cart_manager = \Woolentor\Modules\AbandonedCart\Frontend\Cart_Manager::instance();
                    $cart_manager->mark_cart_recovered( $cart_id );
                }
                
                // Log coupon usage
                update_post_meta( $coupon->get_id(), '_woolentor_used_at', current_time( 'mysql' ) );
                
                do_action( 'woolentor_abandoned_cart_coupon_used', $coupon, $cart_id );
            }
        }
    }

    /**
     * Handle order completion to track conversions
     */
    public function handle_order_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        if( !$order ) {
            return;
        }
        
        $used_coupons = $order->get_coupon_codes();
        
        foreach( $used_coupons as $coupon_code ) {
            $coupon = new \WC_Coupon( $coupon_code );
            
            if( $coupon->get_id() ) {
                $cart_id = get_post_meta( $coupon->get_id(), '_woolentor_abandoned_cart_id', true );
                
                if( $cart_id ) {
                    // Mark order as successful conversion
                    update_post_meta( $coupon->get_id(), '_woolentor_converted_order_id', $order_id );
                    update_post_meta( $coupon->get_id(), '_woolentor_converted_at', current_time( 'mysql' ) );
                    
                    do_action( 'woolentor_abandoned_cart_converted', $coupon, $cart_id, $order_id );
                }
            }
        }
    }

    /**
     * Clean up expired auto-generated coupons
     */
    public function cleanup_expired_coupons() {
        global $wpdb;

        // Get expired auto-generated coupons
        $expired_coupons = $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'shop_coupon'
            AND pm1.meta_key = '_woolentor_auto_generated'
            AND pm1.meta_value = '1'
            AND pm2.meta_key = 'date_expires'
            AND pm2.meta_value < %s
            AND pm2.meta_value != ''",
            date( 'Y-m-d', current_time( 'timestamp' ) )
        ));
        
        foreach( $expired_coupons as $coupon_id ) {
            // Check if coupon was used
            $usage_count = get_post_meta( $coupon_id, 'usage_count', true );
            
            // Only delete unused expired coupons
            if( empty( $usage_count ) || $usage_count == 0 ) {
                wp_delete_post( $coupon_id, true );
            }
        }
        
        // Also clean up very old auto-generated coupons (older than 90 days)
        $old_coupons = $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_coupon'
            AND pm.meta_key = '_woolentor_auto_generated'
            AND pm.meta_value = '1'
            AND p.post_date < %s",
            date( 'Y-m-d H:i:s', strtotime( '-90 days' ) )
        ));
        
        foreach( $old_coupons as $coupon_id ) {
            wp_delete_post( $coupon_id, true );
        }
    }

    /**
     * Get coupon statistics
     */
    public function get_coupon_stats( $days = 30 ) {
        global $wpdb;
        
        $stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_generated,
                SUM(CASE WHEN pm_used.meta_value IS NOT NULL THEN 1 ELSE 0 END) as total_used,
                SUM(CASE WHEN pm_converted.meta_value IS NOT NULL THEN 1 ELSE 0 END) as total_converted
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_auto ON p.ID = pm_auto.post_id
            LEFT JOIN {$wpdb->postmeta} pm_used ON p.ID = pm_used.post_id AND pm_used.meta_key = '_woolentor_used_at'
            LEFT JOIN {$wpdb->postmeta} pm_converted ON p.ID = pm_converted.post_id AND pm_converted.meta_key = '_woolentor_converted_at'
            WHERE p.post_type = 'shop_coupon'
            AND pm_auto.meta_key = '_woolentor_auto_generated'
            AND pm_auto.meta_value = '1'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ) );
        
        if( $stats ) {
            $stats->usage_rate = $stats->total_generated > 0 ? 
                round( ( $stats->total_used / $stats->total_generated ) * 100, 2 ) : 0;
                
            $stats->conversion_rate = $stats->total_used > 0 ? 
                round( ( $stats->total_converted / $stats->total_used ) * 100, 2 ) : 0;
        }
        
        return $stats;
    }

    /**
     * Get coupon performance by template using email_logs
     */
    public function get_template_coupon_performance( $template_id, $days = 30 ) {
        global $wpdb;
        
        $email_logs_table = $wpdb->prefix . 'woolentor_abandoned_cart_email_logs';
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT p.ID) as total_generated,
                SUM(CASE WHEN pm_used.meta_value IS NOT NULL THEN 1 ELSE 0 END) as total_used,
                SUM(CASE WHEN pm_converted.meta_value IS NOT NULL THEN 1 ELSE 0 END) as total_converted
            FROM {$email_logs_table} el
            INNER JOIN {$wpdb->postmeta} pm_cart ON el.cart_id = pm_cart.meta_value
            INNER JOIN {$wpdb->posts} p ON pm_cart.post_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm_used ON p.ID = pm_used.post_id AND pm_used.meta_key = '_woolentor_used_at'
            LEFT JOIN {$wpdb->postmeta} pm_converted ON p.ID = pm_converted.post_id AND pm_converted.meta_key = '_woolentor_converted_at'
            WHERE el.template_id = %d
            AND pm_cart.meta_key = '_woolentor_abandoned_cart_id'
            AND p.post_type = 'shop_coupon'
            AND el.sent_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $template_id,
            $days
        ) );
    }

    /**
     * Validate coupon data structure
     */
    public function validate_coupon_data( $coupon_data ) {
        $defaults = array(
            'enable_coupon' => false,
            'coupon_type' => 'percentage',
            'coupon_amount' => 10,
            'auto_generate' => true,
            'coupon_code' => '',
            'coupon_prefix' => 'SAVE',
            'coupon_length' => 8,
            'coupon_expiry_days' => 7,
            'minimum_amount' => 0,
            'maximum_amount' => 0,
            'usage_limit' => 1,
            'usage_limit_per_customer' => 1,
            'individual_use' => true,
            'exclude_sale_items' => false,
            'free_shipping' => false,
            'product_ids' => array(),
            'excluded_product_ids' => array(),
            'product_categories' => array(),
            'excluded_product_categories' => array()
        );
        
        return wp_parse_args( $coupon_data, $defaults );
    }

    /**
     * Get all coupons generated for abandoned carts
     */
    public function get_abandoned_cart_coupons( $args = array() ) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'status' => 'all', // all, used, unused, expired
            'orderby' => 'post_date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];
        
        $where_clauses = array(
            "p.post_type = 'shop_coupon'",
            "pm_auto.meta_key = '_woolentor_auto_generated'",
            "pm_auto.meta_value = '1'"
        );
        
        $joins = array(
            "INNER JOIN {$wpdb->postmeta} pm_auto ON p.ID = pm_auto.post_id"
        );
        
        // Add status filters
        switch( $args['status'] ) {
            case 'used':
                $joins[] = "INNER JOIN {$wpdb->postmeta} pm_used ON p.ID = pm_used.post_id AND pm_used.meta_key = '_woolentor_used_at'";
                break;
            case 'unused':
                $joins[] = "LEFT JOIN {$wpdb->postmeta} pm_used ON p.ID = pm_used.post_id AND pm_used.meta_key = '_woolentor_used_at'";
                $where_clauses[] = "pm_used.meta_value IS NULL";
                break;
            case 'expired':
                $joins[] = "INNER JOIN {$wpdb->postmeta} pm_expires ON p.ID = pm_expires.post_id AND pm_expires.meta_key = 'date_expires'";
                $where_clauses[] = "pm_expires.meta_value < '" . date( 'Y-m-d' ) . "'";
                break;
        }
        
        $sql = "SELECT p.* FROM {$wpdb->posts} p " .
               implode( ' ', $joins ) . " " .
               "WHERE " . implode( ' AND ', $where_clauses ) . " " .
               "ORDER BY p.{$args['orderby']} {$args['order']} " .
               "LIMIT {$args['per_page']} OFFSET {$offset}";
        
        return $wpdb->get_results( $sql );
    }

    /**
     * Delete coupon by cart ID
     */
    public function delete_coupon_by_cart_id( $cart_id ) {
        global $wpdb;
        
        $coupon_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_woolentor_abandoned_cart_id' 
            AND meta_value = %d",
            $cart_id
        ));
        
        foreach( $coupon_ids as $coupon_id ) {
            wp_delete_post( $coupon_id, true );
        }
        
        return count( $coupon_ids );
    }

    /**
     * Get coupon usage for a specific cart
     */
    public function get_cart_coupon_usage( $cart_id ) {
        $coupon = $this->get_coupon_by_cart_id( $cart_id );
        
        if( !$coupon ) {
            return false;
        }
        
        return array(
            'coupon_id' => $coupon->get_id(),
            'coupon_code' => $coupon->get_code(),
            'usage_count' => $coupon->get_usage_count(),
            'usage_limit' => $coupon->get_usage_limit(),
            'date_expires' => $coupon->get_date_expires(),
            'used_at' => get_post_meta( $coupon->get_id(), '_woolentor_used_at', true ),
            'converted_at' => get_post_meta( $coupon->get_id(), '_woolentor_converted_at', true ),
            'converted_order_id' => get_post_meta( $coupon->get_id(), '_woolentor_converted_order_id', true )
        );
    }

    /**
     * Apply coupon automatically during cart recovery
     */
    public function auto_apply_coupon_on_recovery( $cart_id ) {
        $coupon = $this->get_coupon_by_cart_id( $cart_id );
        
        if( !$coupon || !WC()->cart ) {
            return false;
        }
        
        $coupon_code = $coupon->get_code();
        
        // Check if coupon is already applied
        if( WC()->cart->has_discount( $coupon_code ) ) {
            return true;
        }
        
        // Check if coupon is valid
        if( !$coupon->is_valid() ) {
            return false;
        }
        
        // Apply the coupon
        $applied = WC()->cart->apply_coupon( $coupon_code );
        
        if( $applied ) {
            wc_add_notice( 
                sprintf( 
                    __( 'Coupon "%s" has been automatically applied to your cart!', 'woolentor' ),
                    $coupon_code
                ), 
                'success' 
            );
        }
        
        return $applied;
    }

    /**
     * Create personalized coupon for specific products in cart
     */
    public function create_personalized_coupon( $coupon_data, $cart ) {
        // Get cart contents
        $cart_contents = maybe_unserialize( $cart->cart_contents );
        if( empty( $cart_contents ) ) {
            return false;
        }
        
        // Extract product IDs from cart
        $product_ids = array();
        $category_ids = array();
        
        foreach( $cart_contents as $cart_item ) {
            $product_ids[] = $cart_item['product_id'];
            
            // Get product categories
            $product = wc_get_product( $cart_item['product_id'] );
            if( $product ) {
                $terms = get_the_terms( $cart_item['product_id'], 'product_cat' );
                if( $terms && !is_wp_error( $terms ) ) {
                    foreach( $terms as $term ) {
                        $category_ids[] = $term->term_id;
                    }
                }
            }
        }
        
        // Customize coupon data based on cart contents
        if( isset( $coupon_data['restrict_to_cart_products'] ) && $coupon_data['restrict_to_cart_products'] ) {
            $coupon_data['product_ids'] = array_unique( $product_ids );
        }
        
        if( isset( $coupon_data['restrict_to_cart_categories'] ) && $coupon_data['restrict_to_cart_categories'] ) {
            $coupon_data['product_categories'] = array_unique( $category_ids );
        }
        
        // Generate coupon code
        $coupon_code = $this->generate_coupon_code( $coupon_data, $cart );
        
        // Create the coupon
        return $this->create_coupon( $coupon_code, $coupon_data, $cart );
    }

    /**
     * Get coupon redemption report
     */
    public function get_redemption_report( $start_date = null, $end_date = null ) {
        global $wpdb;
        
        if( !$start_date ) {
            $start_date = date( 'Y-m-d', strtotime( '-30 days' ) );
        }
        if( !$end_date ) {
            $end_date = date( 'Y-m-d' );
        }
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                p.post_title as coupon_code,
                pm_cart.meta_value as cart_id,
                pm_template.meta_value as template_id,
                pm_used.meta_value as used_at,
                pm_converted.meta_value as converted_at,
                pm_order.meta_value as order_id,
                p.post_date as created_at
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_auto ON p.ID = pm_auto.post_id
            LEFT JOIN {$wpdb->postmeta} pm_cart ON p.ID = pm_cart.post_id AND pm_cart.meta_key = '_woolentor_abandoned_cart_id'
            LEFT JOIN {$wpdb->postmeta} pm_template ON p.ID = pm_template.post_id AND pm_template.meta_key = '_woolentor_template_id'
            LEFT JOIN {$wpdb->postmeta} pm_used ON p.ID = pm_used.post_id AND pm_used.meta_key = '_woolentor_used_at'
            LEFT JOIN {$wpdb->postmeta} pm_converted ON p.ID = pm_converted.post_id AND pm_converted.meta_key = '_woolentor_converted_at'
            LEFT JOIN {$wpdb->postmeta} pm_order ON p.ID = pm_order.post_id AND pm_order.meta_key = '_woolentor_converted_order_id'
            WHERE p.post_type = 'shop_coupon'
            AND pm_auto.meta_key = '_woolentor_auto_generated'
            AND pm_auto.meta_value = '1'
            AND DATE(p.post_date) BETWEEN %s AND %s
            ORDER BY p.post_date DESC",
            $start_date,
            $end_date
        ) );
    }

    /**
     * Bulk delete unused expired coupons
     */
    public function bulk_delete_unused_expired_coupons() {
        global $wpdb;
        
        $expired_unused_coupons = $wpdb->get_col(
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_auto ON p.ID = pm_auto.post_id
            INNER JOIN {$wpdb->postmeta} pm_expires ON p.ID = pm_expires.post_id
            LEFT JOIN {$wpdb->postmeta} pm_used ON p.ID = pm_used.post_id AND pm_used.meta_key = '_woolentor_used_at'
            WHERE p.post_type = 'shop_coupon'
            AND pm_auto.meta_key = '_woolentor_auto_generated'
            AND pm_auto.meta_value = '1'
            AND pm_expires.meta_key = 'date_expires'
            AND pm_expires.meta_value < '" . date( 'Y-m-d' ) . "'
            AND pm_expires.meta_value != ''
            AND pm_used.meta_value IS NULL"
        );
        
        $deleted_count = 0;
        foreach( $expired_unused_coupons as $coupon_id ) {
            if( wp_delete_post( $coupon_id, true ) ) {
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }

    /**
     * Get top performing coupon templates using email_logs
     */
    public function get_top_performing_templates( $limit = 10, $days = 30 ) {
        global $wpdb;
        
        $email_logs_table = $wpdb->prefix . 'woolentor_abandoned_cart_email_logs';
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                el.template_id,
                COUNT(DISTINCT p.ID) as total_generated,
                SUM(CASE WHEN pm_used.meta_value IS NOT NULL THEN 1 ELSE 0 END) as total_used,
                SUM(CASE WHEN pm_converted.meta_value IS NOT NULL THEN 1 ELSE 0 END) as total_converted,
                ROUND((SUM(CASE WHEN pm_used.meta_value IS NOT NULL THEN 1 ELSE 0 END) / COUNT(DISTINCT p.ID)) * 100, 2) as usage_rate,
                ROUND((SUM(CASE WHEN pm_converted.meta_value IS NOT NULL THEN 1 ELSE 0 END) / COUNT(DISTINCT p.ID)) * 100, 2) as conversion_rate
            FROM {$email_logs_table} el
            INNER JOIN {$wpdb->postmeta} pm_cart ON el.cart_id = pm_cart.meta_value
            INNER JOIN {$wpdb->posts} p ON pm_cart.post_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm_used ON p.ID = pm_used.post_id AND pm_used.meta_key = '_woolentor_used_at'
            LEFT JOIN {$wpdb->postmeta} pm_converted ON p.ID = pm_converted.post_id AND pm_converted.meta_key = '_woolentor_converted_at'
            WHERE el.template_id IS NOT NULL
            AND pm_cart.meta_key = '_woolentor_abandoned_cart_id'
            AND p.post_type = 'shop_coupon'
            AND el.sent_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY el.template_id
            ORDER BY conversion_rate DESC, usage_rate DESC
            LIMIT %d",
            $days,
            $limit
        ) );
    }
}