jQuery(document).ready(function($) {
  function initVariationForm($form) {
    if ($form.length) {
      $form.wc_variation_form();
      $form.trigger('check_variations');
    }
  }

  $('.variations_form').each(function() {
    initVariationForm($(this));
  });

  $(document).on('change', '.variations_form .variations select', function() {
    const $form = $(this).closest('.variations_form');
    initVariationForm($form);
  });

  $(document).on('found_variation', '.variations_form', function(event, variation) {
    const $form = $(this);
    $form.find('.variation_id').val(variation.variation_id);
  });
});
