/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore imagealternativetext drupalimagealternativetextediting drupalimagetextalternativecommand textalternativemissingview imagetextalternativecommand */

/**
 * @module drupalImage/imagealternativetext/drupalimagealternativetextediting
 */

import { Plugin } from 'ckeditor5/src/core';
import ImageTextAlternativeCommand from '@ckeditor/ckeditor5-image/src/imagetextalternative/imagetextalternativecommand';

/**
 * The Drupal image alternative text editing plugin.
 *
 * Registers the `imageTextAlternative` command.
 *
 * @extends module:core/plugin~Plugin
 *
 * @internal
 */
export default class DrupalImageTextAlternativeEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return ['ImageUtils'];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageAlternativeTextEditing';
  }

  constructor(editor) {
    super(editor);

    /**
     * Keeps references to instances of `TextAlternativeMissingView`.
     *
     * @member {Set<module:drupalImage/imagetextalternative/ui/textalternativemissingview~TextAlternativeMissingView>} #_missingAltTextViewReferences
     * @private
     */
    this._missingAltTextViewReferences = new Set();
  }

  /**
   * @inheritdoc
   */
  init() {
    const editor = this.editor;

    editor.conversion
      .for('editingDowncast')
      .add(this._imageEditingDowncastConverter('attribute:alt', editor))
      // Including changes to src ensures the converter will execute for images
      // that do not yet have alt attributes, as we specifically want to add the
      // missing alt text warning to images without alt attributes.
      .add(this._imageEditingDowncastConverter('attribute:src', editor));

    editor.commands.add(
      'imageTextAlternative',
      new ImageTextAlternativeCommand(this.editor),
    );

    editor.editing.view.on('render', () => {
      // eslint-disable-next-line no-restricted-syntax
      for (const view of this._missingAltTextViewReferences) {
        // Destroy view instances that are not connected to the DOM to ensure
        // there are no memory leaks.
        // https://developer.mozilla.org/en-US/docs/Web/API/Node/isConnected
        if (!view.button.element.isConnected) {
          view.destroy();
          this._missingAltTextViewReferences.delete(view);
        }
      }
    });
  }

  /**
   * Helper that generates model to editing view converters to display missing
   * alt text warning.
   *
   * @param {string} eventName
   *   The name of the event the converter should be attached to.
   *
   * @return {function}
   *   A function that attaches downcast converter to the conversion dispatcher.
   *
   * @private
   */
  _imageEditingDowncastConverter(eventName) {
    const converter = (evt, data, conversionApi) => {
      const editor = this.editor;
      const imageUtils = editor.plugins.get('ImageUtils');
      if (!imageUtils.isImage(data.item)) {
        return;
      }

      const viewElement = conversionApi.mapper.toViewElement(data.item);
      const existingWarning = Array.from(viewElement.getChildren()).find(
        (child) => child.getCustomProperty('drupalImageMissingAltWarning'),
      );
      const hasAlt = data.item.hasAttribute('alt');

      if (hasAlt) {
        // Remove existing warning if alt text is set and there's an existing
        // warning.
        if (existingWarning) {
          conversionApi.writer.remove(existingWarning);
        }
        return;
      }

      // Nothing to do if alt text doesn't exist and there's already an existing
      // warning.
      if (existingWarning) {
        return;
      }

      const view = editor.ui.componentFactory.create(
        'drupalImageAlternativeTextMissing',
      );
      view.listenTo(editor.ui, 'update', () => {
        const selectionRange = editor.model.document.selection.getFirstRange();
        const imageRange = editor.model.createRangeOn(data.item);
        // Set the view `isSelected` property depending on whether the model
        // element associated to the view element is in the selection.
        view.set({
          isSelected:
            selectionRange.containsRange(imageRange) ||
            selectionRange.isIntersecting(imageRange),
        });
      });
      view.render();

      // Add reference to the created view element so that it can be destroyed
      // when the view is no longer connected.
      this._missingAltTextViewReferences.add(view);

      const html = conversionApi.writer.createUIElement(
        'span',
        {
          class: 'image-alternative-text-missing-wrapper',
        },
        function (domDocument) {
          const wrapperDomElement = this.toDomElement(domDocument);
          wrapperDomElement.appendChild(view.element);

          return wrapperDomElement;
        },
      );

      conversionApi.writer.setCustomProperty(
        'drupalImageMissingAltWarning',
        true,
        html,
      );
      conversionApi.writer.insert(
        conversionApi.writer.createPositionAt(viewElement, 'end'),
        html,
      );
    };
    return (dispatcher) => {
      dispatcher.on(eventName, converter, { priority: 'low' });
    };
  }
}
