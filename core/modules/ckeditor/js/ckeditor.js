/**
 * @file
 * CKEditor implementation of {@link Drupal.editors} API.
 */

(function (Drupal, debounce, CKEDITOR, $) {

  "use strict";

  /**
   * @namespace
   */
  Drupal.editors.ckeditor = {

    /**
     * Editor attach callback.
     *
     * @param {HTMLElement} element
     * @param {string} format
     *
     * @return {bool}
     */
    attach: function (element, format) {
      this._loadExternalPlugins(format);
      // Also pass settings that are Drupal-specific.
      format.editorSettings.drupal = {
        format: format.format
      };

      // Set a title on the CKEditor instance that includes the text field's
      // label so that screen readers say something that is understandable
      // for end users.
      var label = $('label[for=' + element.getAttribute('id') + ']').html();
      format.editorSettings.title = Drupal.t("Rich Text Editor, !label field", {'!label': label});

      // CKEditor initializes itself in a read-only state if the 'disabled'
      // attribute is set. It does not respect the 'readonly' attribute,
      // however, so we set the 'readOnly' configuration property manually in
      // that case, for the CKEditor instance that's about to be created.
      format.editorSettings.readOnly = element.hasAttribute('readonly');

      return !!CKEDITOR.replace(element, format.editorSettings);
    },

    /**
     * Editor detach callback.
     *
     * @param {HTMLElement} element
     * @param {string} format
     * @param {string} trigger
     *
     * @return {bool}
     */
    detach: function (element, format, trigger) {
      var editor = CKEDITOR.dom.element.get(element).getEditor();
      if (editor) {
        if (trigger === 'serialize') {
          editor.updateElement();
        }
        else {
          editor.destroy();
          element.removeAttribute('contentEditable');
        }
      }
      return !!editor;
    },

    /**
     *
     * @param {HTMLElement} element
     * @param {function} callback
     *
     * @return {bool}
     */
    onChange: function (element, callback) {
      var editor = CKEDITOR.dom.element.get(element).getEditor();
      if (editor) {
        editor.on('change', debounce(function () {
          callback(editor.getData());
        }, 400));
      }
      return !!editor;
    },

    /**
     *
     * @param {HTMLElement} element
     * @param {object} format
     * @param {string} mainToolbarId
     * @param {string} floatedToolbarId
     *
     * @return {bool}
     */
    attachInlineEditor: function (element, format, mainToolbarId, floatedToolbarId) {
      this._loadExternalPlugins(format);
      // Also pass settings that are Drupal-specific.
      format.editorSettings.drupal = {
        format: format.format
      };

      var settings = $.extend(true, {}, format.editorSettings);

      // If a toolbar is already provided for "true WYSIWYG" (in-place editing),
      // then use that toolbar instead: override the default settings to render
      // CKEditor UI's top toolbar into mainToolbar, and don't render the bottom
      // toolbar at all. (CKEditor doesn't need a floated toolbar.)
      if (mainToolbarId) {
        var settingsOverride = {
          extraPlugins: 'sharedspace',
          removePlugins: 'floatingspace,elementspath',
          sharedSpaces: {
            top: mainToolbarId
          }
        };

        // Find the "Source" button, if any, and replace it with "Sourcedialog".
        // (The 'sourcearea' plugin only works in CKEditor's iframe mode.)
        var sourceButtonFound = false;
        for (var i = 0; !sourceButtonFound && i < settings.toolbar.length; i++) {
          if (settings.toolbar[i] !== '/') {
            for (var j = 0; !sourceButtonFound && j < settings.toolbar[i].items.length; j++) {
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

        settings.extraPlugins += ',' + settingsOverride.extraPlugins;
        settings.removePlugins += ',' + settingsOverride.removePlugins;
        settings.sharedSpaces = settingsOverride.sharedSpaces;
      }

      // CKEditor requires an element to already have the contentEditable
      // attribute set to "true", otherwise it won't attach an inline editor.
      element.setAttribute('contentEditable', 'true');

      return !!CKEDITOR.inline(element, settings);
    },

    /**
     * @param {object} format
     */
    _loadExternalPlugins: function (format) {
      var externalPlugins = format.editorSettings.drupalExternalPlugins;
      // Register and load additional CKEditor plugins as necessary.
      if (externalPlugins) {
        for (var pluginName in externalPlugins) {
          if (externalPlugins.hasOwnProperty(pluginName)) {
            CKEDITOR.plugins.addExternal(pluginName, externalPlugins[pluginName], '');
          }
        }
        delete format.editorSettings.drupalExternalPlugins;
      }
    }

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
    openDialog: function (editor, url, existingValues, saveCallback, dialogSettings) {
      // Locate a suitable place to display our loading indicator.
      var $target = $(editor.container.$);
      if (editor.elementMode === CKEDITOR.ELEMENT_MODE_REPLACE) {
        $target = $target.find('.cke_contents');
      }

      // Remove any previous loading indicator.
      $target.css('position', 'relative').find('.ckeditor-dialog-loading').remove();

      // Add a consistent dialog class.
      var classes = dialogSettings.dialogClass ? dialogSettings.dialogClass.split(' ') : [];
      classes.push('editor-dialog');
      dialogSettings.dialogClass = classes.join(' ');
      dialogSettings.autoResize = Drupal.checkWidthBreakpoint(600);

      // Add a "Loading…" message, hide it underneath the CKEditor toolbar,
      // create a Drupal.Ajax instance to load the dialog and trigger it.
      var $content = $('<div class="ckeditor-dialog-loading"><span style="top: -40px;" class="ckeditor-dialog-loading-link">' + Drupal.t('Loading...') + '</span></div>');
      $content.appendTo($target);

      var ckeditorAjaxDialog = Drupal.ajax({
        dialog: dialogSettings,
        dialogType: 'modal',
        selector: '.ckeditor-dialog-loading-link',
        url: url,
        progress: {'type': 'throbber'},
        submit: {
          editor_object: existingValues
        }
      });
      ckeditorAjaxDialog.execute();

      // After a short delay, show "Loading…" message.
      window.setTimeout(function () {
        $content.find('span').animate({top: '0px'});
      }, 1000);

      // Store the save callback to be executed when this dialog is closed.
      Drupal.ckeditor.saveCallback = saveCallback;
    }
  };

  // Moves the dialog to the top of the CKEDITOR stack.
  $(window).on('dialogcreate', function (e, dialog, $element, settings) {
    $('.editor-dialog').css("zIndex", CKEDITOR.config.baseFloatZIndex + 1);
  });

  // Respond to new dialogs that are opened by CKEditor, closing the AJAX loader.
  $(window).on('dialog:beforecreate', function (e, dialog, $element, settings) {
    $('.ckeditor-dialog-loading').animate({top: '-40px'}, function () {
      $(this).remove();
    });
  });

  // Respond to dialogs that are saved, sending data back to CKEditor.
  $(window).on('editor:dialogsave', function (e, values) {
    if (Drupal.ckeditor.saveCallback) {
      Drupal.ckeditor.saveCallback(values);
    }
  });

  // Respond to dialogs that are closed, removing the current save handler.
  $(window).on('dialog:afterclose', function (e, dialog, $element) {
    if (Drupal.ckeditor.saveCallback) {
      Drupal.ckeditor.saveCallback = null;
    }
  });

})(Drupal, Drupal.debounce, CKEDITOR, jQuery);
