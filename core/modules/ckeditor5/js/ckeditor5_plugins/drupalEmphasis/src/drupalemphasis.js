/* eslint-disable import/no-extraneous-dependencies */
// cspell:ignore drupalemphasisediting

import { Plugin } from 'ckeditor5/src/core';
import DrupalEmphasisEditing from './drupalemphasisediting';

/**
 * Drupal-specific plugin to alter the CKEditor 5 italic command.
 *
 * @private
 */
class DrupalEmphasis extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalEmphasisEditing];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalEmphasis';
  }
}

export default DrupalEmphasis;
