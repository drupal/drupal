// $Id: book.js,v 1.8 2010/11/05 19:47:20 dries Exp $

(function ($) {

Drupal.behaviors.bookFieldsetSummaries = {
  attach: function (context) {
    $('fieldset.book-form', context).drupalSetSummary(function (context) {
      var val = $('.form-item-book-bid select').val();

      if (val === '0') {
        return Drupal.t('Not in book');
      }
      else if (val === 'new') {
        return Drupal.t('New book');
      }
      else {
        return Drupal.checkPlain($('.form-item-book-bid select :selected').text());
      }
    });
  }
};

})(jQuery);
