<?php
/*
 *	Class: NM - WooCommerce Cart - Free shipping-meter (based on: https://wordpress.org/plugins/wpc-free-shipping-bar/)
 */

defined( 'ABSPATH' ) || exit;

class NM_Cart_Free_Shipping_Meter {
    protected static $instance = null;
    
    /**
	 * Constructor
	 */
	public function __construct() {
        add_action( 'woocommerce_widget_shopping_cart_before_buttons', array( $this, 'free_shipping_meter' ), 10 );
    }
    
    /**
	 * Free shipping meter
	 */
    public function free_shipping_meter() {
        if ( ! isset( WC()->cart ) || ! WC()->cart->needs_shipping() || ! WC()->cart->show_shipping() ) {
            return;
        }

        if ( $this->is_shipping_method( $this->get_shipping_method(), 'local_pickup' ) ) {
            return;
        }
        
        $is_qualified = '';

        if ( WC()->customer->has_shipping_address() && ( WC()->cart->get_shipping_total() <= 0 ) ) {
            // shipping fee zero
            $is_qualified = 'zero_fee';
        }

        $applied_coupons = WC()->cart->get_applied_coupons();

        foreach ( $applied_coupons as $coupon_code ) {
            $coupon = new WC_Coupon( $coupon_code );

            if ( $coupon->get_free_shipping() ) {
                // already free shipping
                $is_qualified = 'coupon';
                break;
            }
        }
        
        $free_shipping                  = $this->get_free_shipping();
        $free_shipping_min_amount       = $free_shipping['min_amount'] ?? 0;
        //$free_shipping_ignore_discounts = $free_shipping['ignore_discounts'] ?? 'no';

        if ( ! $free_shipping_min_amount ) {
            return;
        }

        $cart_total          = WC()->cart->get_displayed_subtotal();
        $discount            = WC()->cart->get_discount_total();
        $discount_tax        = WC()->cart->get_discount_tax();
        $price_including_tax = WC()->cart->display_prices_including_tax();
        $price_decimal       = wc_get_price_decimals();

        /*if ( apply_filters( 'nm_cfsm_ignore_discounts', $free_shipping_ignore_discounts !== 'no' ) ) {
            $discount     = 0;
            $discount_tax = 0;
        }*/

        if ( $price_including_tax ) {
            $cart_total = round( $cart_total - ( $discount + $discount_tax ), $price_decimal );
        } else {
            $cart_total = round( $cart_total - $discount, $price_decimal );
        }

        if ( $cart_total >= $free_shipping_min_amount ) {
            $is_qualified = 'total';
        }
        
        global $nm_theme_options;
        
        if ( empty( $is_qualified ) ) {
            $wrapper_class      = 'not-free';
            $remaining          = $free_shipping_min_amount - $cart_total;
            $percent            = 100 - ( $remaining / $free_shipping_min_amount ) * 100;
            $message            = $nm_theme_options['cart_shipping_meter_message'];
            $message_percent    = round( $percent, 0 ) . '%';
        } else {
            $wrapper_class      = 'is-free';
            $remaining          = 0;
            $percent            = 100;
            $message            = $nm_theme_options['cart_shipping_meter_message_qualified'];
            $message_percent    = '<i class="nm-font-check-alt"></i>';
        }
        
        $message = $this->placeholders( $message, $remaining, $free_shipping_min_amount );
        ?>
        <div class="nm-cart-shipping-meter <?php echo esc_attr( $wrapper_class ); ?>">
            <div class="nm-cart-shipping-meter-top">
                <strong><?php echo esc_html( $message ); ?></strong>
                <span><?php echo $message_percent; ?></span>
            </div>
            <div class="nm-cart-shipping-meter-bar">
                <div class="nm-cart-shipping-meter-bar-progress" data-progress="<?php echo esc_attr( $percent ); ?>"></div>
            </div>
        </div>
        <?php
    }
    
    /**
	 * Shipping method: Verify method
	 */
    public function is_shipping_method( $string, $start_string ) {
        $len = strlen( $start_string );

        return ( substr( $string, 0, $len ) === $start_string );
    }
    
    /**
	 * Shipping method: Get method
	 */
    public function get_shipping_method() {
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

        if ( ! $chosen_methods ) {
            return null;
        }
        
        return $chosen_methods[0];
    }
    
    /**
	 * Shipping method: Get method minimum amount
	 */
    public function get_shipping_method_min_amount( $shipping_id ) {
        $packages = WC()->shipping->get_packages();
        $amount   = null;

        foreach ( $packages as $package ) {
            if ( isset( $package['rates'][ $shipping_id ] ) ) {
                $rate = $package['rates'][ $shipping_id ];
                $meta = $rate->get_meta_data();

                if ( isset( $meta['_fs_method']['method_free_shipping'] ) ) {
                    $amount = $meta['_fs_method']['method_free_shipping'] ?: null;
                }
            }
        }

        return $amount;
    }
    
    /**
	 * Shipping method: Get free method
	 */
    public function get_free_shipping() {
        $free_shipping        = [];
        $chosen_shipping_id   = $this->get_shipping_method();
        $is_flexible_shipping = $this->is_shipping_method( $chosen_shipping_id, 'flexible_shipping' );

        if ( $is_flexible_shipping ) {
            $option_name = 'woocommerce_' . str_replace( ':', '_', $chosen_shipping_id ) . '_settings';
            $option      = get_option( $option_name );
            $amount      = $option['method_free_shipping'] ?? null;

            return $amount ?: $this->get_shipping_method_min_amount( $chosen_shipping_id );
        }

        $packages = WC()->cart->get_shipping_packages();
        $package  = reset( $packages );
        $zone     = wc_get_shipping_zone( $package );

        foreach ( $zone->get_shipping_methods( true ) as $method ) {
            if ( $method->id === 'free_shipping' ) {
                $free_shipping['min_amount']       = $method->get_option( 'min_amount', 0 );
                $free_shipping['ignore_discounts'] = $method->get_option( 'ignore_discounts' );
            }
        }
        
        return apply_filters( 'nm_cfsm_get_free_shipping', $free_shipping );
    }
    
    /**
	 * Message: Replace placeholder text
	 */
    public function placeholders( $input_string = '', $remaining = null, $free_shipping_min_amount = null ) {
        if ( $remaining ) {
            //$input_string = str_replace( '{remaining}', wc_price( $remaining ), $input_string );
            $remaining_price = html_entity_decode( strip_tags( wc_price( $remaining ) ) );
            $input_string = str_replace( '{remaining}', $remaining_price, $input_string );
        }

        if ( $free_shipping_min_amount ) {
            //$input_string = str_replace( '{free_shipping_amount}', wc_price( $free_shipping_min_amount ), $input_string );
            $free_shipping_min_amount_price = html_entity_decode( strip_tags( wc_price( $free_shipping_min_amount ) ) );
            $input_string = str_replace( '{free_shipping_amount}', $free_shipping_min_amount_price, $input_string );
        }

        return str_replace( '{subtotal}', WC()->cart->get_cart_subtotal(), $input_string );
    }
}

/**
 * Init NM_Cart_Free_Shipping_Meter class
 */
add_action( 'init', function() {
    global $nm_cart_free_shipping_meter;
    $nm_cart_free_shipping_meter = new NM_Cart_Free_Shipping_Meter();
} );

/**
 * Function: Display free shipping meter
 */
/*function nm_cart_free_shipping_meter() {
    global $nm_cart_free_shipping_meter;
    $nm_cart_free_shipping_meter->free_shipping_meter();
}*/
