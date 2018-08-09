/**
 * @file
 * Drupal Image Caption plugin.
 *
 * This alters the existing CKEditor image2 widget plugin, which is already
 * altered by the Drupal Image plugin, to:
 * - allow for the data-caption and data-align attributes to be set
 * - mimic the upcasting behavior of the caption_filter filter.
 *
 * @ignore
 */

(function(CKEDITOR) {
  /**
   * Finds an element by its name.
   *
   * Function will check first the passed element itself and then all its
   * children in DFS order.
   *
   * @param {CKEDITOR.htmlParser.element} element
   *   The element to search.
   * @param {string} name
   *   The element name to search for.
   *
   * @return {?CKEDITOR.htmlParser.element}
   *   The found element, or null.
   */
  function findElementByName(element, name) {
    if (element.name === name) {
      return element;
    }

    let found = null;
    element.forEach(el => {
      if (el.name === name) {
        found = el;
        // Stop here.
        return false;
      }
    }, CKEDITOR.NODE_ELEMENT);
    return found;
  }

  CKEDITOR.plugins.add('drupalimagecaption', {
    requires: 'drupalimage',

    beforeInit(editor) {
      // Disable default placeholder text that comes with CKEditor's image2
      // plugin: it has an inferior UX (it requires the user to manually delete
      // the place holder text).
      editor.lang.image2.captionPlaceholder = '';

      // Drupal.t() will not work inside CKEditor plugins because CKEditor loads
      // the JavaScript file instead of Drupal. Pull translated strings from the
      // plugin settings that are translated server-side.
      const placeholderText =
        editor.config.drupalImageCaption_captionPlaceholderText;

      // Override the image2 widget definition to handle the additional
      // data-align and data-caption attributes.
      editor.on(
        'widgetDefinition',
        event => {
          const widgetDefinition = event.data;
          if (widgetDefinition.name !== 'image') {
            return;
          }

          // Only perform the downcasting/upcasting for to the enabled filters.
          const captionFilterEnabled =
            editor.config.drupalImageCaption_captionFilterEnabled;
          const alignFilterEnabled =
            editor.config.drupalImageCaption_alignFilterEnabled;

          // Override default features definitions for drupalimagecaption.
          CKEDITOR.tools.extend(
            widgetDefinition.features,
            {
              caption: {
                requiredContent: 'img[data-caption]',
              },
              align: {
                requiredContent: 'img[data-align]',
              },
            },
            true,
          );

          // Extend requiredContent & allowedContent.
          // CKEDITOR.style is an immutable object: we cannot modify its
          // definition to extend requiredContent. Hence we get the definition,
          // modify it, and pass it to a new CKEDITOR.style instance.
          const requiredContent = widgetDefinition.requiredContent.getDefinition();
          requiredContent.attributes['data-align'] = '';
          requiredContent.attributes['data-caption'] = '';
          widgetDefinition.requiredContent = new CKEDITOR.style(
            requiredContent,
          );
          widgetDefinition.allowedContent.img.attributes['!data-align'] = true;
          widgetDefinition.allowedContent.img.attributes[
            '!data-caption'
          ] = true;

          // Override allowedContent setting for the 'caption' nested editable.
          // This must match what caption_filter enforces.
          // @see \Drupal\filter\Plugin\Filter\FilterCaption::process()
          // @see \Drupal\Component\Utility\Xss::filter()
          widgetDefinition.editables.caption.allowedContent =
            'a[!href]; em strong cite code br';

          // Override downcast(): ensure we *only* output <img>, but also ensure
          // we include the data-entity-type, data-entity-uuid, data-align and
          // data-caption attributes.
          const originalDowncast = widgetDefinition.downcast;
          widgetDefinition.downcast = function(element) {
            const img = findElementByName(element, 'img');
            originalDowncast.call(this, img);

            const caption = this.editables.caption;
            const captionHtml = caption && caption.getData();
            const attrs = img.attributes;

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

            // If img is wrapped with a link, we want to return that link.
            if (img.parent.name === 'a') {
              return img.parent;
            }

            return img;
          };

          // We want to upcast <img> elements to a DOM structure required by the
          // image2 widget. Depending on a case it may be:
          //   - just an <img> tag (non-captioned, not-centered image),
          //   - <img> tag in a paragraph (non-captioned, centered image),
          //   - <figure> tag (captioned image).
          // We take the same attributes into account as downcast() does.
          const originalUpcast = widgetDefinition.upcast;
          widgetDefinition.upcast = function(element, data) {
            if (
              element.name !== 'img' ||
              !element.attributes['data-entity-type'] ||
              !element.attributes['data-entity-uuid']
            ) {
              return;
            }
            // Don't initialize on pasted fake objects.
            if (element.attributes['data-cke-realelement']) {
              return;
            }

            element = originalUpcast.call(this, element, data);
            const attrs = element.attributes;

            if (element.parent.name === 'a') {
              element = element.parent;
            }

            let retElement = element;
            let caption;

            // We won't need the attributes during editing: we'll use widget.data
            // to store them (except the caption, which is stored in the DOM).
            if (captionFilterEnabled) {
              caption = attrs['data-caption'];
              delete attrs['data-caption'];
            }
            if (alignFilterEnabled) {
              data.align = attrs['data-align'];
              delete attrs['data-align'];
            }
            data['data-entity-type'] = attrs['data-entity-type'];
            delete attrs['data-entity-type'];
            data['data-entity-uuid'] = attrs['data-entity-uuid'];
            delete attrs['data-entity-uuid'];

            if (captionFilterEnabled) {
              // Unwrap from <p> wrapper created by HTML parser for a captioned
              // image. The captioned image will be transformed to <figure>, so we
              // don't want the <p> anymore.
              if (element.parent.name === 'p' && caption) {
                let index = element.getIndex();
                const splitBefore = index > 0;
                const splitAfter = index + 1 < element.parent.children.length;

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
                const figure = new CKEDITOR.htmlParser.element('figure');
                caption = new CKEDITOR.htmlParser.fragment.fromHtml(
                  caption,
                  'figcaption',
                );

                // Use Drupal's data-placeholder attribute to insert a CSS-based,
                // translation-ready placeholder for empty captions. Note that it
                // also must to be done for new instances (see
                // widgetDefinition._createDialogSaveCallback).
                caption.attributes['data-placeholder'] = placeholderText;

                element.replaceWith(figure);
                figure.add(element);
                figure.add(caption);
                figure.attributes.class = editor.config.image2_captionedClass;
                retElement = figure;
              }
            }

            if (alignFilterEnabled) {
              // If this image doesn't have a caption (or the caption filter is
              // disabled), but it is centered, make sure that it's wrapped with
              // <p>, which will become a part of the widget.
              if (
                data.align === 'center' &&
                (!captionFilterEnabled || !caption)
              ) {
                const p = new CKEDITOR.htmlParser.element('p');
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
            align: 'data-align',
            'data-caption': 'data-caption',
            hasCaption: 'hasCaption',
          });

          // Override Drupal dialog save callback.
          const originalCreateDialogSaveCallback =
            widgetDefinition._createDialogSaveCallback;
          widgetDefinition._createDialogSaveCallback = function(
            editor,
            widget,
          ) {
            const saveCallback = originalCreateDialogSaveCallback.call(
              this,
              editor,
              widget,
            );

            return function(dialogReturnValues) {
              // Ensure hasCaption is a boolean. image2 assumes it always works
              // with booleans; if this is not the case, then
              // CKEDITOR.plugins.image2.stateShifter() will incorrectly mark
              // widget.data.hasCaption as "changed" (e.g. when hasCaption === 0
              // instead of hasCaption === false). This causes image2's "state
              // shifter" to enter the wrong branch of the algorithm and blow up.
              dialogReturnValues.attributes.hasCaption = !!dialogReturnValues
                .attributes.hasCaption;

              const actualWidget = saveCallback(dialogReturnValues);

              // By default, the template of captioned widget has no
              // data-placeholder attribute. Note that it also must be done when
              // upcasting existing elements (see widgetDefinition.upcast).
              if (dialogReturnValues.attributes.hasCaption) {
                actualWidget.editables.caption.setAttribute(
                  'data-placeholder',
                  placeholderText,
                );

                // Some browsers will add a <br> tag to a newly created DOM
                // element with no content. Remove this <br> if it is the only
                // thing in the caption. Our placeholder support requires the
                // element be entirely empty. See filter-caption.css.
                const captionElement = actualWidget.editables.caption.$;
                if (
                  captionElement.childNodes.length === 1 &&
                  captionElement.childNodes.item(0).nodeName === 'BR'
                ) {
                  captionElement.removeChild(captionElement.childNodes.item(0));
                }
              }
            };
          };
          // Low priority to ensure drupalimage's event handler runs first.
        },
        null,
        null,
        20,
      );
    },

    afterInit(editor) {
      const disableButtonIfOnWidget = function(evt) {
        const widget = editor.widgets.focused;
        if (widget && widget.name === 'image') {
          this.setState(CKEDITOR.TRISTATE_DISABLED);
          evt.cancel();
        }
      };

      // Disable alignment buttons if the align filter is not enabled.
      if (
        editor.plugins.justify &&
        !editor.config.drupalImageCaption_alignFilterEnabled
      ) {
        let cmd;
        const commands = [
          'justifyleft',
          'justifycenter',
          'justifyright',
          'justifyblock',
        ];
        for (let n = 0; n < commands.length; n++) {
          cmd = editor.getCommand(commands[n]);
          cmd.contextSensitive = 1;
          cmd.on('refresh', disableButtonIfOnWidget, null, null, 4);
        }
      }
    },
  });
})(CKEDITOR);
