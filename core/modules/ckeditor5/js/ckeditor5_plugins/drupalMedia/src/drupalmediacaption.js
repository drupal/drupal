/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore drupalmediacaption drupalmediacaptionediting drupalmediacaptionui */
import { Plugin } from 'ckeditor5/src/core';
import DrupalMediaCaptionEditing from './drupalmediacaption/drupalmediacaptionediting';
import DrupalMediaCaptionUI from './drupalmediacaption/drupalmediacaptionui';

/**
 * Provides the caption feature on Drupal media elements.
 *
 * @private
 */
export default class DrupalMediaCaption extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalMediaCaptionEditing, DrupalMediaCaptionUI];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalMediaCaption';
  }
}
