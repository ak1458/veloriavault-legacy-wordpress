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
 * Email Template REST API Class
 */
class Email_Template extends WP_REST_Controller {

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
     * Register the routes for email templates.
     */
    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/email-templates',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_email_templates' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_email_template' ),
                    'permission_callback' => array( $this, 'update_permissions_check' ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/email-templates/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_email_template' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_email_template' ),
                    'permission_callback' => array( $this, 'update_permissions_check' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_email_template' ),
                    'permission_callback' => array( $this, 'update_permissions_check' ),
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
    public function permissions_check( $request ) {

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
     * Sanitize template data from form
     */
    private function sanitize_template_data( $post_data ) {
        return array(
            'name' => isset( $post_data['name'] ) ? sanitize_text_field( $post_data['name'] ) : '',
            'subject' => isset( $post_data['subject'] ) ? sanitize_text_field( $post_data['subject'] ) : '',
            'body' => isset( $post_data['body'] ) ? wp_kses_post( $post_data['body'] ) : '',
            'status' => isset( $post_data['status'] ) && $post_data['status'] === 'active' ? 'active' : 'inactive',
            'coupon_data' => $this->sanitize_coupon_data( $post_data )
        );
    }

    /**
     * Sanitize coupon data from form
     */
    private function sanitize_coupon_data( $post_data ) {
        if ( ! isset( $post_data['coupon_data'] ) || ! isset( $post_data['coupon_data']['enable_coupon'] ) || $post_data['coupon_data']['enable_coupon'] === false ) {
            return array( 'enable_coupon' => false );
        }

        $coupon_data = $post_data['coupon_data'];

        return array(
            'enable_coupon' => true,
            'coupon_type' => isset( $coupon_data['coupon_type'] ) ? sanitize_text_field( $coupon_data['coupon_type'] ) : 'percentage',
            'coupon_amount' => isset( $coupon_data['coupon_amount'] ) ? floatval( $coupon_data['coupon_amount'] ) : 10,
            'auto_generate' => isset( $coupon_data['auto_generate'] ) ? true : false,
            'coupon_code' => isset( $coupon_data['coupon_code'] ) ? sanitize_text_field( $coupon_data['coupon_code'] ) : '',
            'coupon_prefix' => isset( $coupon_data['coupon_prefix'] ) ? sanitize_text_field( $coupon_data['coupon_prefix'] ) : 'SAVE',
            'coupon_length' => isset( $coupon_data['coupon_length'] ) ? absint( $coupon_data['coupon_length'] ) : 8,
            'coupon_expiry_days' => isset( $coupon_data['coupon_expiry_days'] ) ? absint( $coupon_data['coupon_expiry_days'] ) : 7,
            'minimum_amount' => isset( $coupon_data['minimum_amount'] ) ? floatval( $coupon_data['minimum_amount'] ) : 0,
            'usage_limit' => 1,
            'individual_use' => true,
            'exclude_sale_items' => false,
            'free_shipping' => false
        );
    }

    /**
     * Get email templates.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_email_templates( $request ) {

        try {
            $params = $request->get_params();

            $page = isset( $params['page'] ) ? absint( $params['page'] ) : 1;
            $per_page = isset( $params['per_page'] ) ? absint( $params['per_page'] ) : 50;
            $status = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'all';
            $search_term = isset( $params['search_term'] ) ? sanitize_text_field( $params['search_term'] ) : '';

            $templates = DB_Handler::instance()->get_email_templates( array(
                'return_type' => 'array',
                'status' => $status,
                'per_page' => $per_page,
                'page' => $page,
                'search_term' => $search_term
            ) );

            foreach ( $templates as $key => $template ) {
                $templates[$key]['coupon_data'] = maybe_unserialize( $template['coupon_data'] );
            }

            // If no templates exist, return default templates
            if ( empty( $templates ) ) {
                $templates = $this->get_default_email_templates();
            }

            $total = DB_Handler::instance()->get_total_email_templates();

            $data = array(
                'data' => [
                    'templates' => $templates,
                    'total' => (int) $total,
                    'pages' => ceil( $total / $per_page )
                ]
            );

            return rest_ensure_response( $data );
        } catch ( Exception $e ) {
            return new WP_Error(
                'email_templates_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Create email template.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_email_template( $request ) {
        
        try {
            $params = $request->get_json_params();

            $template_data = $this->sanitize_template_data( $params );

            // Serialize coupon data
            $template_data['coupon_data'] = maybe_serialize( $template_data['coupon_data'] );
            $template_data['created_at'] = current_time( 'mysql' );
            $template_data['modified_at'] = current_time( 'mysql' );

            $inserted = DB_Handler::instance()->insert_email_template( $template_data );

            if ( $inserted ) {
                return rest_ensure_response( array(
                    'success' => true,
                    'message' => __( 'Template created successfully', 'woolentor' ),
                    'id' => $inserted['id']
                ) );
            } else {
                return new WP_Error(
                    'create_template_error',
                    __( 'Failed to create template', 'woolentor' ),
                    array( 'status' => 500 )
                );
            }
            

        } catch ( Exception $e ) {
            return new WP_Error(
                'create_template_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Get single email template.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_email_template( $request ) {
        try {
            $id = $request['id'];
            $template = DB_Handler::instance()->get_email_template_by_id( $id, 'array' );

            if ( ! $template ) {
                return new WP_Error(
                    'template_not_found',
                    __( 'Email template not found.', 'woolentor' ),
                    array( 'status' => 404 )
                );
            }

            return rest_ensure_response( $template );
        } catch ( Exception $e ) {
            return new WP_Error(
                'get_template_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Update email template.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_email_template( $request ) {
        try {
            $id = $request['id'];
            $params = $request->get_json_params();

            $template_data = $this->sanitize_template_data( $params );

            // Serialize coupon data
            $template_data['coupon_data'] = maybe_serialize( $template_data['coupon_data'] );

            // Update modified_at
            $template_data['modified_at'] = current_time( 'mysql' );

            $template_data['id'] = $id;
            $updated = DB_Handler::instance()->update_email_template( $template_data, array( '%s', '%s', '%s', '%s', '%s', '%s' ) );

            if ( $updated ) {
                return rest_ensure_response( array(
                    'success' => true,
                    'message' => __( 'Email template updated successfully.', 'woolentor' )
                ) );
            } else {
                return new WP_Error(
                    'update_template_error',
                    __( 'Failed to update email template.', 'woolentor' ),
                    array( 'status' => 500 )
                );
            }

        } catch ( Exception $e ) {
            return new WP_Error(
                'update_template_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Delete email template.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_email_template( $request ) {
        try {
            $id = $request['id'];
            $deleted = DB_Handler::instance()->delete_email_template( $id );
            
            if ( $deleted === false ) {
                throw new Exception( __( 'Failed to delete email template.', 'woolentor' ) );
            }

            return rest_ensure_response( array(
                'success' => true,
                'message' => __( 'Email template deleted successfully.', 'woolentor' )
            ) );
        } catch ( Exception $e ) {
            return new WP_Error(
                'delete_template_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Get default email templates.
     *
     * @return array
     */
    private function get_default_email_templates() {
        return array(
            array(
                'id' => 1,
                'name' => __( 'First Reminder', 'woolentor' ),
                'subject' => __( 'You left something in your cart!', 'woolentor' ),
                'content' => __( 'Hi there! You left some items in your cart. Complete your purchase now!', 'woolentor' ),
                'delay_hours' => 1,
                'status' => 'active',
                'created_at' => current_time( 'mysql' )
            ),
            array(
                'id' => 2,
                'name' => __( 'Second Reminder', 'woolentor' ),
                'subject' => __( 'Still interested? Your cart is waiting!', 'woolentor' ),
                'content' => __( 'Your items are still waiting for you. Don\'t miss out!', 'woolentor' ),
                'delay_hours' => 24,
                'status' => 'active',
                'created_at' => current_time( 'mysql' )
            ),
            array(
                'id' => 3,
                'name' => __( 'Third Reminder', 'woolentor' ),
                'subject' => __( 'Your cart is still here!', 'woolentor' ),
                'content' => __( 'Your items are still waiting for you. Don\'t miss out!', 'woolentor' ),
                'delay_hours' => 48,
                'status' => 'active',
                'created_at' => current_time( 'mysql' )
            )
        );
    }


}
