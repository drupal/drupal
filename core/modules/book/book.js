(function ($) {

"use strict";

Drupal.behaviors.bookFieldsetSummaries = {
  attach: function (context) {
    $(context).find('fieldset.book-outline-form').drupalSetSummary(function (context) {
      var $select = $(context).find('.book-title-select');
      var val = $select.val();

      if (val === '0') {
        return Drupal.t('Not in book');
      }
      else if (val === 'new') {
        return Drupal.t('New book');
      }
      else {
        return Drupal.checkPlain($select.find(':selected').text());
      }
    });
  }
};

})(jQuery);
