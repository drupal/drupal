/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words documentselection */
import { Command } from 'ckeditor5/src/core';

/**
 * @module drupalMedia/drupalelementstyle/drupalelementstylecommand
 */

/**
 * Gets closest element that has drupalElementStyle attribute in schema.
 *
 * @param {module:engine/model/documentselection~DocumentSelection} selection
 *   The current document selection.
 * @param {module:engine/model/schema~Schema} schema
 *   The model schema.
 *
 * @return {null|module:engine/model/element~Element}
 *   The closest element that supports element styles.
 */
function getClosestElementWithElementStyleAttribute(selection, schema) {
  const selectedElement = selection.getSelectedElement();

  if (
    selectedElement &&
    schema.checkAttribute(selectedElement, 'drupalElementStyle')
  ) {
    return selectedElement;
  }

  let parent = selection.getFirstPosition().parent;

  while (parent) {
    if (
      parent.is('element') &&
      schema.checkAttribute(parent, 'drupalElementStyle')
    ) {
      return parent;
    }

    parent = parent.parent;
  }

  return null;
}

/**
 * The Drupal Element style command.
 *
 * This is used to apply Drupal Element style option to supported model elements.
 *
 * @extends module:core/command~Command
 *
 * @internal
 */
export default class DrupalElementStyleCommand extends Command {
  /**
   * Constructs a new object.
   *
   * @param {module:core/editor/editor~Editor} editor
   *   The editor instance.
   * @param {Drupal.CKEditor5~DrupalElementStyle[]} styles
   *   All available Drupal Element Styles.
   */
  constructor(editor, styles) {
    super(editor);
    this._styles = new Map(
      styles.map((style) => {
        return [style.name, style];
      }),
    );
  }

  /**
   * @inheritDoc
   */
  refresh() {
    const editor = this.editor;
    const element = getClosestElementWithElementStyleAttribute(
      editor.model.document.selection,
      editor.model.schema,
    );

    this.isEnabled = !!element;

    if (this.isEnabled) {
      this.value = element.getAttribute('drupalElementStyle');

      // If value is falsy, check if there is a default style to apply to the
      // element.
      if (!this.value) {
        // eslint-disable-next-line no-restricted-syntax
        for (const [name, style] of this._styles.entries()) {
          if (style.isDefault) {
            const appliesToCurrentElement = style.modelElements.find(
              (modelElement) => element.is('element', modelElement),
            );
            if (appliesToCurrentElement) {
              this.value = name;
              break;
            }
          }
        }
      }
    } else {
      this.value = false;
    }
  }

  /**
   * Executes the command and applies the style to the selected model element.
   *
   * @example
   *    editor.execute('drupalElementStyle', { value: 'alignLeft' });
   *
   * @param {Object} options
   *   The command options.
   * @param {string} options.value
   *   The name of the style as configured in the Drupal Element style
   *   configuration.
   */
  execute(options = {}) {
    const editor = this.editor;
    const model = editor.model;

    model.change((writer) => {
      const requestedStyle = options.value;
      const element = getClosestElementWithElementStyleAttribute(
        model.document.selection,
        model.schema,
      );

      if (!requestedStyle || this._styles.get(requestedStyle).isDefault) {
        writer.removeAttribute('drupalElementStyle', element);
      } else {
        writer.setAttribute('drupalElementStyle', requestedStyle, element);
      }
    });
  }
}
