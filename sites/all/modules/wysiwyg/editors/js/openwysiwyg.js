// $Id: openwysiwyg.js,v 1.4 2010/11/13 19:07:44 sun Exp $

// Backup $ and reset it to jQuery.
Drupal.wysiwyg._openwysiwyg = $;
$ = jQuery;

// Wrap openWYSIWYG's methods to temporarily use its version of $.
jQuery.each(WYSIWYG, function (key, value) {
  if (jQuery.isFunction(value)) {
    WYSIWYG[key] = function () {
      var old$ = $;
      $ = Drupal.wysiwyg._openwysiwyg;
      var result = value.apply(this, arguments);
      $ = old$;
      return result;
    };
  }
});

// Override editor functions.
WYSIWYG.getEditor = function (n) {
  return Drupal.wysiwyg._openwysiwyg("wysiwyg" + n);
};

(function($) {

/**
 * Attach this editor to a target element.
 */
Drupal.wysiwyg.editor.attach.openwysiwyg = function(context, params, settings) {
  // Initialize settings.
  settings.ImagesDir = settings.path + 'images/';
  settings.PopupsDir = settings.path + 'popups/';
  settings.CSSFile = settings.path + 'styles/wysiwyg.css';
  //settings.DropDowns = [];
  var config = new WYSIWYG.Settings();
  for (var setting in settings) {
    config[setting] = settings[setting];
  }
  // Attach editor.
  WYSIWYG.setSettings(params.field, config);
  WYSIWYG_Core.includeCSS(WYSIWYG.config[params.field].CSSFile);
  WYSIWYG._generate(params.field, config);
};

/**
 * Detach a single or all editors.
 */
Drupal.wysiwyg.editor.detach.openwysiwyg = function(context, params) {
  if (typeof params != 'undefined') {
    var instance = WYSIWYG.config[params.field];
    if (typeof instance != 'undefined') {
      WYSIWYG.updateTextArea(params.field);
      jQuery('#wysiwyg_div_' + params.field).remove();
      delete instance;
    }
    jQuery('#' + params.field).show();
  }
  else {
    jQuery.each(WYSIWYG.config, function(field) {
      WYSIWYG.updateTextArea(field);
      jQuery('#wysiwyg_div_' + field).remove();
      delete this;
      jQuery('#' + field).show();
    });
  }
};

})(jQuery);
