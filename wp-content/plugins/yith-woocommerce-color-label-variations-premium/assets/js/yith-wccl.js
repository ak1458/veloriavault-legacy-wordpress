/**
 * Frontend
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Colors and Labels Variations Premium
 * @version 1.0.0
 */

;(function( $, window, document ){

    if ( typeof yith_wccl_general === 'undefined' )
        return false;

    /**
     * Matches inline variation objects to chosen attributes and return variation
     * @type {Object}
     */
    var variations_match = function( form, value, current_attribute_name ) {
        var match = false,
            product_variations = form.data( 'product_variations' ),
            all_select = form.find( '.variations select' ),
            show_specific_attr = true;
            settings = [];

        // current selected values
        $.each( all_select, function(){
            var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );
            if( current_attribute_name == attribute_name ) {
                settings[attribute_name] = value;
            }
            else {
                if( $(this).val() !== '' ) {
                    settings[attribute_name] = $(this).val();
                }
            }
        });

        for ( var i = 0; i < product_variations.length; i++ ) {
            var variation    = product_variations[i];

            // if found matching variation exit
            if( match ) {
                break;
            }
            var attr = ( show_specific_attr ) ? settings : variation.attributes;
            if( Object.keys(attr).length >= 1 ) {
                match = variation;
                for (var attr_name in attr) {
                    if (variation.attributes.hasOwnProperty(attr_name) ) {
                        var val1 = variation.attributes[attr_name],
                            val2 = settings[attr_name];
                        if (val1 != val2 && attr[attr_name] != '' && val1 !='') {
                            match = false;
                        }
                    }
                }
            }
        }
        return match;
    };

    /**
     * Add to cart variation loop
     * @param event
     */
    var yith_wccl_add_cart = function( event ){

        event.preventDefault();

        var b          = $( this ),
            product_id = b.data( 'product_id' ),
            quantity   = b.data( 'quantity' ),
            attr = [],
            data = {};

        $.each( b.data(), function( key, value ) {
            data[ key ] = value;
        });

        // get select value
        event.data.select.each( function(index){
            attr[ index ] = this.name + '=' + this.value;
        });

        // Trigger event.
        $( document.body ).trigger( 'adding_to_cart', [ b, data ] );

        $.ajax({
            url: yith_wccl_general.ajaxurl.toString().replace( '%%endpoint%%', yith_wccl_general.actionAddCart ),
            type: 'POST',
            data: {
                action: yith_wccl_general.actionAddCart,
                product_id : product_id,
                variation_id : event.data.variation,
                attr: attr.join('&'),
                quantity: quantity,
                context: 'frontend'
            },
            beforeSend: function(){
                b.addClass( 'loading').removeClass( 'added' );
            },
            success: function( res ){

                // redirect to product page if some error occurred
                if ( res.error && res.product_url ) {
                    window.location = res.product_url;
                    return;
                }
                // redirect to cart
                if ( yith_wccl_general.cart_redirect ) {
                    window.location = yith_wccl_general.cart_url;
                    return;
                }

                // change button
                b.removeClass('loading').addClass('added');

                if( ! b.next('.added_to_cart').length ) {
                    b.after(' <a href="' + yith_wccl_general.cart_url + '" class="added_to_cart wc-forward" title="' + yith_wccl_general.view_cart + '">' + yith_wccl_general.view_cart + '</a>');
                }

                $( document.body ).trigger( 'wc_fragment_refresh' );
                // trigger refresh also cart page
                $( document ).trigger( 'wc_update_cart' );

                // added to cart
                $( document.body ).trigger( 'added_to_cart', [ res.fragments, res.cart_hash, b ] );
            }
        });
    }

    var yith_wccl_change_label = function (label, t) {

        t.closest('tr').find('th label').text(label);
    };

    /**
     *
     * @param $form
     * @param attr
     * @constructor
     */
    var WCCL = function( $form, attr ) {

        this.$form              = $form;
        this.$attr              = ( typeof yith_wccl != 'undefined' ) ? JSON.parse( yith_wccl.attributes ) : attr;
        this.$select            = this.$form.find( '.variations select' );
        this.$use_ajax          = this.$form.data( 'product_variations' ) === false;
        this.$attr_number        = this.$form.data('number-variation-attributes');
        // variables for loop
        this.$is_loop           = this.$form.hasClass('in_loop');
        this.$wrapper           = this.$form.closest( yith_wccl_general.wrapper_container_shop ).length ? this.$form.closest( yith_wccl_general.wrapper_container_shop ) : this.$form.closest('.product-add-to-cart' );
        this.$image             = this.$wrapper.find( yith_wccl_general.image_selector ).first();
        this.$def_image_src     = ( this.$image.data('lazy-src') ) ? this.$image.data('lazy-src') : this.$image.attr( 'src' );
        this.$def_image_srcset  = ( this.$image.data('lazy-srcset') ) ? this.$image.data('lazy-srcset') : this.$image.attr( 'srcset' );
        this.$price_html        = this.$wrapper.find( yith_wccl_general.wrapper_price_shop ).clone().wrap('<p>').parent().html();
        this.$button            = this.$wrapper.find( 'a.add_to_cart_button' );
        this.$button_html       = this.$button.html();
        this.$input_qty         = this.$wrapper.find('input.thumbnail-quantity');
        this.$xhr               = null;
        this.variations_gallery = []; // store variations gallery to improve performace

        // prevent undefined attr error
        if( typeof this.$attr == 'undefined' ) {
            this.$attr = [];
        }

        $form.on( 'yith_wccl_form_initialized', { obj: this }, this.init );

        // get default value
        this.$select.each( function() {
            this.setAttribute( 'data-default_value', this.value );
        });

        // reset form and select
        this.resetForm( this );

        if( this.$is_loop ) {
            $form.parent().on( 'change', function(e) { e.stopPropagation(); });
        }

        // hide input qty if present
        if( this.$input_qty.length )
            this.$input_qty.hide();

        if( ! this.$form.hasClass( 'initialized' ) ) {
            this.$form.addClass('initialized').fadeIn().trigger( 'yith_wccl_form_initialized' );
        }
    };

    WCCL.prototype.styleOption = function( obj, option, type, value ) {

        const regExp = /^(ftp|http|https):\/\/[^ "]+$/;

        if( regExp.test(value ) && 'image' !== type ) {
            type = 'image';
        }

        switch ( type ) {

            case 'colorpicker':
                value = value.split(',');

                if( value.length == 1 ) {
                    /*option.append($('<span/>', {
                        'class': 'yith_wccl_value',
                        'css': {
                            'background': value
                        }
                    }));*/
                    option.append($('<div class="yith_wccl_value_wrapper"><span class="yith_wccl_value"></span></div>'));
                    option.find('.yith_wccl_value').css({
                        'background': value,
                    });
                } else {
                    option.append($('<div class="yith_wccl_value_wrapper"><span class="yith_wccl_value"><span class="yith-wccl-bicolor"/></span></div>'));
                    option.find('.yith-wccl-bicolor').css({
                        'background-color': value[0],
                        'border-color': value[1]
                    });

                }
                break;
            case 'image' :
                /*option.append($('<img/>', {
                    'class': 'yith_wccl_value',
                    'src': value,
                    'alt':type
                }));*/
                option.append($('<div class="yith_wccl_value_wrapper"><img class="yith_wccl_value"/></div>'));
                option.find('.yith_wccl_value').attr('src',value).attr('alt',type);
                break;

            case 'label' :
                /*option.append($('<span/>', {
                    'class': 'yith_wccl_value',
                    'text': value
                }));*/
                option.append($('<div class="yith_wccl_value_wrapper"><span class="yith_wccl_value"></span></div>'));
                option.find('.yith_wccl_value').text(value);
                break;

        }
    };

    WCCL.prototype.addTooltip = function( obj, tooltip, option, type, value, tooltip_image = '' ) {

        var tooltip_wrapper = $('<span class="yith_wccl_tooltip"></span>'),
            classes         = yith_wccl_general.tooltip_pos + ' ' + yith_wccl_general.tooltip_ani;

        if( ! yith_wccl_general.tooltip || typeof tooltip == 'undefined' || ( ! tooltip && tooltip_image === '' ) || option.find( '.yith_wccl_tooltip' ).length ) {
            return;
        }

        if( type == 'image' ) {
            tooltip = tooltip.toString().replace('{show_image}', '<img src="' + value + '" />');
        }
        if(  tooltip_image !== '' ) {
            color_image = '<img src="' + tooltip_image + '" />';
            tooltip = color_image + tooltip.toString();
        }

        tooltip_wrapper.addClass( classes );
        option.append( tooltip_wrapper.html( '<span class="yith-wccl-tooltip-image">' + tooltip + '</span>' ) );
    };

    WCCL.prototype.handleSelect = function( event ) {

        var obj = event.data.obj;
        var chosenAttributes= {};

        // First loop selectors for know the current chosen attributes.
        obj.$select.each( function() {
            var p = $(this);
            chosenAttributes[this.name] = p.val();
        });
        //Display C&L attributes.
        obj.$select.each( function() {
        var t               = $(this),
            current_attr    = obj.$attr[ this.name ],
            decoded_name    = decodeURIComponent( this.name ),
            select_box      = t.parent().find( '.select_box' ),
            current_option  = [],
            default_label = t.closest('tr').find('th label').data('default-label'),
            selectParent;

        // Set select parent.
        selectParent = t.closest('.select-wrapper');
        if ( ! selectParent.length ) {
            selectParent = t.closest('td');
            if ( ! selectParent.length ) {
                selectParent = t.parent();
            }
        }

        if( typeof current_attr == 'undefined' ) {
            current_attr = obj.$attr[ decoded_name ];
        }
        // Add description
        if ( yith_wccl_general.description && ! obj.$is_loop && ! obj.$wrapper.length && ! obj.$form.find( '.description_' + decoded_name ).length
             && typeof current_attr != 'undefined' && current_attr.descr ) {
            if( t.closest('tr').length ) {
                t.closest('tr').after( '<tr class="description_' + decoded_name + '"><td colspan="2">' + current_attr.descr + '</td></tr>' );
            } else {
                selectParent.append( '<p class="description_' + decoded_name + '">' + current_attr.descr + '</p>' );
            }
        }
        var type    = ( typeof current_attr != 'undefined' ) ? current_attr.type : t.data('type'),
            opt     = ( typeof current_attr != 'undefined' ) ? current_attr.terms : false;

            selectParent.addClass('yith_wccl_layout_'+yith_wccl_general.variation_layout);
            selectParent.siblings( ".label" ).addClass('yith_wccl_layout_'+yith_wccl_general.variation_layout);

        // exit if is not a custom attr
        if ( ( ! obj.$is_loop && ( typeof current_attr == 'undefined' || ! current_attr.terms ) ) || typeof type == 'undefined' || ! type ) {

            // Show option selected on th label.
            t.on('change', function (e) {
                var label = $(this).closest('tr').find('th label').data('default-label');
                var actual_val = $(this).val();

                if( actual_val ) {
                    label = label + yith_wccl_general.attribute_separator + actual_val;
                }
                if( yith_wccl_general.change_label_on_selected_attribute ) {
                    yith_wccl_change_label(label, $(this));
                }

            });
          return;
        }

        t.addClass('yith_wccl_custom').hide();
            selectParent.addClass('yith_wccl_is_custom');

        if( ! select_box.length || ! yith_wccl_general.grey_out ) {
            select_box.remove();
            select_box = $('<div />', {
                'class': 'select_box_' + type + ' select_box ' + t.attr('name')
            }).insertAfter(t);
        }
        t.find('option').each(function () {
            var option_val = $(this).val();
            if( ( opt && typeof opt[option_val] != 'undefined') || ( typeof $(this).data('value') !== 'undefined' && $(this).data('value') !== '' ) ) {
                current_option.push( option_val );

                var o           = $(this),
                    classes     = 'select_option_' + type + ' select_option',
                    value       = opt && typeof opt[option_val] != 'undefined' ? opt[option_val].value : $(this).data('value'),
                    tooltip     = opt && typeof opt[option_val] != 'undefined' ? opt[option_val].tooltip : $(this).data('tooltip'),
                    tooltip_image     = opt && typeof opt[option_val] != 'undefined' ? opt[option_val].tooltip_image : $(this).data('tooltip_image'),
                    option      = select_box.find('[data-value="' + option_val + '"]'),
                    attr_type   = opt && typeof opt[option_val] != 'undefined' ? opt[option_val].type : $(this).data('type');

                type       =  attr_type ? attr_type : type;
                // add options if missing
                if( ! option.length ) {
                    // add selected class if is default
                    if( option_val == t.val() || option_val == t.attr( 'data-default_value' ) ) {
                        classes += ' selected';
                        if( yith_wccl_general.change_label_on_selected_attribute && default_label !== undefined ) {
                                yith_wccl_change_label( default_label + yith_wccl_general.attribute_separator+o[0].label, t );
                        }
                    }

                    option = $('<div/>', {
                        'class': classes,
                        'data-value': option_val,
                        'data-label': o[0].label,
                        'data-tooltip_image': tooltip_image,
                        'data-attr_name': decoded_name,
                    }).appendTo(select_box);

                    // event
                    option.off('click').on('click', function (e) {

                        var inactive = $(this).hasClass('inactive'),
                            selected = $(this).hasClass('selected');

                        if( inactive ) {

                            var current_attribute_name = t.data('attribute_name') || t.attr('name');

                            if( variations_match( obj.$form, $(this).data('value'), current_attribute_name ) ) {
                                t.val('').change();
                            }
                        }

                        if( selected ) {
                            t.val('').change();
                            if( yith_wccl_general.change_label_on_selected_attribute ) {
                                yith_wccl_change_label(default_label, t);
                            }
                        } else {
                            t.val( o.val() ).change();
                            var label = $(this).data('label');
                            if( yith_wccl_general.change_label_on_selected_attribute ) {
                                yith_wccl_change_label(default_label + yith_wccl_general.attribute_separator + label, t);
                            }
                        }

                        $(this).toggleClass( 'selected' );
                        $(this).siblings().removeClass( 'selected' );
                    });

                    // style option
                    obj.styleOption( obj, option, type, value );
                    // add tooltip if any
                    obj.addTooltip( obj, tooltip, option, type, value, tooltip_image );
                }
            }
        });

        var variationData = obj.$form.data('product_variations');

        //TODO Try to save the actual variations in order to know if they are available or not
        select_box.children().each(function () {
            var val = $(this).data('value') + '';
            var opt_attr_name = $(this).closest( '.yith_wccl_is_custom' ).find( 'select.yith_wccl_custom' ).attr( 'name' );
            let chosenAttr = Object.assign({}, chosenAttributes); //Copy original selected attributes.
            var in_stock = true;

            if ( $.inArray( val, current_option ) == '-1' ) {
                $(this).addClass('yith-wccl-remove');
            } else {
                $(this).removeClass('yith-wccl-remove');
            }

            if( !obj.$use_ajax ) {
                // Add inactive cross on out-of-stock attributes.
                chosenAttr[opt_attr_name] = val;
                var variations = obj.findMatchingVariations(variationData, chosenAttr);
                in_stock = variations.find(variation => variation.is_in_stock === true) ? true : false;
                if (!in_stock) {
                    $(this).addClass('inactive');
                    switch (yith_wccl_general.attribute_style) {
                        case 'blur_cross' :
                            $(this).addClass('inactive_cross');
                            break;
                        case 'hide':
                            $(this).addClass('yith-wccl-remove');
                            break;
                    }
                } else {
                    $(this).removeClass('inactive');
                    switch (yith_wccl_general.attribute_style) {
                        case 'blur_cross' :
                            $(this).removeClass('inactive_cross');
                            break;
                        case 'hide':
                            $(this).removeClass('yith-wccl-remove');
                            break;
                    }
                }
            }
        });

        obj.$form.trigger( 'yith_wccl_select_initialized', [ t, current_attr ] );
    });
    };

    WCCL.prototype.setDefaultValue = function( event ) {
        var obj = event.data.obj;

        obj.$select.each( function () {
            $(this).val( $(this).attr( 'data-default_value' ) );
        });

        obj.$select.first().change();
    }

    WCCL.prototype.changeLoopImage = function( obj, variation ){
        if( ! variation ) {
            obj.$image.attr( 'src', obj.$def_image_src );
            if( obj.$def_image_srcset ) {
                obj.$image.attr( 'srcset', obj.$def_image_srcset ); // restore srcset if any
            }
        } else {

            var var_image           = ( typeof variation.image != 'undefined' && variation.image.thumb_src ) ? variation.image.thumb_src : '',
                var_image_srcset    = ( typeof variation.image != 'undefined' && variation.image.srcset ) ? variation.image.srcset : '';

            if( var_image && var_image.length ) {
                obj.$image.attr('src', var_image );
                obj.$image.attr('data-lazy-src', var_image );
            }
            if( yith_wccl_general.set_srcset_on_loop_image && var_image_srcset && var_image_srcset.length && obj.$def_image_srcset ) {
                obj.$image.attr( 'srcset', var_image_srcset );
                obj.$image.attr( 'data-lazy-srcset', var_image_srcset );
            }
        }
    };

    WCCL.prototype.changeSingleImage = function( obj, variation ) {
        var $product_gallery  = obj.$form.closest( '.product' ).find( '.images' ),
            $product_img_wrap = $product_gallery.find( '.woocommerce-product-gallery__image, .woocommerce-product-gallery__image--placeholder' ).eq( 0 ),
            $product_img      = $product_img_wrap.find( '.wp-post-image' ),
            $product_link     = $product_img_wrap.find( 'a' ).eq( 0 );

        $product_img.wc_set_variation_attr( 'src', variation.image.src );
        $product_img.wc_set_variation_attr( 'height', variation.image.src_h );
        $product_img.wc_set_variation_attr( 'width', variation.image.src_w );
        $product_img.wc_set_variation_attr( 'srcset', variation.image.srcset );
        $product_img.wc_set_variation_attr( 'sizes', variation.image.sizes );
        $product_img.wc_set_variation_attr( 'title', variation.image.title );
        $product_img.wc_set_variation_attr( 'alt', variation.image.alt );
        $product_img.wc_set_variation_attr( 'data-src', variation.image.full_src );
        $product_img.wc_set_variation_attr( 'data-large_image', variation.image.full_src );
        $product_img.wc_set_variation_attr( 'data-large_image_width', variation.image.full_src_w );
        $product_img.wc_set_variation_attr( 'data-large_image_height', variation.image.full_src_h );
        $product_img_wrap.wc_set_variation_attr( 'data-thumb', variation.image.src );
        $product_link.wc_set_variation_attr( 'href', variation.image.full_src );
    };

    WCCL.prototype.changeImageOnHover = function( event ) {

        var obj = event.data.obj;

        if( /*obj.$select.length != 1 ||*/ ! yith_wccl_general.image_hover ) {
            return;
        }

        obj.$form.on('mouseenter', '.select_option', function() {
            var value       = $(this).attr("data-value"),
                //attr_name   = //obj.$select.attr('name'),
                attr_name   = $(this).attr('data-attr_name'),
                variation   = variations_match( obj.$form, value, attr_name ); // find variation

            if( ! yith_wccl_general.image_hover_even_selected && ( $(this).hasClass('selected') || $(this).siblings().hasClass('selected') ) ){
                return;
            }

            if( variation && ( ( variation.image && variation.image.src ) || variation.image_src ) ) {
                if( obj.$form.hasClass('in_loop') ) {
                    obj.changeLoopImage( obj, variation );
                } else {
                    obj.changeSingleImage( obj, variation );
                }
            }
        }).on('mouseleave', '.select_option', function() {

            var variation   = variations_match( obj.$form, '', false ); // find variation

            if( variation && ( ( variation.image && variation.image.src ) || variation.image_src ) ) {
                if( obj.$form.hasClass('in_loop') ) {
                    obj.changeLoopImage( obj, variation );
                } else {
                    obj.changeSingleImage( obj, variation );
                }
            } else {
                if( obj.$form.hasClass('in_loop') ) {
                    obj.changeLoopImage( obj, false );
                } else {
                    obj.$form.wc_variations_image_update( false );
                }
            }
        });

    };

    WCCL.prototype.handleCheckVariations = function( event, data, focus ) {
        var obj = event.data.obj;
        if ( ! focus ) {
            if( obj.$found ) {
                event.data.obj.$found = false;
                if( ! obj.$use_ajax ) return;
            }
            if( obj.$changed ) {
                event.data.obj.$changed = false;
                // reset
                obj.resetLoopForm( obj );
            }
        }
    }

    WCCL.prototype.handleFoundVariation = function( event, variation ) {
        var obj = event.data.obj;

        if( obj.$use_ajax ) {
            obj.handleSelect( event );
        } else {
            obj.$select.last().trigger('focusin');
        }

        if ( obj.$is_loop ) {

            if( obj.$changed ) {
                obj.resetLoopForm( obj );
            }

            // found it!
            event.data.obj.$changed = true;
            event.data.obj.$found = true;

            // change image
            obj.changeLoopImage( obj, variation );

            let selectLength = obj.$select.length;

            if( selectLength === obj.$attr_number ) { //Prevent to change if the attribute number showed are less than the registered.

                if (variation.is_purchasable) {
                    // change price
                    if (variation.price_html) {
                        obj.$wrapper.find(yith_wccl_general.wrapper_price_shop).replaceWith(variation.price_html);
                    }

                    // show qty input
                    if (obj.$input_qty.length) {
                        obj.$input_qty.show();
                    }
                    // change button and add event add to cart

                    if (variation.is_in_stock) {

                        obj.$button.html(yith_wccl_general.add_cart);

                        obj.$button.off('click').on('click', {
                            variation: variation.variation_id,
                            select: obj.$select
                        }, yith_wccl_add_cart);
                    }
                }

                // add availability
                //span.price
                obj.$wrapper.find('.variations_form').after($(variation.availability_html).addClass('ywccl_stock'));

                // set active variation
                obj.$form.data('active_variation', variation.variation_id);

                $(document).trigger('ywccl_found_variation_in_loop', [variation, obj.$button, yith_wccl_general.add_cart]);
            } else {
                //No variation is selected, we'll update only the button link.
                let all_select = obj.$form.find( '.variations select' ),
                    settings = [];

                $.each( all_select, function(){
                    var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );
                    if( $(this).val() !== '' ) {
                        settings[attribute_name] = $(this).val();
                    }
                });
                var newURL = new URL(obj.$button.attr('href'));

                for (var key in settings) {
                    var value = settings[key];
                    if (value) {
                        newURL.searchParams.set(key, value); //newURL.searchParams.append(key, value);
                    }
                }
                obj.$button.attr('href',newURL);
            }
        }
        $(document).trigger('ywccl_after_found_variation', [variation, obj.$button, yith_wccl_general.add_cart]);
    };

    WCCL.prototype.handleVariationGallery = function( event, variation ) {

        var obj             = event.data.obj,
            gallery_wrap    = $( yith_wccl_general.single_gallery_selector ),
            id;

        if( obj.$is_loop || ! gallery_wrap.length ) {
            return;
        }

        if( obj.$xhr !== null ) {
            obj.$xhr.abort();
        }

        id = typeof variation != 'undefined' ? variation.variation_id : obj.$form.find( 'input[name="product_id"]' ).val();
        if( ! id || typeof id == 'undefined' ) {
            return;
        }

        if ( undefined !== obj.variations_gallery[ id ] ) {
            obj.loadVariationGallery( obj.variations_gallery[ id ], gallery_wrap, variation );
        }
        else {
            obj.$xhr = $.ajax({
                url: yith_wccl_general.ajaxurl.toString().replace( '%%endpoint%%', yith_wccl_general.actionVariationGallery ),
                data: {
                    action: yith_wccl_general.actionVariationGallery,
                    id : id,
                    context: 'frontend'
                },
                type: 'POST',
                dataType: 'html',
                beforeSend: function(){
                    gallery_wrap.addClass( 'loading-gallery' );
                },
                success: function( html ){
                    gallery_wrap.removeClass( 'loading-gallery' );
                    obj.$xhr = null;
                    if( html ) {
                        // store variation gallery to improve performance
                        obj.variations_gallery[ id ] = html;
                        obj.loadVariationGallery( html, gallery_wrap, variation );
                    }

                    // Support for Avda custom gallery
                    if( typeof initAvadaWoocommerProductGallery === "function" ){
                        initAvadaWoocommerProductGallery();
                    }
                }
            });
        }
    }

    WCCL.prototype.loadVariationGallery = function( html, gallery_wrap, variation ) {
        gallery_wrap.replaceWith( html ).fadeIn();
        if ( typeof wc_single_product_params !== 'undefined' ) {
            // reload gallery
            $( yith_wccl_general.single_gallery_selector ).wc_product_gallery( wc_single_product_params );
        }

        this.$form.wc_variations_image_update( variation );

        $(document).trigger( 'yith_wccl_product_gallery_loaded' );
        // uncode theme compatibility
        $(document).trigger( 'uncode-quick-view-loaded' );
        // woodmart theme compatibility
        $(document).trigger('wdReplaceMainGallery');
        $(document).trigger('wdResetVariation');
        if( typeof woodmartThemeModule !== 'undefined' ){
            woodmartThemeModule.productImages();
        }
    }

    WCCL.prototype.resetLoopForm = function( obj ){
        // reset image
        obj.changeLoopImage( obj, false );
        obj.$wrapper.find( yith_wccl_general.wrapper_price_shop ).replaceWith( obj.$price_html );
        obj.$wrapper.find('.ywccl_stock').remove();

        if( obj.$input_qty.length ){
            obj.$input_qty.hide();
        }

        obj.$button.html( obj.$button_html )
            .off( 'click', yith_wccl_add_cart )
            .removeClass( 'added' )
            .next('.added_to_cart').remove();

        // set active variation
        obj.$form.data('active_variation', '' );

        $(document).trigger( 'yith_wccl_reset_loop_form',[obj.$button] );
    }

    WCCL.prototype.resetForm = function( obj ) {
        obj.$form.find( 'div.select_option' ).removeClass( 'selected inactive' );
        obj.$select.val('').change();
        obj.$form.trigger( 'reset_data' );
    };

    WCCL.prototype.onReset = function( event ) {
        event.data.obj.$form.find('.select_option.selected').removeClass('selected inactive');
    };

    WCCL.prototype.onAddToCart = function ( event ) {

       if ( $( this ).is('.wc-variation-selection-needed') ) {

           event.data.obj.$select.each(function (){
               $(this).closest('tr').find('th').removeClass('yith-wccl-no-selected');
               if( !$(this).val()  ) {
                   $(this).closest('tr').find('th').addClass('yith-wccl-no-selected');
               }
           });
       }
    };

    WCCL.prototype.findMatchingVariations = function( variations, attributes ) {
        var matching = [];
        for (var i = 0; i < variations.length; i++) {
            var variation = variations[i];
            if (this.isMatch(variation.attributes, attributes)) {
                matching.push(variation);
            }
        }
        return matching;
    };

    /**
     * See if attributes match.
     * @return {Boolean}
     */
    WCCL.prototype.isMatch = function( variation_attributes, attributes ) {
        var match = true;
        for ( var attr_name in variation_attributes ) {
            if ( variation_attributes.hasOwnProperty( attr_name ) ) {
                var val1 = variation_attributes[ attr_name ];
                var val2 = attributes[ attr_name ];
                if ( val1 !== undefined && val2 !== undefined && typeof val2 === "string" && val1.length !== 0 && val2.length !== 0 && val1 !== val2 ) {
                    match = false;
                }
            }
        }
        return match;
    };

    WCCL.prototype.getChosenAttributes = function(form) {
        var data   = {};
        var count  = 0;
        var chosen = 0;

        if ( form.$use_ajax ) {
            return;
        }

        this.$select.each( function() {
            var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );
            var value          = $( this ).val() || '';

            if ( value.length > 0 ) {
                chosen ++;
            }

            count ++;
            data[ attribute_name ] = value;
        });

        return {
            'count'      : count,
            'chosenCount': chosen,
            'data'       : data
        };
    };

    WCCL.prototype.onUpdateAttributes = function(event) {
        var form              = event.data.obj;

        if ( form.$use_ajax ) {
            return;
        }

        var attributes        = form.getChosenAttributes(form),
            currentAttributes = attributes.data;



        // Loop through selects and disable/enable options based on selections.
        form.$select.each( function( index, el ) {
            var current_attr_select     = $( el ),
                current_attr_name       = current_attr_select.data( 'attribute_name' ) || current_attr_select.attr( 'name' ),
                show_option_none        = $( el ).data( 'show_option_none' ),
                option_gt_filter        = ':gt(0)',
                attached_options_count  = 0,
                new_attr_select         = $( '<select/>' ),
                selected_attr_val       = current_attr_select.val() || '',
                selected_attr_val_valid = true;

            // Reference options set at first.
            if ( ! current_attr_select.data( 'attribute_html' ) ) {
                var refSelect = current_attr_select.clone();

                refSelect.find( 'option' ).removeAttr( 'attached' ).prop( 'disabled', false ).prop( 'selected', false );

                // Legacy data attribute.
                current_attr_select.data(
                    'attribute_options',
                    refSelect.find( 'option' + option_gt_filter ).get()
                );
                current_attr_select.data( 'attribute_html', refSelect.html() );
            }

            new_attr_select.html( current_attr_select.data( 'attribute_html' ) );

            // The attribute of this select field should not be taken into account when calculating its matching variations:
            // The constraints of this attribute are shaped by the values of the other attributes.
            var checkAttributes = $.extend( true, {}, currentAttributes );

            checkAttributes[ current_attr_name ] = '';
            var variations = form.findMatchingVariations( event.data.obj.$form.data('product_variations'), checkAttributes );

            // Loop through variations.
            for ( var num in variations ) {
                if ( typeof( variations[ num ] ) !== 'undefined' ) {
                    var variationAttributes = variations[ num ].attributes;

                    for ( var attr_name in variationAttributes ) {
                        if ( variationAttributes.hasOwnProperty( attr_name ) ) {
                            var attr_val         = variationAttributes[ attr_name ],
                                variation_active = '';

                            if ( attr_name === current_attr_name ) {
                                if ( variations[ num ].variation_is_active ) {
                                    variation_active = 'enabled';
                                }

                                if ( attr_val ) {
                                    // Decode entities.
                                    attr_val = $( '<div/>' ).html( attr_val ).text();

                                    // Attach to matching options by value. This is done to compare
                                    // TEXT values rather than any HTML entities.
                                    var $option_elements = new_attr_select.find( 'option' );
                                    if ( $option_elements.length ) {
                                        for (var i = 0, len = $option_elements.length; i < len; i++) {
                                            var $option_element = $( $option_elements[i] ),
                                                option_value = $option_element.val();

                                            if ( attr_val === option_value ) {
                                                $option_element.addClass( 'attached ' + variation_active );
                                                break;
                                            }
                                        }
                                    }
                                } else {
                                    // Attach all apart from placeholder.
                                    new_attr_select.find( 'option:gt(0)' ).addClass( 'attached ' + variation_active );
                                }
                            }
                        }
                    }
                }
            }

            // Count available options.
            attached_options_count = new_attr_select.find( 'option.attached' ).length;

            // Check if current selection is in attached options.
            if ( selected_attr_val ) {
                selected_attr_val_valid = false;

                if ( 0 !== attached_options_count ) {
                    new_attr_select.find( 'option.attached.enabled' ).each( function() {
                        var option_value = $( this ).val();

                        if ( selected_attr_val === option_value ) {
                            selected_attr_val_valid = true;
                            return false; // break.
                        }
                    });
                }
            }

            // Detach the placeholder if:
            // - Valid options exist.
            // - The current selection is non-empty.
            // - The current selection is valid.
            // - Placeholders are not set to be permanently visible.
            if ( attached_options_count > 0 && selected_attr_val && selected_attr_val_valid && ( 'no' === show_option_none ) ) {
                new_attr_select.find( 'option:first' ).remove();
                option_gt_filter = '';
            }

            // Detach unattached.
            new_attr_select.find( 'option' + option_gt_filter + ':not(.attached)' ).remove();

            // Finally, copy to DOM and set value.
            current_attr_select.html( new_attr_select.html() );
            current_attr_select.find( 'option' + option_gt_filter + ':not(.enabled)' ).prop( 'disabled', true );

            // Choose selected value.
            if ( selected_attr_val ) {
                // If the previously selected value is no longer available, fall back to the placeholder (it's going to be there).
                if ( selected_attr_val_valid ) {
                    current_attr_select.val( selected_attr_val );
                } else {
                    current_attr_select.val( '' ).trigger( 'change' );
                }
            } else {
                current_attr_select.val( '' ); // No change event to prevent infinite loop.
            }
        });

        // Custom event for when variations have been updated.
        form.$form.trigger( 'woocommerce_update_variation_values' );
    };

    WCCL.prototype.init = function( event ) {

        var obj = event.data.obj;

        obj.$form.on( 'click.wc-variation-form', '.reset_variations', { obj: obj }, obj.onReset );
        obj.$form.on( 'woocommerce_update_variation_values', { obj: obj }, obj.handleSelect );
        obj.$form.one( 'yith_wccl_select_initialized', { obj: obj }, obj.changeImageOnHover );
        obj.$form.on( 'check_variations', { obj: obj }, obj.handleCheckVariations );
        obj.$form.on( 'found_variation', { obj: obj }, obj.handleFoundVariation );
        if( yith_wccl_general.enable_handle_variation_gallery ){
            obj.$form.on( 'found_variation', {obj: obj}, obj.handleVariationGallery );
            obj.$form.on( 'reset_image', {obj: obj}, obj.handleVariationGallery );
        }
        obj.$form.on( 'click', '.single_add_to_cart_button', { obj: obj }, obj.onAddToCart );

        obj.$select.each( function(){

            // Fix compatibility to avoid conflict with Product Bundles - Variation Bundles
            if( $(this).parents( '.bundled_product' ).length ){
                return;
            }

            var val = $(this).attr( 'data-default_value' );
            $(this).removeAttr( 'data-default_value' );
            $(this).val( val );

            //set label.
            $(this).closest('tr').find('th label').attr('data-default-label', $(this).closest('tr').find('th label').text() );

        });
        obj.$form.on( 'update_variation_values.wc-variation-form', { obj: obj }, obj.onUpdateAttributes );

        // force start select
        obj.handleSelect( event );

    }

    // retrocompatibility
    $.yith_wccl = function( attr ) {
        forms = $( '.variations_form.cart:not(.initialized), .owl-item.cloned .variations_form, form.cart.ywcp_form_loaded' );
        // prevent undefined attr error
        if( typeof attr == 'undefined' ) {
            attr = [];
        }

        forms.each(function (){
            new WCCL( $(this), attr );
        });
    };

    // plugin compatibility
    $(document).on( yith_wccl_general.plugin_compatibility_selectors, function() {

        if( typeof $.yith_wccl != 'undefined' && typeof $.fn.wc_variation_form != 'undefined' ) {
            // not initialized
            $(document).find( '.variations_form:not(.initialized), .owl-item.cloned .variations_form' ).each( function() {
                $(this).wc_variation_form();
            });

            // prevent undefined attr error
            if( typeof attr == 'undefined' ) {
                attr = [];
            }

            $.yith_wccl(attr);
        }
    });

    // Astra infinite scrolling. Facetwp load more
    $(window).on('astWooCommerceAjaxPostsAdded facetwp-loaded', function() {
        if( typeof $.yith_wccl != 'undefined' && typeof $.fn.wc_variation_form != 'undefined' ) {
            // not initialized
            $(document).find( '.variations_form:not(.initialized)' ).each( function() {
                $(this).wc_variation_form();
            });

            $.yith_wccl([]);
        }
    })

    // reinit for woocommerce quick view
    $( 'body' ).on( 'quick-view-displayed', function() {
        var attr_qv = $('.pp_woocommerce_quick_view').find('.yith-wccl-data').data('attr');
        if( attr_qv ) {
            $.yith_wccl(attr_qv);
        }
    });

    // Fix for Flatsome Infinite Scrolling
    $('.shop-container > .products').on('append.infiniteScroll',function(){
        $(document).find( '.variations_form:not(.initialized), .owl-item.cloned .variations_form' ).each( function() {
            $(this).wc_variation_form();
        });

        // prevent undefined attr error
        if( typeof attr == 'undefined' ) {
            attr = [];
        }

        $.yith_wccl(attr);

    });

    // Re-init scripts on gallery loaded
    $(document).on( 'yith_wccl_product_gallery_loaded', function(){
        if( typeof mkdf != 'undefined' && typeof mkdf.modules.common.mkdfPrettyPhoto === "function" ) {
            var item = $('.mkdf-woo-single-page.mkdf-woo-single-has-pretty-photo .images .woocommerce-product-gallery__image');
            if( item.length ) {
                item.children('a').attr('data-rel', 'prettyPhoto[woo_single_pretty_photo]');
                mkdf.modules.common.mkdfPrettyPhoto();
            }
        }

        if( typeof Flatsome != 'undefined' ){
            Flatsome.attach( $( '.product-gallery' ) );
            // foce zoom button to work
            $( '.zoom-button' ).click( function ( ev ) {
                ev.preventDefault();
                $( '.product-gallery-slider' ) .find( '.is-selected a' ).click();
            });
        }
    });

    //Prevent windows.
    $('.single_add_to_cart_button').on('click',function(e){
        if ( $( this ).is('.disabled') ) {
            e.preventDefault();
            if ( $( this ).is('.wc-variation-selection-needed') ) {
                let form = $('.variations_form.cart');
                let selects = form.find( '.variations select' );

                selects.each(function (){
                    $(this).closest('tr').find('th').removeClass('yith-wccl-no-selected');
                    $(this).closest('tr').find('.yith-wccl-no-selected-msg').remove();
                    if( !$(this).val()  ) {
                        $(this).closest('tr').find('th').addClass('yith-wccl-no-selected');
                        $(this).closest('td').append('<div class="yith-wccl-no-selected-msg"><small>'+yith_wccl_general.error_no_selected+'</small></div>');

                    }
                });

                return false;
            }
        }
    });

    // START
    $(document).ready( function(){
        $.yith_wccl();
    });


})( jQuery, window, document );
