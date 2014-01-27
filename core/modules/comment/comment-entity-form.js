/**
 * @file
 * Attaches comment behaviors to the entity form.
 */

(function ($) {

  "use strict";

  Drupal.behaviors.commentFieldsetSummaries = {
    attach: function (context) {
      var $context = $(context);
      $context.find('fieldset.comment-entity-settings-form').drupalSetSummary(function (context) {
        return Drupal.checkPlain($(context).find('.form-item-comment input:checked').next('label').text());
      });
    }
  };

})(jQuery);
