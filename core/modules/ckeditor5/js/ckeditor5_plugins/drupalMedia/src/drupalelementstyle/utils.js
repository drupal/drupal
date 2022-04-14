/* cspell:words documentselection */
/**
 * Checks the schema to see if drupalElementStyle is supported on the element.
 *
 * @param {module:engine/model/element~Element|null} selectedElement
 *   The selected element.
 * @param {string[]} modelAttributes
 *   Array of model attribute keys.
 * @param {module:engine/model/schema~Schema} schema
 *   The model schema.
 *
 * @return {boolean}
 *   Whether element supports any of the drupalElementStyle attributes.
 *
 * @internal
 */
export function elementSupportsDrupalElementStyles(
  selectedElement,
  modelAttributes,
  schema,
) {
  // eslint-disable-next-line no-restricted-syntax
  for (const modelAttribute of modelAttributes) {
    if (schema.checkAttribute(selectedElement, modelAttribute)) {
      return true;
    }
  }
  return false;
}

/**
 * Gets the closest element with any drupalElementStyle attribute in its schema.
 *
 * @param {module:engine/model/documentselection~DocumentSelection} selection
 *   The current document selection.
 * @param {module:engine/model/schema~Schema} schema
 *   The model schema.
 * @param {string[]} modelAttributes
 *   All available Drupal Element Style model attributes.
 *
 * @return {null|module:engine/model/element~Element}
 *   The closest element that supports element styles.
 *
 * @internal
 */
export function getClosestElementWithElementStyleAttribute(
  selection,
  schema,
  modelAttributes,
) {
  const selectedElement = selection.getSelectedElement();
  if (
    selectedElement &&
    elementSupportsDrupalElementStyles(selectedElement, modelAttributes, schema)
  ) {
    return selectedElement;
  }
  let { parent } = selection.getFirstPosition();

  while (parent) {
    if (parent.is('element')) {
      // eslint-disable-next-line no-restricted-syntax
      if (elementSupportsDrupalElementStyles(parent, modelAttributes, schema)) {
        return parent;
      }
    }
    parent = parent.parent;
  }
  return null;
}
