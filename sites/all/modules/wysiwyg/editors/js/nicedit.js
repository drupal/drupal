// $Id: nicedit.js,v 1.5 2010/02/07 14:36:00 sun Exp $
(function($) {

/**
 * Attach this editor to a target element.
 */
Drupal.wysiwyg.editor.attach.nicedit = function(context, params, settings) {
  // Attach editor.
  var editor = new nicEditor(settings);
  editor.panelInstance(params.field);
  editor.addEvent('focus', function () {
    Drupal.wysiwyg.activeId = params.field;
  });
};

/**
 * Detach a single or all editors.
 *
 * See Drupal.wysiwyg.editor.detach.none() for a full description of this hook.
 */
Drupal.wysiwyg.editor.detach.nicedit = function(context, params) {
  if (typeof params != 'undefined') {
    var instance = nicEditors.findEditor(params.field);
    if (instance) {
      instance.ne.removeInstance(params.field);
      instance.ne.removePanel();
    }
  }
  else {
    for (var e in nicEditors.editors) {
      // Save contents of all editors back into textareas.
      var instances = nicEditors.editors[e].nicInstances;
      for (var i = 0; i < instances.length; i++) {
        instances[i].remove();
      }
      // Remove all editor instances.
      nicEditors.editors[e].nicInstances = [];
    }
  }
};

/**
 * Instance methods for nicEdit.
 */
Drupal.wysiwyg.editor.instance.nicedit = {
  insert: function (content) {
    var instance = nicEditors.findEditor(this.field);
    var editingArea = instance.getElm();
    var sel = instance.getSel();
    // IE.
    if (document.selection) {
      editingArea.focus();
      sel.createRange().text = content;
    }
    else {
      // Convert selection to a range.
      var range;
      // W3C compatible.
      if (sel.getRangeAt) {
        range = sel.getRangeAt(0);
      }
      // Safari.
      else {
        range = editingArea.ownerDocument.createRange();
        range.setStart(sel.anchorNode, sel.anchorOffset);
        range.setEnd(sel.focusNode, userSeletion.focusOffset);
      }
      // The code below doesn't work in IE, but it never gets here.
      var fragment = editingArea.ownerDocument.createDocumentFragment();
      // Fragments don't support innerHTML.
      var wrapper = editingArea.ownerDocument.createElement('div');
      wrapper.innerHTML = content;
      while (wrapper.firstChild) {
        fragment.appendChild(wrapper.firstChild);
      }
      range.deleteContents();
      // Only fragment children are inserted.
      range.insertNode(fragment);
    }
  }
};

})(jQuery);
