/* eslint-disable import/no-extraneous-dependencies */
import { Command } from 'ckeditor5/src/core';
import { isDrupalMedia } from '../utils';
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
   * @inheritDoc
   */
  refresh() {
    const element = this.editor.model.document.selection.getSelectedElement();
    this.isEnabled =
      isDrupalMedia(element) &&
      element.getAttribute('drupalMediaIsImage') &&
      element.getAttribute('drupalMediaIsImage') !== METADATA_ERROR;

    if (isDrupalMedia(element) && element.hasAttribute('drupalMediaAlt')) {
      this.value = element.getAttribute('drupalMediaAlt');
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
    const imageElement = model.document.selection.getSelectedElement();

    options.newValue = options.newValue.trim();
    model.change((writer) => {
      if (options.newValue.length > 0) {
        writer.setAttribute('drupalMediaAlt', options.newValue, imageElement);
      } else {
        writer.removeAttribute('drupalMediaAlt', imageElement);
      }
    });
  }
}
