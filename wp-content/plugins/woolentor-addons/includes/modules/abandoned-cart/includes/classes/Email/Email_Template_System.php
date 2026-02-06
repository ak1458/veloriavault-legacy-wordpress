<?php
namespace Woolentor\Modules\AbandonedCart\Email;
use WooLentor\Traits\Singleton;
use Woolentor\Modules\AbandonedCart\Database\DB_Handler;
use Woolentor\Modules\AbandonedCart\Config\Config;

class Email_Template_System {
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
     * @var Coupon_Manager
     */
    private $coupon_manager;

    /**
     * @var Placeholder_Manager
     */
    private $placeholder_manager;

    /**
     * Constructor
     */
    private function __construct() {
        $this->db = DB_Handler::instance();
        $this->tables = Config::get_db_tables();
        $this->coupon_manager = Coupon_Manager::instance();
        $this->placeholder_manager = Placeholder_Manager::instance();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Handle cart abandoned event (NO individual scheduling)
        add_action( 'woolentor_cart_abandoned', array( $this, 'queue_follow_up_emails' ) );
        
        // SINGLE processor for all scheduled emails
        add_action( 'woolentor_process_scheduled_emails', array( $this, 'process_scheduled_emails' ) );
        
        // Clean up when cart is recovered
        add_action( 'woolentor_cart_recovered', array( $this, 'cancel_follow_up_emails' ) );
    }


    /**
     * Queue follow-up emails (NO individual cron events) - REPLACE existing method
     */
    public function queue_follow_up_emails( $cart ) {

        $rule_list = Config::get_rule_list();
        
        if( empty( $rule_list ) ) {
            return;
        }

        foreach( $rule_list as $rule ) {
            $templateInfo = $this->get_email_template( $rule['email_template'] );

            if( $templateInfo->status !== 'active' ) {
                continue;
            }

            // Calculate when to send this email
            $send_time = $this->calculate_send_time( 
                $cart->abandoned_at, 
                $rule['send_after_time'], 
                $rule['send_trigger_unit'] 
            );
            
            // Queue in database ONLY (no individual cron)
            $this->queue_single_email( $cart->id, $rule['email_template'], $send_time );
        }
        
        // Clear cache since new emails are queued
        $this->db->clear_email_count_cache();
    }

    /**
     * Queue single email in database - NEW method
     */
    private function queue_single_email( $cart_id, $template_id, $send_time ) {
        // Check if already queued
        if( $this->db->is_email_scheduled( $cart_id, $template_id ) ) {
            return;
        }

        $template = $this->get_email_template( $template_id );
        if( !$template ) {
            return;
        }

        // Add to database queue ONLY
        $this->db->schedule_email(
            $cart_id,
            $template_id,
            $template->subject,
            date( 'Y-m-d H:i:s', $send_time )
        );
    }

    /**
     * Send template email
     */
    private function send_template_email( $cart, $template ) {
        if( empty( $cart->user_email ) ) {
            return false;
        }

        // Prepare email data
        $email_data = $this->prepare_template_email_data( $cart, $template );

        // Get email content using template
        $content = $this->get_template_email_content( $template, $email_data );

        // Send email with proper headers
        $from_email_options = Config::get_from_email_options();
        $headers = array('Content-Type: text/html; charset=UTF-8');

        if ( $from_email_options['from_reply_to_email_address'] ) {
            $headers [] = "Reply-To: " . $from_email_options['from_reply_to_email_address'];
        }
        if( $from_email_options['from_name'] && $from_email_options['from_email_address'] ) {
            $headers [] = "From: " . $from_email_options['from_name'] . " <" . $from_email_options['from_email_address'] . ">";
        }
        
        $sent = wp_mail( 
            $cart->user_email, 
            $email_data['subject'], 
            $content, 
            $headers 
        );

        return $sent;
    }

