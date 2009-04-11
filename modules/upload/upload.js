// $Id: upload.js,v 1.1 2009/04/11 22:19:45 webchick Exp $

(function($) {

Drupal.behaviors.bookFieldsetSummaries = {
  attach: function(context) {
    $('fieldset#edit-attachments', context).setSummary(function(context) {
      var size = $('#upload-attachments tbody tr').size();
      return Drupal.formatPlural(size, '1 attachment', '@count attachments');
    });
  }
};

})(jQuery);
