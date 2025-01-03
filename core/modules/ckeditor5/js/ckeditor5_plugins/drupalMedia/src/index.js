/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore drupalelementstyle drupallinkmedia drupalmediacaption */
/* cspell:ignore mediaimagetextalternativeediting mediaimagetextalternativeui */
/* cspell:ignore mediaimagetextalternative */

import DrupalMedia from './drupalmedia';
import DrupalLinkMedia from './drupallinkmedia/drupallinkmedia';
import DrupalElementStyle from './drupalelementstyle';
import DrupalMediaCaption from './drupalmediacaption';
import MediaImageTextAlternative from './mediaimagetextalternative';
import MediaImageTextAlternativeEditing from './mediaimagetextalternative/mediaimagetextalternativeediting';
import MediaImageTextAlternativeUi from './mediaimagetextalternative/mediaimagetextalternativeui';

/**
 * @private
 */
export default {
  DrupalMedia,
  MediaImageTextAlternative,
  MediaImageTextAlternativeEditing,
  MediaImageTextAlternativeUi,
  DrupalLinkMedia,
  DrupalMediaCaption,
  DrupalElementStyle,
};
