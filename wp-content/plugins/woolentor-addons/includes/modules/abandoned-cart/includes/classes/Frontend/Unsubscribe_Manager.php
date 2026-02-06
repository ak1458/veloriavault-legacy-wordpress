<?php
namespace Woolentor\Modules\AbandonedCart\Frontend;
use WooLentor\Traits\Singleton;
use Woolentor\Modules\AbandonedCart\Database\DB_Handler;
use Woolentor\Modules\AbandonedCart\Config\Config;

/**
 * Unsubscribe Manager Class
 * 
 * Handles all unsubscribe functionality for abandoned cart emails
 */
class Unsubscribe_Manager {
    use Singleton;

    /**
     * @var DB_Handler
     */
    private $db;

    /**
     * @var array
     */
    private $tables;

    /**
     * Constructor
     */
    private function __construct() {
        $this->db = DB_Handler::instance();
        $this->tables = Config::get_db_tables();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Handle unsubscribe request early
        add_action( 'init', array( $this, 'handle_unsubscribe_request' ), 5 );
        
        // Process unsubscribe on wp_loaded (backup)
        add_action( 'wp_loaded', array( $this, 'process_unsubscribe_backup' ), 10 );
        
        // Display unsubscribe messages
        add_action( 'wp_footer', array( $this, 'display_unsubscribe_message' ), 999 );
        
        // Add body class for unsubscribe pages
        add_filter( 'body_class', array( $this, 'add_unsubscribe_body_class' ) );
    }

    /**
     * Handle unsubscribe request - Main entry point
     */
    public function handle_unsubscribe_request() {
        // Check if this is an unsubscribe request
        if( !isset( $_GET['woolentor_unsubscribe'] ) || !isset( $_GET['key'] ) ) {
            return;
        }

        $cart_id = absint( $_GET['woolentor_unsubscribe'] );
        $key = sanitize_text_field( $_GET['key'] );

        // Process immediately
        $this->process_unsubscribe_request( $cart_id, $key );
    }

    /**
     * Backup process for unsubscribe (in case init doesn't fire properly)
     */
    public function process_unsubscribe_backup() {
        if( !isset( $_GET['woolentor_unsubscribe'] ) || !isset( $_GET['key'] ) ) {
            return;
        }

        // Only run if we haven't processed yet
        if( !get_transient( 'woolentor_unsubscribe_processed' ) ) {
            $cart_id = absint( $_GET['woolentor_unsubscribe'] );
            $key = sanitize_text_field( $_GET['key'] );
            $this->process_unsubscribe_request( $cart_id, $key );
        }
    }

    /**
     * Process unsubscribe request - Main processing function
     */
    private function process_unsubscribe_request( $cart_id, $key ) {
        // Set processed flag to prevent duplicate processing
        set_transient( 'woolentor_unsubscribe_processed', true, 300 );

        try {
            // Validate cart ID
            if( !$cart_id || $cart_id <= 0 ) {
                throw new \Exception( __( 'Invalid unsubscribe request.', 'woolentor' ) );
            }

            // Verify nonce
            if( !wp_verify_nonce( $key, 'woolentor_unsubscribe_' . $cart_id ) ) {
                throw new \Exception( __( 'Invalid or expired unsubscribe link. Please use the latest email.', 'woolentor' ) );
            }

            // Get cart from database
            $cart = $this->db->get_cart( $cart_id );
            if( !$cart ) {
                throw new \Exception( __( 'Cart not found. It may have been already processed.', 'woolentor' ) );
            }

            // Check if already unsubscribed
            if( $cart->unsubscribed == 1 ) {
                $this->set_message( 
                    __( 'You have already unsubscribed from abandoned cart emails for this cart.', 'woolentor' ), 
                    'info' 
                );
                $this->redirect_with_message();
                return;
            }

            // Update cart to unsubscribed status
            $updated = $this->db->update_cart( $cart_id, array(
                'unsubscribed' => 1
            ));

            if( !$updated ) {
                throw new \Exception( __( 'Failed to process unsubscribe request. Please try again.', 'woolentor' ) );
            }

            // Cancel any scheduled emails for this cart
            $cancelled_emails = $this->db->cancel_scheduled_emails( $cart_id );

            // // Log the unsubscribe event
            // $this->log_unsubscribe_event( $cart );

            // Set success message
            $message = __( 'You have been successfully unsubscribed from abandoned cart recovery emails.', 'woolentor' );
            if( $cancelled_emails > 0 ) {
                $message .= ' ' . sprintf( 
                    /* translators: %d: number of cancelled emails */
                    _n( 
                        '%d scheduled email has been cancelled.', 
                        '%d scheduled emails have been cancelled.', 
                        $cancelled_emails, 
                        'woolentor' 
                    ), 
                    $cancelled_emails 
                );
            }

            $this->set_message( $message, 'success' );

            // Fire action for other plugins/extensions
            do_action( 'woolentor_cart_unsubscribed', $cart );
            

        } catch( \Exception $e ) {
            $this->set_message( $e->getMessage(), 'error' );
        }

        // Always redirect to clean URL
        $this->redirect_with_message();
    }

