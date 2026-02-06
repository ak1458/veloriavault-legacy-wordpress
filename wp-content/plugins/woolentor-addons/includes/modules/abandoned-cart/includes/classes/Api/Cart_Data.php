<?php
namespace WooLentor\Modules\AbandonedCart\Api;

defined( 'ABSPATH' ) || exit;
use WP_REST_Controller;
use WP_Error;
use WP_REST_Server;
use Exception;
use Woolentor\Modules\AbandonedCart\Database\DB_Handler;

if (!class_exists('\Woolentor\Modules\AbandonedCart\Database\DB_Handler')) {
    require_once dirname(__DIR__) . '/../Database/DB_Handler.php';
}

/**
 * Abandoned Cart REST API Class
 */
class Cart_Data extends WP_REST_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'woolentor/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'abandoned-cart';

    /**
     * Register the routes for abandoned cart.
     */
    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/carts',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_abandoned_carts' ),
                    'permission_callback' => array( $this, 'get_permissions_check' ),
                    'args'                => array(
                        'page' => array(
                            'default' => 1,
                            'sanitize_callback' => 'absint',
                        ),
                        'per_page' => array(
                            'default' => 10,
                            'sanitize_callback' => 'absint',
                        ),
                        'status' => array(
                            'default' => 'all',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/carts/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_abandoned_cart' ),
                    'permission_callback' => array( $this, 'get_permissions_check' ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/carts/(?P<id>\d+)/send-email',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'send_recovery_email' ),
                    'permission_callback' => array( $this, 'update_permissions_check' ),
                ),
            )
        );

        // Analytics endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/analytics',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_analytics' ),
                    'permission_callback' => array( $this, 'get_permissions_check' ),
                ),
            )
        );

        // Delete cart endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/carts/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_cart' ),
                    'permission_callback' => array( $this, 'update_permissions_check' ),
                ),
            )
        );

        // Bulk actions endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/carts/bulk',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'bulk_actions' ),
                    'permission_callback' => array( $this, 'update_permissions_check' ),
                    'args'                => array(
                        'action' => array(
                            'required' => true,
                            'type' => 'string',
                            'enum' => ['delete', 'send_email', 'unsubscribe'],
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'cart_ids' => array(
                            'required' => true,
                            'type' => 'array',
                            'items' => array('type' => 'integer'),
                            'sanitize_callback' => function($param) {
                                return array_map('absint', $param);
                            },
                        ),
                    ),
                ),
            )
        );

        // Search carts endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/carts/search',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'search_carts' ),
                    'permission_callback' => array( $this, 'get_permissions_check' ),
                    'args'                => array(
                        'search' => array(
                            'required' => true,
                            'type' => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'page' => array(
                            'default' => 1,
                            'sanitize_callback' => 'absint',
                        ),
                        'per_page' => array(
                            'default' => 10,
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );

    }

    /**
     * Check if a given request has access to read settings.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|bool
     */
    public function get_permissions_check( $request ) {

        if( !current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permissions to manage settings.', 'woolentor'),
                array('status' => 401)
            );
        }

        // For POST requests, verify nonce
        if ($request->get_method() === 'POST') {
            $nonce = $request->get_header('X-WP-Nonce');

            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new WP_Error(
                    'rest_forbidden',
                    esc_html__('Nonce verification failed.', 'woolentor'),
                    array('status' => 401)
                );
            }
        }

        return true;
    }

    /**
     * Check if a given request has access to update settings.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|bool
     */
    public function update_permissions_check( $request ) {
        if( !current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permissions to manage settings.', 'woolentor'),
                array('status' => 401)
            );
        }

        // For POST requests, verify nonce
        if ($request->get_method() === 'POST') {
            $nonce = $request->get_header('X-WP-Nonce');

            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new WP_Error(
                    'rest_forbidden',
                    esc_html__('Nonce verification failed.', 'woolentor'),
                    array('status' => 401)
                );
            }
        }

        return true;
    }

    /**
     * Get abandoned carts.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_abandoned_carts( $request ) {
        try {
            $page = $request->get_param( 'page' ) ?? 1;
            $per_page = $request->get_param( 'per_page' ) ?? 10;
            $status = !empty( $request->get_param( 'status' ) ) ? $request->get_param( 'status' ) : 'all';
            $orderby = $request->get_param( 'orderby' ) ?? 'created_at';
            $order = $request->get_param( 'order' ) ?? 'DESC';
            $form_date = $request->get_param( 'date_from' ) ?? '';
            $to_date = $request->get_param( 'date_to' ) ?? '';

            $args = array(
                'per_page' => $per_page,
                'page' => $page,
                'status' => $status,
                'orderby' => $orderby,
                'order' => $order,
                'form_date' => $form_date,
                'to_date' => $to_date
            );

            $db = DB_Handler::instance();
            $carts = $db->get_abandoned_carts( $args );
            $total = $db->get_total_abandoned_carts();

            $carts = array_map( function( $cart ) {
                if( isset( $cart->user_id ) && $cart->user_id > 0 ) {
                    $user = get_user_by( 'id', $cart->user_id );
                    $cart->customer_name = $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->display_name;
                } else if( isset( $cart->customer_other_info ) && !empty( $cart->customer_other_info ) ) {
                    $customer_info = maybe_unserialize( $cart->customer_other_info );
                    $cart->customer_name = $customer_info['billing_first_name'] . ' ' . $customer_info['billing_last_name'];
                } else {
                    $cart->customer_name = esc_html__( 'Guest User', 'woolentor' );
                }
                return $cart;
            }, $carts );

            $data = array(
                'data' => [
                    'carts' => $carts,
                    'total' => (int) $total,
                    'pages' => ceil( $total / $per_page )
                ]
            );

            return rest_ensure_response( $data );
        } catch ( Exception $e ) {
            return new WP_Error(
                'get_carts_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Get single abandoned cart.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_abandoned_cart( $request ) {        
        try {
            $id = $request['id'];
            
            $db = DB_Handler::instance();
            $cart = $db->get_cart( $id, 'array' );

            if ( ! $cart ) {
                return new WP_Error(
                    'cart_not_found',
                    __( 'Abandoned cart not found.', 'woolentor' ),
                    array( 'status' => 404 )
                );
            }

            // Decode cart items if stored as JSON
            if ( isset( $cart['cart_contents'] ) ) {
                $cart['cart_contents'] = maybe_unserialize( $cart['cart_contents'] );
            }

            return rest_ensure_response( $cart );
        } catch ( Exception $e ) {
            return new WP_Error(
                'get_cart_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Send recovery email.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function send_recovery_email( $request ) {
        try {
            $id = $request['id'];
            
            // Get cart details
            $cart = $this->get_abandoned_cart( $request );
            if ( is_wp_error( $cart ) ) {
                return $cart;
            }

            $cart_data = $cart->get_data();
            
            // TODO: Implement actual email sending logic
            // This would integrate with the existing email system
            
            return rest_ensure_response( array(
                'success' => true,
                'message' => __( 'Recovery email sent successfully.', 'woolentor' )
            ) );
        } catch ( Exception $e ) {
            return new WP_Error(
                'send_email_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Get analytics data.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_analytics( $request ) {
        try {
            $db = DB_Handler::instance();

            // Get date range
            $date_range = isset( $request['range'] ) ? sanitize_text_field( $request['range'] ) : '30days';
            $end_date = current_time('Y-m-d');
            
            if ( $date_range === 'custom' ) {
                $start_date = isset( $request['startDate'] ) ? sanitize_text_field( $request['startDate'] ) : date('Y-m-d', strtotime('-30 days'));
                $end_date = isset( $request['endDate'] ) ? sanitize_text_field( $request['endDate'] ) : current_time('Y-m-d');
                
                // Validate date format
                if ( !$this->validate_date( $start_date ) || !$this->validate_date( $end_date ) ) {
                    $start_date = date('Y-m-d', strtotime('-30 days'));
                    $end_date = current_time('Y-m-d');
                }
            } else {
                switch ( $date_range ) {
                    case '7days':
                        $start_date = date('Y-m-d', strtotime('-7 days'));
                        break;
                    case '30days':
                        $start_date = date('Y-m-d', strtotime('-30 days'));
                        break;
                    case '3months':
                        $start_date = date('Y-m-d', strtotime('-3 months'));
                        break;
                    case 'year':
                        $start_date = date('Y-m-d', strtotime('-1 year'));
                        break;
                    default:
                        $start_date = date('Y-m-d', strtotime('-30 days'));
                        break;
                }
            }

            $stats = $db->get_analytics_data( $start_date, $end_date );
            
            // Calculate recovery rate
            $recovery_rate = 0;
            if( $stats['total_abandoned'] > 0 ) {
                $recovery_rate = ( $stats['total_recovered'] / $stats['total_abandoned'] ) * 100;
            }

            // Get additional chart data
            $chart_data = $this->get_chart_data( $db, $start_date, $end_date );

            return rest_ensure_response( array(
                'totalAbandoned' => (int) $stats['total_abandoned'],
                'totalRecovered' => (int) $stats['total_recovered'],
                'abandonedValue' => (float) $stats['abandoned_value'] ?: 0,
                'recoveredRevenue' => (float) $stats['recovered_value'] ?: 0,
                'recoveryRate' => round( $recovery_rate, 2 ),
                'chartData' => $chart_data
            ) );


        } catch ( Exception $e ) {
            return new WP_Error(
                'analytics_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Delete a cart.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_cart( $request ) {
        try {
            $cart_id = (int) $request['id'];
            
            if ( $cart_id <= 0 ) {
                return new WP_Error(
                    'invalid_cart_id',
                    __( 'Invalid cart ID.', 'woolentor' ),
                    array( 'status' => 400 )
                );
            }

            $db = DB_Handler::instance();
            $result = $db->delete_cart( $cart_id );

            if ( $result ) {
                return rest_ensure_response( array(
                    'success' => true,
                    'message' => __( 'Cart deleted successfully.', 'woolentor' )
                ) );
            } else {
                return new WP_Error(
                    'delete_failed',
                    __( 'Failed to delete cart.', 'woolentor' ),
                    array( 'status' => 500 )
                );
            }
        } catch ( Exception $e ) {
            return new WP_Error(
                'delete_cart_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Handle bulk actions on carts.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function bulk_actions( $request ) {
        try {
            $action = $request->get_param( 'action' );
            $cart_ids = $request->get_param( 'cart_ids' );

            if ( empty( $cart_ids ) || ! is_array( $cart_ids ) ) {
                return new WP_Error(
                    'no_cart_ids',
                    __( 'No cart IDs provided.', 'woolentor' ),
                    array( 'status' => 400 )
                );
            }

            $success_count = 0;
            $db = DB_Handler::instance();

            switch ( $action ) {
                case 'delete':
                    foreach ( $cart_ids as $cart_id ) {
                        if ( $db->delete_cart( $cart_id ) ) {
                            $success_count++;
                        }
                    }
                    $message = sprintf(
                        /* translators: %d: number of carts deleted */
                        _n( 
                            '%d cart deleted successfully.', 
                            '%d carts deleted successfully.', 
                            $success_count, 
                            'woolentor' 
                        ),
                        $success_count
                    );
                    break;

                case 'send_email':
                    // @TODO: Implement actual email sending logic
                    // $email_handler = \Woolentor\Modules\AbandonedCart\Email\Email_Handler::instance();
                    // foreach ( $cart_ids as $cart_id ) {
                    //     $cart = $db->get_cart( $cart_id );
                    //     if ( $cart && $email_handler->send_abandoned_cart_email( $cart ) ) {
                    //         $success_count++;
                    //     }
                    // }
                    // $message = sprintf(
                    //     /* translators: %d: number of emails sent */
                    //     _n( 
                    //         '%d recovery email sent successfully.', 
                    //         '%d recovery emails sent successfully.', 
                    //         $success_count, 
                    //         'woolentor' 
                    //     ),
                    //     $success_count
                    // );
                    break;

                case 'unsubscribe':
                    foreach ( $cart_ids as $cart_id ) {
                        if ( $db->update_cart_field( $cart_id, 'unsubscribed', 1 ) ) {
                            $success_count++;
                        }
                    }
                    $message = sprintf(
                        /* translators: %d: number of carts unsubscribed */
                        _n( 
                            '%d cart unsubscribed successfully.', 
                            '%d carts unsubscribed successfully.', 
                            $success_count, 
                            'woolentor' 
                        ),
                        $success_count
                    );
                    break;

                default:
                    return new WP_Error(
                        'invalid_action',
                        __( 'Invalid bulk action.', 'woolentor' ),
                        array( 'status' => 400 )
                    );
            }

            return rest_ensure_response( array(
                'success' => true,
                'message' => $message,
                'processed' => $success_count,
                'total' => count( $cart_ids )
            ) );

        } catch ( Exception $e ) {
            return new WP_Error(
                'bulk_action_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Search carts.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function search_carts( $request ) {
        try {
            $search_term = $request->get_param( 'search' );
            $page = $request->get_param( 'page' ) ?? 1;
            $per_page = $request->get_param( 'per_page' ) ?? 10;
            $status = $request->get_param( 'status' ) ?? 'all';

            if ( empty( $search_term ) ) {
                return new WP_Error(
                    'no_search_term',
                    __( 'Search term is required.', 'woolentor' ),
                    array( 'status' => 400 )
                );
            }

            $args = array(
                'per_page' => $per_page,
                'page' => $page,
                'status' => $status,
                'orderby' => 'created_at',
                'order' => 'DESC',
                'search_term' => $search_term
            );

            $db = DB_Handler::instance();
            $carts = $db->get_abandoned_carts( $args );

            $data = array(
                'data' => [
                    'carts' => $carts,
                    'total' => count( $carts ),
                    'pages' => ceil( count( $carts ) / $per_page )
                ]
            );

            return rest_ensure_response( $data );
        } catch ( Exception $e ) {
            return new WP_Error(
                'search_carts_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Get chart data for analytics
     *
     * @param DB_Handler $db Database handler instance
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array Chart data
     */
    private function get_chart_data( $db, $start_date, $end_date ) {

        try {
            $chart_data_items = $db->get_chart_data( $start_date, $end_date );

            // Format data for charts
            $chart_data = array(
                'dailyTrend' => $this->format_daily_trend_data( $chart_data_items['daily_trend'], $start_date, $end_date ),
                'hourlyPattern' => $this->format_hourly_pattern_data( $chart_data_items['hourly_pattern'] ),
                'monthlyRevenue' => $this->format_monthly_revenue_data( $chart_data_items['monthly_revenue'] )
            );

            return $chart_data;

        } catch ( Exception $e ) {
            return array(
                'dailyTrend' => array(),
                'hourlyPattern' => array(),
                'monthlyRevenue' => array()
            );
        }
    }

    /**
     * Format daily trend data for chart
     */
    private function format_daily_trend_data( $data, $start_date, $end_date ) {
        $formatted = array(
            'labels' => array(),
            'abandoned' => array(),
            'recovered' => array(),
            'recoveryRate' => array()
        );

        // Create array with all dates in range
        $date_data = array();
        foreach ( $data as $row ) {
            $date_data[$row['date']] = $row;
        }

        // Fill in missing dates
        $current_date = $start_date;
        while ( $current_date <= $end_date ) {
            $formatted['labels'][] = date( 'M j', strtotime( $current_date ) );
            
            if ( isset( $date_data[$current_date] ) ) {
                $abandoned = (int) $date_data[$current_date]['abandoned'];
                $recovered = (int) $date_data[$current_date]['recovered'];
                
                $formatted['abandoned'][] = $abandoned;
                $formatted['recovered'][] = $recovered;
                $formatted['recoveryRate'][] = $abandoned > 0 ? round( ( $recovered / $abandoned ) * 100, 1 ) : 0;
            } else {
                $formatted['abandoned'][] = 0;
                $formatted['recovered'][] = 0;
                $formatted['recoveryRate'][] = 0;
            }
            
            $current_date = date( 'Y-m-d', strtotime( $current_date . ' +1 day' ) );
        }

        return $formatted;
    }

    /**
     * Format hourly pattern data for chart
     */
    private function format_hourly_pattern_data( $data ) {
        $formatted = array(
            'labels' => array(),
            'counts' => array()
        );

        // Create array with all hours
        $hour_data = array();
        foreach ( $data as $row ) {
            $hour_data[(int) $row['hour']] = (int) $row['count'];
        }

        // Fill in all 24 hours
        for ( $hour = 0; $hour < 24; $hour++ ) {
            $formatted['labels'][] = $hour . ':00';
            $formatted['counts'][] = isset( $hour_data[$hour] ) ? $hour_data[$hour] : 0;
        }

        return $formatted;
    }

    /**
     * Format monthly revenue data for chart
     */
    private function format_monthly_revenue_data( $data ) {
        $formatted = array(
            'labels' => array(),
            'revenue' => array()
        );

        // Get last 6 months
        $months = array();
        for ( $i = 5; $i >= 0; $i-- ) {
            $month = date( 'Y-m', strtotime( "-{$i} month" ) );
            $months[] = $month;
        }

        // Create array with monthly data
        $month_data = array();
        foreach ( $data as $row ) {
            $month_data[$row['month']] = (float) $row['revenue'];
        }

        // Fill in all months
        foreach ( $months as $month ) {
            $formatted['labels'][] = date( 'M Y', strtotime( $month . '-01' ) );
            $formatted['revenue'][] = isset( $month_data[$month] ) ? $month_data[$month] : 0;
        }

        return $formatted;
    }

    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @param string $date
     * @return bool
     */
    private function validate_date( $date ) {
        $d = \DateTime::createFromFormat( 'Y-m-d', $date );
        return $d && $d->format( 'Y-m-d' ) === $date;
    }


}
