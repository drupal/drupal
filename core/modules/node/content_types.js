/**
 * @file
 * Javascript for the node content editing form.
 */

(function ($) {

  "use strict";

  Drupal.behaviors.contentTypes = {
    attach: function (context) {
      var $context = $(context);
      // Provide the vertical tab summaries.
      $context.find('#edit-submission').drupalSetSummary(function (context) {
        var vals = [];
        vals.push(Drupal.checkPlain($(context).find('#edit-title-label').val()) || Drupal.t('Requires a title'));
        return vals.join(', ');
      });
      $context.find('#edit-workflow').drupalSetSummary(function (context) {
        var vals = [];
        $(context).find("input[name^='settings[node][options']:checked").parent().each(function () {
          vals.push(Drupal.checkPlain($(this).text()));
        });
        if (!$(context).find('#edit-settings-node-options-status').is(':checked')) {
          vals.unshift(Drupal.t('Not published'));
        }
        return vals.join(', ');
      });
      $('#edit-language', context).drupalSetSummary(function (context) {
        var vals = [];

        vals.push($(".form-item-language-configuration-langcode select option:selected", context).text());

        $('input:checked', context).next('label').each(function () {
          vals.push(Drupal.checkPlain($(this).text()));
        });

        return vals.join(', ');
      });
      $context.find('#edit-display').drupalSetSummary(function (context) {
        var vals = [];
        var $context = $(context);
        $context.find('input:checked').next('label').each(function () {
          vals.push(Drupal.checkPlain($(this).text()));
        });
        if (!$context.find('#edit-settings-node-submitted').is(':checked')) {
          vals.unshift(Drupal.t("Don't display post information"));
        }
        return vals.join(', ');
      });
    }
  };

})(jQuery);
