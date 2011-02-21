// $Id: wymeditor.js,v 1.4 2009/09/26 05:37:57 sun Exp $
(function($) {

/**
 * Attach this editor to a target element.
 */
Drupal.wysiwyg.editor.attach.wymeditor = function (context, params, settings) {
  // Prepend basePath to wymPath.
  settings.wymPath = settings.basePath + settings.wymPath;
  // Update activeId on focus.
  settings.postInit = function (instance) {
    $(instance._doc).focus(function () {
      Drupal.wysiwyg.activeId = params.field;
    });
  };
  // Attach editor.
  $('#' + params.field).wymeditor(settings);
};

/**
 * Detach a single or all editors.
 */
Drupal.wysiwyg.editor.detach.wymeditor = function (context, params) {
  if (typeof params != 'undefined') {
    var $field = $('#' + params.field);
    var index = $field.data(WYMeditor.WYM_INDEX);
    if (typeof index != 'undefined') {
      var instance = WYMeditor.INSTANCES[index];
      instance.update();
      $(instance._box).remove();
      $(instance._element).show();
      delete instance;
    }
    $field.show();
  }
  else {
    jQuery.each(WYMeditor.INSTANCES, function () {
      this.update();
      $(this._box).remove();
      $(this._element).show();
      delete this;
    });
  }
};

Drupal.wysiwyg.editor.instance.wymeditor = {
  insert: function (content) {
    var $field = $('#' + this.field);
    var index = $field.data(WYMeditor.WYM_INDEX);
    if (typeof index != 'undefined') {
      var instance = WYMeditor.INSTANCES[index];
      instance.insert(content);
    }
  }
};

})(jQuery);
