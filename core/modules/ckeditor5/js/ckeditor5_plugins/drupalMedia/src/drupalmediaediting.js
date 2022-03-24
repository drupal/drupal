/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words insertdrupalmedia drupalmediaediting */

import { Plugin } from 'ckeditor5/src/core';
import { toWidget, Widget } from 'ckeditor5/src/widget';

import InsertDrupalMediaCommand from './insertdrupalmedia';
import { getPreviewContainer } from './utils';

/**
 * @module drupalMedia/drupalmediaediting
 */

/**
 * @internal
 */
export default class DrupalMediaEditing extends Plugin {
  static get requires() {
    return [Widget];
  }

  init() {
    this.attrs = {
      drupalMediaAlt: 'alt',
      drupalMediaEntityType: 'data-entity-type',
      drupalMediaEntityUuid: 'data-entity-uuid',
      drupalMediaViewMode: 'data-view-mode',
    };
    const options = this.editor.config.get('drupalMedia');
    if (!options) {
      return;
    }
    const { previewURL, themeError } = options;
    this.previewUrl = previewURL;
    this.labelError = Drupal.t('Preview failed');
    this.themeError =
      themeError ||
      `
      <p>${Drupal.t(
        'An error occurred while trying to preview the media. Please save your work and reload this page.',
      )}<p>
    `;

    this._defineSchema();
    this._defineConverters();

    this.editor.commands.add(
      'insertDrupalMedia',
      new InsertDrupalMediaCommand(this.editor),
    );
  }

  /**
   * Fetches preview from the server.
   *
   * @param {module:engine/model/element~Element} modelElement
   *   The model element which preview should be loaded.
   * @return {Promise<{preview: string, label: string}>}
   *   A promise that returns an object.
   *
   * @private
   */
  async _fetchPreview(modelElement) {
    const query = {
      text: this._renderElement(modelElement),
      uuid: modelElement.getAttribute('drupalMediaEntityUuid'),
    };

    const response = await fetch(
      `${this.previewUrl}?${new URLSearchParams(query)}`,
      {
        headers: {
          'X-Drupal-MediaPreview-CSRF-Token':
            this.editor.config.get('drupalMedia').previewCsrfToken,
        },
      },
    );
    if (response.ok) {
      const label = response.headers.get('drupal-media-label');
      const preview = await response.text();
      return { label, preview };
    }

    return { label: this.labelError, preview: this.themeError };
  }

  _defineSchema() {
    const schema = this.editor.model.schema;
    schema.register('drupalMedia', {
      allowWhere: '$block',
      isObject: true,
      isContent: true,
      isBlock: true,
      allowAttributes: Object.keys(this.attrs),
    });
    // Register `<drupal-media>` as a block element in the DOM converter. This
    // ensures that the DOM converter knows to handle the `<drupal-media>` as a
    // block element.
    this.editor.editing.view.domConverter.blockElements.push('drupal-media');
  }

