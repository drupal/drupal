/**
 * @file
 *
 * Dialog API inspired by HTML5 dialog element:
 * http://www.whatwg.org/specs/web-apps/current-work/multipage/commands.html#the-dialog-element
 */
(function ($, Drupal, drupalSettings) {

"use strict";

drupalSettings.dialog = {
  autoOpen: true,
  autoResize: true,
  dialogClass: '',
  close: function (e) {
    Drupal.detachBehaviors(e.target, null, 'unload');
  }
};

Drupal.dialog = function (element, options) {

  function openDialog (settings) {
    settings = $.extend({}, drupalSettings.dialog, options, settings);
    // Trigger a global event to allow scripts to bind events to the dialog.
    $(window).trigger('dialog:beforecreate', [dialog, $element, settings]);
    $element.dialog(settings);
    if (settings.autoResize !== 'false' && settings.autoResize !== false) {
      $(window).on('resize.dialogResize scroll.dialogResize', autoResize);
      resetPosition();
    }
    dialog.open = true;
    $(window).trigger('dialog:aftercreate', [dialog, $element, settings]);
  }

  function closeDialog (value) {
    $(window).trigger('dialog:beforeclose', [dialog, $element]);
    $element.dialog('close');
    dialog.returnValue = value;
    dialog.open = false;
    $(window).off('.dialogResize');
    $(window).trigger('dialog:afterclose', [dialog, $element]);
  }

  /**
   * Resets the current options for positioning.
   *
   * This is used as a window resize and scroll callback to reposition the
   * jQuery UI dialog. Although not a built-in jQuery UI option, this can
   * be disabled by setting autoResize: false in the options array when creating
   * a new Drupal.dialog().
   */
  function resetPosition () {
    var positionOptions = ['width', 'height', 'minWidth', 'minHeight', 'maxHeight', 'maxWidth', 'position'];
    var windowHeight = $(window).height();
    var adjustedOptions = $.extend({ position: { my: "center", at: "center", of: window }}, options);
    var optionValue, adjustedValue;
    for (var n = 0; n < positionOptions.length; n++) {
      if (adjustedOptions[positionOptions[n]]) {
        optionValue = adjustedOptions[positionOptions[n]];
        // jQuery UI does not support percentages on heights, convert to pixels.
        if (positionOptions[n].match(/height/i) && typeof optionValue === 'string' && optionValue.match(/%$/)) {
          adjustedValue = parseInt(0.01 * parseInt(optionValue, 10) * windowHeight, 10);
          // Don't force the dialog to be bigger vertically than needed.
          if (positionOptions[n] === 'height' && $element.parent().outerHeight() < adjustedValue) {
            adjustedValue = 'auto';
          }
          adjustedOptions[positionOptions[n]] = adjustedValue;
        }
      }
    }
    $element.dialog('option', adjustedOptions);
  }

  var undef;
  var $element = $(element);
  var autoResize = Drupal.debounce(resetPosition, 50);
  var dialog = {
    open: false,
    returnValue: undef,
    show: function () {
      openDialog({modal: false});
    },
    showModal: function () {
      openDialog({modal: true});
    },
    close: closeDialog
  };

  return dialog;
};

})(jQuery, Drupal, drupalSettings);
