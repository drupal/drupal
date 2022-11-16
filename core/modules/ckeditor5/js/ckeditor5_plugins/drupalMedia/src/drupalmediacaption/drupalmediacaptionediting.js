/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words insertdrupalmedia JSONified drupalmediacaptioncommand downcasted */
import { Plugin } from 'ckeditor5/src/core';
import { Element, enablePlaceholder } from 'ckeditor5/src/engine';
import { toWidgetEditable } from 'ckeditor5/src/widget';
import { isDrupalMedia } from '../utils';
import ToggleDrupalMediaCaptionCommand from './drupalmediacaptioncommand';

/**
 * A view to model converter for Drupal Media caption.
 *
 * This upcasts the `data-caption` attribute from `<drupal-media>` elements into
 * a `<caption>` model element. This is converted into a model element instead of
 * a model attribute in order to leverage CKEditor 5 built-in editing.
 *
 * @param {module:core/editor/editor~Editor} editor
 *   Editor on which this converter will be used.
 * @return {function}
 *   A function that attaches converter to the dispatcher.
 */
function viewToModelCaption(editor) {
  const converter = (evt, data, conversionApi) => {
    const { viewItem } = data;
    const { writer, consumable } = conversionApi;
    if (
      !data.modelRange ||
      !consumable.consume(viewItem, { attributes: ['data-caption'] })
    ) {
      return;
    }

    const caption = writer.createElement('caption');
    const drupalMedia = data.modelRange.start.nodeAfter;

    // Parse HTML from data-caption attribute and upcast it to model fragment.
    const viewFragment = editor.data.processor.toView(
      viewItem.getAttribute('data-caption'),
    );

    // Consumable must know about those newly parsed view elements.
    conversionApi.consumable.constructor.createFrom(
      viewFragment,
      conversionApi.consumable,
    );
    conversionApi.convertChildren(viewFragment, caption);

    // Insert the caption element into drupalMedia, as a last child.
    writer.append(caption, drupalMedia);
  };

  return (dispatcher) => {
    dispatcher.on('element:drupal-media', converter, { priority: 'low' });
  };
}

/**
 * Gets mapper function for repositioning the `<figcaption>` element.
 *
 * @param {module:engine/view/view~View} editingView
 *   The editing view.
 * @return {function}
 *   A mapper callback that moves `<figcaption>` element after the Drupal Media
 *   preview.
 */
function mapModelPositionToView(editingView) {
  return (evt, data) => {
    const modelPosition = data.modelPosition;
    const parent = modelPosition.parent;

    if (!isDrupalMedia(parent)) {
      return;
    }

    const viewElement = data.mapper.toViewElement(parent);
    data.viewPosition = editingView.createPositionAt(
      viewElement,
      modelPosition.offset + 1,
    );
  };
}

/**
 * A model to view converter for Drupal Media caption.
 *
 * This downcasts the `<caption>` model element into `data-caption` attribute in
 * the view.
 *
 * @param {module:core/editor/editor~Editor} editor
 *   Editor on which this converter will be used.
 * @return {function}
 *   A function that attaches converter to the dispatcher.
 */
function modelCaptionToCaptionAttribute(editor) {
  return (dispatcher) => {
    dispatcher.on('insert:caption', (evt, data, conversionApi) => {
      const { consumable, writer, mapper } = conversionApi;

      if (
        !isDrupalMedia(data.item.parent) ||
        !consumable.consume(data.item, 'insert')
      ) {
        return;
      }

      const range = editor.model.createRangeIn(data.item);
      const viewDocumentFragment = writer.createDocumentFragment();

      // Bind caption model element to the detached view document fragment so
      // all content of the caption will be downcasted into that document
      // fragment.
      mapper.bindElements(data.item, viewDocumentFragment);

      // eslint-disable-next-line no-restricted-syntax
      for (const { item } of Array.from(range)) {
        const itemData = {
          item,
          range: editor.model.createRangeOn(item),
        };

        // The following lines are extracted from
        // DowncastDispatcher._convertInsertWithAttributes().
        const eventName = `insert:${item.name || '$text'}`;

        editor.data.downcastDispatcher.fire(eventName, itemData, conversionApi);

        // eslint-disable-next-line no-restricted-syntax
        for (const key of item.getAttributeKeys()) {
          Object.assign(itemData, {
            attributeKey: key,
            attributeOldValue: null,
            attributeNewValue: itemData.item.getAttribute(key),
          });

          editor.data.downcastDispatcher.fire(
            `attribute:${key}`,
            itemData,
            conversionApi,
          );
        }
      }

      // Unbind all the view elements that were downcasted to the document
      // fragment.
      // eslint-disable-next-line no-restricted-syntax
      for (const child of writer
        .createRangeIn(viewDocumentFragment)
        .getItems()) {
        mapper.unbindViewElement(child);
      }

      mapper.unbindViewElement(viewDocumentFragment);

      // Stringify view document fragment to HTML string.
      const captionText = editor.data.processor.toData(viewDocumentFragment);

      if (captionText) {
        const imageViewElement = mapper.toViewElement(data.item.parent);

        writer.setAttribute('data-caption', captionText, imageViewElement);
      }
    });
  };
}

