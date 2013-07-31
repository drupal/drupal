/**
 * @file
 * Drupal Image plugin.
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

"use strict";

CKEDITOR.plugins.add('drupalimage', {
  init: function (editor) {
    // Register the image command.
    editor.addCommand('drupalimage', {
      allowedContent: 'img[alt,!src,width,height]',
      requiredContent: 'img[alt,src,width,height]',
      modes: { wysiwyg : 1 },
      canUndo: true,
      exec: function (editor) {
        var imageElement = getSelectedImage(editor);
        var imageDOMElement = null;
        var existingValues = {};
        if (imageElement && imageElement.$) {
          imageDOMElement = imageElement.$;

          // Width and height are populated by actual dimensions.
          existingValues.width = imageDOMElement ? imageDOMElement.width : '';
          existingValues.height = imageDOMElement ? imageDOMElement.height : '';
          // Populate all other attributes by their specified attribute values.
          var attribute = null;
          for (var key = 0; key < imageDOMElement.attributes.length; key++) {
            attribute = imageDOMElement.attributes.item(key);
            existingValues[attribute.nodeName.toLowerCase()] = attribute.nodeValue;
          }
        }

        function saveCallback (returnValues) {
          // Save snapshot for undo support.
          editor.fire('saveSnapshot');

          // Create a new image element if needed.
          if (!imageElement && returnValues.attributes.src) {
            imageElement = editor.document.createElement('img');
            imageElement.setAttribute('alt', '');
            editor.insertElement(imageElement);
          }
          // Delete the image if the src was removed.
          if (imageElement && !returnValues.attributes.src) {
            imageElement.remove();
          }
          // Update the image properties.
          else {
            for (var key in returnValues.attributes) {
              if (returnValues.attributes.hasOwnProperty(key)) {
                // Update the property if a value is specified.
                if (returnValues.attributes[key].length > 0) {
                  imageElement.setAttribute(key, returnValues.attributes[key]);
                }
                // Delete the property if set to an empty string.
                else {
                  imageElement.removeAttribute(key);
                }
              }
            }
          }
        }

        // Drupal.t() will not work inside CKEditor plugins because CKEditor
        // loads the JavaScript file instead of Drupal. Pull translated strings
        // from the plugin settings that are translated server-side.
        var dialogSettings = {
          title: imageDOMElement ? editor.config.drupalImage_dialogTitleEdit : editor.config.drupalImage_dialogTitleAdd,
          dialogClass: 'editor-image-dialog'
        };

        // Open the dialog for the edit form.
        Drupal.ckeditor.openDialog(editor, Drupal.url('editor/dialog/image/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
      }
    });

    // Register the toolbar button.
    if (editor.ui.addButton) {
      editor.ui.addButton('DrupalImage', {
        label: Drupal.t('Image'),
        command: 'drupalimage',
        icon: this.path.replace(/plugin\.js.*/, 'image.png')
      });
    }

    // Double clicking an image opens its properties.
    editor.on('doubleclick', function (event) {
      var element = event.data.element;
      if (element.is('img') && !element.data('cke-realelement') && !element.isReadOnly()) {
        editor.getCommand('drupalimage').exec();
      }
    });

    // If the "menu" plugin is loaded, register the menu items.
    if (editor.addMenuItems) {
      editor.addMenuItems({
        image: {
          label: Drupal.t('Image Properties'),
          command : 'drupalimage',
          group: 'image'
        }
      });
    }

    // If the "contextmenu" plugin is loaded, register the listeners.
    if (editor.contextMenu) {
      editor.contextMenu.addListener(function (element, selection) {
        if (getSelectedImage(editor, element)) {
          return { image: CKEDITOR.TRISTATE_OFF };
        }
      });
    }
  }
});

/**
 * Finds an img tag anywhere in the current editor selection.
 */
function getSelectedImage (editor, element) {
  if (!element) {
    var sel = editor.getSelection();
    var selectedText = sel.getSelectedText().replace(/^\s\s*/, '').replace(/\s\s*$/, '');
    var isElement = sel.getType() === CKEDITOR.SELECTION_ELEMENT;
    var isEmptySelection = sel.getType() === CKEDITOR.SELECTION_TEXT && selectedText.length === 0;
    element = (isElement || isEmptySelection) && sel.getSelectedElement();
  }

  if (element && element.is('img') && !element.data('cke-realelement') && !element.isReadOnly()) {
    return element;
  }
}

})(jQuery, Drupal, drupalSettings, CKEDITOR);
