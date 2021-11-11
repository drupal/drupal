/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalimageediting */

import { Plugin } from 'ckeditor5/src/core';
import DrupalImageEditing from './drupalimageediting';

/**
 * @internal
 */
class DrupalImage extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalImageEditing];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImage';
  }
}

export default DrupalImage;
