/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore imagetextalternative missingalternativetextview imagealternativetext */

import { View, ButtonView } from 'ckeditor5/src/ui';

/**
 * @module drupalImage/imagealternativetext/ui/missingalternativetextview
 */

/**
 * A class rendering missing alt text view.
 *
 * @extends module:ui/view~View
 *
 * @internal
 */
export default class MissingAlternativeTextView extends View {
  /**
   * @inheritdoc
   */
  constructor(locale) {
    super(locale);

    const bind = this.bindTemplate;
    this.set('isVisible');
    this.set('isSelected');

    const label = Drupal.t('Add missing alternative text');
    this.button = new ButtonView(locale);
    this.button.set({
      label,
      tooltip: false,
      withText: true,
    });

    this.setTemplate({
      tag: 'span',
      attributes: {
        class: [
          'image-alternative-text-missing',
          bind.to('isVisible', (value) => (value ? '' : 'ck-hidden')),
        ],
        title: label,
      },
      children: [this.button],
    });
  }
}
