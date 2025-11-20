/* eslint-disable import/no-extraneous-dependencies */

import { Command } from 'ckeditor5/src/core';
import { first } from 'ckeditor5/src/utils';

export default class DrupalEntityLinkSuggestionMetadataCommand extends Command {
  refresh() {
    const { model } = this.editor;
    const { selection } = model.document;

    // If the active selection is the link itself, the metadata is available in
    // its data-entity-metadata attribute.
    if (selection.hasAttribute('data-entity-metadata')) {
      this.value = JSON.parse(selection.getAttribute('data-entity-metadata'));
      this.isEnabled = model.schema.checkAttributeInSelection(
        selection,
        'data-entity-metadata',
      );
      return;
    }

    // If the active selection is image or media, the link metadata is stored in
    // the drupalLinkEntityMetadata property.
    const selectedElement =
      selection.getSelectedElement() || first(selection.getSelectedBlocks());
    if (
      selectedElement &&
      selectedElement.hasAttribute('drupalLinkEntityMetadata')
    ) {
      this.value = JSON.parse(
        selectedElement.getAttribute('drupalLinkEntityMetadata'),
      );
      this.isEnabled = model.schema.checkAttribute(
        selectedElement,
        'drupalLinkEntityMetadata',
      );
    }
  }
}
