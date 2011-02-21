// $Id: whizzywig-60.js,v 1.1 2010/10/18 19:37:44 sun Exp $

var buttonPath = null;

(function($) {

/**
 * Attach this editor to a target element.
 */
Drupal.wysiwyg.editor.attach.whizzywig = function(context, params, settings) {
  // Previous versions used per-button images found in this location,
  // now it is only used for custom buttons.
  if (settings.buttonPath) {
    window.buttonPath = settings.buttonPath;
  }
  // Assign the toolbar image path used for native buttons, if available.
  if (settings.toolbarImagePath) {
    btn._f = settings.toolbarImagePath;
  }
  // Fall back to text labels for all buttons.
  else {
    window.buttonPath = 'textbuttons';
  }
  // Whizzywig needs to have the width set 'inline'.
  $field = $('#' + params.field);
  var originalValues = Drupal.wysiwyg.instances[params.field];
  originalValues.originalWidth = $field.css('width');
  originalValues.originalColor = $field.css('color');
  originalValues.originalZindex = $field.css('zIndex');
  $field.css('width', $field.width() + 'px');

  // Attach editor.
  makeWhizzyWig(params.field, (settings.buttons ? settings.buttons : 'all'));
  // Whizzywig fails to detect and set initial textarea contents.
  var instance = $('#whizzy' + params.field).get(0);
  if (instance) {
    instance.contentWindow.document.body.innerHTML = tidyD($field.val());
  }
};

/**
 * Detach a single or all editors.
 */
Drupal.wysiwyg.editor.detach.whizzywig = function(context, params) {
  var detach = function (index) {
    var id = whizzies[index];
    var instance = $('#whizzy' + id).get(0);
    if (!instance) {
      return;
    }
    var editingArea = instance.contentWindow.document;
    var $field = $('#' + id);
    // Whizzywig shows the original textarea in source mode.
    if ($field.css('display') == 'block') {
      editingArea.body.innerHTML = $field.val();
    }

    // Save contents of editor back into textarea.
    $field.val(tidyH(editingArea));
    // Move original textarea back to its previous location.
    $container = $('#CONTAINER' + id);
    $field.insertBefore($container);
    // Remove editor instance.
    $container.remove();
    whizzies.splice(index, 1);

    // Restore original textarea styling.
    var originalValues = Drupal.wysiwyg.instances[id];
    $field.css('width', originalValues.originalWidth);
    $field.css('color', originalValues.originalColor);
    $field.css('zIndex', originalValues.originalZindex);
  };

  if (typeof params != 'undefined') {
    for (var i = 0; i < whizzies.length; i++) {
      if (whizzies[i] == params.field) {
        detach(i);
        break;
      }
    }
  }
  else {
    while (whizzies.length > 0) {
      detach(0);
    }
  }
};

})(jQuery);
