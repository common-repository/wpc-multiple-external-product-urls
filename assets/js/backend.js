(function($) {
  'use strict';

  $(document).on('change', '.wpcme_terms', function() {
    var $this = $(this);
    var val = $this.val();
    var apply = $this.closest('.wpcme-item').find('.wpcme_apply').val();

    if (Array.isArray(val)) {
      $this.closest('.wpcme-item').
          find('.wpcme_apply_val').
          val(val.join()).
          trigger('change');
    } else {
      if (val === null) {
        $this.closest('.wpcme-item').
            find('.wpcme_apply_val').
            val('').
            trigger('change');
      } else {
        $this.closest('.wpcme-item').
            find('.wpcme_apply_val').
            val(String(val)).
            trigger('change');
      }
    }

    $this.data(apply, $this.val().join());
  });

  $(document).on('click touch', '.wpcme-item-header', function(e) {
    if (($(e.target).closest('.wpcme-item-duplicate').length === 0) &&
        ($(e.target).closest('.wpcme-item-remove').length === 0)) {
      $(this).closest('.wpcme-item').toggleClass('active');
    }
  }).on('click touch', '.wpcme-item-remove', function() {
    var r = confirm(
        'Do you want to remove this role? This action cannot undo.');
    if (r == true) {
      $(this).closest('.wpcme-item').remove();
    }
  }).on('click', '.wpcme-remove', function() {
    $(this).closest('.input-panel').remove();
  }).on('click', '.wpcme-add-url', function() {
    let $this = $(this), index = $this.data('count'), key = $this.data('key'),
        id = $this.data('id'), name = 'wpcme_urls';

    if (parseInt(id) > 0) {
      // is variation
      name = `wpcme_urls_v[${id}]`;
    }

    let htmlCode = `<div class="input-panel wpcme-url">
    <span class="wpcme-text-wrapper hint--top" aria-label="${wpcme_vars.hint_text}"><input type="text" placeholder="Buy product" class="wpcme-url-qty" name="${name}[urls][${index}][text]"></span>
    <span class="wpcme-url-wrapper hint--top" aria-label="${wpcme_vars.hint_url}"><input type="url" placeholder="https://" class="wpcme-url-text" name="${name}[urls][${index}][url]"></span>
    <span class="wpcme-remove hint--top" aria-label="${wpcme_vars.hint_remove}">&times;</span>
</div>`;
    $this.before(htmlCode);
    $this.data('count', index + 1);
  }).on('change', '.wpcme_enable', function() {
    let state = $(this).val();

    if (state === 'yes') {
      $(this).
          closest('.wpcme_settings').
          find('.wpcme_settings_enable').
          show();
    } else {
      $(this).
          closest('.wpcme_settings').
          find('.wpcme_settings_enable').
          hide();
    }
  });
})(jQuery);
