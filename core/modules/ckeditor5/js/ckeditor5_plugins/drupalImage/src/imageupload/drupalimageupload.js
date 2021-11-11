/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalimageuploadediting drupalfilerepository */

import { Plugin } from 'ckeditor5/src/core';
import DrupalImageUploadEditing from './drupalimageuploadediting';
import DrupalFileRepository from './drupalfilerepository';

/**
 * Integrates the CKEditor image upload with the Drupal.
 *
 * @internal
 */
class DrupalImageUpload extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalFileRepository, DrupalImageUploadEditing];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageUpload';
  }
}

export default DrupalImageUpload;
