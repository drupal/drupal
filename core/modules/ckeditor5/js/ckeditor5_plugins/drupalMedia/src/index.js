/* eslint-disable import/no-extraneous-dependencies */
// cspell:ignore mediaimagetextalternative drupalmediacaption

import DrupalMedia from './drupalmedia';

// cspell:ignore drupallinkmedia
import DrupalLinkMedia from './drupallinkmedia/drupallinkmedia';

// cspell:ignore drupalelementstyle
import DrupalElementStyle from './drupalelementstyle';

import DrupalMediaCaption from './drupalmediacaption';

// cspell:ignore mediaimagetextalternative
import MediaImageTextAlternative from './mediaimagetextalternative';
import MediaImageTextAlternativeEditing from './mediaimagetextalternative/mediaimagetextalternativeediting';
import MediaImageTextAlternativeUi from './mediaimagetextalternative/mediaimagetextalternativeui';

/**
 * @internal
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
