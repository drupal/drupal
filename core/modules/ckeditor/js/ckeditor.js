(function (Drupal, debounce, CKEDITOR, $) {

  "use strict";

  Drupal.editors.ckeditor = {

    attach: function (element, format) {
      this._loadExternalPlugins(format);
      this._ACF_HACK_to_support_blacklisted_attributes(element, format);
      // Also pass settings that are Drupal-specific.
      format.editorSettings.drupal = {
        format: format.format
      };
      return !!CKEDITOR.replace(element, format.editorSettings);
    },

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

    onChange: function (element, callback) {
      var editor = CKEDITOR.dom.element.get(element).getEditor();
      if (editor) {
        editor.on('change', debounce(function () {
          callback(editor.getData());
        }, 400));
      }
      return !!editor;
    },

    attachInlineEditor: function (element, format, mainToolbarId, floatedToolbarId) {
      this._loadExternalPlugins(format);
      this._ACF_HACK_to_support_blacklisted_attributes(element, format);
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
    },

    /**
     * This is a huge hack to do ONE thing: to allow Drupal to fully mandate what
     * CKEditor should allow by setting CKEditor's allowedContent setting. The
     * problem is that allowedContent only allows for whitelisting, whereas
     * Drupal's default HTML filtering (the filter_html filter) also blacklists
     * the "style" and "on*" ("onClick" etc.) attributes.
     *
     * So this function hacks in explicit support for Drupal's filter_html's need
     * to blacklist specifically those attributes, until ACF supports blacklisting
     * of properties: http://dev.ckeditor.com/ticket/10276.
     *
     * Limitations:
     *   - This does not support blacklisting of other attributes, it's only
     *     intended to implement filter_html's blacklisted attributes.
     *   - This is only a temporary work-around; it assumes the filter_html
     *     filter is being used whenever *any* restriction exists. This is a valid
     *     assumption for the default text formats in Drupal 8 core, but obviously
     *     won't work for release.
     *
     * This is the only way we could get https://drupal.org/node/1936392 committed
     * before Drupal 8 code freeze on July 1, 2013. CKEditor has committed to
     * explicitly supporting this in some way.
     *
     * @todo D8 remove this once http://dev.ckeditor.com/ticket/10276 is done.
     */
    _ACF_HACK_to_support_blacklisted_attributes: function (element, format) {
      function override(rule) {
        var oldValue = rule.attributes;
        function filter_html_override_attributes(attribute) {
          // Disallow the "style" and "on*" attributes on any tag.
          if (attribute === 'style' || attribute.substr(0, 2) === 'on') {
            return false;
          }

          // Ensure the original logic still runs, if any.
          if (typeof oldValue === 'function') {
            return oldValue(attribute);
          }
          else if (typeof oldValue === 'boolean') {
            return oldValue;
          }

          // Otherwise, accept this attribute.
          return true;
        }
        rule.attributes = filter_html_override_attributes;
      }

      CKEDITOR.once('instanceLoaded', function (e) {
        if (e.editor.name === element.id) {
          // If everything is allowed, everything is allowed.
          if (format.editorSettings.allowedContent === true) {
            return;
          }
          // Otherwise, assume Drupal's filter_html filter is being used.
          else {
            // Get the filter object (ACF).
            var filter = e.editor.filter;
            // Find the "config" rule (the one caused by the allowedContent
            // setting) for each HTML tag, and override its "attributes" value.
            for (var el in filter._.rules.elements) {
              if (filter._.rules.elements.hasOwnProperty(el)) {
                for (var i = 0; i < filter._.rules.elements[el].length; i++) {
                  if (filter._.rules.elements[el][i].featureName === 'config') {
                    override(filter._.rules.elements[el][i]);
                  }
                }
              }
            }
          }
        }
      });
    }

  };

  Drupal.ckeditor = {
    /**
     * Variable storing the current dialog's save callback.
     */
    saveCallack: null,

    /**
     * Open a dialog for a Drupal-based plugin.
     *
     * This dynamically loads jQuery UI (if necessary) using the Drupal AJAX
     * framework, then opens a dialog at the specified Drupal path.
     *
     * @param editor
     *   The CKEditor instance that is opening the dialog.
     * @param string url
     *   The URL that contains the contents of the dialog.
     * @param Object existingValues
     *   Existing values that will be sent via POST to the url for the dialog
     *   contents.
     * @param Function saveCallback
     *   A function to be called upon saving the dialog.
     * @param Object dialogSettings
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

      // Add a "Loading…" message, hide it underneath the CKEditor toolbar, create
      // a Drupal.ajax instance to load the dialog and trigger it.
      var $content = $('<div class="ckeditor-dialog-loading"><span style="top: -40px;" class="ckeditor-dialog-loading-link"><a>' + Drupal.t('Loading...') + '</a></span></div>');
      $content.appendTo($target);
      new Drupal.ajax('ckeditor-dialog', $content.find('a').get(0), {
        accepts: 'application/vnd.drupal-modal',
        dialog: dialogSettings,
        selector: '.ckeditor-dialog-loading-link',
        url: url,
        event: 'ckeditor-internal.ckeditor',
        progress: { 'type': 'throbber' },
        submit: {
          editor_object: existingValues
        }
      });
      $content.find('a')
        .on('click', function () { return false; })
        .trigger('ckeditor-internal.ckeditor');

      // After a short delay, show "Loading…" message.
      window.setTimeout(function () {
        $content.find('span').animate({ top: '0px' });
      }, 1000);

      // Store the save callback to be executed when this dialog is closed.
      Drupal.ckeditor.saveCallback = saveCallback;
    }
  };

  // Respond to new dialogs that are opened by CKEditor, closing the AJAX loader.
  $(window).on('dialog:beforecreate', function (e, dialog, $element, settings) {
    $('.ckeditor-dialog-loading').animate({ top: '-40px' }, function () {
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
