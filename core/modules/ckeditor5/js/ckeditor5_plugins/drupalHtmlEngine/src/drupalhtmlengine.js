/* eslint-disable import/no-extraneous-dependencies */
// cSpell:words drupalhtmlwriter
import { Plugin } from 'ckeditor5/src/core';
import DrupalHtmlWriter from './drupalhtmlwriter';

/**
 * A plugin that overrides the CKEditor HTML writer.
 *
 * Override the CKEditor 5 HTML writer to escape ampersand characters (&) and
 * the angle brackets (< and >). This is required because
 * \Drupal\Component\Utility\Xss::filter fails to parse element attributes with
 * unescaped entities in value.
 *
 * @see https://www.drupal.org/project/drupal/issues/3227831
 * @see DrupalHtmlBuilder._escapeAttribute
 *
 * @internal
 */
class DrupalHtmlEngine extends Plugin {
  /**
   * @inheritdoc
   */
  init() {
    this.editor.data.processor.htmlWriter = new DrupalHtmlWriter();
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalHtmlEngine';
  }
}

export default DrupalHtmlEngine;
