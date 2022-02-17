/* eslint-disable import/no-extraneous-dependencies */
// cSpell:words insertdrupalmediacommand
import { Command } from 'ckeditor5/src/core';

/**
 * @module drupalMedia/insertdrupalmediacommand
 */

function createDrupalMedia(writer, attributes) {
  const drupalMedia = writer.createElement('drupalMedia', attributes);
  return drupalMedia;
}

/**
 * @internal
 */
/**
 * The insert media command.
 *
 * The command is registered by the `DrupalMediaEditing` plugin as
 * `insertDrupalMedia`.
 *
 * In order to insert media at the current selection position, execute the
 * command and pass the attributes desired in the drupal-media element:
 *
 *    editor.execute('insertDrupalMedia', {
 *      'alt': 'Alt text',
 *      'data-align': 'left',
 *      'data-caption': 'Caption text',
 *      'data-entity-type': 'media',
 *      'data-entity-uuid': 'media-entity-uuid',
 *      'data-view-mode': 'default',
 *    });
 */
export default class InsertDrupalMediaCommand extends Command {
  execute(attributes) {
    const mediaEditing = this.editor.plugins.get('DrupalMediaEditing');

    // Create object that contains supported data-attributes in view data by
    // flipping `DrupalMediaEditing.attrs` object (i.e. keys from object become
    // values and values from object become keys).
    const dataAttributeMapping = Object.entries(mediaEditing.attrs).reduce(
      (result, [key, value]) => {
        result[value] = key;
        return result;
      },
      {},
    );

    // \Drupal\media\Form\EditorMediaDialog returns data in keyed by
    // data-attributes used in view data. This converts data-attribute keys to
    // keys used in model.
    const modelAttributes = Object.keys(attributes).reduce(
      (result, attribute) => {
        if (dataAttributeMapping[attribute]) {
          result[dataAttributeMapping[attribute]] = attributes[attribute];
        }
        return result;
      },
      {},
    );

    // Check if there's Drupal Element Style matching the default attributes on
    // the media.
    // @see module:drupalMedia/drupalelementstyle/drupalelementstyleediting~DrupalElementStyleEditing
    if (this.editor.plugins.has('DrupalElementStyleEditing')) {
      const elementStyleEditing = this.editor.plugins.get(
        'DrupalElementStyleEditing',
      );
      // eslint-disable-next-line no-restricted-syntax
      for (const style of elementStyleEditing.normalizedStyles) {
        if (
          attributes[style.attributeName] &&
          style.attributeValue === attributes[style.attributeName]
        ) {
          modelAttributes.drupalElementStyle = style.name;
          break;
        }
      }
    }

    this.editor.model.change((writer) => {
      this.editor.model.insertContent(
        createDrupalMedia(writer, modelAttributes),
      );
    });
  }

  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'drupalMedia',
    );
    this.isEnabled = allowedIn !== null;
  }
}
