// eslint-disable-next-line import/no-extraneous-dependencies
import { findAttributeRange } from 'ckeditor5/src/typing';

/**
 * Returns a link range based on selection from
 *  href attribute at selection's first position.
 */
export function getCurrentLinkRange(model, selection, hrefSourceValue) {
  const position = selection.getFirstPosition();
  const linkRange = findAttributeRange(
    position,
    'linkHref',
    hrefSourceValue,
    model,
  );
  return linkRange;
}

/**
 * Returns a text of a link range.
 *
 * If the returned value is `undefined`, the range contains elements other than text nodes.
 */
export function extractTextFromLinkRange(range) {
  let text = '';
  // Per https://stackoverflow.com/a/37576787 for reading in sequence, we cannot
  // use .forEach syntax, so we use traditional for loop.
  /* eslint-disable no-restricted-syntax */
  for (const item of range.getItems()) {
    if (!item.is('$text') && !item.is('$textProxy')) {
      return;
    }
    text += item.data;
  }
  return text;
}
