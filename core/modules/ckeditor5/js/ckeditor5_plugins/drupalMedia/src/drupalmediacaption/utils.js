/* eslint-disable import/prefer-default-export */
import { isDrupalMedia } from '../utils';

/**
 * Returns the Media caption model element for a model selection.
 *
 * @param {module:engine/model/selection~Selection} selection
 *   The current selection.
 * @returns {module:engine/model/element~Element|null}
 *   The Drupal Media caption element for a model selection. Returns null if the
 *   selection has no Drupal Media caption element ancestor.
 */
export function getMediaCaptionFromModelSelection(selection) {
  const captionElement = selection.getFirstPosition().findAncestor('caption');

  if (!captionElement) {
    return null;
  }

  if (isDrupalMedia(captionElement.parent)) {
    return captionElement;
  }

  return null;
}
