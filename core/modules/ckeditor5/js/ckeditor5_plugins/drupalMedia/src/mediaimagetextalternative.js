/* eslint-disable import/no-extraneous-dependencies */
import { Plugin } from 'ckeditor5/src/core';
import MediaImageTextAlternativeEditing from './mediaimagetextalternative/mediaimagetextalternativeediting';
import MediaImageTextAlternativeUi from './mediaimagetextalternative/mediaimagetextalternativeui';

/**
 * @internal
 */
/**
 * The media image text alternative plugin.
 */
export default class MediaImageTextAlternative extends Plugin {
  /**
   * @inheritDoc
   */
  static get requires() {
    return [MediaImageTextAlternativeEditing, MediaImageTextAlternativeUi];
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'MediaImageTextAlternative';
  }
}
