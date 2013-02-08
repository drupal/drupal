(function (Drupal, CKEDITOR) {

"use strict";

Drupal.editors.ckeditor = {

  attach: function (element, format) {
    var externalPlugins = format.editorSettings.externalPlugins;
    // Register and load additional CKEditor plugins as necessary.
    if (externalPlugins) {
      for (var pluginName in externalPlugins) {
        if (externalPlugins.hasOwnProperty(pluginName)) {
          CKEDITOR.plugins.addExternal(pluginName, externalPlugins[pluginName], '');
        }
      }
      delete format.editorSettings.drupalExternalPlugins;
    }
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
      }
    }
    return !!editor;
  }

};

})(Drupal, CKEDITOR);
