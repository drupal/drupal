/* eslint-disable import/no-extraneous-dependencies */
import { Plugin } from 'ckeditor5/src/core';

/**
 * Provides a toolbar item for inserting images.
 *
 * @private
 */
class DrupalInsertImage extends Plugin {
  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    editor.ui.componentFactory.add('drupalInsertImage', () => {
      // Use upstream insertImage component when ImageInsertUI is enabled. The
      // upstream insertImage button supports inserting of external images
      // and uploading images. Out-of-the-box Drupal only uses the insertImage
      // button for inserting external images.
      if (editor.plugins.has('ImageInsertUI')) {
        return editor.ui.componentFactory.create('insertImage');
      }
      // If ImageInsertUI plugin is not enabled, fallback to using uploadImage
      // upstream button.
      if (editor.plugins.has('ImageUpload')) {
        return editor.ui.componentFactory.create('uploadImage');
      }

      throw new Error(
        'drupalInsertImage requires either ImageUpload or ImageInsertUI plugin to be enabled.',
      );
    });
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalInsertImage';
  }
}

export default DrupalInsertImage;
