/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore drupalelementstyle drupalelementstylecommand */
import { Command } from 'ckeditor5/src/core';
import { getClosestElementWithElementStyleAttribute } from './utils';
import { groupNameToModelAttributeKey } from '../utils';

/**
 * @module drupalMedia/drupalelementstyle/drupalelementstylecommand
 */

/**
 * The Drupal Element style command.
 *
 * This is used to apply the Drupal Element Style option to supported model
 * elements.
 *
 * @extends module:core/command~Command
 *
 * @private
 */
export default class DrupalElementStyleCommand extends Command {
  /**
   * Constructs a new object.
   *
   * @param {module:core/editor/editor~Editor} editor
   *   The editor instance.
   * @param {Object<string, Drupal.CKEditor5~DrupalElementStyleDefinition>} styles
   *   All available Drupal Element Styles.
   */
  constructor(editor, styles) {
    super(editor);
    this.styles = {};
    Object.keys(styles).forEach((group) => {
      this.styles[group] = new Map(
        styles[group].map((style) => {
          return [style.name, style];
        }),
      );
    });
    this.modelAttributes = [];
    // eslint-disable-next-line no-restricted-syntax
    for (const group of Object.keys(styles)) {
      const modelAttribute = groupNameToModelAttributeKey(group);
      // Generate list of model attributes.
      this.modelAttributes.push(modelAttribute);
    }
  }

  /**
   * @inheritdoc
   */
  refresh() {
    const { editor } = this;
    const element = getClosestElementWithElementStyleAttribute(
      editor.model.document.selection,
      editor.model.schema,
      this.modelAttributes,
    );

    this.isEnabled = !!element;

    if (this.isEnabled) {
      // Assign value to be corresponding command value based on the element's modelAttribute.
      this.value = this.getValue(element);
    } else {
      this.value = false;
    }
  }

  /**
   * Gets the command value including groups and values.
   *
   * @example {drupalAlign: 'left', drupalViewMode: 'full'}
   *
   * @param {module:engine/model/element~Element} element
   *   The element.
   *
   * @return {Object}
   *   The groups and values in the form of an object.
   */
  getValue(element) {
    const value = {};
    // Get value for each of the Drupal Element Style groups.
    Object.keys(this.styles).forEach((group) => {
      const modelAttribute = groupNameToModelAttributeKey(group);
      if (element.hasAttribute(modelAttribute)) {
        value[group] = element.getAttribute(modelAttribute);
      } else {
        // eslint-disable-next-line no-restricted-syntax
        for (const [, style] of this.styles[group]) {
          // Set it to the default value.
          if (style.isDefault) {
            value[group] = style.name;
          }
        }
      }
    });
    return value;
  }

  /**
   * Executes the command and applies the style to the selected model element.
   *
   * @example
   *    editor.execute('drupalElementStyle', { value: 'left', group: 'align'});
   *
   * @param {Object} options
   *   The command options.
   * @param {string} options.value
   *   The name of the style as configured in the Drupal Element style
   *   configuration.
   * @param {string} options.group
   *   The group name of the drupalElementStyle.
   */
  execute(options = {}) {
    const {
      editor: { model },
    } = this;
    const { value, group } = options;
    const modelAttribute = groupNameToModelAttributeKey(group);
    model.change((writer) => {
      const element = getClosestElementWithElementStyleAttribute(
        model.document.selection,
        model.schema,
        this.modelAttributes,
      );
      if (!value || this.styles[group].get(value).isDefault) {
        // Remove attribute from the element.
        writer.removeAttribute(modelAttribute, element);
      } else {
        // Set the attribute value on the element.
        writer.setAttribute(modelAttribute, value, element);
      }
    });
  }
}
