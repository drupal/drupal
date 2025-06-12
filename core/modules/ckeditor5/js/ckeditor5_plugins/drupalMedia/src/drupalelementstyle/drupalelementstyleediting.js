/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore drupalelementstylecommand */
import { Plugin } from 'ckeditor5/src/core';
import * as icons from '@ckeditor/ckeditor5-icons';
import { first } from 'ckeditor5/src/utils';
import DrupalElementStyleCommand from './drupalelementstylecommand';
import { groupNameToModelAttributeKey } from '../utils';

/**
 * @module drupalMedia/drupalelementstyle/drupalelementstyleediting
 */

/**
 * Gets style definition by name.
 *
 * @param {string} name
 *   The name of the style definition.
 * @param {object} styles
 *   The styles to search from.
 * @return {Drupal.CKEditor5~DrupalElementStyle}
 */
function getStyleDefinitionByName(name, styles) {
  // eslint-disable-next-line no-restricted-syntax
  for (const style of styles) {
    if (style.name === name) {
      return style;
    }
  }
}

/**
 * Returns a model-to-view converted for Drupal Element styles.
 *
 * This model to view converter supports downcasting model to either a CSS class
 * or attribute.
 *
 * Note that only one style can be applied to a single model element.
 *
 * @param {object} styles
 *  Existing styles.
 */
function modelToViewStyleAttribute(styles) {
  return (evt, data, conversionApi) => {
    if (!conversionApi.consumable.consume(data.item, evt.name)) {
      return;
    }

    // Check if there is a style associated with given value.
    const newStyle = getStyleDefinitionByName(data.attributeNewValue, styles);
    const oldStyle = getStyleDefinitionByName(data.attributeOldValue, styles);

    const viewElement = conversionApi.mapper.toViewElement(data.item);
    const viewWriter = conversionApi.writer;

    if (oldStyle) {
      if (oldStyle.attributeName === 'class') {
        viewWriter.removeClass(oldStyle.attributeValue, viewElement);
      } else {
        viewWriter.removeAttribute(oldStyle.attributeName, viewElement);
      }
    }

    if (newStyle) {
      if (newStyle.attributeName === 'class') {
        viewWriter.addClass(newStyle.attributeValue, viewElement);
      } else if (!newStyle.isDefault) {
        // We only reach this condition if the style is not the default value.
        // In those instances, there is no need to downcast as the default value
        // is set automatically when necessary.
        viewWriter.setAttribute(
          newStyle.attributeName,
          newStyle.attributeValue,
          viewElement,
        );
      }
    }
  };
}

/**
 * Returns a view-to-model converter for Drupal Element styles.
 *
 * This view to model converted supports styles that are configured to use
 * either CSS class or an attribute.
 *
 * Note that more than one style can be applied to each modelElement.
 */
function viewToModelStyleAttribute(styles, modelAttribute) {
  // Convert only non–default styles.
  const nonDefaultStyles = styles.filter((style) => !style.isDefault);

  return (evt, data, conversionApi) => {
    if (!data.modelRange) {
      return;
    }

    const viewElement = data.viewItem;
    const modelElement = first(data.modelRange.getItems());

    // Run this converter only if a model element has been found from the model.
    if (!modelElement) {
      return;
    }

    // Stop conversion early if modelAttribute represents an attribute that isn't allowed
    // for the element.
    if (!conversionApi.schema.checkAttribute(modelElement, modelAttribute)) {
      return;
    }

    // Convert styles with CSS classes one by one.
    // eslint-disable-next-line no-restricted-syntax
    for (const style of nonDefaultStyles) {
      // Try to consume class corresponding with the style.
      if (style.attributeName === 'class') {
        if (
          conversionApi.consumable.consume(viewElement, {
            classes: style.attributeValue,
          })
        ) {
          // And convert this style to model attribute.
          conversionApi.writer.setAttribute(
            modelAttribute,
            style.name,
            modelElement,
          );
        }
      } else if (
        conversionApi.consumable.consume(viewElement, {
          attributes: [style.attributeName],
        })
      ) {
        // eslint-disable-next-line no-restricted-syntax
        for (const style of nonDefaultStyles) {
          if (
            style.attributeValue ===
            viewElement.getAttribute(style.attributeName)
          ) {
            conversionApi.writer.setAttribute(
              modelAttribute,
              style.name,
              modelElement,
            );
          }
        }
      }
    }
  };
}