/**
 * The Drupal Media caption editing plugin.
 *
 * @extends module:core/plugin~Plugin
 *
 * @private
 */
export default class DrupalMediaCaptionEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalMediaCaptionEditing';
  }

  /**
   * @inheritdoc
   */
  constructor(editor) {
    super(editor);

    /**
     * A map of saved Drupal Media captions and related model elements.
     *
     * @member {WeakMap.<module:engine/model/element~Element,Object>}
     *
     * @see _saveCaption
     */
    this._savedCaptionsMap = new WeakMap();
  }

  /**
   * @inheritdoc
   */
  init() {
    const editor = this.editor;
    const schema = editor.model.schema;

    // Schema configuration.
    if (!schema.isRegistered('caption')) {
      schema.register('caption', {
        allowIn: 'drupalMedia',
        allowContentOf: '$block',
        isLimit: true,
      });
    } else {
      schema.extend('caption', {
        allowIn: 'drupalMedia',
      });
    }

    editor.commands.add(
      'toggleMediaCaption',
      new ToggleDrupalMediaCaptionCommand(editor),
    );

    this._setupConversion();
  }

  /**
   * Initializes upcasting and downcasting Drupal Media captions.
   */
  _setupConversion() {
    const editor = this.editor;
    const view = editor.editing.view;

    // View -> model converter for the data pipeline.
    editor.conversion.for('upcast').add(viewToModelCaption(editor));

    // Model -> Editing View converter for the data pipeline.
    editor.conversion.for('editingDowncast').elementToElement({
      model: 'caption',
      view: (modelElement, { writer }) => {
        if (!isDrupalMedia(modelElement.parent)) {
          return null;
        }

        const figcaptionElement = writer.createEditableElement('figcaption');

        enablePlaceholder({
          view,
          element: figcaptionElement,
          text: Drupal.t('Enter media caption'),
          keepOnFocus: true,
        });

        return toWidgetEditable(figcaptionElement, writer);
      },
    });
    // The `<caption>` element inside the Drupal Media wrapper is by default
    // placed before the preview. This rearranges the elements so that
    // `<caption>` is rendered after the preview.
    editor.editing.mapper.on(
      'modelToViewPosition',
      mapModelPositionToView(view),
    );

    // Model -> Data converter for the data pipeline.
    editor.conversion
      .for('dataDowncast')
      .add(modelCaptionToCaptionAttribute(editor));
  }

  /**
   * Returns the saved caption of a Drupal Media model element.
   *
   * @param {module:engine/model/element~Element} drupalMediaModelElement
   *   The model element the caption should be returned for.
   * @return {module:engine/model/element~Element|null}
   *   The model caption element or `null` if there is none.
   */
  _getSavedCaption(drupalMediaModelElement) {
    const jsonObject = this._savedCaptionsMap.get(drupalMediaModelElement);

    return jsonObject ? Element.fromJSON(jsonObject) : null;
  }

  /**
   * Saves Drupal Media element caption to allow restoring it in the future.
   *
   * A caption is saved every time it gets hidden and/or the type of an Drupal
   * Media changes. The user should be able to restore it on demand.
   *
   * @param {module:engine/model/element~Element} drupalMediaModelElement
   *   The model element the caption is saved for.
   * @param {module:engine/model/element~Element} caption
   *   The caption model element to be saved.
   *
   * @see _getSavedCaption
   * @see module:engine/model/element~Element#toJSON
   */
  _saveCaption(drupalMediaModelElement, caption) {
    this._savedCaptionsMap.set(drupalMediaModelElement, caption.toJSON());
  }
}
