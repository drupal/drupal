/**
 * @file
 * Drupal Link plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('drupallink', {
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
          var linkDOMElement = null;

          // Set existing values based on selected element.
          var existingValues = {};
          if (linkElement && linkElement.$) {
            linkDOMElement = linkElement.$;

            // Populate an array with the link's current attributes.
            var attribute = null;
            var attributeName;
            for (var attrIndex = 0; attrIndex < linkDOMElement.attributes.length; attrIndex++) {
              attribute = linkDOMElement.attributes.item(attrIndex);
              attributeName = attribute.nodeName.toLowerCase();
              // Don't consider data-cke-saved- attributes; they're just there
              // to work around browser quirks.
              if (attributeName.substring(0, 15) === 'data-cke-saved-') {
                continue;
              }
              // Store the value for this attribute, unless there's a
              // data-cke-saved- alternative for it, which will contain the
              // quirk-free, original value.
              existingValues[attributeName] = linkElement.data('cke-saved-' + attributeName) || attribute.nodeValue;
            }
          }
          // Or, if an image widget is focused, we're editing a link wrapping
          // an image widget.
          else if (focusedImageWidget && focusedImageWidget.data.link) {
            var url = focusedImageWidget.data.link.url;
            existingValues.href = url.protocol + url.url;
          }

          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function (returnValues) {
            // If an image widget is focused, we're not editing an independent
            // link, but we're wrapping an image widget in a link.
            if (focusedImageWidget) {
              var urlMatch = returnValues.attributes.href.match(urlRegex);
              focusedImageWidget.setData('link', {
                type: 'url',
                url: {
                  protocol: urlMatch[1],
                  url: urlMatch[2]
                }
              });
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
        allowedContent: {
          a: {
            attributes: {
              '!href': true
            }
          }
        },
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
          command: 'drupallink',
          icon: this.path + '/link.png'
        });
        editor.ui.addButton('DrupalUnlink', {
          label: Drupal.t('Unlink'),
          command: 'drupalunlink',
          icon: this.path + '/unlink.png'
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

  var urlRegex = /^((?:http|https):\/\/)?(.*)$/;

  /**
   * The image2 plugin is currently tightly coupled to the link plugin: it
   * calls CKEDITOR.plugins.link.parseLinkAttributes().
   *
   * Drupal 8's CKEditor build doesn't include the 'link' plugin. Because it
   * includes its own link plugin that integrates with Drupal's dialog system.
   * So, to allow images to be linked, we need to duplicate the necessary subset
   * of the logic.
   *
   * @todo Remove once we update to CKEditor 4.5.5.
   * @see https://dev.ckeditor.com/ticket/13885
   */
  CKEDITOR.plugins.link = CKEDITOR.plugins.link || {
    parseLinkAttributes: function (editor, element) {
      var href = (element && (element.data('cke-saved-href') || element.getAttribute('href'))) || '';
      var urlMatch = href.match(urlRegex);
      return {
        type: 'url',
        url: {
          protocol: urlMatch[1],
          url: urlMatch[2]
        }
      };
    },
    getLinkAttributes: function (editor, data) {
      var set = {};

      var protocol = (data.url && typeof data.url.protocol !== 'undefined') ? data.url.protocol : 'http://';
      var url = (data.url && CKEDITOR.tools.trim(data.url.url)) || '';
      set['data-cke-saved-href'] = (url.indexOf('/') === 0) ? url : protocol + url;

      // Browser need the "href" fro copy/paste link to work. (#6641)
      if (set['data-cke-saved-href']) {
        set.href = set['data-cke-saved-href'];
      }

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
  };

})(jQuery, Drupal, drupalSettings, CKEDITOR);
