/**
 * Admin
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Colors and Labels Variations
 * @version 1.1.0
 */
jQuery(document).ready(function($) {
    "use strict";

    var colorpicker = $( '.ywccl[data-type="colorpicker"]' ),
        image       = $( '.ywccl[data-type="image"]'),
        // apply colorpicker
        yith_wccl_colorpicker = function( colorpicker ) {
            colorpicker.each( function() {

                $(this).wpColorPicker();

                if( $(this).hasClass('hidden_empty') && ! $(this).val() ) {
                    $(this).closest('.wp-picker-container').hide();
                }
            });
        },
        // apply upload image
        yith_wccl_upload = function( image ) {

            image.each(function(){

                var button = $("<input type='button' name='' id='term_value_button' class='button' value='Upload' />");
                button.insertAfter(this);

                //image uploader
                button.on('click', function(e) {

                    e.preventDefault();

                    var t = $(this),
                        custom_uploader,
                        id = t.attr('id').replace('_button', '');

                    //If the uploader object has already been created, reopen the dialog
                    if (custom_uploader) {
                        custom_uploader.open();
                        return;
                    }

                    var custom_uploader_states = [
                        // Main states.
                        new wp.media.controller.Library({
                            library:   wp.media.query(),
                            multiple:  false,
                            title:     'Choose Image',
                            priority:  20,
                            filterable: 'uploaded'
                        })
                    ];
                    // Create the media frame.
                    custom_uploader = wp.media.frames.downloadable_file = wp.media({
                        // Set the title of the modal.
                        title: 'Choose Image',
                        library: {
                            type: ''
                        },
                        button: {
                            text: 'Choose Image'
                        },
                        multiple: false,
                        states: custom_uploader_states
                    });
                    //When a file is selected, grab the URL and set it as the text field's value
                    custom_uploader.on( 'select' , function() {
                        var attachment = custom_uploader.state().get( 'selection' ).first().toJSON();
                        //$("#" + id).val( attachment.url );
                        $('#yith_wccl_dialog_form '+".ywccl").val( attachment.url );

                    });

                    //Open the uploader dialog
                    custom_uploader.open();
                });
            });
        };

    yith_wccl_colorpicker( colorpicker );
    yith_wccl_upload( image );


    // ADD DESCRIPTION TO ATTRIBUTE FORM

    var form_attr = $( '.product_page_product_attributes .woocommerce form' );

    if( typeof yith_wccl_admin != 'undefined' && yith_wccl_admin.html )
        form_attr.find('.form-field').last().after( yith_wccl_admin.html );


    // FORM DIALOG

    var container           = $('.product_attributes'),
        dialog_wrap         = $( '#yith_wccl_dialog_form' ),
        dialog_error        = dialog_wrap.find( '.dialog_error' ),
        // save original form
        dialog_form_o       = dialog_wrap.find( 'form').clone(),
        reset_form          = function() {
            // clone original form and change with current
            var clone = dialog_form_o.clone();
            dialog_wrap.find( 'form' ).replaceWith( clone );
        };

    // Add a new attribute (via ajax)
    container.on( 'click', 'button.yith_wccl_add_new_attribute', function(e) {
        e.preventDefault();

        var wrapper     = $(this).closest('.woocommerce_attribute'),
            attribute   = wrapper.data( 'taxonomy' ),
            type        = $(this).data( 'type_input' ),
            form        = dialog_wrap.find( 'form' ),
            term_value  = form.find( '#term_value, #term_value_2' );

        // replace standard term value
        term_value.attr( 'data-type', type );

        // check type
        if( type == 'colorpicker' ) {
            yith_wccl_colorpicker( term_value );
            double_color( form.find( '.ywccl_add_color_icon' ) );
        }
        else{
            // remove not used input
            form.find( '#term_value_2, .ywccl_add_color_icon, br').remove();
            if( type == 'image' ) {
                yith_wccl_upload(term_value);
            }
        }

        // init dialog
        dialog_wrap.dialog({
            width: 350,
            modal: true,
            dialogClass: 'yith_wccl_dialog_modal',
            buttons: {
                'Add': function(){
                    $(document).find( '#yith_wccl_dialog_form form' ).trigger( "submit", [ wrapper, attribute ] );
                },
                Cancel: function() {
                    dialog_wrap.dialog( "close" );
                }
            },
            close: function() {
                reset_form();
            }
        });

        return false;
    });

    $(document).on("submit", '#yith_wccl_dialog_form form', function (e, wrapper, attribute) {
        e.preventDefault();

        var t       = $(this),
            form = t.serializeArray(),
            data;

        // add action and taxonomy
        form.push({ name: "action", value: 'yith_wccl_add_new_attribute' }, { name: "taxonomy", value: attribute } );
        data = $.param( form );

        t.block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});

        $.post( yith_wccl_admin.ajaxurl, data, function (response) {

            // unblock form
            t.unblock();

            if ( response.error ) {
                // Error
                dialog_error.html( response.error );
            }
            else if ( response.value ) {
                // Remove error
                dialog_error.html('');
                // Success
                wrapper.find('select.attribute_values').append('<option value="' + response.value + '" selected="selected">' + response.name + '</option>');
                wrapper.find('select.attribute_values').change();

                // close dialog
                dialog_wrap.dialog("close");
            }

        });

        return false;

    });

    var double_color = function( plus ){

        plus.off('click').on( 'click', function(){
            var t = $(this),
                tdata = t.data('content'),
                input_container = $(this).nextAll( '.wp-picker-container' ),
                input_clear = input_container.find( '.wp-picker-clear' );


            // change button content
            t.data( 'content', t.html() );
            t.html( tdata );

            input_clear.click();
            input_container.toggle();
        });
    };

    double_color( $( '.ywccl_add_color_icon' ) );

    // HANDLE PRODUCT VARIATION IMAGE GALLERY

    var updateGalleryInput = function( gallery ){
        var input   = gallery.find( '.yith_wccl_variation_gallery_values' ),
            images  = gallery.find( "li.image:not('.add')" ),
            value   = [];

        $.each( images, function(){
            value.push( $(this).data('value') );
        });

        input.attr( 'value', value.join(',') ).change();
    }

    $(document).on( 'woocommerce_variations_loaded woocommerce_variations_added', function(){
        $( ".woocommerce_variation:not('.initialized')" ).each( function( index, el ) {
            var gallery = $(el).find( '.yith-wccl-variation-gallery-wrapper' ),
                options = $(el).find( '.form-row-full.options' ).first();

            gallery.insertBefore( options );
            // Image ordering.
            gallery.find( '.yith-wccl-variation-gallery-images' ).sortable({
                items: "li.image:not('.add')",
                cursor: 'move',
                scrollSensitivity: 40,
                forcePlaceholderSize: true,
                forceHelperSize: false,
                helper: 'clone',
                opacity: 0.65,
                placeholder: 'yith-wccl-sortable-placeholder',
                start: function( event, ui ) {
                    ui.item.css( 'background-color', '#f6f6f6' );
                },
                stop: function( event, ui ) {
                    ui.item.removeAttr( 'style' );
                },
                update: function() {
                    updateGalleryInput( gallery );
                }
            });

            $(this).addClass( 'initialized' );
        });
    });

    $(document).on( 'click', '.yith-wccl-variation-gallery-images .remove', function (event) {
        event.preventDefault();
        var gallery = $(this).closest( '.yith-wccl-variation-gallery-wrapper' );
        $(this).closest( '.image' ).remove();
        updateGalleryInput( gallery );
    });

    $(document).on( 'click', '.add-variation-gallery-image', function( event ){
        event.preventDefault();

        if( typeof wp == 'undefined' )
            return;

        var button  = $(this),
            html    = '',
            gallery = button.closest( '.yith-wccl-variation-gallery-wrapper' ),
            images  = gallery.find( '.yith-wccl-variation-gallery-images' ),
            index   = button.attr( 'data-index' ),
            media   = wp.media({
                title: woocommerce_admin_meta_boxes_variations.i18n_choose_image,
                button: {
                    text: woocommerce_admin_meta_boxes_variations.i18n_set_image
                },
                library: { type: 'image' },
                multiple : true
            });

        media.on( 'select', function () {
            var attachment = media.state().get( 'selection' ).toJSON(),
                html = attachment.map( function ( image ) {
                    if( image.type !== 'image' || images.find( '[data-value="'+ image.id + '"]' ).length ) {
                        return '';
                    }

                    var id          = image.id,
                        url         = ( image.sizes && image.sizes.thumbnail ) ? image.sizes.thumbnail.url : image.url,
                        template    = wp.template('yith-wccl-variation-gallery-image');

                    return template({ id: id, url: url });
                }).join('');

            images.find( '.image.add' ).before( html );
            updateGalleryInput( gallery );
        });

        media.open();
    });

    /** ------------------------------------------------------------------------
     *  Settings Section Box - Toggle for attributes
     * ------------------------------------------------------------------------- */
    $( document ).on( 'click', '.yith-wccl-section-row-title', function ( event ) {
        var _toggle  = $( event.target ),
            _section = _toggle.closest( '.yith-wccl-attribute-row-box' ),
            _content = _section.find( '.yith-wccl-attribute-section-row-content' ),
            _minus   = _section.find( '.yith-wccl-minus' ),
            _plus    = _section.find( '.yith-wccl-plus' );

        if ( _section.is( '.yith-wccl-closed' ) ) {
            _content.slideDown( 400 );
            _minus.toggleClass('yith-wccl-toggle-disable');
            _plus.toggleClass('yith-wccl-toggle-disable');

        } else {
            _content.css( { display: 'block' } );
            _content.slideUp( 400 );
            _minus.toggleClass('yith-wccl-toggle-disable');
            _plus.toggleClass('yith-wccl-toggle-disable');
        }

        _section.toggleClass( 'yith-wccl-closed' );
    } );

    /** ------------------------------------------------------------------------
     *  Settings Section Box - Toggle for terms
     * ------------------------------------------------------------------------- */
    $( document ).on( 'click', '.yith-wccl-term-section-row-title', function ( event ) {
        var _toggle  = $( event.target ),
            _section = _toggle.closest( '.yith-wccl-term-row-box' ),
            _content = _section.find( '.yith-wccl-term-section-row-content' ),
            _toggle_icon   = _section.find( '.yith-wccl-box-toggle-term svg' );

        if ( _section.is( '.yith-wccl-closed' ) ) {
            _content.slideDown( 400 );
            _toggle_icon.css( { transform : 'rotate(0deg)' } );


        } else {
            _content.css( { display: 'block' } );
            _content.slideUp( 400 );
            _toggle_icon.css( { transform : 'rotate(180deg)' } );
        }

        _section.toggleClass( 'yith-wccl-closed' );
    } );
    /** ------------------------------------------------------------------------
     *  Settings clear terms value when add a new term on attributes page
     * ------------------------------------------------------------------------- */
    $('#submit').on( 'click', function(){
        if ( $('#addtag').length ) {
            setTimeout(function(){
                //Set it as default.
                $('input[type="checkbox"]').prop( "checked", false );
                $('.yith-plugin-fw-media .yith-plugin-fw-media__preview__action--delete').trigger('click');
                $('.wp-picker-default').trigger('click');
            }, 300);

        }
    });


    //Handle visibility on attribute section
    var ywcclFieldsVisibility = {
        showPrefix        : '.ywccl_show_if_',

        conditions        : {
            upload_image            : 'upload_image',
            use_for_tooltip         : 'use_for_tooltip',

            no_image_color          : 'no_image_color',
            image_color             : 'image_color',
            dual_color              : 'dual_color',

            use_for_label           : 'use_for_label',
            override_global         : 'override_global',
        },
        dom               : {
            moveImageFields         : $( '.yith-wccl-term-type-image' ),
            //tooltipImage            : $( '#yith_wccl_term_tooltip_image_type' ),
            tooltipImage            : $( '.ywccl_tooltip_image_type' ),

            useForTooltip           : $( '#yith_wccl_term_use_for_tooltip' ),

            moveLabelFields         : $( '.yith-wccl-term-type-label' ),
            useForlabel             : $( '#yith_wccl_term_use_for_label' ),

            moveColorFields         : $( '.yith-wccl-term-type-colorpicker' ),
            //swatchTypeImage         : $( '#yith_wccl_term_swatch_type'),
            swatchTypeImage         : $( '.ywccl_swatch_type'),

            //Overrideglobal
            overrideGlobal          : $('.yith-wccl-override-global-term input'),
        },
        init              : function () {
            var self = ywcclFieldsVisibility;

            /**
             * Image attribute handler
             */
            self.dom.moveImageFields.insertAfter( ".term-name-wrap" );
            // Tooltip Image selector
            self.dom.tooltipImage.on( 'change', function () {
                var is_in_product =  $(this).closest('.yith-wccl-term-section-row-content').hasClass('yith-wccl-term-section-row-content');
                if( is_in_product ) {
                    self.handle( self.conditions.upload_image, 'upload_image' === $(this).closest('.yith-wccl-term-section-row-content').find(self.dom.tooltipImage).val(), $(this), is_in_product );
                } else {
                    self.handle( self.conditions.upload_image, 'upload_image' === self.dom.tooltipImage.val() );
                }
            } ).trigger( 'change' );

            // Use for tooltip checkbox
            self.dom.useForTooltip.on( 'change', function () {
                self.handle( self.conditions.use_for_tooltip, true !== self.dom.useForTooltip.is(":checked") );
            } ).trigger( 'change' );
            /**
             * Label attribute handler
             */
            self.dom.moveLabelFields.insertAfter( ".term-name-wrap" );
            self.dom.useForlabel.on( 'change', function () {
                self.handle( self.conditions.use_for_label, true !== self.dom.useForlabel.is(":checked") );
            } ).trigger( 'change' );
            /**
             * ColorPicker attribute handler
             */
            self.dom.moveColorFields.insertAfter( ".term-name-wrap" );
            self.dom.swatchTypeImage.on( 'change', function () {

                var is_in_product =  $(this).closest('.yith-wccl-term-section-row-content').hasClass('yith-wccl-term-section-row-content');

                if( is_in_product ) {
                    self.handle( self.conditions.dual_color, 'dual_color' === $(this).closest('.yith-wccl-term-section-row-content').find(self.dom.swatchTypeImage).val(), $(this), is_in_product );
                    self.handle( self.conditions.image_color, 'image_color' === $(this).closest('.yith-wccl-term-section-row-content').find(self.dom.swatchTypeImage).val(), $(this), is_in_product );
                    self.handle( self.conditions.no_image_color, 'image_color' !== $(this).closest('.yith-wccl-term-section-row-content').find(self.dom.swatchTypeImage).val(), $(this), is_in_product );
                } else {
                    self.handle( self.conditions.no_image_color, 'image_color' !== self.dom.swatchTypeImage.val(), $(this) );
                    self.handle( self.conditions.image_color, 'image_color' === self.dom.swatchTypeImage.val(), $(this) );
                    self.handle( self.conditions.dual_color, 'dual_color' === self.dom.swatchTypeImage.val(), $(this) );
                }

            } ).trigger( 'change' );

            //Global.
            self.dom.overrideGlobal.on('change', function () {
                var is_in_product =  $(this).closest('.yith-wccl-term-section-row-content').hasClass('yith-wccl-term-section-row-content');
                var condition = 'yes' === $(this).closest('.yith-wccl-term-section-row-content').find(self.dom.overrideGlobal).val();
                if( is_in_product ) {
                    self.handle( self.conditions.override_global, condition, $(this), is_in_product );
                    if( condition ) {
                        $(this).closest('.yith-wccl-term-section-row-content').find( self.dom.tooltipImage ).trigger('change');
                        $(this).closest('.yith-wccl-term-section-row-content').find( self.dom.swatchTypeImage ).trigger('change');
                    }
                }

            }).trigger('change');
        },
        handle            : function ( target, condition, section, is_in_product ) {
            var targetHide    = ( undefined !== section && is_in_product ) ? section.closest('.yith-wccl-term-section-row-content').find( ywcclFieldsVisibility.showPrefix + target ) :  ywcclFieldsVisibility.showPrefix + target;

            if ( condition ) {
                $( targetHide ).show();
            } else {
                $( targetHide ).hide();
            }
        },
    };
    ywcclFieldsVisibility.init();

    /**
     * Blank state link
     * **/
    $(document).on('click','.yith-wccl-attributes-link', function (e) {
        e.preventDefault();
        $('.attribute_options a').trigger('click');
    });
});