    /**
     * Prepare email data for template - ENHANCED VERSION
     */
    private function prepare_template_email_data( $cart, $template ) {
        $cart_manager = \Woolentor\Modules\AbandonedCart\Frontend\Cart_Manager::instance();
        $cart_info = $cart_manager->get_cart_contents_info( $cart );
        $recovery_url = $cart_manager->get_recovery_url( $cart->id );

        // Process coupon data
        $coupon_data = $this->process_coupon_data( $template, $cart );

        // Get user data if available
        $user = null;
        if( !empty( $cart->user_id ) ) {
            $user = get_user_by( 'id', $cart->user_id );
        }

        // Set context for placeholder manager
        $this->placeholder_manager->set_context( $cart, $template, $coupon_data, $user );

        // Process template content with placeholders - PRESERVE HTML FORMATTING
        $processed_subject = $this->placeholder_manager->replace_placeholders( $template->subject );
        
        // FIXED: Don't strip HTML from content, just process placeholders
        $processed_content = $this->placeholder_manager->replace_placeholders( $template->body );
        
        $processed_heading = $this->placeholder_manager->replace_placeholders( 
            $template->heading ?? $template->name 
        );

        // Prepare email data
        $email_data = array(
            'template_id' => $template->id,
            'template_name' => $template->name,
            'subject' => $processed_subject,
            'content' => $processed_content, // Keep original HTML formatting
            'heading' => $processed_heading,
            'recovery_url' => $recovery_url,
            'cart_items' => $cart_info['items'],
            'cart_total' => wc_price( $cart_info['total'] ),
            'site_name' => get_bloginfo( 'name' ),
            'site_url' => get_bloginfo( 'url' ),
            'coupon_data' => $coupon_data,
        );

        // Add backward compatibility - extract customer name for old templates
        $customer_info = $this->get_customer_info_from_context( $cart, $user );
        $email_data['customer_name'] = $customer_info['customer_full_name'];

        // Clear placeholder context after processing
        $this->placeholder_manager->clear_context();

        return $email_data;
    }

    /**
     * Get customer info for backward compatibility
     */
    private function get_customer_info_from_context( $cart, $user ) {
        $customer_full_name = __( 'Customer', 'woolentor' );

        if( $user ) {
            $first_name = get_user_meta( $user->ID, 'first_name', true ) ?: $user->first_name ?: '';
            $last_name = get_user_meta( $user->ID, 'last_name', true ) ?: $user->last_name ?: '';
            
            if( !empty( $first_name ) || !empty( $last_name ) ) {
                $customer_full_name = trim( $first_name . ' ' . $last_name );
            } elseif( !empty( $user->display_name ) ) {
                $customer_full_name = $user->display_name;
            }
        }

        return array(
            'customer_full_name' => $customer_full_name
        );
    }

    /**
     * Get template email content - COMPLETELY REWRITTEN
     */
    private function get_template_email_content( $template, $data ) {
        // Start output buffering
        ob_start();

        // Load email header
        wc_get_template( 'email-header.php', array(), '', \Woolentor\Modules\AbandonedCart\MODULE_INCLUDES_PATH . '/templates/Email/' );

        // Process and output the content with proper formatting
        echo $this->format_email_content( $data['content'] );

        // Load email footer
        wc_get_template( 'email-footer.php', array(), '', \Woolentor\Modules\AbandonedCart\MODULE_INCLUDES_PATH . '/templates/Email/' );

        $content = ob_get_clean();

        return apply_filters( 'woolentor_followup_email_content', $content, $template, $data );
    }

    /**
     * Format email content for proper display - NEW METHOD
     */
    private function format_email_content( $content ) {
        if( empty( $content ) ) {
            return '';
        }

        // If content doesn't have HTML tags, convert line breaks to HTML
        if( strip_tags( $content ) === $content ) {
            // Plain text content - convert to HTML
            $content = wpautop( $content ); // Convert line breaks to <p> tags
        } else {
            // HTML content - ensure proper formatting
            $content = wpautop( $content, false ); // Don't add extra <br> tags
        }

        // Wrap content in a container with proper email styling
        $formatted_content = '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">';
        $formatted_content .= $content;
        $formatted_content .= '</div>';

        return $formatted_content;
    }

    /**
     * Process coupon data for template
     */
    private function process_coupon_data( $template, $cart ) {
        if( empty( $template->coupon_data ) ) {
            return array();
        }

        $coupon_data = maybe_unserialize( $template->coupon_data );
        
        if( !is_array( $coupon_data ) || !isset( $coupon_data['enable_coupon'] ) || !$coupon_data['enable_coupon'] ) {
            return array();
        }

        // Validate and normalize coupon data
        $coupon_data = $this->coupon_manager->validate_coupon_data( $coupon_data );
        $coupon_data['template_id'] = $template->id;

        // Generate coupon if auto_generate is enabled
        if( isset( $coupon_data['auto_generate'] ) && $coupon_data['auto_generate'] ) {
            $coupon_code = $this->coupon_manager->generate_coupon_code( $coupon_data, $cart );
            $coupon_data['coupon_code'] = $coupon_code;
            
            // Create WooCommerce coupon
            $coupon_id = $this->coupon_manager->create_coupon( $coupon_code, $coupon_data, $cart );
            
            if( $coupon_id ) {
                $coupon_data['coupon_id'] = $coupon_id;
            }
        } elseif( isset( $coupon_data['coupon_code'] ) && !empty( $coupon_data['coupon_code'] ) ) {
            // Use predefined coupon code - check if it exists
            if( !$this->coupon_manager->coupon_exists( $coupon_data['coupon_code'] ) ) {
                // Create the predefined coupon if it doesn't exist
                $coupon_id = $this->coupon_manager->create_coupon( $coupon_data['coupon_code'], $coupon_data, $cart );
                if( $coupon_id ) {
                    $coupon_data['coupon_id'] = $coupon_id;
                }
            }
        }

        return $coupon_data;
    }


