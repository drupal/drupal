/* eslint-disable import/no-extraneous-dependencies */
// cSpell:words conversionutils datafilter
import { Plugin } from 'ckeditor5/src/core';
import { setViewAttributes } from '@ckeditor/ckeditor5-html-support/src/conversionutils';

/**
 * View-to-model conversion helper preserving allowed attributes on the Drupal Media model.
 *
 * @param {module:html-support/datafilter~DataFilter} dataFilter
 *   The General HTML support data filter.
 *
 * @return {function}
 *   function that adds an event listener to upcastDispatcher.
 */
function viewToModelDrupalMediaAttributeConverter(dataFilter) {
  return (dispatcher) => {
    dispatcher.on(
      'element:drupal-media',
      (evt, data, conversionApi) => {
        function preserveElementAttributes(viewElement, attributeName) {
          const viewAttributes = dataFilter._consumeAllowedAttributes(
            viewElement,
            conversionApi,
          );

          if (viewAttributes) {
            conversionApi.writer.setAttribute(
              attributeName,
              viewAttributes,
              data.modelRange,
            );
          }
        }
        function preserveLinkAttributes(linkElement) {
          preserveElementAttributes(linkElement, 'htmlLinkAttributes');
        }

        const viewMediaElement = data.viewItem;
        const viewContainerElement = viewMediaElement.parent;

        if (viewContainerElement.is('element', 'a')) {
          preserveLinkAttributes(viewContainerElement);
        }
      },
      { priority: 'low' },
    );
  };
}

/**
 * Gets descendant element from a container.
 *
 * @param {module:engine/model/writer~Writer} writer
 *   The writer.
 * @param {module:engine/view/element~Element} containerElement
 *   The container element.
 * @param {string} elementName
 *   The element name.
 * @return {module:engine/view/element~Element|undefined}
 *   The descendant element matching element name or undefined if not found.
 */
function getDescendantElement(writer, containerElement, elementName) {
  const range = writer.createRangeOn(containerElement);

  // eslint-disable-next-line no-restricted-syntax
  for (const { item } of range.getWalker()) {
    if (item.is('element', elementName)) {
      return item;
    }
  }
}

/**
 * Model to editing view attribute converter.
 *
 * @return {function}
 *   A function that adds an event listener to downcastDispatcher.
 */
function modelToEditingViewAttributeConverter() {
  return (dispatcher) =>
    dispatcher.on(
      'attribute:linkHref:drupalMedia',
      (evt, data, conversionApi) => {
        if (
          !conversionApi.consumable.consume(
            data.item,
            'attribute:htmlLinkAttributes:drupalMedia',
          )
        ) {
          return;
        }

        const containerElement = conversionApi.mapper.toViewElement(data.item);
        const viewElement = getDescendantElement(
          conversionApi.writer,
          containerElement,
          'a',
        );

        setViewAttributes(
          conversionApi.writer,
          data.item.getAttribute('htmlLinkAttributes'),
          viewElement,
        );
      },
      { priority: 'low' },
    );
}

/**
 * Model to data view attribute converter.
 *
 * @return {function}
 *   function that adds an event listener to downcastDispatcher.
 */
function modelToDataViewAttributeConverter() {
  return (dispatcher) =>
    dispatcher.on(
      'attribute:linkHref:drupalMedia',
      (evt, data, conversionApi) => {
        if (
          !conversionApi.consumable.consume(
            data.item,
            'attribute:htmlLinkAttributes:drupalMedia',
          )
        ) {
          return;
        }

        const mediaElement = conversionApi.mapper.toViewElement(data.item);
        const linkElement = mediaElement.parent;
        setViewAttributes(
          conversionApi.writer,
          data.item.getAttribute('htmlLinkAttributes'),
          linkElement,
        );
      },
      { priority: 'low' },
    );
}

/**
 * Integrates Drupal Media with General HTML Support.
 *
 * @internal
 */
export default class DrupalMediaGeneralHtmlSupport extends Plugin {
  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;

    // This plugin is only needed if General HTML Support plugin is loaded.
    if (!editor.plugins.has('GeneralHtmlSupport')) {
      return;
    }

    const { schema } = editor.model;
    const { conversion } = editor;
    const dataFilter = editor.plugins.get('DataFilter');

    schema.extend('drupalMedia', {
      allowAttributes: ['htmlLinkAttributes'],
    });

    conversion
      .for('upcast')
      .add(viewToModelDrupalMediaAttributeConverter(dataFilter));
    conversion
      .for('editingDowncast')
      .add(modelToEditingViewAttributeConverter());
    conversion.for('dataDowncast').add(modelToDataViewAttributeConverter());
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalMediaGeneralHtmlSupport';
  }
}
