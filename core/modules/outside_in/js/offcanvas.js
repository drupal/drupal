/**
 * @file
 * Drupal's off-canvas library.
 *
 * @todo This functionality should extracted into a new core library or a part
 *  of the current drupal.dialog.ajax library.
 *  https://www.drupal.org/node/2784443
 */

(function ($, Drupal, debounce, displace) {

  'use strict';

  // The minimum width to use body displace needs to match the width at which
  // the tray will be %100 width. @see outside_in.module.css
  var minDisplaceWidth = 768;

  /**
   * The edge of the screen that the dialog should appear on.
   *
   * @type {string}
   */
  var edge = document.documentElement.dir === 'rtl' ? 'left' : 'right';

  var $mainCanvasWrapper = $('[data-offcanvas-main-canvas]');

  /**
   * Resets the size of the dialog.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   */
  function resetSize(event) {
    var offsets = displace.offsets;
    var $element = event.data.$element;
    var $widget = $element.dialog('widget');

    var adjustedOptions = {
      // @see http://api.jqueryui.com/position/
      position: {
        my: edge + ' top',
        at: edge + ' top' + (offsets.top !== 0 ? '+' + offsets.top : ''),
        of: window
      }
    };

    $widget.css({
      position: 'fixed',
      height: ($(window).height() - (offsets.top + offsets.bottom)) + 'px'
    });

    $element
      .dialog('option', adjustedOptions)
      .trigger('dialogContentResize.offcanvas');
  }

  /**
   * Adjusts the dialog on resize.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   */
  function handleDialogResize(event) {
    var $element = event.data.$element;
    var $widget = $element.dialog('widget');

    var $offsets = $widget.find('> :not(#drupal-offcanvas, .ui-resizable-handle)');
    var offset = 0;
    var modalHeight;

    // Let scroll element take all the height available.
    $element.css({height: 'auto'});
    modalHeight = $widget.height();
    $offsets.each(function () { offset += $(this).outerHeight(); });

    // Take internal padding into account.
    var scrollOffset = $element.outerHeight() - $element.height();
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
    var $element = event.data.$element;
    var $widget = $element.dialog('widget');

    var width = $widget.outerWidth();
    var mainCanvasPadding = $mainCanvasWrapper.css('padding-' + edge);
    if (width !== mainCanvasPadding) {
      $mainCanvasWrapper.css('padding-' + edge, width + 'px');
      $widget.attr('data-offset-' + edge, width);
      displace();
    }
  }

  $(window).on({
    'dialog:aftercreate': function (event, dialog, $element, settings) {
      if ($element.is('#drupal-offcanvas')) {
        var eventData = {settings: settings, $element: $element};
        $('.ui-dialog-offcanvas, .ui-dialog-offcanvas .ui-dialog-titlebar').toggleClass('ui-dialog-empty-title', !settings.title);

        $element
          .on('dialogresize.offcanvas', eventData, debounce(bodyPadding, 100))
          .on('dialogContentResize.offcanvas', eventData, handleDialogResize)
          .on('dialogContentResize.offcanvas', eventData, debounce(bodyPadding, 100))
          .trigger('dialogresize.offcanvas');

        $element.dialog('widget').attr('data-offset-' + edge, '');

        $(window)
          .on('resize.offcanvas scroll.offcanvas', eventData, debounce(resetSize, 100))
          .trigger('resize.offcanvas');
      }
    },
    'dialog:beforecreate': function (event, dialog, $element, settings) {
      if ($element.is('#drupal-offcanvas')) {
        $('body').addClass('js-tray-open');
        // @see http://api.jqueryui.com/position/
        settings.position = {
          my: 'left top',
          at: edge + ' top',
          of: window
        };
        settings.dialogClass += ' ui-dialog-offcanvas';
        // Applies initial height to dialog based on window height.
        // See http://api.jqueryui.com/dialog for all dialog options.
        settings.height = $(window).height();
      }
    },
    'dialog:beforeclose': function (event, dialog, $element) {
      if ($element.is('#drupal-offcanvas')) {
        $('body').removeClass('js-tray-open');
        $(document).off('.offcanvas');
        $(window).off('.offcanvas');
        $mainCanvasWrapper.css('padding-' + edge, 0);
      }
    }
  });

})(jQuery, Drupal, Drupal.debounce, Drupal.displace);
