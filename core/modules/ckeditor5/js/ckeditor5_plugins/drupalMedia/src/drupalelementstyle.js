/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalelementstyle drupalelementstyleui drupalelementstyleediting imagestyle drupalmediatoolbar drupalmediaediting */
import { Plugin } from 'ckeditor5/src/core';
import DrupalElementStyleUi from './drupalelementstyle/drupalelementstyleui';
import DrupalElementStyleEditing from './drupalelementstyle/drupalelementstyleediting';

/**
 * @module drupalMedia/drupalelementstyle
 */

/**
 * The Drupal Element Style plugin.
 *
 * This plugin is internal and it is currently only used for providing
 * `data-align` support to `<drupal-media>`. However, this plugin isn't tightly
 * coupled to `<drupal-media>` or `data-align`. The intent is to make this
 * plugin a starting point for adding `data-align` support to other elements,
 * because the `FilterAlign` filter plugin PHP code also does not limit itself
 * to a specific HTML element. This could be also used for other filters to
 * provide same authoring experience as `FilterAlign` without the need for
 * additional JavaScript code.
 *
 * To be able to change element styles in the UI, the model element needs to
 * have a toolbar where the element style buttons can be displayed.
 *
 * This plugin is inspired by the CKEditor 5 Image Style plugin.
 *
 * @see module:image/imagestyle~ImageStyle
 * @see core/modules/ckeditor5/css/media-alignment.css
 * @see module:drupalMedia/drupalmediaediting~DrupalMediaEditing
 * @see module:drupalMedia/drupalmediatoolbar~DrupalMediaToolbar
 *
 * @private
 */
export default class DrupalElementStyle extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalElementStyleEditing, DrupalElementStyleUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalElementStyle';
  }
}
