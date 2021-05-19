/**
 * @file
 * Drupal Image Style plugin.
 *
 * This alters the existing CKEditor image2 widget plugin, which is already
 * altered by the Drupal Image plugin, to allow for the data-image-style
 * attribute to be set.
 */

(function (CKEDITOR) {
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
    element.forEach((el) => {
      if (el.name === name) {
        found = el;
        // Stop here.
        return false;
      }
    }, CKEDITOR.NODE_ELEMENT);
    return found;
  }
  CKEDITOR.plugins.add('drupalimagestyle', {
    requires: 'drupalimage',

    beforeInit: function beforeInit(editor) {
      // Override the image2 widget definition to handle the additional
      // data-image-style attributes.
      editor.on(
        'widgetDefinition',
        (event) => {
          const widgetDefinition = event.data;
          if (widgetDefinition.name !== 'image') {
            return;
          }
          // Override default features definitions for drupalimagestyle.
          CKEDITOR.tools.extend(
            widgetDefinition.features,
            {
              drupalimagestyle: {
                requiredContent: 'img[data-image-style]',
              },
            },
            true,
          );

          // Override requiredContent & allowedContent.
          const requiredContent =
            widgetDefinition.requiredContent.getDefinition();
          requiredContent.attributes['data-image-style'] = '';
          widgetDefinition.requiredContent = new CKEDITOR.style(
            requiredContent,
          );
          widgetDefinition.allowedContent.img.attributes[
            '!data-image-style'
          ] = true;

          // Decorate downcast().
          const originalDowncast = widgetDefinition.downcast;
          widgetDefinition.downcast = function (element) {
            let img = originalDowncast.call(this, element);
            if (!img) {
              img = findElementByName(element, 'img');
            }
            if (
              this.data.hasOwnProperty('data-image-style') &&
              this.data['data-image-style'] !== ''
            ) {
              img.attributes['data-image-style'] =
                this.data['data-image-style'];
            }
            return img;
          };

          // Decorate upcast().
          const originalUpcast = widgetDefinition.upcast;
          widgetDefinition.upcast = function (element, data) {
            if (
              element.name !== 'img' ||
              !element.attributes['data-entity-type'] ||
              !element.attributes['data-entity-uuid'] ||
              // Don't initialize on pasted fake objects.
              element.attributes['data-cke-realelement']
            ) {
              return;
            }

            // Parse the data-image-style attribute.
            data['data-image-style'] = element.attributes['data-image-style'];

            // Upcast after parsing so correct element attributes are parsed.
            element = originalUpcast.call(this, element, data);

            return element;
          };

          // Protected; keys of the widget data to be sent to the Drupal dialog.
          // Append to the values defined by the drupalimage plugin.
          // @see core/modules/ckeditor/js/plugins/drupalimage/plugin.js
          CKEDITOR.tools.extend(widgetDefinition._mapDataToDialog, {
            'data-image-style': 'data-image-style',
          });
          // Low priority to ensure drupalimage's event handler runs first.
        },
        null,
        null,
        20,
      );
    },
  });
})(CKEDITOR);
