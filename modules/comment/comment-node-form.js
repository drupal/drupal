// $Id$

(function($) {

Drupal.behaviors.commentFieldsetSummaries = {
  attach: function(context) {
    $('fieldset#edit-comment-settings', context).setSummary(function(context) {
      return Drupal.checkPlain($('input:checked', context).parent().text());
    });
  }
};

})(jQuery);
