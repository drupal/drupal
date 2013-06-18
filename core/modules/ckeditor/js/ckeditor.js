(function (Drupal, CKEDITOR, $) {

"use strict";

Drupal.editors.ckeditor = {

  attach: function (element, format) {
    this._loadExternalPlugins(format);
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
      var changed = function () {
        window.setTimeout(function () {
          callback(editor.getData());
        }, 0);
      };
      // @todo Make this more elegant once http://dev.ckeditor.com/ticket/9794
      // is fixed.
      editor.on('key', changed);
      editor.on('paste', changed);
      editor.on('afterCommandExec', changed);
    }
    return !!editor;
  },

  attachInlineEditor: function (element, format, mainToolbarId, floatedToolbarId) {
    this._loadExternalPlugins(format);

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
  }

};

})(Drupal, CKEDITOR, jQuery);
