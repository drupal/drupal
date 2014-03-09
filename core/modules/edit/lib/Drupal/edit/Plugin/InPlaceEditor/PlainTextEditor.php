<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditor\PlainTextEditor.
 */

namespace Drupal\edit\Plugin\InPlaceEditor;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\edit\Plugin\InPlaceEditorBase;

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
    if ($field_definition->getCardinality() != 1) {
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
        'edit/edit.inPlaceEditor.plainText',
      ),
    );
  }

}
