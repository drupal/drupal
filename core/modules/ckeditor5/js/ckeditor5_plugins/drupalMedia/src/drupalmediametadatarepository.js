/* eslint-disable import/no-extraneous-dependencies */

import { Plugin } from 'ckeditor5/src/core';

/**
 * @module drupalMedia/drupalmediametadatarepository
 */

/**
 * Fetch metadata from the backend.
 *
 * @param {string} url
 *   The URL used for retrieving the metadata.
 * @return {Promise<Object>}
 *   Promise containing response content.
 *
 * @private
 */
const _fetchMetadata = async (url) => {
  const response = await fetch(url);
  if (response.ok) {
    return JSON.parse(await response.text());
  }

  throw new Error('Fetching media embed metadata from the server failed.');
};

/**
 * @internal
 */
export default class DrupalMediaMetadataRepository extends Plugin {
  /**
   * @inheritdoc
   */
  init() {
    this._data = new WeakMap();
  }

  /**
   * Gets metadata for `drupalMedia` model element.
   *
   * @param {module:engine/model/element~Element} modelElement
   *   The model element which metadata should be retrieved.
   * @return {Promise<Object>}
   */
  getMetadata(modelElement) {
    // If metadata was retrieved earlier for the model element, return the
    // cached value.
    if (this._data.get(modelElement)) {
      return new Promise((resolve) => {
        resolve(this._data.get(modelElement));
      });
    }

    const options = this.editor.config.get('drupalMedia');
    if (!options) {
      return new Promise((resolve, reject) => {
        reject(
          new Error(
            'drupalMedia configuration is required for parsing metadata.',
          ),
        );
      });
    }

    if (!modelElement.hasAttribute('drupalMediaEntityUuid')) {
      return new Promise((resolve, reject) => {
        reject(
          new Error(
            'drupalMedia element must have drupalMediaEntityUuid attribute to retrieve metadata.',
          ),
        );
      });
    }

    const { metadataUrl } = options;
    const query = new URLSearchParams({
      uuid: modelElement.getAttribute('drupalMediaEntityUuid'),
    });
    // The `metadataUrl` received from the server already includes a query
    // string (for the CSRF token).
    // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Media::getDynamicPluginConfig()
    const url = `${metadataUrl}&${query}`;

    return _fetchMetadata(url).then((metadata) => {
      this._data.set(modelElement, metadata);
      return metadata;
    });
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalMediaMetadataRepository';
  }
}
