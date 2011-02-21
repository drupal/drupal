// $Id: whizzywig.js,v 1.9 2010/10/18 19:53:12 sun Exp $

var wysiwygWhizzywig = { currentField: null, fields: {} };
var buttonPath = null;

/**
 * Override Whizzywig's document.write() function.
 *
 * Whizzywig uses document.write() by default, which leads to a blank page when
 * invoked in jQuery.ready().  Luckily, Whizzywig developers implemented a
 * shorthand w() substitute function that we can override to redirect the output
 * into the global wysiwygWhizzywig variable.
 *
 * @see o()
 */
var w = function (string) {
  if (string) {
    wysiwygWhizzywig.fields[wysiwygWhizzywig.currentField] += string;
  }
  return wysiwygWhizzywig.fields[wysiwygWhizzywig.currentField];
};

/**
 * Override Whizzywig's document.getElementById() function.
 *
 * Since we redirect the output of w() into a temporary string upon attaching
 * an editor, we also have to override the o() shorthand substitute function
 * for document.getElementById() to search in the document or our container.
 * This override function also inserts the editor instance when Whizzywig
 * tries to access its IFRAME, so it has access to the full/regular window
 * object.
 *
 * @see w()
 */
var o = function (id) {
  // Upon first access to "whizzy" + id, Whizzywig tries to access its IFRAME,
  // so we need to insert the editor into the DOM.
  if (id == 'whizzy' + wysiwygWhizzywig.currentField && wysiwygWhizzywig.fields[wysiwygWhizzywig.currentField]) {
    jQuery('#' + wysiwygWhizzywig.currentField).after('<div id="' + wysiwygWhizzywig.currentField + '-whizzywig"></div>');
    // Iframe's .contentWindow becomes null in Webkit if inserted via .after().
    jQuery('#' + wysiwygWhizzywig.currentField + '-whizzywig').html(w());
    // Prevent subsequent invocations from inserting the editor multiple times.
    wysiwygWhizzywig.fields[wysiwygWhizzywig.currentField] = '';
  }
  // If id exists in the regular window.document, return it.
  if (jQuery('#' + id).size()) {
    return jQuery('#' + id).get(0);
  }
  // Otherwise return id from our container.
  return jQuery('#' + id, w()).get(0);
};

(function($) {

/**
 * Attach this editor to a target element.
 */
Drupal.wysiwyg.editor.attach.whizzywig = function(context, params, settings) {
  // Assign button images path, if available.
  if (settings.buttonPath) {
    window.buttonPath = settings.buttonPath;
  }
  // Create Whizzywig container.
  wysiwygWhizzywig.currentField = params.field;
  wysiwygWhizzywig.fields[wysiwygWhizzywig.currentField] = '';
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
    var body = instance.contentWindow.document.body;
    var $field = $('#' + id);
    // Whizzywig shows the original textarea in source mode.
    if ($field.css('display') == 'block') {
      body.innerHTML = $field.val();
    }
    body.innerHTML = tidyH(body.innerHTML);

    // Save contents of editor back into textarea.
    $field.val(window.get_xhtml ? get_xhtml(body) : body.innerHTML);
    $field.val($field.val().replace(location.href + '#', '#'));
    // Remove editor instance.
    $('#' + id + '-whizzywig').remove();
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
