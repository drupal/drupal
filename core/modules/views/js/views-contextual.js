/**
 * @file
 * Javascript related to contextual links.
 */

(function ($) {

  "use strict";

  /**
   * Attaches contextual region classes to views elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Adds class `contextual-region` to views elements.
   */
  Drupal.behaviors.viewsContextualLinks = {
    attach: function (context) {
      var id = $('body').attr('data-views-page-contextual-id');

      $('[data-contextual-id="' + id + '"]')
        .closest(':has(.view)')
        .addClass('contextual-region');
    }
  };

})(jQuery);
