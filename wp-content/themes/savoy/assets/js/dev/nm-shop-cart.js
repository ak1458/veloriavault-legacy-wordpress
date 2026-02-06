(function($) {
	
	'use strict';
	
	// Extend core script
	$.extend($.nmTheme, {
		
		/**
		 *	Initialize cart scripts
		 */
		cart_init: function() {
			var self = this;
            
            // Return early if Cart Block is used
            if ($('.wp-block-woocommerce-cart').length) {
                return;
            }
            
			// Init quantity buttons
            self.quantityInputsBindButtons($('.woocommerce'));
            
            /* Bind: "added_to_cart" event (products can be added via cross sells) */
            self.$body.on('added_to_cart', function() {
                // Is the quick-view visible?
                if ($('#nm-quickview').is(':visible')) {
                    self.cartTriggerUpdate();
                }
            });
            
            /* Coupon - Bind: Coupon toggle button */
            $('#nm-coupon-btn').on('click', function(e) {
                e.preventDefault();
                $(this).next('.nm-coupon').slideToggle(300);
            });
            
            /* Coupon - Bind: Input text change */
            $('.cart-collaterals').on('change keyup paste', '#nm-coupon-code', function() {
                self.cartCouponFormSetButtonState(this);
            });
            
            /* Coupon: Set button state */
            var $couponInput = $('#nm-coupon-code');
            if ($couponInput.length) {
                self.cartCouponFormSetButtonState($couponInput[0]);
            }
		},
        
        /**
		 *	Coupon form: Set button state
		 */
        cartCouponFormSetButtonState: function(couponInput) {
            if ($(couponInput).val().length > 1) {
                $('#nm-apply-coupon-btn').removeAttr('disabled');
            } else {
                $('#nm-apply-coupon-btn').attr('disabled', 'disabled');
            }
        },
        
        /**
		 *	Trigger update button
		 */
        cartTriggerUpdate: function() {
            // Get original update button
            var $wooUpdateButton = $('div.woocommerce > form button[name="update_cart"]');

            // Trigger "click" event
            setTimeout(function() { // Use a small timeout to make sure the element isn't disabled
                $wooUpdateButton.trigger('click');
            }, 100);
        }
		
	});
	
	// Add extension so it can be called from $.nmThemeExtensions
	$.nmThemeExtensions.cart = $.nmTheme.cart_init;
	
})(jQuery);