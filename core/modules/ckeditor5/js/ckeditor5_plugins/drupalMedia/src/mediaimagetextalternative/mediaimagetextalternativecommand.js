/* eslint-disable import/no-extraneous-dependencies */
import { Command } from 'ckeditor5/src/core';
import { getClosestSelectedDrupalMediaElement } from '../utils';
import { METADATA_ERROR } from './utils';

/**
 * The media image text alternative command.
 *
 * This is used to change the `alt` attribute of `<drupalMedia>` elements.
 *
 * @see https://github.com/ckeditor/ckeditor5/blob/master/packages/ckeditor5-image/src/imagetextalternative/imagetextalternativecommand.js
 */
export default class MediaImageTextAlternativeCommand extends Command {
  /**
   * The command value: `false` if there is no `alt` attribute, otherwise the value of the `alt` attribute.

  /**
   * @inheritdoc
   */
  refresh() {
    const drupalMediaElement = getClosestSelectedDrupalMediaElement(
      this.editor.model.document.selection,
    );
    this.isEnabled =
      drupalMediaElement?.getAttribute('drupalMediaIsImage') &&
      drupalMediaElement.getAttribute('drupalMediaIsImage') !== METADATA_ERROR;

    if (this.isEnabled) {
      this.value = drupalMediaElement.getAttribute('drupalMediaAlt');
    } else {
      this.value = false;
    }
  }

  /**
   * Executes the command.
   *
   * @param {Object} options
   *   An options object.
   * @param {String} options.newValue The new value of the `alt` attribute to set.
   */
  execute(options) {
    const { model } = this.editor;
    const drupalMediaElement = getClosestSelectedDrupalMediaElement(
      model.document.selection,
    );

    options.newValue = options.newValue.trim();
    model.change((writer) => {
      if (options.newValue.length > 0) {
        writer.setAttribute(
          'drupalMediaAlt',
          options.newValue,
          drupalMediaElement,
        );
      } else {
        writer.removeAttribute('drupalMediaAlt', drupalMediaElement);
      }
    });
  }
}
