<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditor\PlainTextEditor.
 */

namespace Drupal\edit\Plugin\InPlaceEditor;

use Drupal\edit\EditorBase;
use Drupal\edit\Annotation\InPlaceEditor;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the plain text in-place editor.
 *
 * @InPlaceEditor(
 *   id = "plain_text"
 * )
 */
class PlainTextEditor extends EditorBase {

  /**
   * {@inheritdoc}
   *
   * @todo The processed text logic is too coupled to text fields. Figure out
   *   how to generalize to other textual field types.
   */
  function isCompatible(FieldDefinitionInterface $field_definition, array $items) {
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
        array('edit', 'edit.inPlaceEditor.plainText'),
      ),
    );
  }

}