    /**
     * SINGLE PROCESSOR for all scheduled emails - REPLACE existing method
     */
    public function process_scheduled_emails() {
        $start_time = microtime( true );
        $max_execution_time = 25; // Max 25 seconds
        
        $batch_size = $this->get_optimal_batch_size();
        $emails_to_send = $this->db->get_pending_scheduled_emails( $batch_size );
        
        if( empty( $emails_to_send ) ) {
            // Clean up old emails while we're here
            $this->db->cleanup_old_scheduled_emails( 7 );
            return;
        }

        $processed_count = 0;
        $sent_count = 0;
        $failed_count = 0;
        $cancelled_count = 0;

        foreach( $emails_to_send as $scheduled_email ) {
            $processed_count++;
            
            // Double-check cart status (in case it changed)
            if( $scheduled_email->cart_status !== 'abandoned' || $scheduled_email->unsubscribed ) {
                $this->db->update_scheduled_email_status( 
                    $scheduled_email->cart_id, 
                    $scheduled_email->template_id, 
                    'cancelled' 
                );
                $cancelled_count++;
                continue;
            }

            // Send the email
            $sent = $this->send_scheduled_email( $scheduled_email );


            if( $sent ) {
                $sent_count++;
                $this->db->update_scheduled_email_status( 
                    $scheduled_email->cart_id, 
                    $scheduled_email->template_id, 
                    'sent' 
                );
            } else {
                $failed_count++;
                $this->db->update_scheduled_email_status( 
                    $scheduled_email->cart_id, 
                    $scheduled_email->template_id, 
                    'failed' 
                );
            }

            // Throttling to prevent overwhelming mail server
            if( $processed_count % 5 === 0 ) {
                usleep( 500000 ); // 0.5 second delay every 5 emails
            }

            // Check execution time limit
            if( ( microtime( true ) - $start_time ) > $max_execution_time ) {
                break;
            }
        }
        
        // Clear cache after processing
        $this->db->clear_email_count_cache();
        
        // Clean up old scheduled emails
        $this->db->cleanup_old_scheduled_emails( 7 );
        
        // Update performance metrics
        $execution_time = microtime( true ) - $start_time;
        $this->update_performance_metrics( $processed_count, $execution_time, $sent_count );
    }

    /**
     * Send email using scheduled data - NEW method
     */
    private function send_scheduled_email( $scheduled_email ) {

        // Get template
        $template = $this->get_email_template( $scheduled_email->template_id );
        if( !$template || empty( $scheduled_email->user_email ) ) {
            return false;
        }

        // Create cart object from scheduled data
        $cart = (object) array(
            'id' => $scheduled_email->cart_id,
            'user_id' => $scheduled_email->user_id,
            'user_email' => $scheduled_email->user_email,
            'cart_contents' => $scheduled_email->cart_contents,
            'customer_other_info' => $scheduled_email->customer_other_info,
            'cart_total' => $scheduled_email->cart_total,
            'cart_currency' => $scheduled_email->cart_currency,
            'abandoned_at' => $scheduled_email->abandoned_at,
            'status' => $scheduled_email->cart_status,
            'unsubscribed' => $scheduled_email->unsubscribed
        );

        // Check if already sent (double protection)
        if( $this->email_already_sent( $cart->id, $template->id ) ) {
            return false;
        }

        return $this->send_template_email( $cart, $template );
    }


    /**
     * Get optimal batch size based on performance - NEW method
     */
    private function get_optimal_batch_size() {
        $performance_data = get_option( 'woolentor_email_performance', array() );
        $pending_count = $this->db->get_pending_email_count();
        
        // Default batch size
        $batch_size = 25;
        
        // Adjust based on volume
        if( $pending_count > 100 ) {
            $batch_size = 50; // High volume
        } elseif( $pending_count > 50 ) {
            $batch_size = 35; // Medium-high volume
        } elseif( $pending_count < 10 ) {
            $batch_size = 10; // Low volume
        }
        
        // Adjust based on recent performance
        if( !empty( $performance_data['success_rate'] ) ) {
            if( $performance_data['success_rate'] < 80 ) {
                $batch_size = max( 10, $batch_size - 10 ); // Reduce if poor performance
            } elseif( $performance_data['success_rate'] > 95 && $performance_data['avg_execution_time'] < 15 ) {
                $batch_size = min( 50, $batch_size + 5 ); // Increase if good performance
            }
        }
        
        return $batch_size;
    }


