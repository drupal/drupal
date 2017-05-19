/**
 * @file
 * Defines Javascript behaviors for the media form.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behaviors for summaries for tabs in the media edit form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the media edit form.
   */
  Drupal.behaviors.mediaFormSummaries = {
    attach: function (context) {
      var $context = $(context);

      $context.find('.media-form-author').drupalSetSummary(function (context) {
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
    }
  };

})(jQuery, Drupal);