    /**
     * Set message for display
     */
    private function set_message( $message, $type = 'info' ) {
        set_transient( 'woolentor_unsubscribe_message', array(
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ), 600 ); // Store for 10 minutes
    }

    /**
     * Redirect with message
     */
    private function redirect_with_message() {
        if( headers_sent() ) {
            return;
        }

        // Get redirect URL
        $redirect_url = $this->get_redirect_url();
        
        // Add unsubscribe flag
        $redirect_url = add_query_arg( array(
            'woolentor_unsubscribed' => '1',
            'timestamp' => time()
        ), $redirect_url );

        // Safe redirect
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Get redirect URL after unsubscribe
     */
    private function get_redirect_url() {
        // Check if there's a custom redirect URL in settings
        $redirect_url = woolentor_get_option( 
            'unsubscribe_redirect_url', 
            'woolentor_abandoned_cart_settings', 
            '' 
        );

        if( !empty( $redirect_url ) && filter_var( $redirect_url, FILTER_VALIDATE_URL ) ) {
            return $redirect_url;
        }

        // Default redirects in order of preference
        $possible_redirects = array(
            wc_get_page_permalink( 'shop' ),     // Shop page
            wc_get_cart_url(),                   // Cart page
            home_url()                           // Home page
        );

        foreach( $possible_redirects as $url ) {
            if( !empty( $url ) && $url !== home_url() . '/' ) {
                return $url;
            }
        }

        return home_url();
    }

    /**
     * Display unsubscribe message
     */
    public function display_unsubscribe_message() {
        // Check if we should display unsubscribe message
        if( !isset( $_GET['woolentor_unsubscribed'] ) ) {
            return;
        }

        $message_data = get_transient( 'woolentor_unsubscribe_message' );
        if( !$message_data ) {
            return;
        }

        $message = $message_data['message'];
        $type = $message_data['type'];

        // Delete the transient after reading
        delete_transient( 'woolentor_unsubscribe_message' );

        $this->render_message_html( $message, $type );
    }

    /**
     * Render message HTML
     */
    private function render_message_html( $message, $type ) {
        // Message type configurations
        $type_configs = array(
            'success' => array(
                'bg' => '#d4edda',
                'border' => '#c3e6cb',
                'text' => '#155724',
                'icon' => '✓'
            ),
            'error' => array(
                'bg' => '#f8d7da',
                'border' => '#f5c6cb',
                'text' => '#721c24',
                'icon' => '✗'
            ),
            'info' => array(
                'bg' => '#d1ecf1',
                'border' => '#bee5eb',
                'text' => '#0c5460',
                'icon' => 'ℹ'
            ),
            'warning' => array(
                'bg' => '#fff3cd',
                'border' => '#ffeaa7',
                'text' => '#856404',
                'icon' => '⚠'
            )
        );

        $config = $type_configs[$type] ?? $type_configs['info'];
        $unique_id = 'woolentor-msg-' . uniqid();

        ?>
        <div id="<?php echo esc_attr( $unique_id ); ?>" class="woolentor-unsubscribe-message" style="display: none;">
            <style>
                .woolentor-unsubscribe-message {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    z-index: 999999;
                    background: linear-gradient(135deg, <?php echo esc_attr( $config['bg'] ); ?> 0%, <?php echo esc_attr( $config['border'] ); ?> 100%);
                    color: <?php echo esc_attr( $config['text'] ); ?>;
                    border-bottom: 3px solid <?php echo esc_attr( $config['border'] ); ?>;
                    padding: 20px;
                    text-align: center;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    font-size: 16px;
                    line-height: 1.5;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                    animation: slideDown 0.5s ease-out;
                    backdrop-filter: blur(10px);
                }

                @keyframes slideDown {
                    from {
                        transform: translateY(-100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }

                @keyframes slideUp {
                    from {
                        transform: translateY(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateY(-100%);
                        opacity: 0;
                    }
                }

                .woolentor-unsubscribe-message .message-content {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    max-width: 800px;
                    margin: 0 auto;
                }

                .woolentor-unsubscribe-message .message-icon {
                    font-size: 20px;
                    font-weight: bold;
                    width: 25px;
                    line-height: 1;
                    padding: 0;
                    margin-top: -1px;
                    background-color: #fff;
                    border-radius: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 25px;
                }

                .woolentor-unsubscribe-message .message-text {
                    font-weight: 500;
                }

                .woolentor-unsubscribe-message .close-btn {
                    position: absolute;
                    right: 20px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: <?php echo esc_attr( $config['text'] ); ?>;
                    opacity: 0.7;
                    padding: 5px;
                    border-radius: 50%;
                    transition: all 0.3s ease;
                    width: 36px;
                    height: 36px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .woolentor-unsubscribe-message .close-btn:hover {
                    opacity: 1;
                    background: rgba(0,0,0,0.1);
                    transform: translateY(-50%) scale(1.1);
                }

                .woolentor-unsubscribe-message .progress-bar {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    height: 3px;
                    background: <?php echo esc_attr( $config['text'] ); ?>;
                    opacity: 0.3;
                    animation: progressBar 12s linear;
                }

                @keyframes progressBar {
                    from { width: 100%; }
                    to { width: 0%; }
                }

                body.woolentor-unsubscribe-active {
                    padding-top: 80px !important;
                    transition: padding-top 0.5s ease;
                }

                /* Mobile responsiveness */
                @media (max-width: 768px) {
                    .woolentor-unsubscribe-message {
                        padding: 15px 50px 15px 15px;
                        font-size: 14px;
                    }
                    
                    .woolentor-unsubscribe-message .message-content {
                        flex-direction: column;
                        gap: 5px;
                    }
                    
                    .woolentor-unsubscribe-message .close-btn {
                        right: 10px;
                        width: 32px;
                        height: 32px;
                        font-size: 20px;
                    }
                    
                    body.woolentor-unsubscribe-active {
                        padding-top: 70px !important;
                    }
                }
            </style>
            
            <div class="message-content">
                <span class="message-icon"><?php echo esc_html( $config['icon'] ); ?></span>
                <span class="message-text"><?php echo wp_kses_post( $message ); ?></span>
            </div>
            
            <button class="close-btn" onclick="woolentorCloseMessage('<?php echo esc_js( $unique_id ); ?>')" title="<?php esc_attr_e( 'Close', 'woolentor' ); ?>">&times;</button>
            
            <div class="progress-bar"></div>
        </div>

        <script>
            function woolentorCloseMessage(elementId) {
                var element = document.getElementById(elementId);
                if (element) {
                    element.style.animation = 'slideUp 0.5s ease-in';
                    document.body.classList.remove('woolentor-unsubscribe-active');
                    setTimeout(function() {
                        element.style.display = 'none';
                        document.body.style.paddingTop = '';
                    }, 500);
                }
            }

            // Show message when page loads
            document.addEventListener('DOMContentLoaded', function() {
                var messageElement = document.getElementById('<?php echo esc_js( $unique_id ); ?>');
                if (messageElement) {
                    messageElement.style.display = 'block';
                    document.body.classList.add('woolentor-unsubscribe-active');
                }
                
                // Auto-hide after 12 seconds
                setTimeout(function() {
                    woolentorCloseMessage('<?php echo esc_js( $unique_id ); ?>');
                }, 12000);
            });
        </script>
        <?php
    }

    /**
     * Add body class for unsubscribe pages
     */
    public function add_unsubscribe_body_class( $classes ) {
        if( isset( $_GET['woolentor_unsubscribed'] ) ) {
            $classes[] = 'woolentor-unsubscribe-page';
        }
        return $classes;
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array( 
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',           // Nginx proxy
            'HTTP_CLIENT_IP',           // Proxy
            'HTTP_X_FORWARDED_FOR',     // Load balancer/proxy
            'HTTP_X_FORWARDED',         // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP', // Cluster
            'HTTP_FORWARDED_FOR',       // Proxy
            'HTTP_FORWARDED',           // Proxy
            'REMOTE_ADDR'               // Standard
        );
        
        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) && !empty( $_SERVER[ $key ] ) ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
                    
                    // Validate IP and exclude private/reserved ranges
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Get unsubscribe URL for a cart
     */
    public function get_unsubscribe_url( $cart_id ) {
        return add_query_arg( array(
            'woolentor_unsubscribe' => $cart_id,
            'key' => wp_create_nonce( 'woolentor_unsubscribe_' . $cart_id )
        ), home_url() );
    }

}