  _defineConverters() {
    const conversion = this.editor.conversion;

    conversion.for('upcast').elementToElement({
      view: {
        name: 'drupal-media',
      },
      model: 'drupalMedia',
    });

    conversion.for('dataDowncast').elementToElement({
      model: 'drupalMedia',
      view: {
        name: 'drupal-media',
      },
    });
    conversion
      .for('editingDowncast')
      .elementToElement({
        model: 'drupalMedia',
        view: (modelElement, { writer }) => {
          const container = writer.createContainerElement('figure', {
            class: 'drupal-media',
          });
          if (!this.previewUrl) {
            // If preview URL isn't available, insert empty preview element
            // which indicates that preview couldn't be loaded.
            const mediaPreview = writer.createRawElement('div', {
              'data-drupal-media-preview': 'unavailable',
            });
            writer.insert(writer.createPositionAt(container, 0), mediaPreview);
          }
          writer.setCustomProperty('drupalMedia', true, container);

          return toWidget(container, writer, {
            label: Drupal.t('Media widget'),
          });
        },
      })
      .add((dispatcher) => {
        const converter = (event, data, conversionApi) => {
          const viewWriter = conversionApi.writer;
          const modelElement = data.item;
          const container = conversionApi.mapper.toViewElement(data.item);

          // Search for preview container recursively from its children because
          // the preview container could be wrapped with an element such as
          // `<a>`.
          let media = getPreviewContainer(container.getChildren());

          // Use pre-existing media preview container if one exists. If the
          // preview element doesn't exist, create a new element.
          if (media) {
            // Stop processing if media preview is unavailable or a preview is
            // already loading.
            if (media.getAttribute('data-drupal-media-preview') !== 'ready') {
              return;
            }

            // Preview was ready meaning that a new preview can be loaded.
            // "Change the attribute to loading to prepare for the loading of
            // the updated preview. Preview is kept intact so that it remains
            // interactable in the UI until the new preview has been rendered.
            viewWriter.setAttribute(
              'data-drupal-media-preview',
              'loading',
              media,
            );
          } else {
            media = viewWriter.createRawElement('div', {
              'data-drupal-media-preview': 'loading',
            });
            viewWriter.insert(viewWriter.createPositionAt(container, 0), media);
          }

          this._fetchPreview(modelElement).then(({ label, preview }) => {
            if (!media) {
              // Nothing to do if associated preview wrapped no longer exist.
              return;
            }
            // CKEditor 5 doesn't support async view conversion. Therefore, once
            // the promise is fulfilled, the editing view needs to be modified
            // manually.
            this.editor.editing.view.change((writer) => {
              const mediaPreview = writer.createRawElement(
                'div',
                { 'data-drupal-media-preview': 'ready', 'aria-label': label },
                (domElement) => {
                  domElement.innerHTML = preview;
                },
              );
              // Insert the new preview before the previous preview element to
              // ensure that the location remains same even if it is wrapped
              // with another element.
              writer.insert(writer.createPositionBefore(media), mediaPreview);
              writer.remove(media);
            });
          });
        };

        // List all attributes that should trigger re-rendering of the
        // preview.
        dispatcher.on('attribute:drupalMediaEntityUuid:drupalMedia', converter);
        dispatcher.on('attribute:drupalMediaViewMode:drupalMedia', converter);
        dispatcher.on('attribute:drupalMediaEntityType:drupalMedia', converter);
        dispatcher.on('attribute:drupalMediaAlt:drupalMedia', converter);

        return dispatcher;
      });

    conversion.for('editingDowncast').add((dispatcher) => {
      dispatcher.on(
        'attribute:drupalElementStyle:drupalMedia',
        (evt, data, conversionApi) => {
          const alignMapping = {
            alignLeft: 'drupal-media-style-align-left',
            alignRight: 'drupal-media-style-align-right',
            alignCenter: 'drupal-media-style-align-center',
          };
          const viewElement = conversionApi.mapper.toViewElement(data.item);
          const viewWriter = conversionApi.writer;

          // If the prior value is alignment related, it should be removed
          // whether or not the module property is consumed.
          if (alignMapping[data.attributeOldValue]) {
            viewWriter.removeClass(
              alignMapping[data.attributeOldValue],
              viewElement,
            );
          }

          // If the new value is not alignment related, do not proceed.
          if (!alignMapping[data.attributeNewValue]) {
            return;
          }

          // The model property is already consumed, do not proceed.
          if (!conversionApi.consumable.consume(data.item, evt.name)) {
            return;
          }

          // Add the alignment class in the view that corresponds to the value
          // of the model's drupalElementStyle property.
          viewWriter.addClass(
            alignMapping[data.attributeNewValue],
            viewElement,
          );
        },
      );
    });

    // Set attributeToAttribute conversion for all supported attributes.
    Object.keys(this.attrs).forEach((modelKey) => {
      const attributeMapping = {
        model: {
          key: modelKey,
          name: 'drupalMedia',
        },
        view: {
          name: 'drupal-media',
          key: this.attrs[modelKey],
        },
      };
      // Attributes should be rendered only in dataDowncast to avoid having
      // unfiltered data-attributes on the Drupal Media widget.
      conversion.for('dataDowncast').attributeToAttribute(attributeMapping);
      conversion.for('upcast').attributeToAttribute(attributeMapping);
    });
  }

  /**
   * MediaFilterController::preview requires the saved element.
   *
   * Not previewing data-caption since it does not get updated by new changes.
   *
   * @param {module:engine/model/element~Element} modelElement
   *   The drupalMedia model element to be converted.
   * @return {string}
   *   The model element converted into HTML.
   */
  _renderElement(modelElement) {
    // Create model document fragment which contains the model element so that
    // it can be stringified using the dataDowncast.
    const modelDocumentFragment = this.editor.model.change((writer) => {
      const modelDocumentFragment = writer.createDocumentFragment();
      // Create shallow clone of the model element to ensure that the original
      // model element remains untouched and that the caption is not rendered
      // into the preview.
      const clonedModelElement = writer.cloneElement(modelElement, false);
      // Remove attributes from the model element to ensure they are not
      // downcast into the preview request. For example, the `linkHref` model
      // attribute would downcast into a wrapping `<a>` element, which the
      // preview endpoint would not be able to handle.
      const attributeIgnoreList = ['linkHref'];
      attributeIgnoreList.forEach((attribute) => {
        writer.removeAttribute(attribute, clonedModelElement);
      });
      writer.append(clonedModelElement, modelDocumentFragment);

      return modelDocumentFragment;
    });

    return this.editor.data.stringify(modelDocumentFragment);
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalMediaEditing';
  }
}
