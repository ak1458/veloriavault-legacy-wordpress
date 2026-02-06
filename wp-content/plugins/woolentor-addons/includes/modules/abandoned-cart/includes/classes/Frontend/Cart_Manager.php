<?php
namespace Woolentor\Modules\AbandonedCart\Frontend;
use WooLentor\Traits\Singleton;
use Woolentor\Modules\AbandonedCart\Database\DB_Handler;
use Woolentor\Modules\AbandonedCart\Config\Config;

class Cart_Manager {
    use Singleton;

    /**
     * @var DB_Handler
     */
    private $db;

    /**
     * Session key for storing our unique session ID
     */
    const SESSION_KEY = 'woolentor_cart_session_id';

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
        // Don't track during cart recovery process
        // if( $this->is_recovery_in_progress() ) {
        //     return;
        // }

        // Track cart changes
        add_action( 'woocommerce_add_to_cart', array( $this, 'track_cart_changes' ) );
        add_action( 'woocommerce_cart_item_removed', array( $this, 'track_cart_changes' ) );
        add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'track_cart_changes' ) );

        // Check abandoned carts
        add_action( 'woolentor_abandoned_cart_check', array( $this, 'check_abandoned_carts' ) );
        
        // Cleanup old carts
        add_action( 'woolentor_abandoned_cart_cleanup', array( $this, 'cleanup_old_carts' ) );

        // Mark as recovered when order is created
        add_action( 'woocommerce_new_order', array( $this, 'handle_order_completed' ), 10, 1 );
        add_action( 'woocommerce_thankyou', array( $this, 'handle_order_completed' ), 10, 1 );
    }

    /**
     * Check if recovery is in progress
     */
    private function is_recovery_in_progress() {
        return defined( 'WOOLENTOR_CART_RECOVERY_IN_PROGRESS' ) && WOOLENTOR_CART_RECOVERY_IN_PROGRESS;
    }

    /**
     * Track cart changes
     */
    public function track_cart_changes() {
        // Skip if WooCommerce cart is not available
        if( !WC()->cart || !WC()->session ) {
            return;
        }

        // Skip if cart is empty
        if( WC()->cart->is_empty() ) {
            $this->handle_empty_cart();
            return;
        }

        try {
            $cart_data = $this->prepare_cart_data();
            $session_id = $this->get_or_create_session_id();
            $existing_cart = $this->get_existing_cart( $session_id );

            if( $existing_cart ) {
                // Check if the cart was completed and we need a new session
                if( $existing_cart->status === 'completed' ) {
                    $session_id = $this->create_new_session_id();
                    $cart_data['session_id'] = $session_id;
                    $this->create_new_cart( $cart_data );
                } else {
                    $this->update_existing_cart( $existing_cart->id, $cart_data );
                }
            } else {
                $cart_data['session_id'] = $session_id;
                $this->create_new_cart( $cart_data );
            }
        } catch( \Exception $e ) {
            error_log( 'WooLentor Abandoned Cart Error: ' . $e->getMessage() );
        }
    }

    /**
     * Handle empty cart
     */
    private function handle_empty_cart() {
        $session_id = $this->get_session_id();
        if( $session_id ) {
            $existing_cart = $this->get_existing_cart( $session_id );
            if( $existing_cart && in_array( $existing_cart->status, array( 'pending', 'abandoned' ) ) ) {
                // Delete the cart record when cart is emptied
                $this->db->delete_cart( $existing_cart->id );
            }
        }
    }

    /**
     * Get or create unique session ID
     */
    private function get_or_create_session_id() {
        $session_id = $this->get_session_id();
        
        if( !$session_id ) {
            $session_id = $this->create_new_session_id();
        }

        return $session_id;
    }

    /**
     * Get existing session ID from WooCommerce session
     */
    private function get_session_id() {
        return \WC()->session->get( self::SESSION_KEY );
    }

    /**
     * Create new unique session ID
     */
    private function create_new_session_id() {
        $session_id = md5( uniqid( wp_rand(), true ) );
        WC()->session->set( self::SESSION_KEY, $session_id );
        return $session_id;
    }

    /**
     * Prepare cart data
     */
    private function prepare_cart_data() {
        $cart = WC()->cart;
        $cart_contents = $cart->get_cart();

        // Calculate cart total manually to ensure accuracy
        $cart_total = 0;
        foreach( $cart_contents as $cart_item ) {
            $product = wc_get_product( $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'] );
            if( $product ) {
                $product_price = $product->get_price();
                $cart_total += $product_price * $cart_item['quantity'];
            }
        }

        $data = array(
            'cart_contents' => maybe_serialize( $cart_contents ),
            'cart_total' => floatval( $cart_total ),
            'cart_currency' => get_woocommerce_currency(),
            'modified_at' => current_time( 'mysql' )
        );

        // Add user information
        if( is_user_logged_in() ) {
            $user = wp_get_current_user();
            $data['user_id'] = $user->ID;
            $data['user_email'] = $user->user_email;
        } else {
            // Try to get email from session or checkout form
            $guest_email = WC()->session->get( 'billing_email' );
            if( !empty( $guest_email ) ) {
                $data['user_email'] = sanitize_email( $guest_email );
            }
        }

        return $data;
    }

    /**
     * Get existing cart by session ID
     */
    private function get_existing_cart( $session_id ) {
        return $this->db->get_cart_by_session( $session_id );
    }

    /**
     * Update existing cart
     */
    private function update_existing_cart( $cart_id, $data ) {
        return $this->db->update_cart( $cart_id, $data );
    }

    /**
     * Create new cart
     */
    private function create_new_cart( $data ) {
        $data['created_at'] = current_time( 'mysql' );
        $data['status'] = 'pending';
        return $this->db->insert_cart( $data );
    }

    /**
     * Get cart by email (for returning customers)
     */
    private function get_cart_by_email( $email ) {
        return $this->db->get_cart_by_user( null, $email );
    }

    /**
     * Check abandoned carts
     */
    public function check_abandoned_carts() {
        $threshold_time = Config::get_abandoned_time();
        $pending_carts = $this->db->get_pending_carts( $threshold_time );

        if( !empty( $pending_carts ) ) {
            foreach( $pending_carts as $cart ) {
                $this->mark_cart_abandoned( $cart );
            }
        }
    }

    /**
     * Mark cart as abandoned
     */
    private function mark_cart_abandoned( $cart ) {
        $updated = $this->db->update_cart( $cart->id, array(
            'status' => 'abandoned',
            'abandoned_at' => current_time( 'mysql' )
        ));

        if( $updated ) {
            // Refresh cart object
            $cart = $this->db->get_cart( $cart->id );
            do_action( 'woolentor_cart_abandoned', $cart );
        }

        return $updated;
    }

    /**
     * Mark cart as recovered
     */
    public function mark_cart_recovered( $cart_id ) {
        $updated = $this->db->update_cart( $cart_id, array(
            'status' => 'recovered',
            'recovered_at' => current_time( 'mysql' )
        ));

        if( $updated ) {
            $cart = $this->db->get_cart( $cart_id );
            do_action( 'woolentor_cart_recovered', $cart );

            // Update session mapping after recovery
            // $this->update_session_mapping_after_recovery( $cart );
        }

        return $updated;
    }

    /**
     * Update session mapping after recovery to prevent duplicate tracking
     */
    private function update_session_mapping_after_recovery( $cart ) {
        if( !WC()->session ) {
            return;
        }

        $current_session_id = $this->get_session_id();
        
        // If session ID has changed, update the recovered cart record
        if( $cart->session_id !== $current_session_id ) {
            $this->db->update_cart( $cart->id, array(
                'session_id' => $current_session_id,
                'status' => 'recovered' // Ensure it stays recovered
            ));
        }
    }

    /**
     * Handle order completed
     */
    public function handle_order_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        if( !$order ) {
            return;
        }

        $order_status = $order->get_status();
        $statuses = Config::get_order_statuses();

        if( is_array( $statuses ) && !empty( $statuses ) && !in_array( $order_status, $statuses ) ) {
            return;
        }

        if( isset( WC()->session ) ){

            $user_id = $order->get_user_id();
            $user_email = $order->get_billing_email();

            // Try to find cart by session ID first
            $session_id = $this->get_session_id();
            $cart = null;

            if( $session_id ) {
                $cart = $this->get_existing_cart( $session_id );
            }

            // Fallback: find by user ID or email
            if( !$cart ) {
                $cart = $user_id ? $this->db->get_cart_by_user( $user_id ) : $this->db->get_cart_by_user( null, $user_email );
            }

            if( $cart && in_array( $cart->status, array( 'pending', 'abandoned', 'recovered' ) ) ) {
                $this->db->update_cart( $cart->id, array(
                    'status' => 'completed',
                    'recovered_at' => current_time( 'mysql' )
                ));

                // Clear the session to prevent tracking the same cart again
                WC()->session->__unset( self::SESSION_KEY );
                
            }
        }
    }

    /**
     * Cleanup old carts
     */
    public function cleanup_old_carts() {
        $days = Config::get_cleanup_days();
        return $this->db->cleanup( $days );
    }

    /**
     * Generate recovery key
     */
    public function generate_recovery_key( $cart_id ) {
        $key = wp_generate_password( 20, false );
        $this->db->update_cart( $cart_id, array(
            'recovery_key' => $key
        ));
        return $key;
    }

    /**
     * Validate recovery key
     */
    public function validate_recovery_key( $key ) {
        $cart = $this->db->get_cart_by_recovery_key( $key );
        if( $cart && $cart->recovery_key === $key ) {
            return true;
        }
        return false;
    }

    /**
     * Get cart contents info
     */
    public function get_cart_contents_info( $cart ) {
        $contents = maybe_unserialize( $cart->cart_contents );
        $items = array();

        if( !empty( $contents ) && is_array( $contents ) ) {
            foreach( $contents as $item ) {
                $product = wc_get_product( $item['product_id'] );
                if( !$product ) {
                    continue;
                }

                $items[] = array(
                    'name' => $product->get_name(),
                    'quantity' => $item['quantity'],
                    'price' => $product->get_price() * $item['quantity'],
                    'image' => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                    'url' => $product->get_permalink()
                );
            }
        }

        return array(
            'items' => $items,
            'total' => $cart->cart_total,
            'currency' => $cart->cart_currency
        );
    }

    /**
     * Get recovery URL for a cart
     */
    public function get_recovery_url( $cart_id ) {

        $cart = $this->db->get_cart( $cart_id );
        $key = '';
        if( $cart && $cart->recovery_key ) {
            $key = $cart->recovery_key;
        }else{
            $key = $this->generate_recovery_key( $cart_id );
        }

        return add_query_arg( array(
            'wlrekey' => $key
        ), wc_get_cart_url() );
    }

    /**
     * Get pending carts that should be marked as abandoned
     */
    public function get_pending_carts( $threshold_minutes ) {
        return $this->db->get_pending_carts( $threshold_minutes );
    }

    /**
     * Clear session data (useful for testing)
     */
    public function clear_session() {
        WC()->session->__unset( self::SESSION_KEY );
    }

    /**
     * Get current session ID (for debugging)
     */
    public function get_current_session_id() {
        return $this->get_session_id();
    }
}