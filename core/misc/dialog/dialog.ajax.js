/**
 * @file
 * Extends the Drupal AJAX functionality to integrate the dialog API.
 */

(function ($, Drupal, { focusable }) {
  /**
   * Initialize dialogs for Ajax purposes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behaviors for dialog ajax functionality.
   */
  Drupal.behaviors.dialog = {
    attach(context, settings) {
      // Provide a known 'drupal-modal' DOM element for Drupal-based modal
      // dialogs. Non-modal dialogs are responsible for creating their own
      // elements, since there can be multiple non-modal dialogs at a time.
      if (!document.querySelector('#drupal-modal')) {
        // Add 'ui-front' jQuery UI class so jQuery UI widgets like autocomplete
        // sit on top of dialogs. For more information see
        // http://api.jqueryui.com/theming/stacking-elements/.
        document.body.insertAdjacentHTML(
          'beforeend',
          '<div id="drupal-modal" class="ui-front" style="display:none"></div>',
        );
      }

      // Special behaviors specific when attaching content within a dialog.
      // These behaviors usually fire after a validation error inside a dialog.
      if (context !== document) {
        const dialog = context.closest('.ui-dialog-content');
        if (dialog) {
          // Remove and replace the dialog buttons with those from the new form.
          if ($(dialog).dialog('option', 'drupalAutoButtons')) {
            // Trigger an event to detect/sync changes to buttons.
            dialog.dispatchEvent(new CustomEvent('dialogButtonsChange'));
          }

          setTimeout(function () {
            // Account for pre-existing focus handling that may have already moved
            // the focus inside the dialog.
            if (!dialog.contains(document.activeElement)) {
              // Move focus to the first focusable element in the next event loop
              // to allow dialog buttons to be changed first.
              $(dialog).dialog('instance')._focusedElement = null;
              $(dialog).dialog('instance')._focusTabbable();
            }
          }, 0);
        }
      }

      const originalClose = settings.dialog.close;
      // Overwrite the close method to remove the dialog on closing.
      settings.dialog.close = function (event, ...args) {
        originalClose.apply(settings.dialog, [event, ...args]);
        // Check if the opener element is inside an AJAX container.
        const $element = $(event.target);
        const ajaxContainer = $element.data('uiDialog')
          ? $element
              .data('uiDialog')
              .opener.closest('[data-drupal-ajax-container]')
          : [];

        // If the opener element was in an ajax container, and focus is on the
        // body element, we can assume focus was lost. To recover, focus is
        // moved to the first focusable element in the container.
        if (
          ajaxContainer.length &&
          (document.activeElement === document.body ||
            $(document.activeElement).not(':visible'))
        ) {
          const focusableChildren = focusable(ajaxContainer[0]);
          if (focusableChildren.length > 0) {
            setTimeout(() => {
              focusableChildren[0].focus();
            }, 0);
          }
        }
        $(event.target).remove();
      };
    },

    /**
     * Scan a dialog for any primary buttons and move them to the button area.
     *
     * @param {jQuery} $dialog
     *   A jQuery object containing the element that is the dialog target.
     *
     * @return {Array}
     *   An array of buttons that need to be added to the button area.
     */
    prepareDialogButtons($dialog) {
      const buttons = [];
      const buttonSelectors =
        '.form-actions input[type=submit], .form-actions a.button, .form-actions a.action-link';
      const buttonElements = $dialog[0].querySelectorAll(buttonSelectors);

      buttonElements.forEach((button) => {
        button.style.display = 'none';
        buttons.push({
          text: button.innerHTML || button.getAttribute('value'),
          class: button.getAttribute('class'),
          'data-once': button.dataset.once,
          click(e) {
            if (button.tagName === 'A') {
              button.click();
            } else {
              ['mousedown', 'mouseup', 'click'].forEach((event) =>
                button.dispatchEvent(new MouseEvent(event)),
              );
            }
            e.preventDefault();
          },
        });
      });
      return buttons;
    },
  };

  /**
   * Command to open a dialog.
   *
   * @param {Drupal.Ajax} ajax
   *   The Drupal Ajax object.
   * @param {object} response
   *   Object holding the server response.
   * @param {number} [status]
   *   The HTTP status code.
   *
   * @return {boolean|undefined}
   *   Returns false if there was no selector property in the response object.
   */
  Drupal.AjaxCommands.prototype.openDialog = function (ajax, response, status) {
    if (!response.selector) {
      return false;
    }
    let dialog = document.querySelector(response.selector);
    if (!dialog) {
      // Create the element if needed.
      dialog = document.createElement('div');
      dialog.id = response.selector.replace(/^#/, '');
      dialog.classList.add('ui-front');
      document.body.appendChild(dialog);
    }
    // Set up the wrapper, if there isn't one.
    if (!ajax.wrapper) {
      ajax.wrapper = dialog.id;
    }

    // Use the ajax.js insert command to populate the dialog contents.
    response.command = 'insert';
    response.method = 'html';
    ajax.commands.insert(ajax, response, status);

    // Move the buttons to the jQuery UI dialog buttons area.
    response.dialogOptions = response.dialogOptions || {};
    if (typeof response.dialogOptions.drupalAutoButtons === 'undefined') {
      response.dialogOptions.drupalAutoButtons = true;
    } else if (response.dialogOptions.drupalAutoButtons === 'false') {
      response.dialogOptions.drupalAutoButtons = false;
    } else {
      response.dialogOptions.drupalAutoButtons =
        !!response.dialogOptions.drupalAutoButtons;
    }
    if (
      !response.dialogOptions.buttons &&
      response.dialogOptions.drupalAutoButtons
    ) {
      response.dialogOptions.buttons =
        Drupal.behaviors.dialog.prepareDialogButtons($(dialog));
    }

    const dialogButtonsChange = () => {
      const buttons = Drupal.behaviors.dialog.prepareDialogButtons($(dialog));
      $(dialog).dialog('option', 'buttons', buttons);
    };

    // Bind dialogButtonsChange.
    dialog.addEventListener('dialogButtonsChange', dialogButtonsChange);
    dialog.addEventListener('dialog:beforeclose', (event) => {
      dialog.removeEventListener('dialogButtonsChange', dialogButtonsChange);
    });

    // Open the dialog itself.
    const createdDialog = Drupal.dialog(dialog, response.dialogOptions);
    if (response.dialogOptions.modal) {
      createdDialog.showModal();
    } else {
      createdDialog.show();
    }

    // Add the standard Drupal class for buttons for style consistency.
    dialog.parentElement
      ?.querySelector('.ui-dialog-buttonset')
      ?.classList.add('form-actions');
  };

  /**
   * Command to close a dialog.
   *
   * If no selector is given, it defaults to trying to close the modal.
   *
   * @param {Drupal.Ajax} [ajax]
   *   The ajax object.
   * @param {object} response
   *   Object holding the server response.
   * @param {string} response.selector
   *   The selector of the dialog.
   * @param {boolean} response.persist
   *   Whether to persist the dialog element or not.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.closeDialog = function (
    ajax,
    response,
    status,
  ) {
    const dialog = document.querySelector(response.selector);
    if (dialog) {
      Drupal.dialog(dialog).close();
      if (!response.persist) {
        dialog.remove();
      }
    }
  };

  /**
   * Command to set a dialog property.
   *
   * JQuery UI specific way of setting dialog options.
   *
   * @param {Drupal.Ajax} [ajax]
   *   The Drupal Ajax object.
   * @param {object} response
   *   Object holding the server response.
   * @param {string} response.selector
   *   Selector for the dialog element.
   * @param {string} response.optionsName
   *   Name of a key to set.
   * @param {string} response.optionValue
   *   Value to set.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.setDialogOption = function (
    ajax,
    response,
    status,
  ) {
    const dialog = document.querySelector(response.selector);
    if (dialog) {
      $(dialog).dialog('option', response.optionName, response.optionValue);
    }
  };

  /**
   * Binds a listener on dialog creation to handle the cancel link.
   *
   * @param {DrupalDialogEvent} e
   *   The event triggered.
   * @param {Drupal.dialog~dialogDefinition} dialog
   *   The dialog instance.
   * @param {object} [settings]
   *   Dialog settings.
   */
  window.addEventListener('dialog:aftercreate', (event) => {
    const dialog = event.dialog;
    const cancelButton = event.target.querySelector('.dialog-cancel');
    const cancelClick = (e) => {
      dialog.close('cancel');
      e.preventDefault();
      e.stopPropagation();
    };
    cancelButton?.removeEventListener('click', cancelClick);
    cancelButton?.addEventListener('click', cancelClick);
  });

  /**
   * Ajax command to open URL in a modal dialog.
   *
   * @param {Drupal.Ajax} [ajax]
   *   An Ajax object.
   * @param {object} response
   *   The Ajax response.
   */
  Drupal.AjaxCommands.prototype.openModalDialogWithUrl = function (
    ajax,
    response,
  ) {
    const dialogOptions = response.dialogOptions || {};
    const elementSettings = {
      progress: { type: 'throbber' },
      dialogType: 'modal',
      dialog: dialogOptions,
      url: response.url,
      httpMethod: 'GET',
    };
    Drupal.ajax(elementSettings).execute();
  };
})(jQuery, Drupal, window.tabbable);
