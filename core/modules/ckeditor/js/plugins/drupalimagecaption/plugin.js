/**
 * @file
 * Drupal Image Caption plugin.
 *
 * This alters the existing CKEditor image2 widget plugin, which is already
 * altered by the Drupal Image plugin, to:
 * - allow for the data-caption and data-align attributes to be set
 * - mimic the upcasting behavior of the caption_filter filter
 */
(function (CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('drupalimagecaption', {
    requires: 'drupalimage',

    beforeInit: function (editor) {
      // Disable default placeholder text that comes with CKEditor's image2
      // plugin: it has an inferior UX (it requires the user to manually delete
      // the place holder text).
      editor.lang.image2.captionPlaceholder = '';

      // Drupal.t() will not work inside CKEditor plugins because CKEditor loads
      // the JavaScript file instead of Drupal. Pull translated strings from the
      // plugin settings that are translated server-side.
      var placeholderText = editor.config.drupalImageCaption_captionPlaceholderText;

      // Override the image2 widget definition to handle the additional
      // data-align and data-caption attributes.
      editor.on('widgetDefinition', function (event) {
        var widgetDefinition = event.data;
        if (widgetDefinition.name !== 'image') {
          return;
        }

        // Only perform the downcasting/upcasting for to the enabled filters.
        var captionFilterEnabled = editor.config.drupalImageCaption_captionFilterEnabled;
        var alignFilterEnabled = editor.config.drupalImageCaption_alignFilterEnabled;

        // Override default features definitions for drupalimagecaption.
        CKEDITOR.tools.extend(widgetDefinition.features, {
          caption: {
            requiredContent: 'img[data-caption]'
          },
          align: {
            requiredContent: 'img[data-align]'
          }
        }, true);

        // Override requiredContent & allowedContent.
        widgetDefinition.requiredContent = 'img[alt,src,width,height,data-editor-file-uuid,data-align,data-caption]';
        widgetDefinition.allowedContent.img.attributes += ',data-align,data-caption';

        // Override allowedContent setting for the 'caption' nested editable.
        // This must match what caption_filter enforces.
        // @see \Drupal\filter\Plugin\Filter\FilterCaption::process()
        // @see \Drupal\Component\Utility\Xss::filter()
        widgetDefinition.editables.caption.allowedContent = 'a[!href]; em strong cite code br';

        // Override downcast(): ensure we *only* output <img>, but also ensure
        // we include the data-editor-file-uuid, data-align and data-caption
        // attributes.
        widgetDefinition.downcast = function (element) {
          // Find an image element in the one being downcasted (can be itself).
          var img = findElementByName(element, 'img');
          var caption = this.editables.caption;
          var captionHtml = caption && caption.getData();
          var attrs = img.attributes;

          if (captionFilterEnabled) {
            // If image contains a non-empty caption, serialize caption to the
            // data-caption attribute.
            if (captionHtml) {
              attrs['data-caption'] = captionHtml;
            }
          }
          if (alignFilterEnabled) {
            if (this.data.align !== 'none') {
              attrs['data-align'] = this.data.align;
            }
          }
          attrs['data-editor-file-uuid'] = this.data['data-editor-file-uuid'];

          return img;
        };

        // We want to upcast <img> elements to a DOM structure required by the
        // image2 widget. Depending on a case it may be:
        //   - just an <img> tag (non-captioned, not-centered image),
        //   - <img> tag in a paragraph (non-captioned, centered image),
        //   - <figure> tag (captioned image).
        // We take the same attributes into account as downcast() does.
        widgetDefinition.upcast = function (element, data) {
          if (element.name !== 'img' || !element.attributes['data-editor-file-uuid']) {
            return;
          }
          // Don't initialize on pasted fake objects.
          else if (element.attributes['data-cke-realelement']) {
            return;
          }

          var attrs = element.attributes;
          var retElement = element;

          // We won't need the attributes during editing: we'll use widget.data
          // to store them (except the caption, which is stored in the DOM).
          if (captionFilterEnabled) {
            var caption = attrs['data-caption'];
            delete attrs['data-caption'];
          }
          if (alignFilterEnabled) {
            data.align = attrs['data-align'];
            delete attrs['data-align'];
          }
          data['data-editor-file-uuid' ] = attrs['data-editor-file-uuid'];
          delete attrs['data-editor-file-uuid'];

          if (captionFilterEnabled) {
            // Unwrap from <p> wrapper created by HTML parser for a captioned
            // image. The captioned image will be transformed to <figure>, so we
            // don't want the <p> anymore.
            if (element.parent.name === 'p' && caption) {
              var index = element.getIndex();
              var splitBefore = index > 0;
              var splitAfter = index + 1 < element.parent.children.length;

              if (splitBefore) {
                element.parent.split(index);
              }
              index = element.getIndex();
              if (splitAfter) {
                element.parent.split(index + 1);
              }

              element.parent.replaceWith(element);
              retElement = element;
            }

            // If this image has a caption, create a full <figure> structure.
            if (caption) {
              var figure = new CKEDITOR.htmlParser.element('figure');
              caption = new CKEDITOR.htmlParser.fragment.fromHtml(caption, 'figcaption');

              // Use Drupal's data-placeholder attribute to insert a CSS-based,
              // translation-ready placeholder for empty captions. Note that it
              // also must to be done for new instances (see
              // widgetDefinition._createDialogSaveCallback).
              caption.attributes['data-placeholder'] = placeholderText;

              element.replaceWith(figure);
              figure.add(element);
              figure.add(caption);
              figure.attributes['class'] = editor.config.image2_captionedClass;
              retElement = figure;
            }
          }

          if (alignFilterEnabled) {
            // If this image doesn't have a caption (or the caption filter is
            // disabled), but it is centered, make sure that it's wrapped with
            // <p>, which will become a part of the widget.
            if (data.align === 'center' && (!captionFilterEnabled || !caption)) {
              var p = new CKEDITOR.htmlParser.element('p');
              element.replaceWith(p);
              p.add(element);
              // Apply the class for centered images.
              p.addClass(editor.config.image2_alignClasses[1]);
              retElement = p;
            }
          }

          // Return the upcasted element (<img>, <figure> or <p>).
          return retElement;
        };

        // Protected; keys of the widget data to be sent to the Drupal dialog.
        // Append to the values defined by the drupalimage plugin.
        // @see core/modules/ckeditor/js/plugins/drupalimage/plugin.js
        CKEDITOR.tools.extend(widgetDefinition._mapDataToDialog, {
          'align': 'data-align',
          'data-caption': 'data-caption',
          'hasCaption': 'hasCaption'
        });

        // Override Drupal dialog save callback.
        var originalCreateDialogSaveCallback = widgetDefinition._createDialogSaveCallback;
        widgetDefinition._createDialogSaveCallback = function (editor, widget) {
          var saveCallback = originalCreateDialogSaveCallback.call(this, editor, widget);

          return function (dialogReturnValues) {
            // Ensure hasCaption is a boolean. image2 assumes it always works
            // with booleans; if this is not the case, then
            // CKEDITOR.plugins.image2.stateShifter() will incorrectly mark
            // widget.data.hasCaption as "changed" (e.g. when hasCaption === 0
            // instead of hasCaption === false). This causes image2's "state
            // shifter" to enter the wrong branch of the algorithm and blow up.
            dialogReturnValues.attributes.hasCaption = !!dialogReturnValues.attributes.hasCaption;

            var actualWidget = saveCallback(dialogReturnValues);

            // By default, the template of captioned widget has no
            // data-placeholder attribute. Note that it also must be done when
            // upcasting existing elements (see widgetDefinition.upcast).
            if (dialogReturnValues.attributes.hasCaption) {
              actualWidget.editables.caption.setAttribute('data-placeholder', placeholderText);
            }
          };
        };
      }, null, null, 20); // Low priority to ensure drupalimage's event handler runs first.
    }
  });

  /**
   * Finds an element by its name.
   *
   * Function will check first the passed element itself and then all its
   * children in DFS order.
   *
   * @param CKEDITOR.htmlParser.element element
   * @param String name
   * @return CKEDITOR.htmlParser.element
   */
  function findElementByName(element, name) {
    if (element.name === name) {
      return element;
    }

    var found = null;
    element.forEach(function (el) {
      if (el.name === name) {
        found = el;
        return false; // Stop here.
      }
    }, CKEDITOR.NODE_ELEMENT);
    return found;
  }

})(CKEDITOR);
