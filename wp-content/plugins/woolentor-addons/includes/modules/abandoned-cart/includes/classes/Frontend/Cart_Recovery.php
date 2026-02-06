<?php
namespace Woolentor\Modules\AbandonedCart\Frontend;
use WooLentor\Traits\Singleton;
use Woolentor\Modules\AbandonedCart\Database\DB_Handler;

class Cart_Recovery {
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
     * @var array Recovery data storage
     */
    private $recovery_data = null;

    /**
     * Constructor
     */
    private function __construct() {
        $this->db = DB_Handler::instance();
        $this->cart_manager = Cart_Manager::instance();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Handle cart recovery at the proper time (after WooCommerce is loaded)
        add_action( 'wp_loaded', array( $this, 'handle_cart_recovery' ), 20 );
        
        // Handle user login
        add_action( 'wp_login', array( $this, 'handle_user_login' ), 10, 2 );
        
        // Handle cart restored
        add_action( 'woocommerce_cart_restored', array( $this, 'handle_cart_restored' ) );
        
        // Store recovery data early (on init)
        add_action( 'init', array( $this, 'store_recovery_data' ), 1 );
    }

    /**
     * Store recovery data early in the process
     */
    public function store_recovery_data() {
        if( !isset( $_GET['wlrekey'] ) ) {
            return;
        }

        $key = sanitize_text_field( $_GET['wlrekey'] );

        // Store for later processing
        $this->recovery_data = array(
            'key' => $key
        );
    }

    /**
     * Handle cart recovery (runs after wp_loaded)
     */
    public function handle_cart_recovery() {
        // Check if we have recovery data stored
        if( !$this->recovery_data ) {
            return;
        }

        $key = $this->recovery_data['key'];

        // Verify recovery key
        if( !$this->cart_manager->validate_recovery_key( $key ) ) {
            wc_add_notice( __( 'Invalid recovery link.', 'woolentor' ), 'error' );
            $this->redirect_to_cart();
            return;
        }

        // Get cart
        $cart = $this->db->get_cart_by_recovery_key( $key );
        if( !$cart || $cart->status !== 'abandoned' ) {
            wc_add_notice( __( 'Cart not found or already recovered.', 'woolentor' ), 'error' );
            $this->redirect_to_cart();
            return;
        }

        // Check if WooCommerce cart is available
        if( !WC()->cart ) {
            // WooCommerce not ready, try again later
            add_action( 'woocommerce_init', function() use ( $cart ) {
                $this->process_cart_recovery( $cart );
            });
            return;
        }

        // Process the recovery
        $this->process_cart_recovery( $cart );
    }

    /**
     * Process cart recovery
     */
    private function process_cart_recovery( $cart ) {
        // Restore cart contents
        if( $this->restore_cart_contents( $cart ) ) {
            // Mark cart as recovered
            $this->cart_manager->mark_cart_recovered( $cart->id );

            wc_add_notice( __( 'Your cart has been restored successfully.', 'woolentor' ), 'success' );
        } else {
            wc_add_notice( __( 'Unable to restore your cart. Some items may no longer be available.', 'woolentor' ), 'error' );
        }

        // Redirect to cart page
        $this->redirect_to_cart();
    }

    /**
     * Safe redirect to cart
     */
    private function redirect_to_cart() {
        if( !headers_sent() ) {
            wp_safe_redirect( wc_get_cart_url() );
            exit;
        }
    }

    /**
     * Handle user login
     */
    public function handle_user_login( $user_login, $user ) {
        // Delay processing until WooCommerce is ready
        add_action( 'wp_loaded', function() use ( $user ) {
            $this->process_user_login_recovery( $user );
        }, 25 );
    }

    /**
     * Process user login recovery
     */
    private function process_user_login_recovery( $user ) {
        if( !WC()->cart ) {
            return;
        }

        $cart = $this->db->get_cart_by_user( $user->ID, $user->user_email );
        if( !$cart || $cart->status !== 'abandoned' ) {
            return;
        }

        // Only restore if current cart is empty
        if( WC()->cart->is_empty() ) {
            if( $this->restore_cart_contents( $cart ) ) {
                $this->cart_manager->mark_cart_recovered( $cart->id );
                wc_add_notice( __( 'Your previous cart has been restored.', 'woolentor' ), 'success' );
            }
        }
    }

