/**
 * @file
 * Javascript related to contextual links.
 */

(function ($) {

  "use strict";

  /**
   *
   * @type {Drupal~behavior}
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
