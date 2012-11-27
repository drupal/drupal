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
  dialogClass: '',
  close: function (e) {
    Drupal.detachBehaviors(e.target, null, 'unload');
  }
};

Drupal.behaviors.dialog = {
  attach: function () {
    // Provide a known 'drupal-modal' dom element for Drupal code to use for
    // modal dialogs. Since there can be multiple non-modal dialogs at a time,
    // it is the responsibility of calling code to create the elements it needs.
    if (!$('#drupal-modal').length) {
      $('<div id="drupal-modal" />').hide().appendTo('body');
    }
  }
};

Drupal.dialog = function (element, options) {

  function openDialog (settings) {
    settings = $.extend(settings, defaults);
    // Trigger a global event to allow scripts to bind events to the dialog.
    $(window).trigger('dialog:beforecreate', [dialog, $element, settings]);
    $element.dialog(settings);
    dialog.open = true;
    $(window).trigger('dialog:aftercreate', [dialog, $element, settings]);
  }

  function closeDialog (value) {
    $(window).trigger('dialog:beforeclose', [dialog, $element]);
    $element.dialog('close');
    dialog.returnValue = value;
    dialog.open = false;
    $(window).trigger('dialog:afterclose', [dialog, $element]);
  }

  var undef;
  var $element = $(element);
  var defaults = $.extend(options, drupalSettings.dialog);
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

/**
 * Binds a listener on dialog creation to handle the cancel link.
 */
$(window).on('dialog:aftercreate', function (e, dialog, $element, settings) {
  $element.on('click.dialog', '.dialog-cancel', function (e) {
    dialog.close('cancel');
    e.preventDefault();
    e.stopPropagation();
  });
});

/**
 * Removes all 'dialog' listeners.
 */
$(window).on('dialog:beforeclose', function (e, dialog, $element) {
  $element.off('.dialog');
});

})(jQuery, Drupal, drupalSettings);
