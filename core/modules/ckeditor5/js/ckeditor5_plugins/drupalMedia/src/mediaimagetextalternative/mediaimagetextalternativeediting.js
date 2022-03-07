/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words mediaimagetextalternativecommand drupalmediametadatarepository insertdrupalmediacommand */

import { Plugin } from 'ckeditor5/src/core';
import { TooltipView, Template } from 'ckeditor5/src/ui';
import MediaImageTextAlternativeCommand from './mediaimagetextalternativecommand';
import DrupalMediaMetadataRepository from '../drupalmediametadatarepository';
import { isDrupalMedia } from '../utils';
import { METADATA_ERROR } from './utils';

/**
 * @module drupalMedia/mediaimagetextalternative/mediaimagetextalternativeediting
 */

/**
 * The media image text alternative editing plugin.
 */
export default class MediaImageTextAlternativeEditing extends Plugin {
  /**
   * @inheritDoc
   */
  static get requires() {
    return [DrupalMediaMetadataRepository];
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'MediaImageTextAlternativeEditing';
  }

  /**
   * Upcasts `drupalMediaIsImage` from Drupal Media metadata.
   *
   * @param {module:engine/model/node~Node} modelElement
   *   The `drupalMedia` model element.
   *
   * @see module:drupalMedia/drupalmediametadatarepository~DrupalMediaMetadataRepository
   *
   * @private
   */
  _upcastDrupalMediaIsImage(modelElement) {
    const { model, plugins } = this.editor;
    const metadataRepository = plugins.get('DrupalMediaMetadataRepository');

    // Get all metadata for drupalMedia elements to set value for
    // drupalMediaIsImage attribute. When other plugins start using the
    // metadata, this functionality will be handled more generically.
    metadataRepository
      .getMetadata(modelElement)
      .then((metadata) => {
        if (!modelElement) {
          // Nothing to do if model element has been removed before
          // promise was resolved.
          return;
        }
        // Enqueue a model change that is not visible to the undo/redo feature.
        model.enqueueChange({ isUndoable: false }, (writer) => {
          writer.setAttribute(
            'drupalMediaIsImage',
            !!metadata.imageSourceMetadata,
            modelElement,
          );
        });
      })
      .catch((e) => {
        if (!modelElement) {
          // Nothing to do if model element has been removed before
          // promise was resolved.
          return;
        }
        console.warn(e.toString());
        model.enqueueChange({ isUndoable: false }, (writer) => {
          writer.setAttribute(
            'drupalMediaIsImage',
            METADATA_ERROR,
            modelElement,
          );
        });
      });
  }

  /**
   * @inheritDoc
   */
  init() {
    const {
      editor,
      editor: { model, conversion },
    } = this;

    model.schema.extend('drupalMedia', {
      allowAttributes: ['drupalMediaIsImage'],
    });

    // Listen to `insertContent` event on the model to set `drupalMediaIsImage`
    // attribute when `drupalMedia` model element is inserted directly to the
    // model.
    // @see module:drupalMedia/insertdrupalmediacommand~InsertDrupalMediaCommand
    this.listenTo(model, 'insertContent', (evt, [modelElement]) => {
      if (!isDrupalMedia(modelElement)) {
        return;
      }

      this._upcastDrupalMediaIsImage(modelElement);
    });

    // On upcast, get `drupalMediaIsImage` attribute value from media metadata
    // repository.
    conversion.for('upcast').add((dispatcher) => {
      dispatcher.on(
        'element:drupal-media',
        (event, data) => {
          const [modelElement] = data.modelRange.getItems();
          if (!isDrupalMedia(modelElement)) {
            return;
          }

          this._upcastDrupalMediaIsImage(modelElement);
        },
        // This converter needs to have the lowest priority to ensure that the
        // model element and its attributes have been converted.
        { priority: 'lowest' },
      );
    });

    // Display error in the editor if fetching Drupal Media metadata failed.
    conversion.for('editingDowncast').add((dispatcher) => {
      dispatcher.on(
        'attribute:drupalMediaIsImage',
        (event, data, conversionApi) => {
          const { writer, mapper } = conversionApi;
          const container = mapper.toViewElement(data.item);

          if (data.attributeNewValue !== METADATA_ERROR) {
            const existingError = Array.from(container.getChildren()).find(
              (child) => child.getCustomProperty('drupalMediaMetadataError'),
            );
            // If the view contains an existing error, it should be removed
            // since retrieving metadata was successful.
            if (existingError) {
              writer.setCustomProperty(
                'widgetLabel',
                existingError.getCustomProperty(
                  'drupalMediaOriginalWidgetLabel',
                ),
                existingError,
              );
              writer.removeElement(existingError);
            }

            return;
          }

          const message = Drupal.t(
            'Not all functionality may be available because some information could not be retrieved.',
          );

          const tooltip = new TooltipView();
          tooltip.text = message;
          tooltip.position = 'sw';

          const html = new Template({
            tag: 'span',
            children: [
              {
                tag: 'span',
                attributes: {
                  class: 'drupal-media__metadata-error-icon',
                },
              },
              tooltip,
            ],
          }).render();

          const error = writer.createRawElement(
            'div',
            {
              class: 'drupal-media__metadata-error',
            },
            (domElement, domConverter) => {
              domConverter.setContentOf(domElement, html.outerHTML);
            },
          );
          writer.setCustomProperty('drupalMediaMetadataError', true, error);

          // Edit widget label to ensure the current status of media embed is
          // available for screen reader users.
          const originalWidgetLabel =
            container.getCustomProperty('widgetLabel');
          writer.setCustomProperty(
            'drupalMediaOriginalWidgetLabel',
            originalWidgetLabel,
            error,
          );
          writer.setCustomProperty(
            'widgetLabel',
            `${originalWidgetLabel} (${message})`,
            container,
          );

          writer.insert(writer.createPositionAt(container, 0), error);
        },
        { priority: 'low' },
      );
    });

    editor.commands.add(
      'mediaImageTextAlternative',
      new MediaImageTextAlternativeCommand(this.editor),
    );
  }
}
