<?php
namespace Woolentor\Modules\AbandonedCart\Database;
use WooLentor\Traits\Singleton;
use Woolentor\Modules\AbandonedCart\Config\Config;

class DB_Handler {
    use Singleton;

    /**
     * @var \wpdb
     */
    private $wpdb;

    /**
     * @var array
     */
    private $tables;

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tables = Config::get_db_tables();
    }

    /**
     * Get abandoned carts with pagination
     */
    public function get_abandoned_carts( $args = array() ) {
        $defaults = array(
            'per_page' => 10,
            'page' => 1,
            'status' => 'all',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search_term' => '',
            'form_date' => '',
            'to_date' => ''
        );

        $args = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $where_clauses = array();
        $values = array();


        if( $args['status'] !== 'all' && $args['status'] !== '' ) {
            $where_clauses[] = "status = %s";
            $values[] = $args['status'];
        }else{
            $where_clauses[] = "status IN ('abandoned', 'recovered')";
        }

        if( $args['search_term'] ) {
            $where_clauses[] = "user_email LIKE %s OR user_id = %d OR session_id LIKE %s";
            $values[] = '%' . $args['search_term'] . '%';
            $values[] = $args['search_term'];
            $values[] = '%' . $args['search_term'] . '%';
        }

        if( $args['form_date'] ) {
            $where_clauses[] = "abandoned_at >= %s";
            $values[] = $args['form_date'];
        }

        if( $args['to_date'] ) {
            $where_clauses[] = "abandoned_at <= %s";
            $values[] = $args['to_date'];
        }

        if( $where_clauses ) {
            $where_sql = "WHERE " . implode( ' AND ', $where_clauses );
        }

        $prepare_values = array_merge($values, [$args['per_page'], $offset]);
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->tables['carts']}
            {$where_sql}
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d",
            ...$prepare_values
        );

        return $this->wpdb->get_results( $query );
    }

    /**
     * Get total count of abandoned carts
     */
    public function get_total_abandoned_carts() {
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['carts']} WHERE status IN ('abandoned', 'recovered')",
        );
        return $this->wpdb->get_var( $query );
    }

    /**
     * Get total abandoned cart value
     */
    public function get_total_abandoned_value() {

        $query = $this->wpdb->prepare(
            "SELECT SUM(cart_total) FROM {$this->tables['carts']} WHERE status = %s",
            'abandoned'
        );

        $value = $this->wpdb->get_var( $query );
        return $value ? $value : 0;
    }

    /**
     * Get total recovered carts
     */
    public function get_total_recovered_carts() {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['carts']} WHERE status = %s",
                'recovered'
            )
        );
    }

    /**
     * Get total recovered carts
     */
    public function get_total_recovered_value() {
        $value = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(cart_total) FROM {$this->tables['carts']} WHERE status = %s",
                'recovered'
            )
        );
        return $value ? $value : 0;
    }

    /**
     * Get cart by ID
     */
    public function get_cart( $cart_id, $return_type = 'object' ) {

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->tables['carts']} WHERE id = %d",
            $cart_id
        );

        return $return_type === 'array' ? $this->wpdb->get_row( $query, ARRAY_A ) : $this->wpdb->get_row( $query );
    }

    /**
     * Get cart by recovery key
     */
    public function get_cart_by_recovery_key( $key, $return_type = 'object' ) {
        $query = $this->wpdb->prepare( "SELECT * FROM {$this->tables['carts']} WHERE recovery_key = %s", $key );
        return $return_type === 'array' ? $this->wpdb->get_row( $query, ARRAY_A ) : $this->wpdb->get_row( $query );
    }

    /**
     * Get cart by user ID or email
     */
    public function get_cart_by_user( $user_id = null, $email = null, $session_id = null ) {
        if( !$user_id && !$email && !$session_id ) {
            return false;
        }

        $where_clauses = array();
        $values = array();

        if( $user_id ) {
            // $where_clauses[] = "user_id = %d AND recovery_key IS NULL";
            $where_clauses[] = "user_id = %d";
            $values[] = $user_id;
        }

        if( $email ) {
            // $where_clauses[] = "user_email = %s AND recovery_key IS NULL";
            $where_clauses[] = "user_email = %s";
            $values[] = $email;
        }

        if( $session_id ) {
            // $where_clauses[] = "session_id = %s AND recovery_key IS NULL";
            $where_clauses[] = "session_id = %s";
            $values[] = $session_id;
        }

        $where_clauses[] = "status IN ('pending', 'abandoned')";
        
        $where_sql = "(" . implode( ' OR ', array_slice($where_clauses, 0, -1) ) . ") AND " . end($where_clauses);
        
        return $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT * FROM {$this->tables['carts']} WHERE {$where_sql} ORDER BY modified_at DESC LIMIT 1",
            $values
        ));
    }

    /**
     * Get cart by session ID
     */
    public function get_cart_by_session( $session_id ) {
        if( empty( $session_id ) ) {
            return false;
        }

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['carts']} 
                WHERE session_id = %s 
                AND status IN ('pending', 'abandoned', 'recovered') 
                ORDER BY modified_at DESC LIMIT 1",
                $session_id
            )
        );
    }

    /**
     * Get pending carts that should be marked as abandoned
     */
    public function get_pending_carts( $threshold_minutes ) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['carts']} 
                WHERE status = 'pending' 
                AND TIMESTAMPDIFF(MINUTE, modified_at, %s) >= %d",
                current_time( 'mysql' ),
                $threshold_minutes
            )
        );
    }

    /**
     * Insert new cart
     */
    public function insert_cart( $data ) {
        $defaults = array(
            'user_id' => null,
            'user_email' => null,
            'session_id' => null,
            'cart_contents' => '',
            'cart_total' => 0,
            'cart_currency' => get_woocommerce_currency(),
            'created_at' => current_time( 'mysql' ),
            'modified_at' => current_time( 'mysql' ),
            'status' => 'pending'
        );

        $data = wp_parse_args( $data, $defaults );

        // Format for database insert
        $format = array(
            '%d', // user_id
            '%s', // user_email
            '%s', // session_id
            '%s', // cart_contents
            '%f', // cart_total
            '%s', // cart_currency
            '%s', // created_at
            '%s', // modified_at
            '%s'  // status
        );

        $inserted = $this->wpdb->insert(
            $this->tables['carts'],
            $data,
            $format
        );

        if( $inserted ) {
            return $this->wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update cart
     */
    public function update_cart( $cart_id, $data ) {
        $data['modified_at'] = current_time( 'mysql' );

        $format = array();
        foreach( $data as $key => $value ) {
            if( in_array( $key, array( 'user_id' ) ) ) {
                $format[] = '%d';
            } elseif( in_array( $key, array( 'cart_total' ) ) ) {
                $format[] = '%f';
            } else {
                $format[] = '%s';
            }
        }

        $unsubscribed = $this->wpdb->update(
            $this->tables['carts'],
            $data,
            array( 'id' => $cart_id ),
            $format,
            array( '%d' )
        );

        return $unsubscribed;
    }

    /**
     * Update cart field
     */
    public function update_cart_field( $cart_id, $field, $value ) {
        return $this->wpdb->update( $this->tables['carts'], array( $field => $value ), array( 'id' => $cart_id ), array( '%s' ), array( '%d' ) );
    }

    /**
     * Delete cart
     */
    public function delete_cart( $cart_id ) {
        // First delete related email logs
        $this->wpdb->delete(
            $this->tables['email_logs'],
            array( 'cart_id' => $cart_id ),
            array( '%d' )
        );

        // Then delete the cart
        return $this->wpdb->delete(
            $this->tables['carts'],
            array( 'id' => $cart_id ),
            array( '%d' )
        );
    }


    /**
     * Email Template
     */
    public function insert_email_template( $data ) {
        $defaults = array(
            'name' => '',
            'subject' => '',
            'body' => '',
            'status' => 'active',
            'coupon_data' => array(
                'enable_coupon' => false
            )
        );

        $data = wp_parse_args( $data, $defaults );

        $format = array(
            '%s', // name
            '%s', // subject
            '%s', // body
            '%s', // status
            '%s',  // coupon_data,
            '%s'  // created_at
        );

        $inserted = $this->wpdb->insert(
            $this->tables['email_templates'],
            $data,
            $format
        );

        return $inserted ? array(
            'success' => true,
            'id' => $this->wpdb->insert_id,
        ) : false;
    }

    /**
     * Get email templates
     * @param array $args
     * @return array | object
     */
    public function get_email_templates( $args = array() ) {

        $defaults = array(
            'per_page' => 10,
            'page' => 1,
            'status' => 'all',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search_term' => '',
            'form_date' => '',
            'to_date' => '',
            'return_type' => 'object'
        );

        $args = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $where_clauses = array();
        $values = array();


        if( $args['status'] !== 'all' && $args['status'] !== '' ) {
            $where_clauses[] = "status = %s";
            $values[] = $args['status'];
        }else{
            $where_clauses[] = "status IN ('active', 'inactive')";
        }

        if( $args['search_term'] ) {
            $where_clauses[] = "name LIKE %s OR subject LIKE %s";
            $values[] = '%' . $args['search_term'] . '%';
            $values[] = '%' . $args['search_term'] . '%';
        }

        if( $args['form_date'] ) {
            $where_clauses[] = "created_at >= %s";
            $values[] = $args['form_date'];
        }

        if( $args['to_date'] ) {
            $where_clauses[] = "created_at <= %s";
            $values[] = $args['to_date'];
        }

        if( $where_clauses ) {
            $where_sql = "WHERE " . implode( ' AND ', $where_clauses );
        }

        $prepare_values = array_merge($values, [$args['per_page'], $offset]);
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->tables['email_templates']}
            {$where_sql}
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d",
            ...$prepare_values
        );

        $templates = $args['return_type'] === 'array' ? $this->wpdb->get_results( $query, ARRAY_A ) : $this->wpdb->get_results( $query );
        return $templates;

    }

     /**
     * Get total count of email templates
     */
    public function get_total_email_templates() {
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['email_templates']}",
        );
        return $this->wpdb->get_var( $query );
    }

    /**
     * Get email template by ID
     */
    public function get_email_template_by_id( $id, $return_type = 'object' ) {
        $query = $this->wpdb->prepare( "SELECT * FROM {$this->tables['email_templates']} WHERE id = %d", $id );
        return $return_type === 'array' ? $this->wpdb->get_row( $query, ARRAY_A ) : $this->wpdb->get_row( $query );
    }

    /**
     * Update email template
     */
    public function update_email_template( $data, $format ) {
        $updated = $this->wpdb->update(
            $this->tables['email_templates'],
            $data,
            array( 'id' => $data['id'] ),
            $format
        );

        return $updated ? array(
            'success' => true,
            'id' => $data['id']
        ) : false;
    }

    /**
     * Delete email template
     * @param int $id
     * @return bool
     */
    public function delete_email_template( $id ) {

        // First, cancel any scheduled emails for this template
        $this->wpdb->update(
            $this->tables['email_logs'],
            array( 'status' => 'cancelled' ),
            array( 'template_id' => $id, 'status' => 'scheduled' ),
            array( '%s' ),
            array( '%d', '%s' )
        );
        
        // Delete the template
        return $this->wpdb->delete( $this->tables['email_templates'], array( 'id' => $id ), array( '%d' ) ) ? true : false;
    }

    /**
     * Log email
     */
    public function log_email( $data ) {
        $defaults = array(
            'cart_id' => 0,
            'template_id' => null,
            'subject' => '',
            'scheduled_at' => null,
            'sent_at' => null,
            'status' => 'scheduled',
            'email_data' => null
        );

        $data = wp_parse_args( $data, $defaults );

        // If it's being sent now, set sent_at and status
        if( $data['status'] === 'sent' && empty( $data['sent_at'] ) ) {
            $data['sent_at'] = current_time( 'mysql' );
        }

        // Serialize email_data if it's an array
        if( is_array( $data['email_data'] ) ) {
            $data['email_data'] = maybe_serialize( $data['email_data'] );
        }

        $format = array(
            '%d', // cart_id
            '%s', // template_id (can be null)
            '%s', // subject
            '%s', // scheduled_at (can be null)
            '%s', // sent_at (can be null)
            '%s', // status
            '%s'  // email_data (can be null)
        );

        // Handle null template_id
        if( $data['template_id'] === null ) {
            $format[1] = '%d';
        } else {
            $format[1] = '%d';
        }

        return $this->wpdb->insert(
            $this->tables['email_logs'],
            $data,
            $format
        );
    }

    /**
     * Schedule email
     */
    public function schedule_email( $cart_id, $template_id, $subject, $scheduled_at, $email_data = null ) {
        return $this->log_email( array(
            'cart_id' => $cart_id,
            'template_id' => $template_id,
            'subject' => $subject,
            'scheduled_at' => $scheduled_at,
            'status' => 'scheduled',
            'email_data' => $email_data
        ));
    }

    /**
     * Update email status
     */
    public function update_email_status( $email_id, $status, $sent_at = null ) {
        $data = array( 'status' => $status );
        $format = array( '%s' );

        if( $sent_at ) {
            $data['sent_at'] = $sent_at;
            $format[] = '%s';
        } elseif( $status === 'sent' ) {
            $data['sent_at'] = current_time( 'mysql' );
            $format[] = '%s';
        }

        return $this->wpdb->update(
            $this->tables['email_logs'],
            $data,
            array( 'id' => $email_id ),
            $format,
            array( '%d' )
        );
    }

    public function email_already_sent( $cart_id, $template_id ) {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['email_logs']} WHERE cart_id = %d AND template_id = %d AND status = 'sent'",
                $cart_id,
                $template_id
            )
        );
    }

    /**
     * Get emails that should be sent now with optimized query
     */
    public function get_pending_scheduled_emails( $limit = 25 ) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT 
                    el.cart_id,
                    el.template_id,
                    el.subject,
                    el.scheduled_at,
                    el.id as log_id,
                    c.user_email,
                    c.status as cart_status,
                    c.unsubscribed,
                    c.user_id,
                    c.cart_contents,
                    c.customer_other_info,
                    c.cart_total,
                    c.cart_currency,
                    c.abandoned_at
                FROM {$this->tables['email_logs']} el
                INNER JOIN {$this->tables['carts']} c ON el.cart_id = c.id
                WHERE el.status = 'scheduled'
                AND el.scheduled_at <= %s
                AND c.status = 'abandoned'
                AND c.unsubscribed = 0
                ORDER BY el.scheduled_at ASC
                LIMIT %d",
                current_time( 'mysql' ),
                $limit
            )
        );
    }

    /**
     * Check if email already scheduled for cart and template
     */
    public function is_email_scheduled( $cart_id, $template_id ) {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['email_logs']} 
                WHERE cart_id = %d AND template_id = %d 
                AND status IN ('scheduled', 'sent')",
                $cart_id,
                $template_id
            )
        );
        
        return $count > 0;
    }

    /**
     * Cancel scheduled emails for a cart
     */
    public function cancel_scheduled_emails( $cart_id ) {
        return $this->wpdb->update(
            $this->tables['email_logs'],
            array( 'status' => 'cancelled' ),
            array( 
                'cart_id' => $cart_id,
                'status' => 'scheduled'
            ),
            array( '%s' ),
            array( '%d', '%s' )
        );
    }

    /**
     * Get email logs for a cart with template information
     */
    public function get_cart_email_logs_with_templates( $cart_id ) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT el.*, et.name as template_name 
                FROM {$this->tables['email_logs']} el
                LEFT JOIN {$this->tables['email_templates']} et ON el.template_id = et.id
                WHERE el.cart_id = %d 
                ORDER BY el.scheduled_at DESC, el.sent_at DESC",
                $cart_id
            )
        );
    }

    /**
     * Get email statistics
     */
    public function get_email_stats( $days = 30 ) {
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_emails,
                    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_emails,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_emails,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_emails,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_emails
                FROM {$this->tables['email_logs']} 
                WHERE scheduled_at >= DATE_SUB(NOW(), INTERVAL %d DAY) 
                OR sent_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days,
                $days
            ),
            ARRAY_A
        );

        // Calculate success rate
        if( $stats && $stats['total_emails'] > 0 ) {
            $stats['success_rate'] = round( ( $stats['sent_emails'] / $stats['total_emails'] ) * 100, 2 );
        } else {
            $stats['success_rate'] = 0;
        }

        return $stats;
    }

    /**
     * Get template email performance
     */
    public function get_template_email_performance( $template_id, $days = 30 ) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    template_id,
                    COUNT(*) as total_scheduled,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as total_cancelled,
                    ROUND((SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as success_rate
                FROM {$this->tables['email_logs']} 
                WHERE template_id = %d
                AND (scheduled_at >= DATE_SUB(NOW(), INTERVAL %d DAY) 
                OR sent_at >= DATE_SUB(NOW(), INTERVAL %d DAY))
                GROUP BY template_id",
                $template_id,
                $days,
                $days
            )
        );
    }

    /**
     * Update scheduled email status
     */
    public function update_scheduled_email_status( $cart_id, $template_id, $status ) {
        $data = array( 'status' => $status );
        $format = array( '%s' );

        if( $status === 'sent' ) {
            $data['sent_at'] = current_time( 'mysql' );
            $format[] = '%s';
        }
        
        return $this->wpdb->update(
            $this->tables['email_logs'],
            $data,
            array( 
                'cart_id' => $cart_id, 
                'template_id' => $template_id,
                'status' => 'scheduled'
            ),
            $format,
            array( '%d', '%d', '%s' )
        );
    }

    /**
     * Clean up old scheduled emails
     */
    public function cleanup_old_scheduled_emails( $days = 7 ) {
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->tables['email_logs']} 
                SET status = 'expired'
                WHERE status = 'scheduled' 
                AND scheduled_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }

    /**
     * Get pending email count with caching
     */
    public function get_pending_email_count() {
        $cache_key = 'woolentor_pending_email_count';
        $count = wp_cache_get( $cache_key );
        
        if ( $count === false ) {
            $count = $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->tables['email_logs']} 
                WHERE status = 'scheduled' 
                AND scheduled_at <= NOW()"
            );
            
            // Cache for 2 minutes
            wp_cache_set( $cache_key, $count, '', 120 );
        }
        
        return intval( $count );
    }

    /**
     * Clear email count cache
     */
    public function clear_email_count_cache() {
        wp_cache_delete( 'woolentor_pending_email_count' );
        wp_cache_delete( 'woolentor_next_email_time' );
    }

    /**
     * Get analytics data
     */
    public function get_analytics_data( $start_date, $end_date ) {
        $abandoned_count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['carts']} 
                WHERE status IN ('abandoned', 'recovered') 
                AND DATE(created_at) BETWEEN %s AND %s",
                $start_date,
                $end_date
            )
        );

        $recovered_count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['carts']} 
                WHERE status = 'recovered' 
                AND DATE(recovered_at) BETWEEN %s AND %s",
                $start_date,
                $end_date
            )
        );

        $abandoned_value = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(cart_total) FROM {$this->tables['carts']} 
                WHERE status = 'abandoned' 
                AND DATE(created_at) BETWEEN %s AND %s",
                $start_date,
                $end_date
            )
        );

        $recovered_value = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(cart_total) FROM {$this->tables['carts']} 
                WHERE status = 'recovered' 
                AND DATE(recovered_at) BETWEEN %s AND %s",
                $start_date,
                $end_date
            )
        );

        return array(
            'total_abandoned' => intval( $abandoned_count ),
            'total_recovered' => intval( $recovered_count ),
            'abandoned_value' => floatval( $abandoned_value ),
            'recovered_value' => floatval( $recovered_value )
        );
    }

    /**
     * Get daily analytics data for charts
     */
    public function get_daily_analytics_data( $start_date, $end_date ) {
        // Get abandoned carts data
        $abandoned_data = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count,
                    SUM(cart_total) as value
                FROM {$this->tables['carts']} 
                WHERE status = 'abandoned'
                AND DATE(created_at) BETWEEN %s AND %s
                GROUP BY DATE(created_at)
                ORDER BY date ASC",
                $start_date,
                $end_date
            )
        );

        // Get recovered carts data
        $recovered_data = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT 
                    DATE(recovered_at) as date,
                    COUNT(*) as count,
                    SUM(cart_total) as value
                FROM {$this->tables['carts']} 
                WHERE status IN ('recovered')
                AND recovered_at IS NOT NULL
                AND DATE(recovered_at) BETWEEN %s AND %s
                GROUP BY DATE(recovered_at)
                ORDER BY date ASC",
                $start_date,
                $end_date
            )
        );

        // Create a complete date range
        $period = new \DatePeriod(
            new \DateTime($start_date),
            new \DateInterval('P1D'),
            new \DateTime($end_date . ' +1 day')
        );

        $chart_data = array(
            'labels' => array(),
            'abandoned' => array(),
            'recovered' => array(),
            'abandoned_values' => array(),
            'recovered_values' => array()
        );

        // Index data by date for easy lookup
        $abandoned_by_date = array();
        foreach ($abandoned_data as $row) {
            $abandoned_by_date[$row->date] = $row;
        }

        $recovered_by_date = array();
        foreach ($recovered_data as $row) {
            $recovered_by_date[$row->date] = $row;
        }

        // Fill in all dates with data
        foreach ($period as $date) {
            $date_string = $date->format('Y-m-d');
            
            $chart_data['labels'][] = $date->format('M j');
            
            // Abandoned data
            if (isset($abandoned_by_date[$date_string])) {
                $chart_data['abandoned'][] = intval($abandoned_by_date[$date_string]->count);
                $chart_data['abandoned_values'][] = floatval($abandoned_by_date[$date_string]->value);
            } else {
                $chart_data['abandoned'][] = 0;
                $chart_data['abandoned_values'][] = 0;
            }
            
            // Recovered data
            if (isset($recovered_by_date[$date_string])) {
                $chart_data['recovered'][] = intval($recovered_by_date[$date_string]->count);
                $chart_data['recovered_values'][] = floatval($recovered_by_date[$date_string]->value);
            } else {
                $chart_data['recovered'][] = 0;
                $chart_data['recovered_values'][] = 0;
            }
        }

        return $chart_data;
    }

    public function get_chart_data( $start_date, $end_date ) {
            
        // Get daily trend data for the last 7 days
        $daily_trend = $this->wpdb->get_results( $this->wpdb->prepare( "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as abandoned,
                SUM(CASE WHEN status = 'recovered' THEN 1 ELSE 0 END) as recovered
            FROM {$this->tables['carts']}
            WHERE created_at >= %s AND created_at <= %s AND status IN ('abandoned', 'recovered')
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $start_date, $end_date . ' 23:59:59' ), ARRAY_A );

        // Get hourly abandonment pattern
        $hourly_pattern = $this->wpdb->get_results( "
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as count
            FROM {$this->tables['carts']}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY HOUR(created_at)
            ORDER BY hour ASC
        ", ARRAY_A );

        // Get monthly revenue recovery for the last 6 months
        $monthly_revenue = $this->wpdb->get_results( "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(CASE WHEN status = 'recovered' THEN cart_total ELSE 0 END) as revenue
            FROM {$this->tables['carts']}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ", ARRAY_A );

        return array(
            'daily_trend' => $daily_trend,
            'hourly_pattern' => $hourly_pattern,
            'monthly_revenue' => $monthly_revenue
        );

    }

    /**
     * Get email logs for a cart
     */
    public function get_cart_email_logs( $cart_id ) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['email_logs']} WHERE cart_id = %d ORDER BY sent_at DESC",
                $cart_id
            )
        );
    }

    /**
     * Clean up old records
     */
    public function cleanup( $days = 30 ) {
        $deleted_carts = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['carts']} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );

        // Clean up orphaned email logs
        $deleted_emails = $this->wpdb->query(
            "DELETE e FROM {$this->tables['email_logs']} e 
             LEFT JOIN {$this->tables['carts']} c ON e.cart_id = c.id 
             WHERE c.id IS NULL"
        );

        return array(
            'carts' => $deleted_carts,
            'emails' => $deleted_emails
        );
    }

    /**
     * Get recovery stats
     */
    public function get_recovery_stats( $days = 30 ) {
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_carts,
                    SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned_carts,
                    SUM(CASE WHEN status = 'recovered' THEN 1 ELSE 0 END) as recovered_carts,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_carts,
                    SUM(CASE WHEN status = 'abandoned' THEN cart_total ELSE 0 END) as abandoned_value,
                    SUM(CASE WHEN status = 'recovered' THEN cart_total ELSE 0 END) as recovered_value
                FROM {$this->tables['carts']} 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            ),
            ARRAY_A
        );

        // Calculate recovery rate
        $recovery_rate = 0;
        if( $stats['abandoned_carts'] > 0 ) {
            $recovery_rate = ( $stats['recovered_carts'] / $stats['abandoned_carts'] ) * 100;
        }

        $stats['recovery_rate'] = round( $recovery_rate, 2 );

        return $stats;
    }

    /**
     * Get cart by user ID or email (improved logic for logged-in users)
     */
    public function get_cart_by_user_advanced( $user_id, $email, $session_id ) {
        $where_conditions = array();
        $values = array();

        // Priority 1: Check by user_id and session_id (most specific)
        if( $user_id && $session_id ) {
            $cart = $this->wpdb->get_row( $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['carts']} 
                WHERE user_id = %d AND session_id = %s 
                AND status IN ('pending', 'abandoned') 
                ORDER BY modified_at DESC LIMIT 1",
                $user_id,
                $session_id
            ));
            if( $cart ) return $cart;
        }

        // Priority 2: Check by user_id only
        if( $user_id ) {
            $cart = $this->wpdb->get_row( $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['carts']} 
                WHERE user_id = %d 
                AND status IN ('pending', 'abandoned') 
                ORDER BY modified_at DESC LIMIT 1",
                $user_id
            ));
            if( $cart ) return $cart;
        }

        // Priority 3: Check by email and session_id
        if( $email && $session_id ) {
            $cart = $this->wpdb->get_row( $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['carts']} 
                WHERE user_email = %s AND session_id = %s 
                AND status IN ('pending', 'abandoned') 
                ORDER BY modified_at DESC LIMIT 1",
                $email,
                $session_id
            ));
            if( $cart ) return $cart;
        }

        return false;
    }

    /**
     * Get cart by session (improved logic for guest users)
     */
    public function get_cart_by_session_advanced( $session_id, $email = null ) {
        // Priority 1: Check by session_id (most reliable for guests)
        if( $session_id ) {
            $cart = $this->wpdb->get_row( $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['carts']} 
                WHERE session_id = %s 
                AND status IN ('pending', 'abandoned') 
                ORDER BY modified_at DESC LIMIT 1",
                $session_id
            ));
            if( $cart ) return $cart;
        }

        // Priority 2: Check by email if session_id doesn't match
        if( $email ) {
            $cart = $this->wpdb->get_row( $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['carts']} 
                WHERE user_email = %s AND user_id IS NULL 
                AND status IN ('pending', 'abandoned') 
                ORDER BY modified_at DESC LIMIT 1",
                $email
            ));
            if( $cart ) return $cart;
        }

        return false;
    }

    /**
     * Search carts
     */
    public function search_carts( $search_term, $args = array() ) {
        $defaults = array(
            'per_page' => 10,
            'page' => 1,
            'status' => 'all'
        );

        $args = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['carts']} 
                WHERE status IN ('abandoned', 'recovered')
                AND (user_email LIKE %s OR cart_contents LIKE %s)
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d",
                '%' . $this->wpdb->esc_like( $search_term ) . '%',
                '%' . $this->wpdb->esc_like( $search_term ) . '%',
                $args['per_page'],
                $offset
            )
        );
    }

    /**
     * Update cart status in bulk
     */
    public function bulk_update_status( $cart_ids, $status ) {
        if( empty( $cart_ids ) || !is_array( $cart_ids ) ) {
            return false;
        }

        $ids_placeholder = implode( ',', array_fill( 0, count( $cart_ids ), '%d' ) );
        $query_args = array_merge( array( $status, current_time( 'mysql' ) ), $cart_ids );

        return $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->tables['carts']} 
                SET status = %s, modified_at = %s 
                WHERE id IN ({$ids_placeholder})",
                $query_args
            )
        );
    }
}