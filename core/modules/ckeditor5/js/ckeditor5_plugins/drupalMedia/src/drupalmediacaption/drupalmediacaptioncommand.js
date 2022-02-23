/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words imagecaption */
import { Command } from 'ckeditor5/src/core';
import { isDrupalMedia } from '../utils';

/**
 * Gets the caption model element from the media model selection.
 *
 * @param {module:engine/model/element~Element} drupalMediaModelElement
 *   The model element from which caption should be retrieved.
 * @returns {module:engine/model/element~Element|null}
 *   The caption element or `null` if the selection has no child caption
 *   element.
 */
export function getCaptionFromDrupalMediaModelElement(drupalMediaModelElement) {
  // eslint-disable-next-line no-restricted-syntax
  for (const node of drupalMediaModelElement.getChildren()) {
    if (!!node && node.is('element', 'caption')) {
      return node;
    }
  }

  return null;
}

/**
 * The toggle Drupal Media caption command.
 *
 * This command either adds or removes the caption of a selected drupalMedia
 * element.
 *
 * This is inspired by the CKEditor 5 image caption plugin.
 *
 * @see module:image/imagecaption~ImageCaption
 *
 * @extends module:core/command~Command
 *
 * @internal
 */
export default class ToggleDrupalMediaCaptionCommand extends Command {
  /**
   * @inheritDoc
   */
  refresh() {
    const element = this.editor.model.document.selection.getSelectedElement();

    this.isEnabled = isDrupalMedia(element);

    if (!this.isEnabled) {
      this.value = false;
    } else {
      this.value = !!getCaptionFromDrupalMediaModelElement(element);
    }
  }

  /**
   * Executes the command.
   *
   * @example
   *   editor.execute('toggleMediaCaption');
   *
   * @param {Object} [options]
   *   Options for the executed command.
   * @param {String} [options.focusCaptionOnShow]
   *   When true and the caption shows up, the selection will be moved into it
   *    When true: If a caption is present, the selection will be moved to that
   *    caption immediately.
   *
   * @fires execute
   */
  execute(options = {}) {
    const { focusCaptionOnShow } = options;
    this.editor.model.change((writer) => {
      if (this.value) {
        this._hideDrupalMediaCaption(writer);
      } else {
        this._showDrupalMediaCaption(writer, focusCaptionOnShow);
      }
    });
  }

  /**
   * Shows the caption of a selected drupalMedia element.
   *
   * This also attempts to restore the caption content from the
   * `DrupalMediaEditing` caption registry. If the `focusCaptionOnShow` option
   * is true, the selection is immediately moved to the caption.
   *
   * @param {module:engine/model/writer~Writer} writer
   *   The model writer.
   * @param {bool} focusCaptionOnShow
   *   Flag indicating whether the caption should be focused.
   */
  _showDrupalMediaCaption(writer, focusCaptionOnShow) {
    const model = this.editor.model;
    const selection = model.document.selection;
    const mediaCaptionEditing = this.editor.plugins.get(
      'DrupalMediaCaptionEditing',
    );
    const selectedMedia = selection.getSelectedElement();
    const savedCaption = mediaCaptionEditing._getSavedCaption(selectedMedia);

    // Try restoring the caption from the DrupalMediaCaptionEditing plugin storage.
    const newCaptionElement = savedCaption || writer.createElement('caption');

    writer.append(newCaptionElement, selectedMedia);

    if (focusCaptionOnShow) {
      writer.setSelection(newCaptionElement, 'in');
    }
  }

  /**
   * Hides the caption of a selected drupalMedia element.
   *
   * The content of the caption is stored in the `DrupalMediaCaptionEditing`
   * caption registry to make this a reversible action.
   *
   * @param {module:engine/model/writer~Writer} writer
   *   The model writer.
   */
  _hideDrupalMediaCaption(writer) {
    const editor = this.editor;
    const selection = editor.model.document.selection;
    const mediaCaptionEditing = editor.plugins.get('DrupalMediaCaptionEditing');
    const selectedMedia = selection.getSelectedElement();

    if (selectedMedia) {
      const captionElement =
        getCaptionFromDrupalMediaModelElement(selectedMedia);

      // Store the caption content so it can be restored quickly if the user
      // changes their mind.
      mediaCaptionEditing._saveCaption(selectedMedia, captionElement);
      writer.setSelection(selectedMedia, 'on');
      writer.remove(captionElement);
    }
  }
}
