(function($) {
  'use strict';

  $(document).on('found_variation', function(e, t) {
    var pid = $(e['target']).closest('.variations_form').data('product_id');
    var $wrap = $(document).find('.wpcme-wrap-' + pid);

    if (t.wpcme_enable === 'yes' && t.wpcme_purchasable === 'no') {
      $(e['target']).
          closest('.variations_form').
          find('.woocommerce-variation-add-to-cart').
          hide();
    } else {
      $(e['target']).
          closest('.variations_form').
          find('.woocommerce-variation-add-to-cart').
          show();
    }

    if (t.wpcme_enable === 'yes') {
      if (t.wpcme_urls !== undefined) {
        $wrap.replaceWith(wpcme_decode_entities(t.wpcme_urls));
      } else {
        $wrap.html('');
      }
    } else if (t.wpcme_enable === 'no') {
      $wrap.html('');
    } else {
      let variable_urls = $('.wpcme-variable-' + pid).data('wpcme');

      if (variable_urls !== undefined) {
        $wrap.replaceWith(wpcme_decode_entities(variable_urls));
      }
    }

    $(document.body).trigger('wpcme_found_variation');
  });

  $(document).on('reset_data', function(e) {
    let pid = $(e['target']).closest('.variations_form').data('product_id');
    let $wrap = $(document).find('.wpcme-wrap-' + pid);
    let variable_urls = $('.wpcme-variable-' + pid).data('wpcme');

    if (variable_urls !== undefined) {
      $wrap.replaceWith(wpcme_decode_entities(variable_urls));
    }

    $(document.body).trigger('wpcme_reset_data');
  });
})(jQuery);

function wpcme_decode_entities(encodedString) {
  let textArea = document.createElement('textarea');
  textArea.innerHTML = encodedString;

  return textArea.value;
}