// $Id: upload.js,v 1.2 2009/04/12 03:52:22 webchick Exp $

(function($) {

Drupal.behaviors.uploadFieldsetSummaries = {
  attach: function(context) {
    $('fieldset#edit-attachments', context).setSummary(function(context) {
      var size = $('#upload-attachments tbody tr').size();
      return Drupal.formatPlural(size, '1 attachment', '@count attachments');
    });
  }
};

})(jQuery);
