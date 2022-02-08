/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalelementstylecommand */
import { Plugin, icons } from 'ckeditor5/src/core';
import { first } from 'ckeditor5/src/utils';
import DrupalElementStyleCommand from './drupalelementstylecommand';

/**
 * @module drupalMedia/drupalelementstyle/drupalelementstyleediting
 */

/**
 * Gets style definition by name.
 *
 * @param {string} name
 *   The name of the style definition.
 * @param styles
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
      } else {
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
 * Note that only one style can be applied to each model element.
 */
function viewToModelStyleAttribute(styles) {
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

    // Stop conversion early if the drupalElementStyle attribute isn't allowed
    // for the element.
    if (
      !conversionApi.schema.checkAttribute(modelElement, 'drupalElementStyle')
    ) {
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
            'drupalElementStyle',
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
              'drupalElementStyle',
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
 * Additional Drupal Element styles can be defined with `drupalElementStyles`
 * configuration key.
 *
 * @example
 *    config:
 *      drupalElementStyles:
 *         options:
 *           - name: 'side'
 *             icon: 'objectBlockRight'
 *             title: 'Side image'
 *             attributeName: 'class'
 *             attributeValue: 'image-side'
 *             modelElement: ['drupalMedia']
 *
 * @see Drupal.CKEditor5~DrupalElementStyle
 *
 * @extends module:core/plugin~Plugin
 *
 * @internal
 */
export default class DrupalElementStyleEditing extends Plugin {
  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;

    // Ensure that the drupalElementStyles.options exists always.
    editor.config.define('drupalElementStyles', { options: [] });
    const stylesConfig = editor.config.get('drupalElementStyles').options;

    /**
     * The Drupal Element Styles.
     *
     * @typedef {Object} Drupal.CKEditor5~DrupalElementStyle
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
     * @type {Drupal.CKEditor5~DrupalElementStyle[]}
     */
    this.normalizedStyles = stylesConfig
      .map((style) => {
        // Allow defining style icon as a string that is referring to the
        // CKEditor 5 default icons.
        if (typeof style.icon === 'string') {
          if (icons[style.icon]) {
            style.icon = icons[style.icon];
          }
        }
        return style;
      })
      .filter((style) => {
        if (!style.attributeName || !style.attributeValue) {
          console.warn(
            'drupalElementStyles options must include attributeName and attributeValue.',
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
    const editor = this.editor;
    const schema = editor.model.schema;

    const modelToViewConverter = modelToViewStyleAttribute(
      this.normalizedStyles,
    );
    const viewToModelConverter = viewToModelStyleAttribute(
      this.normalizedStyles,
    );

    editor.editing.downcastDispatcher.on(
      'attribute:drupalElementStyle',
      modelToViewConverter,
    );
    editor.data.downcastDispatcher.on(
      'attribute:drupalElementStyle',
      modelToViewConverter,
    );

    // Allow drupalElementStyle on all model elements that have associated
    // styles.
    const modelElements = [
      ...new Set(
        this.normalizedStyles
          .map((style) => {
            return style.modelElements;
          })
          .flat(),
      ),
    ];
    modelElements.forEach((modelElement) => {
      schema.extend(modelElement, { allowAttributes: 'drupalElementStyle' });
    });

    // View to model converter that runs on all elements.
    editor.data.upcastDispatcher.on(
      'element',
      viewToModelConverter,
      // This needs to be set as low priority to ensure this runs always after
      // the element has been converted to a model element.
      { priority: 'low' },
    );
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'DrupalElementStyleEditing';
  }
}
