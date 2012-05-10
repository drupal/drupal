(function ($) {

"use strict";

Drupal.behaviors.contentTypes = {
  attach: function (context) {
    var $context = $(context);
    // Provide the vertical tab summaries.
    $context.find('fieldset#edit-submission').drupalSetSummary(function(context) {
      var vals = [];
      vals.push(Drupal.checkPlain($(context).find('#edit-title-label').val()) || Drupal.t('Requires a title'));
      return vals.join(', ');
    });
    $context.find('fieldset#edit-workflow').drupalSetSummary(function(context) {
      var vals = [];
      $(context).find("input[name^='node_options']:checked").parent().each(function() {
        vals.push(Drupal.checkPlain($(this).text()));
      });
      if (!$(context).find('#edit-node-options-status').is(':checked')) {
        vals.unshift(Drupal.t('Not published'));
      }
      return vals.join(', ');
    });
    $context.find('fieldset#edit-display').drupalSetSummary(function(context) {
      var vals = [];
      var $context = $(context);
      $context.find('input:checked').next('label').each(function() {
        vals.push(Drupal.checkPlain($(this).text()));
      });
      if (!$context.find('#edit-node-submitted').is(':checked')) {
        vals.unshift(Drupal.t("Don't display post information"));
      }
      return vals.join(', ');
    });
  }
};

})(jQuery);
