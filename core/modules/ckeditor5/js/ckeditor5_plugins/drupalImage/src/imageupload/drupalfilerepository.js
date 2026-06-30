/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore uploadurl drupalimageuploadadapter  */

import { Plugin, logWarning, FileRepository } from 'ckeditor5';
import DrupalImageUploadAdapter from './drupalimageuploadadapter';

/**
 * Provides a Drupal upload adapter.
 *
 * @private
 */
export default class DrupalFileRepository extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [FileRepository];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalFileRepository';
  }

  /**
   * @inheritdoc
   */
  init() {
    const options = this.editor.config.get('drupalImageUpload');

    if (!options) {
      return;
    }

    if (!options.uploadUrl) {
      logWarning('simple-upload-adapter-missing-uploadurl');

      return;
    }

    this.editor.plugins.get(FileRepository).createUploadAdapter = (loader) => {
      return new DrupalImageUploadAdapter(loader, options);
    };
  }
}