/**
 * The Drupal Element Style editing plugin.
 *
 * Additional Drupal Element Styles can be defined with `drupalElementStyles`
 * configuration key.
 *
 * Additional Drupal Element Styles can support multiple axes (e.g. media
 * alignment and media view modes) by adding the new group under
 * drupalElementStyles.
 *
 * @example
 *    config:
 *      drupalElementStyles:
 *        side:
 *          - name: 'side'
 *            icon: 'IconObjectRight'
 *            title: 'Side image'
 *            attributeName: 'class'
 *            attributeValue: 'image-side'
 *            modelElements: ['drupalMedia']
 *        align:
 *           - name: 'right'
 *             title: 'Right aligned media'
 *             icon: 'IconObjectInlineRight'
 *             attributeName: 'data-align'
 *             modelElements: [ 'drupalMedia' ]
 *           - name: 'left'
 *             title: 'Left aligned media'
 *             icon: 'IconObjectInlineLeft'
 *             attributeName: 'data-align'
 *             attributeValue: 'left'
 *             modelElements: [ 'drupalMedia' ]
 *        viewMode:
 *           - name: 'full view mode'
 *             title: 'Full view mode'
 *             attributeName: 'data-view-mode'
 *             attributeValue: 'full'
 *             modelElements: [ 'drupalMedia' ]
 *           - name: 'compact view mode'
 *             title: 'Compact view mode'
 *             attributeName: 'data-view-mode'
 *             attributeValue: 'compact'
 *             modelElements: [ 'drupalMedia' ]
 *
 * @see Drupal.CKEditor5~DrupalElementStyleDefinition
 *
 * @extends module:core/plugin~Plugin
 *
 * @private
 */
export default class DrupalElementStyleEditing extends Plugin {
  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;

    const stylesConfig = editor.config.get('drupalElementStyles');
    this.normalizedStyles = {};

    /**
     * The Drupal Element Style definitions.
     *
     * @typedef {Object} Drupal.CKEditor5~DrupalElementStyleDefinition
     *   Object that contains an array of DrupalElementStyle objects for each
     *   group.
     *
     * @prop {string} name
     *   The name of the style used for identifying the button.
     * @prop {string} title
     *   The title of the style displayed in the UI.
     * @prop {string} attributeName
     *   The name of the attribute in view.
     * @prop {string} attributeValue
     *   The value of the attribute in view.
     * @prop {string[]} modelElements
     *   A list of model elements that the style can be attached to.
     * @prop {string} [icon]
     *   An icon for the style button. This needs to either refer to an icon in
     *   the CKEditor 5 core icons, or this can be the XML content of the icon.
     *
     * @type {Drupal.CKEditor5~DrupalElementStyleDefinition}
     */
    Object.keys(stylesConfig).forEach((group) => {
      this.normalizedStyles[group] = stylesConfig[group] // array of styles
        .map((style) => {
          // Allow defining style icon as a string that is referring to the
          // CKEditor 5 default icons.
          if (typeof style.icon === 'string') {
            if (icons[style.icon]) {
              style.icon = icons[style.icon];
            }
          }
          if (style.name) {
            // Make sure names are all strings.
            style.name = `${style.name}`;
          }
          return style;
        })
        .filter((style) => {
          if (
            !style.isDefault &&
            (!style.attributeName || !style.attributeValue)
          ) {
            console.warn(
              `${style.attributeValue} drupalElementStyles options must include attributeName and attributeValue.`,
            );
            return false;
          }
          if (!style.modelElements || !Array.isArray(style.modelElements)) {
            console.warn(
              'drupalElementStyles options must include an array of supported modelElements.',
            );
            return false;
          }

          if (!style.name) {
            console.warn('drupalElementStyles options must include a name.');
            return false;
          }

          return true;
        });
    });

    this._setupConversion();

    editor.commands.add(
      'drupalElementStyle',
      new DrupalElementStyleCommand(editor, this.normalizedStyles),
    );
  }

  /**
   * Sets up conversion for Drupal Element Styles.
   *
   * @see modelToViewStyleAttribute()
   * @see viewToModelStyleAttribute()
   *
   * @private
   */
  _setupConversion() {
    const { editor } = this;
    const { schema } = editor.model;

    const groupNamesArr = Object.keys(this.normalizedStyles);

    groupNamesArr.forEach((group) => {
      const modelAttribute = groupNameToModelAttributeKey(group);

      const modelToViewConverter = modelToViewStyleAttribute(
        this.normalizedStyles[group],
      );
      const viewToModelConverter = viewToModelStyleAttribute(
        this.normalizedStyles[group],
        modelAttribute,
      );

      editor.editing.downcastDispatcher.on(
        `attribute:${modelAttribute}`,
        modelToViewConverter,
      );
      editor.data.downcastDispatcher.on(
        `attribute:${modelAttribute}`,
        modelToViewConverter,
      );

      // Allow drupalElementStyle model attributes on all model elements that
      // have associated styles.
      const modelElements = [
        ...new Set(
          this.normalizedStyles[group]
            .map((style) => {
              return style.modelElements;
            })
            .flat(),
        ),
      ];
      modelElements.forEach((modelElement) => {
        schema.extend(modelElement, {
          allowAttributes: modelAttribute,
        });
      });
      // View to model converter that runs on all elements.
      editor.data.upcastDispatcher.on(
        'element',
        viewToModelConverter,
        // This needs to be set as low priority to ensure this runs always after
        // the element has been converted to a model element.
        { priority: 'low' },
      );
    });
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalElementStyleEditing';
  }
}
