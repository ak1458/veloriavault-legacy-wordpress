/**
 * global wdp_export_items, wdpTools
 * */

jQuery(document).ready(function () {

    setTimeout( function() {
      jQuery('.import-notice').hide();
    }, 3500);

    jQuery( '.section_choice' ).click( function () {
      jQuery( '.section_choice' ).removeClass( 'active' );
      jQuery( this ).addClass( 'active' );

      jQuery( '.tools-section' ).removeClass( 'active' );
      jQuery( '#' + jQuery( this ).data( 'section' ) + '_section' ).addClass( 'active' );

      window.location.href = jQuery( this ).attr( 'href' );
    } );

    jQuery( '.wdp-export-bulk-ranges' ).click(function () {

      window.open(ajaxurl + '?action=export-csv-bulk-ranges' + '&' + wdpTools.security_param + '=' + wdpTools.security, '_blank')
    });

    jQuery( '.wdp-export-json-settings' ).click(function () {

      var export_select = jQuery('#wdp-export-select').val();
      window.open(ajaxurl + '?action=export-json-settings' + '&' + 'export_select=' +export_select + '&' + wdpTools.security_param + '=' + wdpTools.security, '_blank')
    });

    setTimeout( function () {
      if ( window.location.hash.indexOf( 'section' ) !== - 1 ) {
        jQuery( '.section_choice[href="' + window.location.hash + '"]' ).click();
      } else {
        jQuery( '.section_choice' ).first().click();
      }
    }, 0 );

    if (window.wdp_export_items) {
        var wdp_export_items = JSON.parse(window.wdp_export_items);

        jQuery('#wdp-export-select').change(function () {
            var selected = jQuery(this).val();
            jQuery('#wdp-export-data').val(JSON.stringify(wdp_export_items[selected]['data'], null, 5));
        }).change();


        jQuery('#wdp-export-data').click(function () {
            jQuery(this).select();
        });

      jQuery('#wdp-import').click(function (e) {
        try {
          JSON.parse(jQuery('#wdp-import-data').val());
        } catch ($e) {
          showError($e);
          e.preventDefault();
          return false;
        }
      });
    }

    function showError($e) {
      var message = '';
      if ( $e instanceof Error) {
        message = $e.message;
      } else if ( $e instanceof String) {
        message = $e;
      } else {
        return;
      }

      let errorEl = jQuery("<p class='import-notice notice-fail'>" + message + "</p>")
      errorEl.insertBefore(jQuery("label[for='wdp-import-data']"));
      setTimeout(function() {
        errorEl.remove();
      }, 10000);
    }

    jQuery('#wdp-import-json').click(function (e) {
      e.preventDefault()
      jQuery('#json-to-import').trigger('click')
    });

    jQuery('#json-to-import').change(function (e) {
      const file = e.target.files[0];

      if (!file) {
        return
      }

      const reader = new FileReader();
      reader.addEventListener('load', function() {
        try {
          JSON.parse(reader.result);
          jQuery('#wdp-import-data').val(reader.result)
        } catch (err) {
          showError(err)
          return false
        }
      })

      reader.readAsText(file);
    });

  jQuery('#rules-to-import-csv').change(function(){
    if(jQuery(this).val() !== undefined){
      jQuery('#wdp-import-csv').removeAttr('disabled');
    }
  });

  jQuery('#wdp-migrate-common-to-product-only').click(function () {
    let props = {
      action: 'migrate-common-to-product-only',
    };
    props[wdpTools.security_param] = wdpTools.security

    jQuery.post({
      url: ajaxurl,
      data: props,
      dataType: 'json',
      success: (data) => {
        jQuery(this).closest('.tools-section').find('.migration-rules-affected').hide();
        jQuery(this).closest('.migrate-rules-div').find('.migration-rules-affected').text(data.data).show();
      }
    });
  });

  jQuery('#wdp-migrate-product-only-to-common').click(function () {
    let props = {
      action: 'migrate-product-only-to-common',
    };
    props[wdpTools.security_param] = wdpTools.security

    jQuery.post({
      url: ajaxurl,
      data: props,
      dataType: 'json',
      success: (data) => {
        jQuery(this).closest('.tools-section').find('.migration-rules-affected').hide();
        jQuery(this).closest('.migrate-rules-div').find('.migration-rules-affected').text(data.data).show();
      }
    });
  });

  jQuery('#manage_bulk_ranges_section .wdp-import-tools-form').on("submit", function (event) {
    let filePath = jQuery(this).siblings("#rules-to-import").val();
    let warning = jQuery(this).siblings(".no-file-warning");
    if (!filePath) {
      warning.show();
      event.preventDefault();
    } else {
      warning.hide();
    }
  })
});
