/**
 * @file
 * Views dialog behaviors.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  function handleDialogResize(e) {
    var $modal = $(e.currentTarget);
    var $viewsOverride = $modal.find('[data-drupal-views-offset]');
    var $scroll = $modal.find('[data-drupal-views-scroll]');
    var offset = 0;
    var modalHeight;
    if ($scroll.length) {
      // Add a class to do some styles adjustments.
      $modal.closest('.views-ui-dialog').addClass('views-ui-dialog-scroll');
      // Let scroll element take all the height available.
      $scroll.css({overflow: 'visible', height: 'auto'});
      modalHeight = $modal.height();
      $viewsOverride.each(function () { offset += $(this).outerHeight(); });

      // Take internal padding into account.
      var scrollOffset = $scroll.outerHeight() - $scroll.height();
      $scroll.height(modalHeight - offset - scrollOffset);
      // Reset scrolling properties.
      $modal.css('overflow', 'hidden');
      $scroll.css('overflow', 'auto');
    }
  }

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.viewsModalContent = {
    attach: function (context) {
      $('body').once('viewsDialog').on('dialogContentResize.viewsDialog', '.ui-dialog-content', handleDialogResize);
      // When expanding details, make sure the modal is resized.
      $(context).find('.scroll').once('detailsUpdate').on('click', 'summary', function (e) {
        $(e.currentTarget).trigger('dialogContentResize');
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        $('body').removeOnce('viewsDialog').off('.viewsDialog');
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
