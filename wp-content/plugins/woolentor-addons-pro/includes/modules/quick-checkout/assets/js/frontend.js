;(function($){
 "use strict";
    
    var WooLentorQuickCheckout = {

        body: $('body'),
        modal: $('#woolentor-quick-checkout-modal'),
        modalbody: $('.woolentor-quick-checkout-modal-body'),
        singlePageBtn: $('.woolentor-quick-checkout-button-single-product'),
        cartButton: $('.woolentor-quick-checkout-button-single-product').parents('form.cart').find('.single_add_to_cart_button'),
        productIdStores: $('.woolentor-quick-checkout-button-single-product').parents('form.cart').find('[name="add-to-cart"]'),
        variationForm: $('.woolentor-quick-checkout-button-single-product').parents('.variations_form'),

        /**
         * [init]
         * @return {[void]} Initial Function
         */
        init: function(){
            $( document )
                .on( 'click.WooLentorQuickCheckout', 'a.woolentor-quick-checkout-button', this.openQuickCheckout )
                .on( 'click.WooLentorQuickCheckout', '.woolentor-quick-checkout-modal-close', this.closeQuickCheckout )
                .on( 'click.WooLentorQuickCheckout', '.woolentor-quick-checkout-overlay', this.closeQuickCheckout );

            $( document ).keyup( this.closeKeyUp );

            // Single Product
            this.variationForm
                .on("woocommerce_variation_has_changed", function () {
                    WooLentorQuickCheckout.singlePageBtn.toggleClass("disabled", WooLentorQuickCheckout.cartButton.hasClass("disabled"));
                })
                .on("hide_variation", function () {
                    WooLentorQuickCheckout.singlePageBtn.toggleClass("disabled", !false);
                });

        },

         /**
         * [openQuickCheckout] Close quickview
         * @param  event
         * @return {[void]}
         */
         openQuickCheckout: function( event ) {
            event.preventDefault();

            var $this = $(this),
                productID = $this.data('product_id'),
                mode = $this.data('checkout_mode'),
                url = $this.data('checkout_url'),
                checkout_url = `${url}&woolentor_quick_checkout=quickcheckout&add-to-cart=${productID}&quantity=1`;

            // If Button is Disable and Single product page.
            if ( $this.hasClass('disabled') ){
                WooLentorQuickCheckout.cartButton.trigger("click");
                return;
            }

            // Variable Product for archive and Shop page.
            if( $this.hasClass('woolentor-quick-checkout-redirect-product-page') ){
                let urlToRedirect = $this.attr('href');
                window.location = urlToRedirect;
                return;
            }

            // For Redirect Mode Shop/Archive page
            if(mode == 'redirect' && !$this.hasClass('woolentor-quick-checkout-button-single-product')){
                WooLentorQuickCheckout.dataRegenarate(checkout_url, mode, $this);
                return;
            }

            // Single Product Page
            if( $this.hasClass('woolentor-quick-checkout-button-single-product') ){
                var formData = $this.parents("form.cart").serialize(),
                    singleProductID = WooLentorQuickCheckout.productIdStores.val(),
                    checkout_url = `${url}&woolentor_quick_checkout=quickcheckout&add-to-cart=${singleProductID}&${formData}`;

                if(mode == 'redirect'){
                    let redirectUrl = new URL(url);
                    redirectUrl.searchParams.delete('nonce');
                    WooLentorQuickCheckout.dataRegenarate(checkout_url, mode, $this, redirectUrl);
                }else{
                    WooLentorQuickCheckout.dataRegenarate(checkout_url, mode, $this);
                }
                return;

            }

            WooLentorQuickCheckout.dataRegenarate(checkout_url, mode, $this);
                
        },

        /**
         * Manage AJAX Request
         * @param {*} requestUrl 
         * @param {*} mode 
         * @param {*} $this 
         * @param {*} redirectUrl 
         */
        dataRegenarate: function (requestUrl, mode, $this, redirectUrl = null) {
            $.ajax({
                url: requestUrl,
                context: document.body,
                beforeSend: function () {
                    $this.addClass('loading');
                    if (mode == 'popup') {
                        WooLentorQuickCheckout.modalbody.html('');
                        WooLentorQuickCheckout.body.addClass('woolentor-quick-checkout-loader');
                        WooLentorQuickCheckout.modal.addClass('loading').addClass('woolentor-quick-checkout-open');
                    }
                }
            })
            .done(function (response) {
                $this.removeClass('loading');
                if (mode == 'popup') {
                    WooLentorQuickCheckout.body.removeClass('woolentor-quick-checkout-loader');
                    WooLentorQuickCheckout.modal.removeClass('loading');
                    WooLentorQuickCheckout.modalbody.html('<iframe src="' + requestUrl + '"></iframe>');
                } else {
                    if( redirectUrl === null && response?.success ){
                        window.location = response.data.url;
                    }else{
                        window.location = redirectUrl;
                    }
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.error('Request failed: ' + textStatus, errorThrown);
            });
        },
        

        /**
         * [closeQuickCheckout] Close quick checkout
         * @param  event
         * @return {[void]}
         */
        closeQuickCheckout: function( event ) {
            event.preventDefault();
            WooLentorQuickCheckout.modal.removeClass('woolentor-quick-checkout-open');
        },

        /**
         * [closeKeyUp] Close quick checkout after press ESC Button
         * @param  event
         * @return {[void]}
         */
        closeKeyUp: function(event){
            if( event.keyCode === 27 ){
                WooLentorQuickCheckout.modal.removeClass('woolentor-quick-checkout-open');
            }
        },

    };

    $( document ).ready( function() {
        WooLentorQuickCheckout.init();
    });
    
})(jQuery);