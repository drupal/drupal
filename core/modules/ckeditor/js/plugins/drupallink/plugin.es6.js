/**
 * @file
 * Drupal Link plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  function parseAttributes(editor, element) {
    var parsedAttributes = {};

    var domElement = element.$;
    var attribute;
    var attributeName;
    for (var attrIndex = 0; attrIndex < domElement.attributes.length; attrIndex++) {
      attribute = domElement.attributes.item(attrIndex);
      attributeName = attribute.nodeName.toLowerCase();
      // Ignore data-cke-* attributes; they're CKEditor internals.
      if (attributeName.indexOf('data-cke-') === 0) {
        continue;
      }
      // Store the value for this attribute, unless there's a data-cke-saved-
      // alternative for it, which will contain the quirk-free, original value.
      parsedAttributes[attributeName] = element.data('cke-saved-' + attributeName) || attribute.nodeValue;
    }

    // Remove any cke_* classes.
    if (parsedAttributes.class) {
      parsedAttributes.class = CKEDITOR.tools.trim(parsedAttributes.class.replace(/cke_\S+/, ''));
    }

    return parsedAttributes;
  }

  function getAttributes(editor, data) {
    var set = {};
    for (var attributeName in data) {
      if (data.hasOwnProperty(attributeName)) {
        set[attributeName] = data[attributeName];
      }
    }

    // CKEditor tracks the *actual* saved href in a data-cke-saved-* attribute
    // to work around browser quirks. We need to update it.
    set['data-cke-saved-href'] = set.href;

    // Remove all attributes which are not currently set.
    var removed = {};
    for (var s in set) {
      if (set.hasOwnProperty(s)) {
        delete removed[s];
      }
    }

    return {
      set: set,
      removed: CKEDITOR.tools.objectKeys(removed)
    };
  }

  CKEDITOR.plugins.add('drupallink', {
    icons: 'drupallink,drupalunlink',
    hidpi: true,

    init: function (editor) {
      // Add the commands for link and unlink.
      editor.addCommand('drupallink', {
        allowedContent: {
          a: {
            attributes: {
              '!href': true
            },
            classes: {}
          }
        },
        requiredContent: new CKEDITOR.style({
          element: 'a',
          attributes: {
            href: ''
          }
        }),
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          var drupalImageUtils = CKEDITOR.plugins.drupalimage;
          var focusedImageWidget = drupalImageUtils && drupalImageUtils.getFocusedWidget(editor);
          var linkElement = getSelectedLink(editor);

          // Set existing values based on selected element.
          var existingValues = {};
          if (linkElement && linkElement.$) {
            existingValues = parseAttributes(editor, linkElement);
          }
          // Or, if an image widget is focused, we're editing a link wrapping
          // an image widget.
          else if (focusedImageWidget && focusedImageWidget.data.link) {
            existingValues = CKEDITOR.tools.clone(focusedImageWidget.data.link);
          }

          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function (returnValues) {
            // If an image widget is focused, we're not editing an independent
            // link, but we're wrapping an image widget in a link.
            if (focusedImageWidget) {
              focusedImageWidget.setData('link', CKEDITOR.tools.extend(returnValues.attributes, focusedImageWidget.data.link));
              editor.fire('saveSnapshot');
              return;
            }

            editor.fire('saveSnapshot');

            // Create a new link element if needed.
            if (!linkElement && returnValues.attributes.href) {
              var selection = editor.getSelection();
              var range = selection.getRanges(1)[0];

              // Use link URL as text with a collapsed cursor.
              if (range.collapsed) {
                // Shorten mailto URLs to just the email address.
                var text = new CKEDITOR.dom.text(returnValues.attributes.href.replace(/^mailto:/, ''), editor.document);
                range.insertNode(text);
                range.selectNodeContents(text);
              }

              // Create the new link by applying a style to the new text.
              var style = new CKEDITOR.style({element: 'a', attributes: returnValues.attributes});
              style.type = CKEDITOR.STYLE_INLINE;
              style.applyToRange(range);
              range.select();

              // Set the link so individual properties may be set below.
              linkElement = getSelectedLink(editor);
            }
            // Update the link properties.
            else if (linkElement) {
              for (var attrName in returnValues.attributes) {
                if (returnValues.attributes.hasOwnProperty(attrName)) {
                  // Update the property if a value is specified.
                  if (returnValues.attributes[attrName].length > 0) {
                    var value = returnValues.attributes[attrName];
                    linkElement.data('cke-saved-' + attrName, value);
                    linkElement.setAttribute(attrName, value);
                  }
                  // Delete the property if set to an empty string.
                  else {
                    linkElement.removeAttribute(attrName);
                  }
                }
              }
            }

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: linkElement ? editor.config.drupalLink_dialogTitleEdit : editor.config.drupalLink_dialogTitleAdd,
            dialogClass: 'editor-link-dialog'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('editor/dialog/link/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });
      editor.addCommand('drupalunlink', {
        contextSensitive: 1,
        startDisabled: 1,
        requiredContent: new CKEDITOR.style({
          element: 'a',
          attributes: {
            href: ''
          }
        }),
        exec: function (editor) {
          var style = new CKEDITOR.style({element: 'a', type: CKEDITOR.STYLE_INLINE, alwaysRemoveElement: 1});
          editor.removeStyle(style);
        },
        refresh: function (editor, path) {
          var element = path.lastElement && path.lastElement.getAscendant('a', true);
          if (element && element.getName() === 'a' && element.getAttribute('href') && element.getChildCount()) {
            this.setState(CKEDITOR.TRISTATE_OFF);
          }
          else {
            this.setState(CKEDITOR.TRISTATE_DISABLED);
          }
        }
      });

      // CTRL + K.
      editor.setKeystroke(CKEDITOR.CTRL + 75, 'drupallink');

      // Add buttons for link and unlink.
      if (editor.ui.addButton) {
        editor.ui.addButton('DrupalLink', {
          label: Drupal.t('Link'),
          command: 'drupallink'
        });
        editor.ui.addButton('DrupalUnlink', {
          label: Drupal.t('Unlink'),
          command: 'drupalunlink'
        });
      }

      editor.on('doubleclick', function (evt) {
        var element = getSelectedLink(editor) || evt.data.element;

        if (!element.isReadOnly()) {
          if (element.is('a')) {
            editor.getSelection().selectElement(element);
            editor.getCommand('drupallink').exec();
          }
        }
      });

      // If the "menu" plugin is loaded, register the menu items.
      if (editor.addMenuItems) {
        editor.addMenuItems({
          link: {
            label: Drupal.t('Edit Link'),
            command: 'drupallink',
            group: 'link',
            order: 1
          },

          unlink: {
            label: Drupal.t('Unlink'),
            command: 'drupalunlink',
            group: 'link',
            order: 5
          }
        });
      }

      // If the "contextmenu" plugin is loaded, register the listeners.
      if (editor.contextMenu) {
        editor.contextMenu.addListener(function (element, selection) {
          if (!element || element.isReadOnly()) {
            return null;
          }
          var anchor = getSelectedLink(editor);
          if (!anchor) {
            return null;
          }

          var menu = {};
          if (anchor.getAttribute('href') && anchor.getChildCount()) {
            menu = {link: CKEDITOR.TRISTATE_OFF, unlink: CKEDITOR.TRISTATE_OFF};
          }
          return menu;
        });
      }
    }
  });

  /**
   * Get the surrounding link element of current selection.
   *
   * The following selection will all return the link element.
   *
   * @example
   *  <a href="#">li^nk</a>
   *  <a href="#">[link]</a>
   *  text[<a href="#">link]</a>
   *  <a href="#">li[nk</a>]
   *  [<b><a href="#">li]nk</a></b>]
   *  [<a href="#"><b>li]nk</b></a>
   *
   * @param {CKEDITOR.editor} editor
   *   The CKEditor editor object
   *
   * @return {?HTMLElement}
   *   The selected link element, or null.
   *
   */
  function getSelectedLink(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    if (selectedElement && selectedElement.is('a')) {
      return selectedElement;
    }

    var range = selection.getRanges(true)[0];

    if (range) {
      range.shrink(CKEDITOR.SHRINK_TEXT);
      return editor.elementPath(range.getCommonAncestor()).contains('a', 1);
    }
    return null;
  }

  // Expose an API for other plugins to interact with drupallink widgets.
  // (Compatible with the official CKEditor link plugin's API:
  // http://dev.ckeditor.com/ticket/13885.)
  CKEDITOR.plugins.drupallink = {
    parseLinkAttributes: parseAttributes,
    getLinkAttributes: getAttributes
  };

})(jQuery, Drupal, drupalSettings, CKEDITOR);
