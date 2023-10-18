/* eslint-disable import/no-extraneous-dependencies */
// cspell:ignore documentselection
import { isWidget } from 'ckeditor5/src/widget';

/**
 * Checks if the provided model element is `drupalMedia`.
 *
 * @param {module:engine/model/element~Element} modelElement
 *   The model element to be checked.
 * @return {boolean}
 *   A boolean indicating if the element is a drupalMedia element.
 *
 * @private
 */
export function isDrupalMedia(modelElement) {
  return !!modelElement && modelElement.is('element', 'drupalMedia');
}

/**
 * Checks if view element is <drupal-media> element.
 *
 * @param {module:engine/view/element~Element} viewElement
 *   The view element.
 * @return {boolean}
 *   A boolean indicating if the element is a <drupal-media> element.
 *
 * @private
 */
export function isDrupalMediaWidget(viewElement) {
  return (
    isWidget(viewElement) && !!viewElement.getCustomProperty('drupalMedia')
  );
}

/**
 * Gets `drupalMedia` element from selection.
 *
 * @param {module:engine/model/selection~Selection|module:engine/model/documentselection~DocumentSelection} selection
 *   The current selection.
 * @return {module:engine/model/element~Element|null}
 *   The `drupalMedia` element which could be either the current selected an
 *   ancestor of the selection. Returns null if the selection has no Drupal
 *   Media element.
 *
 * @private
 */
export function getClosestSelectedDrupalMediaElement(selection) {
  const selectedElement = selection.getSelectedElement();

  return isDrupalMedia(selectedElement)
    ? selectedElement
    : selection.getFirstPosition().findAncestor('drupalMedia');
}

/**
 * Gets selected Drupal Media widget if only Drupal Media is currently selected.
 *
 * @param {module:engine/model/selection~Selection} selection
 *   The current selection.
 * @return {module:engine/view/element~Element|null}
 *   The currently selected Drupal Media widget or null.
 *
 * @private
 */
export function getClosestSelectedDrupalMediaWidget(selection) {
  const viewElement = selection.getSelectedElement();
  if (viewElement && isDrupalMediaWidget(viewElement)) {
    return viewElement;
  }

  // Perhaps nothing is selected.
  if (selection.getFirstPosition() === null) {
    return null;
  }

  let parent = selection.getFirstPosition().parent;

  while (parent) {
    if (parent.is('element') && isDrupalMediaWidget(parent)) {
      return parent;
    }

    parent = parent.parent;
  }

  return null;
}

/**
 * Checks if value is a JavaScript object.
 *
 * This will return true for any type of JavaScript object. (e.g. arrays,
 * functions, objects, regexes, new Number(0), and new String(''))
 *
 * @param value
 *   Value to check.
 * @return {boolean}
 *   True if value is an object, else false.
 */
export function isObject(value) {
  const type = typeof value;
  return value != null && (type === 'object' || type === 'function');
}

/**
 * Gets the preview container element from the media element.
 *
 * @param {Iterable.<module:engine/view/element~Element>} children
 *   The child elements.
 * @return {null|module:engine/view/element~Element}
 *   The preview child element if available.
 */
export function getPreviewContainer(children) {
  // eslint-disable-next-line no-restricted-syntax
  for (const child of children) {
    if (child.hasAttribute('data-drupal-media-preview')) {
      return child;
    }

    if (child.childCount) {
      const recursive = getPreviewContainer(child.getChildren());
      // Return only if preview container was found within this element's
      // children.
      if (recursive) {
        return recursive;
      }
    }
  }

  return null;
}

/**
 * Gets model attribute key based on Drupal Element Style group.
 *
 * @example
 *    Example: 'align' -> 'drupalElementStyleAlign'
 *
 * @param {string} group
 *   The name of the group (ex. 'align', 'viewMode').
 * @return {string}
 *   Model attribute key.
 *
 * @internal
 */
export function groupNameToModelAttributeKey(group) {
  // Manipulate string to have first letter capitalized to append in camel case.
  const capitalizedFirst = group[0].toUpperCase() + group.substring(1);
  return `drupalElementStyle${capitalizedFirst}`;
}
