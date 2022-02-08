/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalmediaediting drupalmediageneralhtmlsupport drupalmediaui drupalmediatoolbar mediaimagetextalternative */

import { Plugin } from 'ckeditor5/src/core';
import DrupalMediaEditing from './drupalmediaediting';
import DrupalMediaUI from './drupalmediaui';
import DrupalMediaToolbar from './drupalmediatoolbar';

import MediaImageTextAlternative from './mediaimagetextalternative';
import DrupalMediaGeneralHtmlSupport from './drupalmediageneralhtmlsupport';

/**
 * @internal
 */
export default class DrupalMedia extends Plugin {
  static get requires() {
    return [
      DrupalMediaEditing,
      DrupalMediaGeneralHtmlSupport,
      DrupalMediaUI,
      DrupalMediaToolbar,
      MediaImageTextAlternative,
    ];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalMedia';
  }
}
