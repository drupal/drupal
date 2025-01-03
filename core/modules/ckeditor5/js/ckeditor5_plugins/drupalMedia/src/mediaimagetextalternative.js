/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore mediaimagetextalternative mediaimagetextalternativeediting */
/* cspell:ignore mediaimagetextalternativeui */
import { Plugin } from 'ckeditor5/src/core';
import MediaImageTextAlternativeEditing from './mediaimagetextalternative/mediaimagetextalternativeediting';
import MediaImageTextAlternativeUi from './mediaimagetextalternative/mediaimagetextalternativeui';

/**
 * The media image text alternative plugin.
 *
 * @private
 */
export default class MediaImageTextAlternative extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [MediaImageTextAlternativeEditing, MediaImageTextAlternativeUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'MediaImageTextAlternative';
  }
}
