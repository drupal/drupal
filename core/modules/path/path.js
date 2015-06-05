/**
 * @file
 * Attaches behaviors for the Path module.
 */
(function ($) {

  "use strict";

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.pathDetailsSummaries = {
    attach: function (context) {
      $(context).find('.path-form').drupalSetSummary(function (context) {
        var path = $('.form-item-path-0-alias input').val();

        return path ?
          Drupal.t('Alias: @alias', {'@alias': path}) :
          Drupal.t('No alias');
      });
    }
  };

})(jQuery);