    /**
     * Update performance metrics
     */
    private function update_performance_metrics( $processed_count, $execution_time, $success_count ) {
        $success_rate = $processed_count > 0 ? ( $success_count / $processed_count ) * 100 : 100;
        
        $current_data = get_option( 'woolentor_email_performance', array() );
        
        $new_data = array(
            'last_run' => time(),
            'last_processed' => $processed_count,
            'last_execution_time' => $execution_time,
            'success_rate' => $success_rate,
            'avg_execution_time' => isset( $current_data['avg_execution_time'] ) 
                ? ( ( $current_data['avg_execution_time'] + $execution_time ) / 2 ) 
                : $execution_time,
            'total_runs' => isset( $current_data['total_runs'] ) ? $current_data['total_runs'] + 1 : 1
        );
        
        update_option( 'woolentor_email_performance', $new_data );
    }

    
    /**
     * Cancel follow-up emails when cart is recovered - REPLACE existing method
     */
    public function cancel_follow_up_emails( $cart ) {
        // NO individual cron cleanup needed - just update database
        $this->db->cancel_scheduled_emails( $cart->id );
        
        // Clear cache
        $this->db->clear_email_count_cache();

        // Send recovery report to admin
        $this->send_recovery_report_to_admin( $cart );
    }

    /**
     * Send recovery report to admin
     */
    public function send_recovery_report_to_admin( $cart ) {
        $admin_email = Config::get_recovery_report_notify_to_admin_options();

        if( empty( $admin_email['email'] ) ) {
            return;
        }

        if( $admin_email['enable'] === 'on' ) {
            $subject = __( 'Recovery Report', 'woolentor' );
            $headers = array('Content-Type: text/html; charset=UTF-8');

            $from_email_options = Config::get_from_email_options();
            if( $from_email_options['from_name'] && $from_email_options['from_email_address'] ) {
                $headers [] = "From: " . $from_email_options['from_name'] . " <" . $from_email_options['from_email_address'] . ">";
            }

            $content = $this->get_email_content_for_admin( $cart );

            wp_mail( $admin_email['email'], $subject, $content, $headers );
        }
    }

    /**
     * Get email content for admin
     */
    private function get_email_content_for_admin( $cart ) {
        $cart_manager = \Woolentor\Modules\AbandonedCart\Frontend\Cart_Manager::instance();
        $cart_info = $cart_manager->get_cart_contents_info( $cart );


        $content = __( 'Recovery report: ', 'woolentor' );
        $content .= '<br>';
        $content .= __( 'Cart ID: ', 'woolentor' ) . $cart->id;
        $content .= '<br>';
        $content .= __( 'Cart Total: ', 'woolentor' ) . $cart_info['total'];
        $content .= '<br>';
        $content .= __( 'Cart Items: ', 'woolentor' );
        $content .= '<br>';
        $content .= $this->placeholder_manager->generate_cart_items_html( $cart_info['items'] );
        $content .= '<br>';
        $content .= __( 'Cart Currency: ', 'woolentor' ) . $cart_info['currency'];
        $content .= '<br>';
        $content .= __( 'Cart Abandoned At: ', 'woolentor' ) . $cart->abandoned_at;

        ob_start();

        wc_get_template( 'email-header.php', array(), '', \Woolentor\Modules\AbandonedCart\MODULE_INCLUDES_PATH . '/templates/Email/' );
        echo $this->format_email_content( $content );

        wc_get_template( 'email-footer.php', array(), '', \Woolentor\Modules\AbandonedCart\MODULE_INCLUDES_PATH . '/templates/Email/' );

        return ob_get_clean();
    }

    /**
     * Get email template by ID
     */
    public function get_email_template( $template_id ) {
        $template = $this->db->get_email_template_by_id( $template_id );
        return $template;
    }

    /**
     * Calculate send time based on trigger settings
     */
    private function calculate_send_time( $abandoned_at, $trigger_after, $trigger_unit ) {
        $abandoned_timestamp = strtotime( $abandoned_at );
        
        switch( $trigger_unit ) {
            case 'minutes':
                return $abandoned_timestamp + ( $trigger_after * MINUTE_IN_SECONDS );
            case 'hours':
                return $abandoned_timestamp + ( $trigger_after * HOUR_IN_SECONDS );
            case 'days':
                return $abandoned_timestamp + ( $trigger_after * DAY_IN_SECONDS );
            default:
                return $abandoned_timestamp + ( $trigger_after * MINUTE_IN_SECONDS );
        }
    }

    /**
     * Check if email was already sent
     */
    private function email_already_sent( $cart_id, $template_id ) {
        $count = $this->db->email_already_sent( $cart_id, $template_id );
        return $count > 0;
    }
}