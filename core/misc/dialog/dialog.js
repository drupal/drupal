/**
 * @file
 * Dialog API inspired by HTML5 dialog element.
 *
 * @see http://www.whatwg.org/specs/web-apps/current-work/multipage/commands.html#the-dialog-element
 */

class DrupalDialogEvent extends Event {
  constructor(type, dialog, settings = null) {
    super(`dialog:${type}`, { bubbles: true });
    this.dialog = dialog;
    this.settings = settings;
  }
}

(function ($, Drupal, drupalSettings, bodyScrollLock) {
  /**
   * Default dialog options.
   *
   * @type {object}
   *
   * @prop {boolean} [autoOpen=true]
   * @prop {string} [dialogClass='']
   * @prop {string} [buttonClass='button']
   * @prop {string} [buttonPrimaryClass='button--primary']
   * @prop {function} close
   */
  drupalSettings.dialog = {
    autoOpen: true,
    dialogClass: '',
    // Drupal-specific extensions: see dialog.jquery-ui.js.
    buttonClass: 'button',
    buttonPrimaryClass: 'button--primary',
    // When using this API directly (when generating dialogs on the client
    // side), you may want to override this method and do
    // `jQuery(event.target).remove()` as well, to remove the dialog on
    // closing.
    close(event) {
      Drupal.dialog(event.target).close();
      Drupal.detachBehaviors(event.target, null, 'unload');
    },
  };

  /**
   * @typedef {object} Drupal.dialog~dialogDefinition
   *
   * @prop {boolean} open
   *   Is the dialog open or not.
   * @prop {*} returnValue
   *   Return value of the dialog.
   * @prop {function} show
   *   Method to display the dialog on the page.
   * @prop {function} showModal
   *   Method to display the dialog as a modal on the page.
   * @prop {function} close
   *   Method to hide the dialog from the page.
   */

  /**
   * Polyfill HTML5 dialog element with jQueryUI.
   *
   * @param {HTMLElement} element
   *   The element that holds the dialog.
   * @param {object} options
   *   jQuery UI options to be passed to the dialog.
   *
   * @return {Drupal.dialog~dialogDefinition}
   *   The dialog instance.
   */
  Drupal.dialog = function (element, options) {
    let undef;

    const $element = $(element);
    const domElement = $element.get(0);

    const dialog = {
      open: false,
      returnValue: undef,
    };

    function openDialog(settings) {
      settings = $.extend({}, drupalSettings.dialog, options, settings);
      // Trigger a global event to allow scripts to bind events to the dialog.
      const event = new DrupalDialogEvent('beforecreate', dialog, settings);
      domElement.dispatchEvent(event);
      $element.dialog(event.settings);
      dialog.open = true;

      // Locks the body scroll only when it opens in modal.
      if (settings.modal) {
        // Locks the body when the dialog opens.
        bodyScrollLock.lock(domElement);
      }

      domElement.dispatchEvent(
        new DrupalDialogEvent('aftercreate', dialog, settings),
      );
    }

    function closeDialog(value) {
      domElement.dispatchEvent(new DrupalDialogEvent('beforeclose', dialog));

      // Unlocks the body when the dialog closes.
      bodyScrollLock.clearBodyLocks();

      $element.dialog('close');
      dialog.returnValue = value;
      dialog.open = false;

      domElement.dispatchEvent(new DrupalDialogEvent('afterclose', dialog));
    }

    dialog.show = () => {
      openDialog({ modal: false });
    };
    dialog.showModal = () => {
      openDialog({ modal: true });
    };
    dialog.close = closeDialog;

    return dialog;
  };
})(jQuery, Drupal, drupalSettings, bodyScrollLock);
