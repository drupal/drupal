// $Id: book.js,v 1.6 2009/04/27 20:19:35 webchick Exp $

(function ($) {

Drupal.behaviors.bookFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-book', context).setSummary(function (context) {
      var val = $('#edit-book-bid').val();

      if (val === '0') {
        return Drupal.t('Not in book');
      }
      else if (val === 'new') {
        return Drupal.t('New book');
      }
      else {
        return Drupal.checkPlain($('#edit-book-bid :selected').text());
      }
    });
  }
};

})(jQuery);
