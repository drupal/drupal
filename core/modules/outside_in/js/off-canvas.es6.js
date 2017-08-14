/**
 * @file
 * Drupal's off-canvas library.
 *
 * @todo This functionality should extracted into a new core library or a part
 *  of the current drupal.dialog.ajax library.
 *  https://www.drupal.org/node/2784443
 *
 * @private
 */

(function ($, Drupal, debounce, displace) {
  // The minimum width to use body displace needs to match the width at which
  // the tray will be %100 width. @see outside_in.module.css
  const minDisplaceWidth = 768;

  /**
   * The edge of the screen that the dialog should appear on.
   *
   * @type {string}
   */
  const edge = document.documentElement.dir === 'rtl' ? 'left' : 'right';

  const $mainCanvasWrapper = $('[data-off-canvas-main-canvas]');

  /**
   * Resets the size of the dialog.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   */
  function resetSize(event) {
    const offsets = displace.offsets;
    const $element = event.data.$element;
    const $widget = $element.dialog('widget');
    const $elementScroll = $element.scrollTop();

    const adjustedOptions = {
      // @see http://api.jqueryui.com/position/
      position: {
        my: `${edge} top`,
        at: `${edge} top${offsets.top !== 0 ? `+${offsets.top}` : ''}`,
        of: window,
      },
    };

    $widget.css({
      position: 'fixed',
      height: `${$(window).height() - (offsets.top + offsets.bottom)}px`,
    });

    $element
      .dialog('option', adjustedOptions)
      .trigger('dialogContentResize.off-canvas');

    // Reset the elements scroll position after being repositioned.
    $element.scrollTop($elementScroll);
  }

  /**
   * Adjusts the dialog on resize.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   */
  function handleDialogResize(event) {
    const $element = event.data.$element;
    const $widget = $element.dialog('widget');

    const $offsets = $widget.find('> :not(#drupal-off-canvas, .ui-resizable-handle)');
    let offset = 0;
    let modalHeight;

    // Let scroll element take all the height available.
    $element.css({ height: 'auto' });
    modalHeight = $widget.height();
    $offsets.each(function () {
      offset += $(this).outerHeight();
    });

    // Take internal padding into account.
    const scrollOffset = $element.outerHeight() - $element.height();
    $element.height(modalHeight - offset - scrollOffset);
  }

  /**
   * Adjusts the body padding when the dialog is resized.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   */
  function bodyPadding(event) {
    if ($('body').outerWidth() < minDisplaceWidth) {
      return;
    }
    const $element = event.data.$element;
    const $widget = $element.dialog('widget');

    const width = $widget.outerWidth();
    const mainCanvasPadding = $mainCanvasWrapper.css(`padding-${edge}`);
    if (width !== mainCanvasPadding) {
      $mainCanvasWrapper.css(`padding-${edge}`, `${width}px`);
      $widget.attr(`data-offset-${edge}`, width);
      displace();
    }
  }

  /**
   * Attaches off-canvas dialog behaviors.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches event listeners for off-canvas dialogs.
   */
  Drupal.behaviors.offCanvasEvents = {
    attach() {
      $(window).once('off-canvas').on({
        'dialog:aftercreate': function (event, dialog, $element, settings) {
          if ($element.is('#drupal-off-canvas')) {
            const eventData = { settings, $element };
            $('.ui-dialog-off-canvas, .ui-dialog-off-canvas .ui-dialog-titlebar').toggleClass('ui-dialog-empty-title', !settings.title);

            $element
              .on('dialogresize.off-canvas', eventData, debounce(bodyPadding, 100))
              .on('dialogContentResize.off-canvas', eventData, handleDialogResize)
              .on('dialogContentResize.off-canvas', eventData, debounce(bodyPadding, 100))
              .trigger('dialogresize.off-canvas');

            $element.dialog('widget').attr(`data-offset-${edge}`, '');

            $(window)
              .on('resize.off-canvas scroll.off-canvas', eventData, debounce(resetSize, 100))
              .trigger('resize.off-canvas');
          }
        },
        'dialog:beforecreate': function (event, dialog, $element, settings) {
          if ($element.is('#drupal-off-canvas')) {
            $('body').addClass('js-tray-open');
            // @see http://api.jqueryui.com/position/
            settings.position = {
              my: 'left top',
              at: `${edge} top`,
              of: window,
            };
            settings.dialogClass += ' ui-dialog-off-canvas';
            // Applies initial height to dialog based on window height.
            // See http://api.jqueryui.com/dialog for all dialog options.
            settings.height = $(window).height();
          }
        },
        'dialog:beforeclose': function (event, dialog, $element) {
          if ($element.is('#drupal-off-canvas')) {
            $('body').removeClass('js-tray-open');
            // Remove all *.off-canvas events
            $(document).off('.off-canvas');
            $(window).off('.off-canvas');
            $mainCanvasWrapper.css(`padding-${edge}`, 0);
          }
        },
      });
    },
  };
}(jQuery, Drupal, Drupal.debounce, Drupal.displace));
