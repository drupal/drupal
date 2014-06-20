<?php

/**
 * @file
 * Contains \Drupal\quickedit\Plugin\InPlaceEditor\PlainTextEditor.
 */

namespace Drupal\quickedit\Plugin\InPlaceEditor;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\quickedit\Plugin\InPlaceEditorBase;

/**
 * Defines the plain text in-place editor.
 *
 * @InPlaceEditor(
 *   id = "plain_text"
 * )
 */
class PlainTextEditor extends InPlaceEditorBase {

  /**
   * {@inheritdoc}
   *
   * @todo The processed text logic is too coupled to text fields. Figure out
   *   how to generalize to other textual field types.
   */
  public function isCompatible(FieldItemListInterface $items) {
    $field_definition = $items->getFieldDefinition();

    // This editor is incompatible with multivalued fields.
    if ($field_definition->getFieldStorageDefinition()->getCardinality() != 1) {
      return FALSE;
    }
    // This editor is incompatible with processed ("rich") text fields.
    elseif ($field_definition->getSetting('text_processing')) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return array(
      'library' => array(
        'quickedit/quickedit.inPlaceEditor.plainText',
      ),
    );
  }

}
