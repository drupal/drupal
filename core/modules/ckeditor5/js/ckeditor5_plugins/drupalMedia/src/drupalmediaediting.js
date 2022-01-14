/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words insertdrupalmedia */

import { Plugin } from 'ckeditor5/src/core';
import { toWidget, Widget } from 'ckeditor5/src/widget';

import InsertDrupalMediaCommand from './insertdrupalmedia';

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
      drupalMediaAlign: 'data-align',
      drupalMediaCaption: 'data-caption',
      drupalMediaEntityType: 'data-entity-type',
      drupalMediaEntityUuid: 'data-entity-uuid',
      drupalMediaViewMode: 'data-view-mode',
    };
    const options = this.editor.config.get('drupalMedia');
    if (!options) {
      return;
    }
    const { previewURL, themeError } = options;
    this.previewURL = previewURL;
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

  async _fetchPreview(url, query) {
    const response = await fetch(`${url}?${new URLSearchParams(query)}`, {
      headers: {
        'X-Drupal-MediaPreview-CSRF-Token':
          this.editor.config.get('drupalMedia').previewCsrfToken,
      },
    });
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
      allowAttributes: Object.keys(this.attrs),
    });
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

    conversion.for('editingDowncast').elementToElement({
      model: 'drupalMedia',
      view: (modelElement, { writer: viewWriter }) => {
        const container = viewWriter.createContainerElement('div', {
          class: 'drupal-media',
        });
        const media = viewWriter.createRawElement(
          'div',
          { 'data-drupal-media-preview': 'loading' },
          (domElement) => {
            if (this.previewURL) {
              this._fetchPreview(this.previewURL, {
                text: this._renderElement(modelElement),
                uuid: modelElement.getAttribute('drupalMediaEntityUuid'),
              }).then(({ label, preview }) => {
                domElement.innerHTML = preview;
                domElement.setAttribute('aria-label', label);
                domElement.setAttribute('data-drupal-media-preview', 'ready');
              });
            } else {
              domElement.innerHTML = this.themeError;
              domElement.setAttribute('aria-label', 'drupal-media');
              domElement.setAttribute(
                'data-drupal-media-preview',
                'unavailable',
              );
            }
          },
        );
        viewWriter.insert(viewWriter.createPositionAt(container, 0), media);
        viewWriter.setCustomProperty('drupalMedia', true, container);
        return toWidget(container, viewWriter, { label: 'media widget' });
      },
    });

    // Set attributeToAttribute conversion for all supported attributes.
    Object.keys(this.attrs).forEach((modelKey) => {
      conversion.attributeToAttribute({
        model: {
          key: modelKey,
          name: 'drupalMedia',
        },
        view: {
          name: 'drupal-media',
          key: this.attrs[modelKey],
        },
      });
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
   *
   * @todo: is there a better way to get the rendered dataDowncast string
   *   https://www.drupal.org/project/ckeditor5/issues/3231337?
   */
  _renderElement(modelElement) {
    const attrs = modelElement.getAttributes();
    let element = '<drupal-media';
    Array.from(attrs).forEach((attr) => {
      if (this.attrs[attr[0]] && attr[0] !== 'drupalMediaCaption') {
        element += ` ${this.attrs[attr[0]]}="${attr[1]}"`;
      }
    });
    element += '></drupal-media>';

    return element;
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalMediaEditing';
  }
}
