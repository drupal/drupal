/**
 * @file
 * CKEditor implementation of {@link Drupal.editors} API.
 */

(function(Drupal, debounce, CKEDITOR, $, displace, AjaxCommands) {
  /**
   * @namespace
   */
  Drupal.editors.ckeditor = {
    /**
     * Editor attach callback.
     *
     * @param {HTMLElement} element
     *   The element to attach the editor to.
     * @param {string} format
     *   The text format for the editor.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.replace()` created an editor or not.
     */
    attach(element, format) {
      this._loadExternalPlugins(format);
      // Also pass settings that are Drupal-specific.
      format.editorSettings.drupal = {
        format: format.format,
      };

      // Set a title on the CKEditor instance that includes the text field's
      // label so that screen readers say something that is understandable
      // for end users.
      const label = $(`label[for=${element.getAttribute('id')}]`).html();
      format.editorSettings.title = Drupal.t('Rich Text Editor, !label field', {
        '!label': label,
      });

      return !!CKEDITOR.replace(element, format.editorSettings);
    },

    /**
     * Editor detach callback.
     *
     * @param {HTMLElement} element
     *   The element to detach the editor from.
     * @param {string} format
     *   The text format used for the editor.
     * @param {string} trigger
     *   The event trigger for the detach.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.dom.element.get(element).getEditor()`
     *   found an editor or not.
     */
    detach(element, format, trigger) {
      const editor = CKEDITOR.dom.element.get(element).getEditor();
      if (editor) {
        if (trigger === 'serialize') {
          editor.updateElement();
        } else {
          editor.destroy();
          element.removeAttribute('contentEditable');
        }
      }
      return !!editor;
    },

    /**
     * Reacts on a change in the editor element.
     *
     * @param {HTMLElement} element
     *   The element where the change occurred.
     * @param {function} callback
     *   Callback called with the value of the editor.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.dom.element.get(element).getEditor()`
     *   found an editor or not.
     */
    onChange(element, callback) {
      const editor = CKEDITOR.dom.element.get(element).getEditor();
      if (editor) {
        editor.on(
          'change',
          debounce(() => {
            callback(editor.getData());
          }, 400),
        );

        // A temporary workaround to control scrollbar appearance when using
        // autoGrow event to control editor's height.
        // @todo Remove when http://dev.ckeditor.com/ticket/12120 is fixed.
        editor.on('mode', () => {
          const editable = editor.editable();
          if (!editable.isInline()) {
            editor.on(
              'autoGrow',
              evt => {
                const doc = evt.editor.document;
                const scrollable = CKEDITOR.env.quirks
                  ? doc.getBody()
                  : doc.getDocumentElement();

                if (scrollable.$.scrollHeight < scrollable.$.clientHeight) {
                  scrollable.setStyle('overflow-y', 'hidden');
                } else {
                  scrollable.removeStyle('overflow-y');
                }
              },
              null,
              null,
              10000,
            );
          }
        });
      }
      return !!editor;
    },

    /**
     * Attaches an inline editor to a DOM element.
     *
     * @param {HTMLElement} element
     *   The element to attach the editor to.
     * @param {object} format
     *   The text format used in the editor.
     * @param {string} [mainToolbarId]
     *   The id attribute for the main editor toolbar, if any.
     * @param {string} [floatedToolbarId]
     *   The id attribute for the floated editor toolbar, if any.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.replace()` created an editor or not.
     */
    attachInlineEditor(element, format, mainToolbarId, floatedToolbarId) {
      this._loadExternalPlugins(format);
      // Also pass settings that are Drupal-specific.
      format.editorSettings.drupal = {
        format: format.format,
      };

      const settings = $.extend(true, {}, format.editorSettings);

      // If a toolbar is already provided for "true WYSIWYG" (in-place editing),
      // then use that toolbar instead: override the default settings to render
      // CKEditor UI's top toolbar into mainToolbar, and don't render the bottom
      // toolbar at all. (CKEditor doesn't need a floated toolbar.)
      if (mainToolbarId) {
        const settingsOverride = {
          extraPlugins: 'sharedspace',
          removePlugins: 'floatingspace,elementspath',
          sharedSpaces: {
            top: mainToolbarId,
          },
        };

        // Find the "Source" button, if any, and replace it with "Sourcedialog".
        // (The 'sourcearea' plugin only works in CKEditor's iframe mode.)
        let sourceButtonFound = false;
        for (
          let i = 0;
          !sourceButtonFound && i < settings.toolbar.length;
          i++
        ) {
          if (settings.toolbar[i] !== '/') {
            for (
              let j = 0;
              !sourceButtonFound && j < settings.toolbar[i].items.length;
              j++
            ) {
              if (settings.toolbar[i].items[j] === 'Source') {
                sourceButtonFound = true;
                // Swap sourcearea's "Source" button for sourcedialog's.
                settings.toolbar[i].items[j] = 'Sourcedialog';
                settingsOverride.extraPlugins += ',sourcedialog';
                settingsOverride.removePlugins += ',sourcearea';
              }
            }
          }
        }

        settings.extraPlugins += `,${settingsOverride.extraPlugins}`;
        settings.removePlugins += `,${settingsOverride.removePlugins}`;
        settings.sharedSpaces = settingsOverride.sharedSpaces;
      }

      // CKEditor requires an element to already have the contentEditable
      // attribute set to "true", otherwise it won't attach an inline editor.
      element.setAttribute('contentEditable', 'true');

      return !!CKEDITOR.inline(element, settings);
    },

    /**
     * Loads the required external plugins for the editor.
     *
     * @param {object} format
     *   The text format used in the editor.
     */
    _loadExternalPlugins(format) {
      const externalPlugins = format.editorSettings.drupalExternalPlugins;
      // Register and load additional CKEditor plugins as necessary.
      if (externalPlugins) {
        Object.keys(externalPlugins || {}).forEach(pluginName => {
          CKEDITOR.plugins.addExternal(
            pluginName,
            externalPlugins[pluginName],
            '',
          );
        });
        delete format.editorSettings.drupalExternalPlugins;
      }
    },
  };

  Drupal.ckeditor = {
    /**
     * Variable storing the current dialog's save callback.
     *
     * @type {?function}
     */
    saveCallback: null,

    /**
     * Open a dialog for a Drupal-based plugin.
     *
     * This dynamically loads jQuery UI (if necessary) using the Drupal AJAX
     * framework, then opens a dialog at the specified Drupal path.
     *
     * @param {CKEditor} editor
     *   The CKEditor instance that is opening the dialog.
     * @param {string} url
     *   The URL that contains the contents of the dialog.
     * @param {object} existingValues
     *   Existing values that will be sent via POST to the url for the dialog
     *   contents.
     * @param {function} saveCallback
     *   A function to be called upon saving the dialog.
     * @param {object} dialogSettings
     *   An object containing settings to be passed to the jQuery UI.
     */
    openDialog(editor, url, existingValues, saveCallback, dialogSettings) {
      // Locate a suitable place to display our loading indicator.
      let $target = $(editor.container.$);
      if (editor.elementMode === CKEDITOR.ELEMENT_MODE_REPLACE) {
        $target = $target.find('.cke_contents');
      }

      // Remove any previous loading indicator.
      $target
        .css('position', 'relative')
        .find('.ckeditor-dialog-loading')
        .remove();

      // Add a consistent dialog class.
      const classes = dialogSettings.dialogClass
        ? dialogSettings.dialogClass.split(' ')
        : [];
      classes.push('ui-dialog--narrow');
      dialogSettings.dialogClass = classes.join(' ');
      dialogSettings.autoResize = window.matchMedia(
        '(min-width: 600px)',
      ).matches;
      dialogSettings.width = 'auto';

      // Add a "Loading…" message, hide it underneath the CKEditor toolbar,
      // create a Drupal.Ajax instance to load the dialog and trigger it.
      const $content = $(
        `<div class="ckeditor-dialog-loading"><span style="top: -40px;" class="ckeditor-dialog-loading-link">${Drupal.t(
          'Loading...',
        )}</span></div>`,
      );
      $content.appendTo($target);

      const ckeditorAjaxDialog = Drupal.ajax({
        dialog: dialogSettings,
        dialogType: 'modal',
        selector: '.ckeditor-dialog-loading-link',
        url,
        progress: { type: 'throbber' },
        submit: {
          editor_object: existingValues,
        },
      });
      ckeditorAjaxDialog.execute();

      // After a short delay, show "Loading…" message.
      window.setTimeout(() => {
        $content.find('span').animate({ top: '0px' });
      }, 1000);

      // Store the save callback to be executed when this dialog is closed.
      Drupal.ckeditor.saveCallback = saveCallback;
    },
  };

  // Moves the dialog to the top of the CKEDITOR stack.
  $(window).on('dialogcreate', (e, dialog, $element, settings) => {
    $('.ui-dialog--narrow').css('zIndex', CKEDITOR.config.baseFloatZIndex + 1);
  });

  // Respond to new dialogs that are opened by CKEditor, closing the AJAX loader.
  $(window).on('dialog:beforecreate', (e, dialog, $element, settings) => {
    $('.ckeditor-dialog-loading').animate({ top: '-40px' }, function() {
      $(this).remove();
    });
  });

  // Respond to dialogs that are saved, sending data back to CKEditor.
  $(window).on('editor:dialogsave', (e, values) => {
    if (Drupal.ckeditor.saveCallback) {
      Drupal.ckeditor.saveCallback(values);
    }
  });

  // Respond to dialogs that are closed, removing the current save handler.
  $(window).on('dialog:afterclose', (e, dialog, $element) => {
    if (Drupal.ckeditor.saveCallback) {
      Drupal.ckeditor.saveCallback = null;
    }
  });

  // Formulate a default formula for the maximum autoGrow height.
  $(document).on('drupalViewportOffsetChange', () => {
    CKEDITOR.config.autoGrow_maxHeight =
      0.7 *
      (window.innerHeight - displace.offsets.top - displace.offsets.bottom);
  });

  // Redirect on hash change when the original hash has an associated CKEditor.
  function redirectTextareaFragmentToCKEditorInstance() {
    const hash = window.location.hash.substr(1);
    const element = document.getElementById(hash);
    if (element) {
      const editor = CKEDITOR.dom.element.get(element).getEditor();
      if (editor) {
        const id = editor.container.getAttribute('id');
        window.location.replace(`#${id}`);
      }
    }
  }
  $(window).on(
    'hashchange.ckeditor',
    redirectTextareaFragmentToCKEditorInstance,
  );

  // Set autoGrow to make the editor grow the moment it is created.
  CKEDITOR.config.autoGrow_onStartup = true;

  // Set the CKEditor cache-busting string to the same value as Drupal.
  CKEDITOR.timestamp = drupalSettings.ckeditor.timestamp;

  if (AjaxCommands) {
    /**
     * Command to add style sheets to a CKEditor instance.
     *
     * Works for both iframe and inline CKEditor instances.
     *
     * @param {Drupal.Ajax} [ajax]
     *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
     * @param {object} response
     *   The response from the Ajax request.
     * @param {string} response.editor_id
     *   The CKEditor instance ID.
     * @param {number} [status]
     *   The XMLHttpRequest status.
     *
     * @see http://docs.ckeditor.com/#!/api/CKEDITOR.dom.document
     */
    AjaxCommands.prototype.ckeditor_add_stylesheet = function(
      ajax,
      response,
      status,
    ) {
      const editor = CKEDITOR.instances[response.editor_id];

      if (editor) {
        response.stylesheets.forEach(url => {
          editor.document.appendStyleSheet(url);
        });
      }
    };
  }
})(
  Drupal,
  Drupal.debounce,
  CKEDITOR,
  jQuery,
  Drupal.displace,
  Drupal.AjaxCommands,
);
