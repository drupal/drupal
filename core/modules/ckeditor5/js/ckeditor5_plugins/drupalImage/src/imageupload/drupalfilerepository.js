/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words uploadurl drupalimageuploadadapter  */

import { Plugin } from 'ckeditor5/src/core';
import { FileRepository } from 'ckeditor5/src/upload';
import { logWarning } from 'ckeditor5/src/utils';
import DrupalImageUploadAdapter from './drupalimageuploadadapter';

/**
 * @internal
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
