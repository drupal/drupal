/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore drupalmediaediting drupalmediageneralhtmlsupport drupalmediaui drupalmediatoolbar mediaimagetextalternative */

import { Plugin } from 'ckeditor5/src/core';
import DrupalMediaEditing from './drupalmediaediting';
import DrupalMediaUI from './drupalmediaui';
import DrupalMediaToolbar from './drupalmediatoolbar';

import MediaImageTextAlternative from './mediaimagetextalternative';
import DrupalMediaGeneralHtmlSupport from './drupalmediageneralhtmlsupport';

/**
 * Main entrypoint to the Drupal media widget.
 *
 * See individual capabilities for details:
 *  - {@link DrupalMediaEditing}
 *  - {@link DrupalMediaGeneralHtmlSupport}
 *  - {@link DrupalMediaUI}
 *  - {@link DrupalMediaToolbar}
 *  - {@link MediaImageTextAlternative}
 *
 * @private
 */
export default class DrupalMedia extends Plugin {
  /**
   * @inheritdoc
   */
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
