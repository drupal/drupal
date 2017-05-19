/**
 * @file
 * Defines Javascript behaviors for the node module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Behaviors for tabs in the node edit form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the node edit form.
   */
  Drupal.behaviors.nodeDetailsSummaries = {
    attach: function (context) {
      var $context = $(context);

      $context.find('.node-form-author').drupalSetSummary(function (context) {
        var $authorContext = $(context);
        var name = $authorContext.find('.field--name-uid input').val();
        var date = $authorContext.find('.field--name-created input').val();

        if (name && date) {
          return Drupal.t('By @name on @date', {'@name': name, '@date': date});
        }
        else if (name) {
          return Drupal.t('By @name', {'@name': name});
        }
        else if (date) {
          return Drupal.t('Authored on @date', {'@date': date});
        }
      });

      $context.find('.node-form-options').drupalSetSummary(function (context) {
        var $optionsContext = $(context);
        var vals = [];

        if ($optionsContext.find('input').is(':checked')) {
          $optionsContext.find('input:checked').next('label').each(function () {
            vals.push(Drupal.checkPlain($.trim($(this).text())));
          });
          return vals.join(', ');
        }
        else {
          return Drupal.t('Not promoted');
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
