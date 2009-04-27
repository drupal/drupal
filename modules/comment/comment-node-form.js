// $Id: comment-node-form.js,v 1.2 2009/04/27 20:19:36 webchick Exp $

(function ($) {

Drupal.behaviors.commentFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-comment-settings', context).setSummary(function (context) {
      return Drupal.checkPlain($('input:checked', context).parent().text());
    });
  }
};

})(jQuery);
