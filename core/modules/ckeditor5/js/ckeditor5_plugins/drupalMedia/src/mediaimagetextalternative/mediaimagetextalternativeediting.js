/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore drupalmediametadatarepository insertdrupalmediacommand */
/* cspell:ignore mediaimagetextalternative mediaimagetextalternativecommand */
/* cspell:ignore mediaimagetextalternativeediting */

import { Plugin } from 'ckeditor5/src/core';
import { Template } from 'ckeditor5/src/ui';
import MediaImageTextAlternativeCommand from './mediaimagetextalternativecommand';
import DrupalMediaMetadataRepository from '../drupalmediametadatarepository';
import { METADATA_ERROR } from './utils';

/**
 * @module drupalMedia/mediaimagetextalternative/mediaimagetextalternativeediting
 */

/**
 * The media image text alternative editing plugin.
 */
export default class MediaImageTextAlternativeEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalMediaMetadataRepository];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'MediaImageTextAlternativeEditing';
  }

  /**
   * @inheritdoc
   */
  init() {
    const {
      editor,
      editor: { model, conversion },
    } = this;

    model.schema.extend('drupalMedia', {
      allowAttributes: ['drupalMediaIsImage'],
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

          const html = new Template({
            tag: 'span',
            children: [
              {
                tag: 'span',
                attributes: {
                  class: 'drupal-media__metadata-error-icon',
                  'data-cke-tooltip-text': message,
                },
              },
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
