/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupallinkmediaediting drupallinkmediaui */

import { Plugin } from 'ckeditor5/src/core';
import DrupalLinkMediaEditing from './drupallinkmediaediting';
import DrupalLinkMediaUI from './drupallinkmediaui';

/**
 * @private
 */
export default class DrupalLinkMedia extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalLinkMediaEditing, DrupalLinkMediaUI];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalLinkMedia';
  }
}
