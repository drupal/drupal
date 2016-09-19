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

  /**
   * The edge of the screen that the dialog should appear on.
   *
   * @type {string}
   */
  var edge = document.documentElement.dir === 'rtl' ? 'left' : 'right';

  var $mainCanvasWrapper = $('#main-canvas-wrapper');

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
      .trigger('dialogContentResize.outsidein');
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
          .on('dialogresize.outsidein', eventData, debounce(bodyPadding, 100))
          .on('dialogContentResize.outsidein', eventData, handleDialogResize)
          .trigger('dialogresize.outsidein');

        $element.dialog('widget').attr('data-offset-' + edge, '');

        $(window)
          .on('resize.outsidein scroll.outsidein', eventData, debounce(resetSize, 100))
          .trigger('resize.outsidein');
      }
    },
    'dialog:beforecreate': function (event, dialog, $element, settings) {
      if ($element.is('#drupal-offcanvas')) {
        // @see http://api.jqueryui.com/position/
        settings.position = {
          my: 'left top',
          at: edge + ' top',
          of: window
        };
        settings.dialogClass = 'ui-dialog-offcanvas';
      }
    },
    'dialog:beforeclose': function (event, dialog, $element) {
      if ($element.is('#drupal-offcanvas')) {
        $(document).off('.outsidein');
        $(window).off('.outsidein');
        $mainCanvasWrapper.css('padding-' + edge, 0);
      }
    }
  });

})(jQuery, Drupal, Drupal.debounce, Drupal.displace);
