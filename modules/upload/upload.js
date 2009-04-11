// $Id$

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
