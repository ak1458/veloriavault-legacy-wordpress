<?php
namespace Woolentor\Modules\AbandonedCart\Email;
use WooLentor\Traits\Singleton;

class Placeholder_Manager {
    use Singleton;

    /**
     * @var array Current cart data for placeholder generation
     */
    private $current_cart = null;

    /**
     * @var array Current template data for placeholder generation
     */
    private $current_template = null;

    /**
     * @var array Current coupon data for placeholder generation
     */
    private $current_coupon_data = null;

    /**
     * @var array Current user data for placeholder generation
     */
    private $current_user = null;

    /**
     * @var array Cached admin info to avoid repeated queries
     */
    private $admin_info_cache = null;

    /**
     * @var array Cached customer info to avoid repeated queries
     */
    private $customer_info_cache = null;

    /**
     * Get all supported placeholders with their descriptions
     */
    public function get_supported_placeholders() {
        $placeholders = [
            // Site information
            'site_name' => __( 'Site Name', 'woolentor' ),
            'site_url' => __( 'Site URL', 'woolentor' ),
            
            // Customer information
            'customer_first_name' => __( 'Customer First Name', 'woolentor' ),
            'customer_last_name' => __( 'Customer Last Name', 'woolentor' ),
            'customer_full_name' => __( 'Customer Full Name', 'woolentor' ),
            
            // Cart information
            'cart_total' => __( 'Cart Total', 'woolentor' ),
            'cart_items' => __( 'Cart Items', 'woolentor' ),
            'cart_items_names' => __( 'Cart Items Names', 'woolentor' ),
            'cart_abandonment_date' => __( 'Cart Abandonment Date', 'woolentor' ),
            
            // URLs
            'checkout_url' => __( 'Checkout URL', 'woolentor' ),
            'cart_url' => __( 'Cart URL', 'woolentor' ),
            'shop_url' => __( 'Shop URL', 'woolentor' ),
            'recovery_link' => __( 'Recovery Link', 'woolentor' ), // Added missing placeholder
            
            // Coupon information
            'coupon_code' => __( 'Coupon Code', 'woolentor' ),
            'coupon_amount' => __( 'Coupon Discount Amount', 'woolentor' ),
            'coupon_expiry_date' => __( 'Coupon Expiry Date', 'woolentor' ),
            
            // Admin information
            'admin_first_name' => __( 'Admin First Name', 'woolentor' ),
            'admin_last_name' => __( 'Admin Last Name', 'woolentor' ),
            'admin_full_name' => __( 'Admin Full Name', 'woolentor' ),
            'admin_email' => __( 'Admin Email', 'woolentor' ),
            
            // System links
            'unsubscribe_link' => __( 'Unsubscribe Link', 'woolentor' ),
            'privacy_policy_url' => __( 'Privacy Policy URL', 'woolentor' ),
            'terms_conditions_url' => __( 'Terms & Conditions URL', 'woolentor' ),
            
            // Date/Time
            'current_date' => __( 'Current Date', 'woolentor' ),
            'current_year' => __( 'Current Year', 'woolentor' ),
        ];

        return apply_filters( 'woolentor_supported_placeholders', $placeholders );
    }

    /**
     * Set context for placeholder processing
     */
    public function set_context( $cart = null, $template = null, $coupon_data = null, $user = null ) {
        $this->current_cart = $cart;
        $this->current_template = $template;
        $this->current_coupon_data = $coupon_data;
        $this->current_user = $user;
        
        // Clear caches when context changes
        $this->admin_info_cache = null;
        $this->customer_info_cache = null;
    }

    /**
     * Clear current context
     */
    public function clear_context() {
        $this->current_cart = null;
        $this->current_template = null;
        $this->current_coupon_data = null;
        $this->current_user = null;
        $this->admin_info_cache = null;
        $this->customer_info_cache = null;
    }

