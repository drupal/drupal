/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words imagealternativetext imagetextalternativeediting drupalimagealternativetextediting drupalimagealternativetextui */

/**
 * @module drupalImage/imagealternativetext
 */

import { Plugin } from 'ckeditor5/src/core';
import DrupalImageAlternativeTextEditing from './imagealternativetext/drupalimagealternativetextediting';
import DrupalImageAlternativeTextUi from './imagealternativetext/drupalimagealternativetextui';

/**
 * The Drupal-specific image text alternative plugin.
 *
 * This has been implemented based on the CKEditor 5 built in image alternative
 * text plugin. This plugin enhances the original upstream form with a toggle
 * button that allows users to explicitly mark images as decorative, which is
 * downcast to an empty `alt` attribute. This plugin also provides a warning for
 * images that are missing the `alt` attribute, to ensure content authors don't
 * leave the alternative text blank by accident.
 *
 * @see module:image/imagetextalternative~ImageTextAlternative
 *
 * @extends module:core/plugin~Plugin
 */
export default class DrupalImageAlternativeText extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalImageAlternativeTextEditing, DrupalImageAlternativeTextUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageAlternativeText';
  }
}
