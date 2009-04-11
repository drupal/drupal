// $Id: comment-node-form.js,v 1.1 2009/04/11 22:19:44 webchick Exp $

(function($) {

Drupal.behaviors.commentFieldsetSummaries = {
  attach: function(context) {
    $('fieldset#edit-comment-settings', context).setSummary(function(context) {
      return Drupal.checkPlain($('input:checked', context).parent().text());
    });
  }
};

})(jQuery);