    /**
     * Replace placeholders in content - ENHANCED VERSION
     */
    public function replace_placeholders( $content, $custom_data = array() ) {
        if( empty( $content ) ) {
            return $content;
        }

        // Find all placeholders in the content
        preg_match_all( '/\{([^}]+)\}/', $content, $matches );
        
        if( empty( $matches[1] ) ) {
            return $content;
        }

        // Clean up URL placeholders first
        $content = $this->clean_url_placeholders( $content );

        $placeholders = array();
        $supported_placeholders = array_keys( $this->get_supported_placeholders() );

        foreach( $matches[1] as $placeholder ) {
            // Skip if already processed
            if( isset( $placeholders['{' . $placeholder . '}'] ) ) {
                continue;
            }

            // Check if custom data is provided first
            if( isset( $custom_data[$placeholder] ) ) {
                $placeholders['{' . $placeholder . '}'] = $custom_data[$placeholder];
                continue;
            }

            // Only process supported placeholders
            if( !in_array( $placeholder, $supported_placeholders ) ) {
                continue;
            }

            // Generate placeholder value automatically
            $value = $this->generate_placeholder_value( $placeholder );
            if( $value !== null ) {
                $placeholders['{' . $placeholder . '}'] = $value;
            }
        }

        // Replace placeholders in content
        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $content );
    }

    /**
     * Clean up URL placeholders that might be wrapped with protocol prefixes
     * 
     * This handles cases where users accidentally include http:// or https:// 
     * before URL placeholders in TinyMCE editor
     */
    private function clean_url_placeholders( $content ) {
        // Define URL placeholders that should be cleaned
        $url_placeholders = array(
            'recovery_link',
            'checkout_url',
            'cart_url',
            'shop_url',
            'site_url',
            'unsubscribe_link',
            'privacy_policy_url',
            'terms_conditions_url'
        );

        foreach( $url_placeholders as $placeholder ) {
            // Remove http:// and https:// prefixes from placeholders
            $content = str_replace( 'http://{' . $placeholder . '}', '{' . $placeholder . '}', $content );
            $content = str_replace( 'https://{' . $placeholder . '}', '{' . $placeholder . '}', $content );
            
            // Also handle cases with extra spaces
            $content = str_replace( 'http:// {' . $placeholder . '}', '{' . $placeholder . '}', $content );
            $content = str_replace( 'https:// {' . $placeholder . '}', '{' . $placeholder . '}', $content );
            
            // Handle cases where users might add www
            $content = str_replace( 'http://www.{' . $placeholder . '}', '{' . $placeholder . '}', $content );
            $content = str_replace( 'https://www.{' . $placeholder . '}', '{' . $placeholder . '}', $content );
            
            // Handle cases in link tags that TinyMCE might create
            $content = preg_replace( '/href=["\']https?:\/\/\{' . preg_quote($placeholder, '/') . '\}["\']/', 'href="{\1' . $placeholder . '}"', $content );
        }

        // Apply filter to allow extending URL placeholder cleaning
        return apply_filters( 'woolentor_clean_url_placeholders', $content, $url_placeholders );
    }

    /**
     * Generate placeholder value automatically based on current context - ENHANCED VERSION
     */
    public function generate_placeholder_value( $placeholder ) {
        $value = null;

        switch( $placeholder ) {
            // Site information
            case 'site_name':
                $value = get_bloginfo( 'name' );
                break;
                
            case 'site_url':
                $value = get_bloginfo( 'url' );
                break;

            // Customer information
            case 'customer_first_name':
            case 'customer_last_name':
            case 'customer_full_name':
                $customer_info = $this->get_customer_info();
                $value = $customer_info[$placeholder] ?? '';
                break;

            // Cart information
            case 'cart_total':
                $value = $this->get_cart_total();
                break;
                
            case 'cart_items':
                $value = $this->get_cart_items_html();
                break;
                
            case 'cart_items_names':
                $value = $this->get_cart_items_names();
                break;
                
            case 'cart_abandonment_date':
                $value = $this->get_cart_abandonment_date();
                break;

            // URLs
            case 'checkout_url':
            case 'recovery_link': // Handle both checkout_url and recovery_link
                $value = $this->get_checkout_url();
                break;
                
            case 'cart_url':
                $value = wc_get_cart_url();
                break;
                
            case 'shop_url':
                $value = wc_get_page_permalink( 'shop' );
                break;

            // Coupon information
            case 'coupon_code':
                $value = $this->get_coupon_code();
                break;
                
            case 'coupon_amount':
                $value = $this->get_coupon_amount();
                break;
                
            case 'coupon_expiry_date':
                $value = $this->get_coupon_expiry_date();
                break;

            // Admin information
            case 'admin_first_name':
            case 'admin_last_name':
            case 'admin_full_name':
            case 'admin_email':
                $admin_info = $this->get_admin_info();
                $value = $admin_info[$placeholder] ?? '';
                break;

            // System links
            case 'unsubscribe_link':
                $value = $this->get_unsubscribe_link();
                break;
                
            case 'privacy_policy_url':
                $value = get_privacy_policy_url();
                break;
                
            case 'terms_conditions_url':
                $value = wc_get_page_permalink( 'terms' );
                break;

            // Date/Time
            case 'current_date':
                $value = date_i18n( get_option( 'date_format' ) );
                break;
                
            case 'current_year':
                $value = date( 'Y' );
                break;

            default:
                $value = null;
                break;
        }

        // Allow filtering of placeholder values
        return apply_filters( 'woolentor_placeholder_value', $value, $placeholder, $this );
    }

    /**
     * Get customer information from current context - ENHANCED WITH CHECKOUT DATA
     */
    private function get_customer_info() {
        // Return cached info if available
        if( $this->customer_info_cache !== null ) {
            return $this->customer_info_cache;
        }

        $customer_first_name = '';
        $customer_last_name = '';
        $customer_full_name = __( 'Valued Customer', 'woolentor' );

        // Try to get from current user first
        if( $this->current_user ) {
            $user = $this->current_user;
        } elseif( $this->current_cart && !empty( $this->current_cart->user_id ) ) {
            $user = get_user_by( 'id', $this->current_cart->user_id );
        } else {
            $user = null;
        }

        if( $user ) {
            // Get user meta first, then fallback to user object properties
            $customer_first_name = get_user_meta( $user->ID, 'first_name', true );
            $customer_last_name = get_user_meta( $user->ID, 'last_name', true );

            // Fallback to user object properties if meta is empty
            if( empty( $customer_first_name ) && isset( $user->first_name ) ) {
                $customer_first_name = $user->first_name;
            }
            if( empty( $customer_last_name ) && isset( $user->last_name ) ) {
                $customer_last_name = $user->last_name;
            }

            // Build full name
            if( !empty( $customer_first_name ) || !empty( $customer_last_name ) ) {
                $customer_full_name = trim( $customer_first_name . ' ' . $customer_last_name );
            } elseif( !empty( $user->display_name ) ) {
                $customer_full_name = $user->display_name;
            } elseif( !empty( $user->user_nicename ) ) {
                $customer_full_name = $user->user_nicename;
            } else {
                $customer_full_name = __( 'Customer', 'woolentor' );
            }
        } else {
            // For guest users, try to get from checkout data first
            if( $this->current_cart && !empty( $this->current_cart->customer_other_info ) ) {
                $customer_other_info = maybe_unserialize( $this->current_cart->customer_other_info );
                
                if( !empty( $customer_other_info ) && is_array( $customer_other_info ) ) {
                    $customer_first_name = $customer_other_info['billing_first_name'] ?? '';
                    $customer_last_name = $customer_other_info['billing_last_name'] ?? '';
                    
                    if( !empty( $customer_first_name ) || !empty( $customer_last_name ) ) {
                        $customer_full_name = trim( $customer_first_name . ' ' . $customer_last_name );
                    }
                }
            }
            
            // Fallback: extract name from email or use generic greeting
            if( $customer_full_name === __( 'Valued Customer', 'woolentor' ) && $this->current_cart && !empty( $this->current_cart->user_email ) ) {
                $email_parts = explode( '@', $this->current_cart->user_email );
                if( !empty( $email_parts[0] ) ) {
                    $customer_full_name = ucfirst( $email_parts[0] );
                }
            }
        }

        // Cache the result
        $this->customer_info_cache = array(
            'customer_first_name' => $customer_first_name,
            'customer_last_name' => $customer_last_name,
            'customer_full_name' => $customer_full_name
        );

        return $this->customer_info_cache;
    }

    /**
     * Get admin information
     */
    private function get_admin_info() {
        // Return cached info if available
        if( $this->admin_info_cache !== null ) {
            return $this->admin_info_cache;
        }

        $admin_first_name = '';
        $admin_last_name = '';
        $admin_full_name = get_bloginfo( 'name' );
        $admin_email = get_option( 'admin_email' );

        // Get the first administrator user
        $admin_users = get_users( array(
            'role' => 'administrator',
            'number' => 1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => 'all'
        ) );

        if( !empty( $admin_users ) ) {
            $admin = $admin_users[0];
            
            // Get admin meta
            $admin_first_name = get_user_meta( $admin->ID, 'first_name', true ) ?: '';
            $admin_last_name = get_user_meta( $admin->ID, 'last_name', true ) ?: '';
            
            // Build full name
            if( !empty( $admin_first_name ) || !empty( $admin_last_name ) ) {
                $admin_full_name = trim( $admin_first_name . ' ' . $admin_last_name );
            } elseif( !empty( $admin->display_name ) ) {
                $admin_full_name = $admin->display_name;
            } elseif( !empty( $admin->user_nicename ) ) {
                $admin_full_name = $admin->user_nicename;
            }
            
            // Use admin email if available
            if( !empty( $admin->user_email ) ) {
                $admin_email = $admin->user_email;
            }
        }

        // Cache the result
        $this->admin_info_cache = array(
            'admin_first_name' => $admin_first_name,
            'admin_last_name' => $admin_last_name,
            'admin_full_name' => $admin_full_name,
            'admin_email' => $admin_email
        );

        return $this->admin_info_cache;
    }

    /**
     * Get cart total
     */
    private function get_cart_total() {
        if( !$this->current_cart ) {
            return '';
        }

        return wc_price( $this->current_cart->cart_total );
    }

    /**
     * Get cart items as HTML - IMPROVED VERSION
     */
    private function get_cart_items_html() {
        if( !$this->current_cart ) {
            return '';
        }

        $cart_manager = \Woolentor\Modules\AbandonedCart\Frontend\Cart_Manager::instance();
        $cart_info = $cart_manager->get_cart_contents_info( $this->current_cart );
        
        return $this->generate_cart_items_html( $cart_info['items'] );
    }

    /**
     * Get cart items names as text
     */
    private function get_cart_items_names() {
        if( !$this->current_cart ) {
            return '';
        }

        $cart_manager = \Woolentor\Modules\AbandonedCart\Frontend\Cart_Manager::instance();
        $cart_info = $cart_manager->get_cart_contents_info( $this->current_cart );
        
        return $this->generate_cart_items_names( $cart_info['items'] );
    }

    /**
     * Get cart abandonment date
     */
    private function get_cart_abandonment_date() {
        if( !$this->current_cart || empty( $this->current_cart->abandoned_at ) ) {
            return '';
        }

        return date_i18n( get_option( 'date_format' ), strtotime( $this->current_cart->abandoned_at ) );
    }

    /**
     * Get checkout URL (recovery URL if available)
     */
    private function get_checkout_url() {
        if( $this->current_cart ) {
            $cart_manager = \Woolentor\Modules\AbandonedCart\Frontend\Cart_Manager::instance();
            return $cart_manager->get_recovery_url( $this->current_cart->id );
        }

        return wc_get_checkout_url();
    }

    /**
     * Get coupon code
     */
    private function get_coupon_code() {
        return $this->current_coupon_data['coupon_code'] ?? '';
    }

    /**
     * Get coupon amount
     */
    private function get_coupon_amount() {
        if( empty( $this->current_coupon_data ) ) {
            return '';
        }

        $amount = $this->current_coupon_data['coupon_amount'] ?? '';
        $type = $this->current_coupon_data['coupon_type'] ?? 'percentage';

        if( empty( $amount ) ) {
            return '';
        }

        if( $type === 'percentage' ) {
            return $amount . '%';
        } else {
            return wc_price( $amount );
        }
    }

    /**
     * Get coupon expiry date
     */
    private function get_coupon_expiry_date() {
        if( empty( $this->current_coupon_data['coupon_expiry_days'] ) ) {
            return '';
        }

        $expiry_date = date( 'Y-m-d', strtotime( '+' . intval( $this->current_coupon_data['coupon_expiry_days'] ) . ' days' ) );

        return date_i18n( get_option( 'date_format' ), strtotime( $expiry_date ) );
    }

    /**
     * Generate unsubscribe link
     */
    private function get_unsubscribe_link() {
        if( !$this->current_cart ) {
            return '#';
        }

        return add_query_arg( array(
            'woolentor_unsubscribe' => $this->current_cart->id,
            'key' => wp_create_nonce( 'woolentor_unsubscribe_' . $this->current_cart->id )
        ), home_url() );
    }

    /**
     * Generate cart items HTML for email - COMPLETELY REWRITTEN FOR BETTER FORMATTING
     */
    public function generate_cart_items_html( $cart_items ) {
        if( empty( $cart_items ) ) {
            return '';
        }

        $html = '<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; border: 1px solid #e9ecef;">';
        $html .= '<h3 style="margin: 0 0 20px 0; color: #333; font-size: 18px; font-weight: bold;">' . __( 'Your Cart Items', 'woolentor' ) . '</h3>';
        
        // Create a proper table layout for better email client compatibility
        $html .= '<table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden;">';
        
        foreach( $cart_items as $index => $item ) {
            $border_top = $index > 0 ? 'border-top: 1px solid #e9ecef;' : '';
            
            $html .= '<tr style="' . $border_top . '">';
            
            // Product Image
            $html .= '<td style="padding: 15px; width: 80px; vertical-align: top;">';
            if( !empty( $item['image'] ) ) {
                $html .= '<img src="' . esc_url( $item['image'] ) . '" alt="' . esc_attr( $item['name'] ) . '" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid #e0e0e0; display: block;" />';
            } else {
                // Placeholder if no image
                $html .= '<div style="width: 70px; height: 70px; background: #f0f0f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">No Image</div>';
            }
            $html .= '</td>';
            
            // Product Details
            $html .= '<td style="padding: 15px; vertical-align: top;">';
            $html .= '<div style="font-size: 16px; font-weight: 600; color: #333; margin: 0 0 8px 0; line-height: 1.4;">' . esc_html( $item['name'] ) . '</div>';
            $html .= '<div style="font-size: 14px; color: #666; margin: 0 0 8px 0;">';
            $html .= sprintf( __( 'Quantity: %d', 'woolentor' ), absint( $item['quantity'] ) );
            $html .= '</div>';
            $html .= '<div style="font-size: 16px; font-weight: 600; color: #28a745;">' . wc_price( $item['price'] ) . '</div>';
            $html .= '</td>';
            
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Generate cart items names as comma-separated string
     */
    private function generate_cart_items_names( $cart_items ) {
        if( empty( $cart_items ) ) {
            return '';
        }

        $names = array();
        foreach( $cart_items as $item ) {
            $quantity = absint( $item['quantity'] );
            if( $quantity > 1 ) {
                $names[] = $item['name'] . ' (x' . $quantity . ')';
            } else {
                $names[] = $item['name'];
            }
        }

        return implode( ', ', $names );
    }

    /**
     * Preview placeholder replacement (for testing/admin interface)
     */
    public function preview_placeholders( $content, $sample_data = array() ) {
        // Set sample context if not provided
        if( empty( $sample_data ) ) {
            $sample_data = $this->get_sample_placeholder_data();
        }

        return $this->replace_placeholders( $content, $sample_data );
    }

    /**
     * Get sample data for placeholder preview - ENHANCED VERSION
     */
    public function get_sample_placeholder_data() {
        return array(
            'site_name' => get_bloginfo( 'name' ),
            'site_url' => get_bloginfo( 'url' ),
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'customer_full_name' => 'John Doe',
            'cart_total' => wc_price( 99.99 ),
            'cart_items' => $this->get_sample_cart_items_html(),
            'cart_items_names' => 'Product 1, Product 2',
            'cart_abandonment_date' => date_i18n( get_option( 'date_format' ) ),
            'checkout_url' => wc_get_checkout_url(),
            'recovery_link' => wc_get_checkout_url() . '?recover=sample', // Added recovery_link
            'cart_url' => wc_get_cart_url(),
            'shop_url' => wc_get_page_permalink( 'shop' ),
            'coupon_code' => 'SAVE20',
            'coupon_amount' => '20%',
            'coupon_expiry_date' => date_i18n( get_option( 'date_format' ), strtotime( '+7 days' ) ),
            'admin_first_name' => 'Admin',
            'admin_last_name' => 'User',
            'admin_full_name' => 'Admin User',
            'admin_email' => get_option( 'admin_email' ),
            'unsubscribe_link' => home_url( '?unsubscribe=sample' ),
            'privacy_policy_url' => get_privacy_policy_url(),
            'terms_conditions_url' => wc_get_page_permalink( 'terms' ),
            'current_date' => date_i18n( get_option( 'date_format' ) ),
            'current_year' => date( 'Y' ),
        );
    }

    /**
     * Get sample cart items HTML for preview
     */
    private function get_sample_cart_items_html() {
        $sample_items = array(
            array(
                'name' => 'Sample Product 1',
                'quantity' => 1,
                'price' => 29.99,
                'image' => 'https://via.placeholder.com/70x70'
            ),
            array(
                'name' => 'Sample Product 2',
                'quantity' => 2,
                'price' => 39.99,
                'image' => 'https://via.placeholder.com/70x70'
            )
        );

        return $this->generate_cart_items_html( $sample_items );
    }

    /**
     * Get placeholders found in content
     */
    public function get_placeholders_in_content( $content ) {
        preg_match_all( '/\{([^}]+)\}/', $content, $matches );
        return array_unique( $matches[1] );
    }

    /**
     * Validate placeholders in content
     */
    public function validate_placeholders( $content ) {
        $found_placeholders = $this->get_placeholders_in_content( $content );
        $supported_placeholders = array_keys( $this->get_supported_placeholders() );
        
        $valid = array();
        $invalid = array();
        
        foreach( $found_placeholders as $placeholder ) {
            if( in_array( $placeholder, $supported_placeholders ) ) {
                $valid[] = $placeholder;
            } else {
                $invalid[] = $placeholder;
            }
        }
        
        return array(
            'valid' => $valid,
            'invalid' => $invalid,
            'total_found' => count( $found_placeholders )
        );
    }

    /**
     * Filter hook for extending supported placeholders
     */
    public function filter_supported_placeholders( $placeholders ) {
        return $placeholders;
    }

    /**
     * Filter hook for modifying placeholder values
     */
    public function filter_placeholder_value( $value, $placeholder, $manager ) {
        return $value;
    }
}