    /**
     * Handle cart restored
     */
    public function handle_cart_restored() {
        if( !is_user_logged_in() ) {
            return;
        }

        $user_id = get_current_user_id();
        $cart = $this->db->get_cart_by_user( $user_id );

        if( $cart && $cart->status === 'abandoned' ) {
            $this->cart_manager->mark_cart_recovered( $cart->id );
        }
    }

    /**
     * Restore cart contents (with better error handling)
     */
    private function restore_cart_contents( $cart ) {
        $cart_contents = maybe_unserialize( $cart->cart_contents );
        if( empty( $cart_contents ) || !is_array( $cart_contents ) ) {
            return false;
        }

        // Ensure WooCommerce cart is available
        if( !WC()->cart ) {
            return false;
        }

        // Clear current cart
        WC()->cart->empty_cart();

        $restored_items = 0;
        $failed_items = 0;

        // Add items back to cart
        foreach( $cart_contents as $cart_item_key => $cart_item ) {
            $product_id = isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;
            $variation_id = isset( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0;
            $quantity = isset( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 1;
            $variation = isset( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ? $cart_item['variation'] : array();
            $cart_item_data = isset( $cart_item['cart_item_data'] ) && is_array( $cart_item['cart_item_data'] ) ? $cart_item['cart_item_data'] : array();

            // Validate product exists and is purchasable
            $product = wc_get_product( $variation_id ? $variation_id : $product_id );
            if( !$product || !$product->is_purchasable() ) {
                $failed_items++;
                continue;
            }

            // Check stock status
            if( !$product->is_in_stock() || !$product->has_enough_stock( $quantity ) ) {
                $failed_items++;
                continue;
            }

            try {
                $added_key = WC()->cart->add_to_cart( 
                    $product_id, 
                    $quantity, 
                    $variation_id, 
                    $variation, 
                    $cart_item_data 
                );

                if( $added_key ) {
                    $restored_items++;
                } else {
                    $failed_items++;
                }

            } catch( \Exception $e ) {
                error_log( 'WooLentor Cart Recovery Error: ' . $e->getMessage() );
                $failed_items++;
            }
        }

        // Add notice about failed items
        if( $failed_items > 0 ) {
            wc_add_notice( 
                sprintf(
                    /* translators: %d: number of items */
                    _n( 
                        '%d item could not be restored (no longer available or out of stock).', 
                        '%d items could not be restored (no longer available or out of stock).', 
                        $failed_items, 
                        'woolentor' 
                    ),
                    $failed_items
                ), 
                'error' 
            );
        }

        return $restored_items > 0;
    }

    /**
     * Get recovery URL
     */
    public function get_recovery_url( $cart_id ) {
        $key = $this->cart_manager->generate_recovery_key( $cart_id );
        return add_query_arg( array(
            'woolentor_recover_cart' => $cart_id,
            'key' => $key
        ), wc_get_cart_url() );
    }

    /**
     * Validate cart recovery request
     */
    public function validate_recovery_request( $cart_id, $key ) {
        // Basic validation
        if( !$cart_id || !$key ) {
            return false;
        }

        // Check if cart exists and key is valid
        return $this->cart_manager->validate_recovery_key( $cart_id, $key );
    }

    /**
     * Check if product can be restored
     */
    private function can_restore_product( $product_id, $variation_id = 0, $quantity = 1 ) {
        $product = wc_get_product( $variation_id ? $variation_id : $product_id );
        
        if( !$product ) {
            return false;
        }

        if( !$product->is_purchasable() ) {
            return false;
        }

        if( !$product->is_in_stock() ) {
            return false;
        }

        if( !$product->has_enough_stock( $quantity ) ) {
            return false;
        }

        return true;
    }

    /**
     * Get cart recovery stats
     */
    public function get_recovery_stats( $days = 30 ) {
        return $this->db->get_recovery_stats( $days );
    }
}