/**
 * @file
 * Attaches behaviors for the Path module.
 */
(function ($) {

"use strict";

Drupal.behaviors.pathFieldsetSummaries = {
  attach: function (context) {
    $(context).find('fieldset.path-form').drupalSetSummary(function (context) {
      var path = $('.form-item-path-alias input').val();

      return path ?
        Drupal.t('Alias: @alias', { '@alias': path }) :
        Drupal.t('No alias');
    });
  }
};

})(jQuery);
