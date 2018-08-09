/**
 * @file
 * Views dialog behaviors.
 */

(function($, Drupal, drupalSettings) {
  function handleDialogResize(e) {
    const $modal = $(e.currentTarget);
    const $viewsOverride = $modal.find('[data-drupal-views-offset]');
    const $scroll = $modal.find('[data-drupal-views-scroll]');
    let offset = 0;
    let modalHeight;
    if ($scroll.length) {
      // Add a class to do some styles adjustments.
      $modal.closest('.views-ui-dialog').addClass('views-ui-dialog-scroll');
      // Let scroll element take all the height available.
      $scroll.css({ overflow: 'visible', height: 'auto' });
      modalHeight = $modal.height();
      $viewsOverride.each(function() {
        offset += $(this).outerHeight();
      });

      // Take internal padding into account.
      const scrollOffset = $scroll.outerHeight() - $scroll.height();
      $scroll.height(modalHeight - offset - scrollOffset);
      // Reset scrolling properties.
      $modal.css('overflow', 'hidden');
      $scroll.css('overflow', 'auto');
    }
  }

  /**
   * Functionality for views modals.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches modal functionality for views.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the modal functionality.
   */
  Drupal.behaviors.viewsModalContent = {
    attach(context) {
      $('body')
        .once('viewsDialog')
        .on(
          'dialogContentResize.viewsDialog',
          '.ui-dialog-content',
          handleDialogResize,
        );
      // When expanding details, make sure the modal is resized.
      $(context)
        .find('.scroll')
        .once('detailsUpdate')
        .on('click', 'summary', e => {
          $(e.currentTarget).trigger('dialogContentResize');
        });
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        $('body')
          .removeOnce('viewsDialog')
          .off('.viewsDialog');
      }
    },
  };
})(jQuery, Drupal, drupalSettings);
