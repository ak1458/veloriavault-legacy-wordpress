<?php
namespace Woolentor\Modules\AbandonedCart\Frontend;
use WooLentor\Traits\Singleton;
use Woolentor\Modules\AbandonedCart\Database\DB_Handler;

/**
 * Checkout Data Manager Class
 * 
 * Handles capturing checkout field data for guest users
 */
class Checkout_Data_Manager {
    use Singleton;

    /**
     * @var DB_Handler
     */
    private $db;

    /**
     * @var Cart_Manager
     */
    private $cart_manager;

    /**
     * Constructor
     */
    private function __construct() {
        $this->db = DB_Handler::instance();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Capture checkout data when order review is updated
        add_action( 'woocommerce_checkout_update_order_review', array( $this, 'capture_checkout_data' ), 10, 1 );
        
        // Capture data when checkout form is processed
        add_action( 'woocommerce_checkout_process', array( $this, 'capture_checkout_data_from_process' ), 5 );
        
        // AJAX handler for custom checkout data capture
        add_action( 'wp_ajax_woolentor_save_checkout_data', array( $this, 'ajax_save_checkout_data' ) );
        add_action( 'wp_ajax_nopriv_woolentor_save_checkout_data', array( $this, 'ajax_save_checkout_data' ) );

        // Enqueue checkout scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_scripts' ) );
    }

    /**
     * Set cart manager instance
     */
    public function set_cart_manager( $cart_manager ) {
        $this->cart_manager = $cart_manager;
    }

    /**
     * Capture checkout data from order review update
     */
    public function capture_checkout_data( $posted_data ) {
        // Only for non-logged in users
        if( is_user_logged_in() ) {
            return;
        }

        if( !WC()->session || !WC()->cart || WC()->cart->is_empty() ) {
            return;
        }

        // Parse the posted data
        parse_str( $posted_data, $checkout_data );
        
        // Process and save the checkout data
        $this->process_and_save_checkout_data( $checkout_data );
    }

    /**
     * Capture checkout data from checkout process
     */
    public function capture_checkout_data_from_process() {
        // Only for non-logged in users
        if( is_user_logged_in() ) {
            return;
        }

        if( !WC()->session || !WC()->cart || WC()->cart->is_empty() ) {
            return;
        }

        // Get data from $_POST
        $checkout_data = $_POST;
        
        // Process and save the checkout data
        $this->process_and_save_checkout_data( $checkout_data );
    }

    /**
     * Process and save checkout data
     */
    private function process_and_save_checkout_data( $checkout_data ) {
        if( empty( $checkout_data ) ) {
            return;
        }

        // Get or create session ID for cart tracking
        if( !$this->cart_manager ) {
            $this->cart_manager = Cart_Manager::instance();
        }

        $session_id = \WC()->session->get( Cart_Manager::SESSION_KEY );
        if( !$session_id ) {
            return; // No active cart session
        }

        // Get existing cart
        $existing_cart = $this->db->get_cart_by_session( $session_id );
        if( !$existing_cart ) {
            return; // No cart found
        }

        // Extract and sanitize relevant checkout fields
        $customer_info = $this->extract_customer_info( $checkout_data );
        
        if( empty( $customer_info ) ) {
            return; // No useful data to save
        }

        // Update the cart with customer info
        $updated = $this->db->update_cart( $existing_cart->id, array(
            'customer_other_info' => maybe_serialize( $customer_info ),
            'user_email' => $customer_info['billing_email'] ?? $existing_cart->user_email,
            'modified_at' => current_time( 'mysql' )
        ));

        // Also store in session for immediate use
        if( $updated && !empty( $customer_info['billing_email'] ) ) {
            WC()->session->set( 'billing_email', $customer_info['billing_email'] );
        }

        // Fire action for other plugins
        do_action( 'woolentor_checkout_data_captured', $customer_info, $existing_cart->id );
    }

