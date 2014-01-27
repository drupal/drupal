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
        modes: { wysiwyg: 1 },
        canUndo: true,
        exec: function (editor, override) {
          var imageDOMElement = null;
          var existingValues = {};
          var dialogTitle;
          var saveCallback = function (returnValues) {
            var selection = editor.getSelection();
            var imageElement = selection.getSelectedElement();

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
                    var value = returnValues.attributes[key];
                    imageElement.data('cke-saved-' + key, value);
                    imageElement.setAttribute(key, value);
                  }
                  // Delete the property if set to an empty string.
                  else {
                    imageElement.removeAttribute(key);
                  }
                }
              }
            }

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };

          // Allow CKEditor Widget plugins to execute DrupalImage's 'drupalimage'
          // command. In this case, they need to provide the DOM element for the
          // image (because this plugin wouldn't know where to find it), its
          // existing values (because they're stored within the Widget in whatever
          // way it sees fit) and a save callback (again because the Widget may
          // store the returned values in whatever way it sees fit).
          if (override) {
            imageDOMElement = override.imageDOMElement;
            existingValues = override.existingValues;
            dialogTitle = override.dialogTitle;
            if (override.saveCallback) {
              saveCallback = override.saveCallback;
            }
          }
          // Otherwise, retrieve the selected image and allow it to be edited, or
          // if no image is selected: insert a new one.
          else {
            var selection = editor.getSelection();
            var imageElement = selection.getSelectedElement();

            // If the 'drupalimage' command is being applied to a CKEditor widget,
            // then edit that Widget instead.
            if (imageElement && imageElement.type === CKEDITOR.NODE_ELEMENT && imageElement.hasAttribute('data-widget-wrapper')) {
              editor.widgets.focused.edit();
              return;
            }
            // Otherwise, check if the 'drupalimage' command is being applied to
            // an existing image tag, and then open a dialog to edit it.
            else if (isImage(imageElement) && imageElement.$) {
              imageDOMElement = imageElement.$;

              // Width and height are populated by actual dimensions.
              existingValues.width = imageDOMElement ? imageDOMElement.naturalWidth : '';
              existingValues.height = imageDOMElement ? imageDOMElement.naturalHeight : '';
              // Populate all other attributes by their specified attribute values.
              var attribute = null, attributeName;
              for (var key = 0; key < imageDOMElement.attributes.length; key++) {
                attribute = imageDOMElement.attributes.item(key);
                attributeName = attribute.nodeName.toLowerCase();
                // Don't consider data-cke-saved- attributes; they're just there to
                // work around browser quirks.
                if (attributeName.substring(0, 15) === 'data-cke-saved-') {
                  continue;
                }
                // Store the value for this attribute, unless there's a
                // data-cke-saved- alternative for it, which will contain the quirk-
                // free, original value.
                existingValues[attributeName] = imageElement.data('cke-saved-' + attributeName) || attribute.nodeValue;
              }

              dialogTitle = editor.config.drupalImage_dialogTitleEdit;
            }
            // The 'drupalimage' command is being executed to add a new image.
            else {
              dialogTitle = editor.config.drupalImage_dialogTitleAdd;
              // Allow other plugins to override the image insertion: they must
              // listen to this event and cancel the event to do so.
              if (!editor.fire('drupalimageinsert')) {
                return;
              }
            }
          }

          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated strings
          // from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: dialogTitle,
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
            command: 'drupalimage',
            group: 'image'
          }
        });
      }

      // If the "contextmenu" plugin is loaded, register the listeners.
      if (editor.contextMenu) {
        editor.contextMenu.addListener(function (element, selection) {
          if (isImage(element)) {
            return { image: CKEDITOR.TRISTATE_OFF };
          }
        });
      }
    }
  });

  function isImage(element) {
    return element && element.is('img') && !element.data('cke-realelement') && !element.isReadOnly();
  }

})(jQuery, Drupal, drupalSettings, CKEDITOR);
