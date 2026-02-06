/**
 * Frontend Cart
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Colors and Labels Variations Premium
 * @version 3.0.0
 */

;(function($, window, document){

    const YITH_WCCL_Cart = {
        config: {
            editLinkContainer: '.yith-wccl-cart-container',
            editVariationLink: '.yith-wccl-edit-product-cart',
            closeModalSelector: '.modal-header .close',
            wcProductGallery: '.woocommerce-product-gallery',
            modalDialog: '.modal-dialog',
            variationsForm: '.variations_form',
            updateCartItemSelector: '.yith-wccl-modal-update'
        },

        init: function() {
            this.bindEvents();
            this.moveEditAttributeLink();
        },

        bindEvents: function() {
            $(document).on('updated_wc_div', this.moveEditAttributeLink.bind(this));
            $(document).on('click', this.config.editVariationLink, this.openModal.bind(this));
            $(document).on('click', `${this.config.closeModalSelector}, .overlay`, this.closeModal.bind(this));
            $(window).on("resize", this.centerModal.bind(this));
            $(document).on('click', this.config.updateCartItemSelector, this.onUpdateCartItem.bind(this));
            $(document).on("ywccl_after_found_variation", this.handleUpdateButton.bind(this));
        },

        moveEditAttributeLink: function() {
            $(this.config.editLinkContainer + ':not(.yith-wccl-initialized)').each(function() {
                var link = $(this);
                var productNameRow = link.closest('.product-name');
                productNameRow.append(link);
                $(this).addClass('yith-wccl-initialized');
            });
        },

        handleUpdateButton: function( event, variation ) {
            let button = $(this.config.updateCartItemSelector);
            button.removeClass('disabled');
            if( !variation.is_in_stock ) {
                button.addClass('disabled');
            }
        },

        openModal: function(event) {
            event.preventDefault();
            let post_data = {
                'product_id': $(event.currentTarget).data('product_id'),
                'variation_id': $(event.currentTarget).data('variation_id'),
                'cart_item_key': $(event.currentTarget).data('cart-item-key'),
                security: yith_wccl_cart.edit_attributes,
                action: 'yith_wccl_edit_attributes_on_cart'
            };
            $.ajax({
                type: "POST",
                data: post_data,
                url: yith_wccl_cart.ajaxurl.toString().replace('%%endpoint%%', 'yith_wccl_edit_attributes_on_cart'),
                success: this.onOpenModalSuccess.bind(this, post_data),
                error: this.onAjaxError
            });
        },

        onOpenModalSuccess: function(post_data, response) {
            $('body').append(response);
            $('body').addClass('yith-wccl-modal-is-open');

            this.modal = $('.yith-wccl-modal');

            let variationAttributes = this.getVariationAttributes(post_data.variation_id);
            if (variationAttributes !== null) {
                this.updateAttributeSelectors(variationAttributes);
            }

            this.displayAttributes(this.modal.find('.yith-wccl-data').data('attr'));

            this.reinitFunctions();

            this.displayModal(post_data.cart_item_key);
        },

        closeModal: function(event) {
            if (this.popupActive) {
                $(this.modal).remove();
                this.popupActive = false;
                $('body').removeClass('yith-wccl-modal-is-open');
            }
        },

        centerModal: function() {
            let t = $(this.config.modalDialog),
                window_w = $(window).width(),
                window_h = $(window).height(),
                width = 0,
                height = 0,
                top = 0;

                if( window_w > 768 ) {
                    width = 758;
                    height = window_h / 3;
                    top = ( ((window_h / 2) - (height / 2)) - 100 );

                } else if( window_w > 430 ) {
                    width = window_w - 60;
                    height = window_h / 2;
                    top = ((window_h / 2) - (height / 2));

                } else {
                    width = window_w - 60;
                    height = window_h - 150;
                    top = ((window_h / 2) - (height / 2));
                }

            t.css({
                'left': ((window_w / 2) - (width / 2)) + 'px',
                'top': top + 'px',
                'width': width + 'px',
                'height': height + 'px'
            });
        },

        displayModal: function(cartItemKey) {
            let updateButton = $(this.modal).find(this.config.updateCartItemSelector);
            updateButton.data('cart_item_key', cartItemKey);

            $(this.modal).css('display', "block");
            this.popupActive = true;

            this.centerModal();
        },

        getVariationAttributes: function(variationId) {
            let variationsData = $(this.config.variationsForm).data('product_variations');
            if (variationsData !== 'undefined') {
                let variation = variationsData.find(variation => variation.variation_id === variationId);
                return variation ? variation.attributes : null;
            } else {
                console.log('Variations cannot be retrieved');
                return null;
            }
        },

        updateAttributeSelectors: function(attributes) {
            $.each(attributes, function(attributeName, attributeValue) {
                let selector = '[name="' + attributeName + '"]',
                    $attributeField = $(this.modal).find(selector);
                if ($attributeField.length) {
                    $attributeField.val(attributeValue).trigger('change');
                }
            }.bind(this));
        },

        displayAttributes: function(attr_qv) {
            if (attr_qv) {
                $.yith_wccl(attr_qv);
            }
        },

        reinitFunctions: function() {
            $(this.config.wcProductGallery).wc_product_gallery();
            $(this.config.variationsForm).wc_variation_form();
        },

        onUpdateCartItem: function(event) {
            let all_select = $(this.config.variationsForm).find('.variations select'),
                canBeUpdateItem = true,
                chosenAttributes = {};

            $.each(all_select, function() {
                $(this).closest('tr').find('th').removeClass('yith-wccl-no-selected');
                $(this).closest('tr').find('.yith-wccl-no-selected-msg').remove();
                if (!$(this).val()) {
                    $(this).closest('tr').find('th').addClass('yith-wccl-no-selected');
                    $(this).closest('td').append('<div class="yith-wccl-no-selected-msg"><small>'+yith_wccl_general.error_no_selected+'</small></div>');
                    canBeUpdateItem = false;
                } else {
                    let attribute_name = $(this).data('attribute_name') || $(this).attr('name');
                    chosenAttributes[attribute_name] = $(this).val();
                }
            });

            if (canBeUpdateItem) {
                let variationsData = $(this.config.variationsForm).data('product_variations'),
                    variation = this.findVariation(chosenAttributes, variationsData);

                if (variation && variation.is_in_stock) {
                    this.updateCartItem(variation.variation_id, chosenAttributes, $(event.currentTarget).data('cart_item_key'));
                } else {
                    console.log('Variation is not in stock');
                }
            } else {
                console.log('Missing one attribute to be selected');
            }
        },

        updateCartItem: function(variationID, selectedAttributes, cartItemKey) {
            let postData = {
                action: 'yith_wccl_update_cart_item',
                cart_item_key: cartItemKey,
                variationID: variationID,
                selectedAttributes: selectedAttributes,
                security: yith_wccl_cart.update_cart_item
            };

            $.ajax({
                type: 'POST',
                data: postData,
                url: yith_wccl_cart.ajaxurl.toString().replace('%%endpoint%%', 'yith_wccl_update_cart_item'),
                success: this.onUpdateCartItemSuccess.bind(this),
                error: this.onAjaxError
            });
        },

        onUpdateCartItemSuccess: function(response) {
            if (response.success) {
                if (window.wc && window.wc.wcBlocksData) {
                    const { CART_STORE_KEY } = window.wc.wcBlocksData;
                    window.wp.data.dispatch(CART_STORE_KEY).invalidateResolutionForStore();
                    window.wp.data.select(CART_STORE_KEY).getCartData();
                } else {
                    $(document).trigger('wc_update_cart');
                }
                this.closeModal();
            } else {
                console.error('Failed to update cart item:', response.data);
            }
        },

        findVariation: function(attributes, variations) {
            return variations.find(variation => Object.keys(attributes).every(key => ( variation.attributes[key] === attributes[key] || variation.attributes[key] === '' )));
        },

        onAjaxError: function(xhr, ajaxOptions, thrownError) {
            console.error('Error:', xhr.status, thrownError);
        }
    };

    $(document).ready(function() {
        YITH_WCCL_Cart.init();
    });

})(jQuery, window, document);