    /**
     * Extract relevant customer information from checkout data
     */
    private function extract_customer_info( $checkout_data ) {
        $customer_info = array();
        
        // Define the fields we want to capture
        $billing_fields = array(
            'billing_first_name',
            'billing_last_name', 
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
            'billing_email',
            'billing_phone'
        );

        $shipping_fields = array(
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company', 
            'shipping_address_1',
            'shipping_address_2',
            'shipping_city',
            'shipping_state',
            'shipping_postcode',
            'shipping_country'
        );

        $other_fields = array(
            'order_comments',
            'ship_to_different_address',
            'payment_method',
            'terms',
            'createaccount'
        );

        // Extract billing fields
        foreach( $billing_fields as $field ) {
            if( isset( $checkout_data[$field] ) && !empty( $checkout_data[$field] ) ) {
                $customer_info[$field] = $this->sanitize_field_value( $field, $checkout_data[$field] );
            }
        }

        // Extract shipping fields
        foreach( $shipping_fields as $field ) {
            if( isset( $checkout_data[$field] ) && !empty( $checkout_data[$field] ) ) {
                $customer_info[$field] = $this->sanitize_field_value( $field, $checkout_data[$field] );
            }
        }

        // Extract other fields
        foreach( $other_fields as $field ) {
            if( isset( $checkout_data[$field] ) && !empty( $checkout_data[$field] ) ) {
                $customer_info[$field] = $this->sanitize_field_value( $field, $checkout_data[$field] );
            }
        }

        // Add timestamp and additional metadata
        if( !empty( $customer_info ) ) {
            $customer_info['captured_at'] = current_time( 'mysql' );
            $customer_info['user_agent'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';
            $customer_info['ip_address'] = $this->get_client_ip();
            $customer_info['checkout_step'] = $this->determine_checkout_step( $checkout_data );
        }

        // Extract custom fields (from plugins or themes)
        $custom_fields = $this->extract_custom_fields( $checkout_data );
        if( !empty( $custom_fields ) ) {
            $customer_info['custom_fields'] = $custom_fields;
        }

        return $customer_info;
    }

    /**
     * Sanitize field value based on field type
     */
    private function sanitize_field_value( $field_name, $value ) {
        switch( $field_name ) {
            case 'billing_email':
                return sanitize_email( $value );
                
            case 'billing_phone':
                return sanitize_text_field( $value );
                
            case 'billing_first_name':
            case 'billing_last_name':
            case 'shipping_first_name': 
            case 'shipping_last_name':
            case 'billing_city':
            case 'shipping_city':
                return sanitize_text_field( ucwords( strtolower( trim( $value ) ) ) );
                
            case 'billing_address_1':
            case 'billing_address_2':
            case 'shipping_address_1':
            case 'shipping_address_2':
                return sanitize_text_field( $value );
                
            case 'billing_company':
            case 'shipping_company':
                return sanitize_text_field( $value );
                
            case 'billing_postcode':
            case 'shipping_postcode':
                return sanitize_text_field( strtoupper( $value ) );
                
            case 'billing_country':
            case 'shipping_country':
            case 'billing_state':
            case 'shipping_state':
                return sanitize_text_field( $value );
                
            case 'order_comments':
                return sanitize_textarea_field( $value );
                
            case 'payment_method':
                return sanitize_text_field( $value );
                
            case 'ship_to_different_address':
            case 'terms':
            case 'createaccount':
                return (bool) $value;
                
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Extract custom fields (from plugins, themes, etc.)
     */
    private function extract_custom_fields( $checkout_data ) {
        $custom_fields = array();
        
        // Common patterns for custom fields
        $custom_patterns = array(
            '/^billing_(.+)/',
            '/^shipping_(.+)/',
            '/^order_(.+)/',
            '/^custom_(.+)/',
            '/^_(.+)/',
        );

        // WooCommerce default fields to exclude
        $default_fields = array(
            'billing_first_name', 'billing_last_name', 'billing_company',
            'billing_address_1', 'billing_address_2', 'billing_city',
            'billing_state', 'billing_postcode', 'billing_country',
            'billing_email', 'billing_phone',
            'shipping_first_name', 'shipping_last_name', 'shipping_company',
            'shipping_address_1', 'shipping_address_2', 'shipping_city', 
            'shipping_state', 'shipping_postcode', 'shipping_country',
            'order_comments', 'ship_to_different_address',
            'payment_method', 'terms', 'createaccount'
        );

        foreach( $checkout_data as $key => $value ) {
            // Skip empty values and default fields
            if( empty( $value ) || in_array( $key, $default_fields ) ) {
                continue;
            }

            // Check if it matches custom field patterns
            $is_custom = false;
            foreach( $custom_patterns as $pattern ) {
                if( preg_match( $pattern, $key ) ) {
                    $is_custom = true;
                    break;
                }
            }

            if( $is_custom ) {
                $custom_fields[$key] = sanitize_text_field( $value );
            }
        }

        return $custom_fields;
    }

    /**
     * Determine checkout step based on available data
     */
    private function determine_checkout_step( $checkout_data ) {
        if( isset( $checkout_data['payment_method'] ) ) {
            return 'payment_selected';
        } elseif( isset( $checkout_data['billing_email'] ) && isset( $checkout_data['billing_first_name'] ) ) {
            return 'billing_completed';
        } elseif( isset( $checkout_data['billing_email'] ) ) {
            return 'email_entered';
        } else {
            return 'initial';
        }
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array( 
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) && !empty( $_SERVER[ $key ] ) ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * AJAX handler to save checkout data
     */
    public function ajax_save_checkout_data() {
        check_ajax_referer( 'woolentor_checkout_data', 'nonce' );

        if( is_user_logged_in() ) {
            wp_send_json_error( __( 'Not applicable for logged in users.', 'woolentor' ) );
        }

        $checkout_data = isset( $_POST['checkout_data'] ) ? $_POST['checkout_data'] : array();
        
        if( empty( $checkout_data ) ) {
            wp_send_json_error( __( 'No checkout data provided.', 'woolentor' ) );
        }

        // Process the data
        $this->process_and_save_checkout_data( $checkout_data );

        wp_send_json_success( __( 'Checkout data saved successfully.', 'woolentor' ) );
    }

    /**
     * Enqueue scripts for checkout page
     */
    public function enqueue_checkout_scripts() {
        if( !is_checkout() || is_user_logged_in() ) {
            return;
        }

        // Enqueue inline script to capture checkout changes
        wp_enqueue_script( 'woolentor-abandoned-cart', \Woolentor\Modules\AbandonedCart\MODULE_ASSETS . '/js/frontend.js', array( 'jquery' ), WOOLENTOR_VERSION, true );
        wp_localize_script( 'woolentor-abandoned-cart', 'woolentorAbancart', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'woolentor_checkout_data' )
        ) );
    }

    // ===========================================
    // PUBLIC UTILITY METHODS
    // ===========================================

    /**
     * Get customer info from cart
     */
    public function get_customer_info_from_cart( $cart_id ) {
        $cart = $this->db->get_cart( $cart_id );
        if( !$cart || empty( $cart->customer_other_info ) ) {
            return array();
        }

        return maybe_unserialize( $cart->customer_other_info );
    }

    /**
     * Get formatted customer name from cart data
     */
    public function get_customer_name_from_cart( $cart_id ) {
        $customer_info = $this->get_customer_info_from_cart( $cart_id );
        
        if( empty( $customer_info ) ) {
            return __( 'Customer', 'woolentor' );
        }

        $first_name = $customer_info['billing_first_name'] ?? '';
        $last_name = $customer_info['billing_last_name'] ?? '';

        if( !empty( $first_name ) || !empty( $last_name ) ) {
            return trim( $first_name . ' ' . $last_name );
        }

        if( !empty( $customer_info['billing_email'] ) ) {
            $email_parts = explode( '@', $customer_info['billing_email'] );
            return ucfirst( $email_parts[0] );
        }

        return __( 'Customer', 'woolentor' );
    }

    /**
     * Get customer address from cart data
     */
    public function get_customer_address_from_cart( $cart_id, $type = 'billing' ) {
        $customer_info = $this->get_customer_info_from_cart( $cart_id );
        
        if( empty( $customer_info ) ) {
            return '';
        }

        $address_parts = array();
        
        if( !empty( $customer_info[$type . '_address_1'] ) ) {
            $address_parts[] = $customer_info[$type . '_address_1'];
        }
        
        if( !empty( $customer_info[$type . '_address_2'] ) ) {
            $address_parts[] = $customer_info[$type . '_address_2'];
        }
        
        if( !empty( $customer_info[$type . '_city'] ) ) {
            $address_parts[] = $customer_info[$type . '_city'];
        }
        
        if( !empty( $customer_info[$type . '_state'] ) ) {
            $address_parts[] = $customer_info[$type . '_state'];
        }
        
        if( !empty( $customer_info[$type . '_postcode'] ) ) {
            $address_parts[] = $customer_info[$type . '_postcode'];
        }

        return implode( ', ', $address_parts );
    }

    /**
     * Update customer info for existing cart
     */
    public function update_customer_info( $cart_id, $customer_info ) {
        $existing_info = $this->get_customer_info_from_cart( $cart_id );
        $merged_info = array_merge( $existing_info, $customer_info );
        $merged_info['updated_at'] = current_time( 'mysql' );

        return $this->db->update_cart( $cart_id, array(
            'customer_other_info' => maybe_serialize( $merged_info )
        ));
    }

    /**
     * Get checkout completion percentage
     */
    public function get_checkout_completion_percentage( $cart_id ) {
        $customer_info = $this->get_customer_info_from_cart( $cart_id );
        
        if( empty( $customer_info ) ) {
            return 0;
        }

        $required_fields = array(
            'billing_email',
            'billing_first_name', 
            'billing_last_name',
            'billing_address_1',
            'billing_city',
            'billing_country'
        );

        $completed_fields = 0;
        foreach( $required_fields as $field ) {
            if( !empty( $customer_info[$field] ) ) {
                $completed_fields++;
            }
        }

        return round( ( $completed_fields / count( $required_fields ) ) * 100 );
    }

    /**
     * Clear customer info for cart (for testing)
     */
    public function clear_customer_info( $cart_id ) {
        return $this->db->update_cart( $cart_id, array(
            'customer_other_info' => null
        ));
    }
}