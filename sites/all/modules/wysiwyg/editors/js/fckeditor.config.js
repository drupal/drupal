// $Id: fckeditor.config.js,v 1.7 2009/09/29 01:48:23 sun Exp $

Drupal = window.parent.Drupal;

/**
 * Fetch and provide original editor settings as local variable.
 *
 * FCKeditor does not support to pass complex variable types to the editor.
 * Instance settings passed to FCKinstance.Config are temporarily stored in
 * FCKConfig.PageConfig.
 */
var wysiwygFormat = FCKConfig.PageConfig.wysiwygFormat;
var wysiwygSettings = Drupal.settings.wysiwyg.configs.fckeditor[wysiwygFormat];
var pluginSettings = (Drupal.settings.wysiwyg.plugins[wysiwygFormat] ? Drupal.settings.wysiwyg.plugins[wysiwygFormat] : { 'native': {}, 'drupal': {} });

/**
 * Apply format-specific settings.
 */
for (var setting in wysiwygSettings) {
  if (setting == 'buttons') {
    // Apply custom Wysiwyg toolbar for this format.
    // FCKConfig.ToolbarSets['Wysiwyg'] = wysiwygSettings.buttons;

    // Temporarily stack buttons into multiple button groups and remove
    // separators until #277954 is solved.
    FCKConfig.ToolbarSets['Wysiwyg'] = [];
    for (var i = 0; i < wysiwygSettings.buttons[0].length; i++) {
      FCKConfig.ToolbarSets['Wysiwyg'].push([wysiwygSettings.buttons[0][i]]);
    }
    FCKTools.AppendStyleSheet(document, '#xToolbar .TB_Start { display:none; }');
    // Set valid height of select element in silver and office2003 skins.
    if (FCKConfig.SkinPath.match(/\/office2003\/$/)) {
      FCKTools.AppendStyleSheet(document, '#xToolbar .SC_FieldCaption { height: 24px; } #xToolbar .TB_End { display: none; }');
    }
    else if (FCKConfig.SkinPath.match(/\/silver\/$/)) {
      FCKTools.AppendStyleSheet(document, '#xToolbar .SC_FieldCaption { height: 27px; }');
    }
  }
  else {
    FCKConfig[setting] = wysiwygSettings[setting];
  }
}

/**
 * Initialize this editor instance.
 */
Drupal.wysiwyg.editor.instance.fckeditor.init(window);

/**
 * Register native plugins for this input format.
 *
 * Parameters to Plugins.Add are:
 * - Plugin name.
 * - Languages the plugin is available in.
 * - Location of the plugin folder; <plugin_name>/fckplugin.js is appended.
 */
for (var plugin in pluginSettings['native']) {
  // Languages and path may be undefined for internal plugins.
  FCKConfig.Plugins.Add(plugin, pluginSettings['native'][plugin].languages, pluginSettings['native'][plugin].path);
}

/**
 * Register Drupal plugins for this input format.
 *
 * Parameters to addPlugin() are:
 * - Plugin name.
 * - Format specific plugin settings.
 * - General plugin settings.
 * - A reference to this window so the plugin setup can access FCKConfig.
 */
for (var plugin in pluginSettings.drupal) {
  Drupal.wysiwyg.editor.instance.fckeditor.addPlugin(plugin, pluginSettings.drupal[plugin], Drupal.settings.wysiwyg.plugins.drupal[plugin], window);
}

