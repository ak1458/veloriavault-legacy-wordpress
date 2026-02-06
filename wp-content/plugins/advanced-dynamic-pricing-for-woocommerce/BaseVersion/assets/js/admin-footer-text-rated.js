/* global jQuery, ajaxurl */
jQuery(document).ready(function ($) {
  $( 'a.wdp-rating-link' ).on( 'click', function() {
    $.post(
      ajaxurl,
      {
        action: 'wdp_ajax',
        method: 'admin_footer_text_rated',
        security: wdp_admin_footer_text_rated_data.security,
        security_param: wdp_admin_footer_text_rated_data.security_query_arg
      }
    )
    $( this ).parent().text( $( this ).data( 'rated' ) )
  });
})
