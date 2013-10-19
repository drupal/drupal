<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditor\DirectEditor.
 */

namespace Drupal\edit\Plugin\InPlaceEditor;

use Drupal\edit\EditorBase;
use Drupal\edit\Annotation\InPlaceEditor;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the direct editor.
 *
 * @InPlaceEditor(
 *   id = "direct"
 * )
 */
class DirectEditor extends EditorBase {

  /**
   * {@inheritdoc}
   *
   * @todo The processed text logic is too coupled to text fields. Figure out
   *   how to generalize to other textual field types.
   */
  function isCompatible(FieldDefinitionInterface $field_definition, array $items) {
    // This editor is incompatible with multivalued fields.
    if ($field_definition->getFieldCardinality() != 1) {
      return FALSE;
    }
    // This editor is incompatible with processed ("rich") text fields.
    elseif ($field_definition->getFieldSetting('text_processing')) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Implements \Drupal\edit\EditPluginInterface::getAttachments().
   */
  public function getAttachments() {
    return array(
      'library' => array(
        array('edit', 'edit.editorWidget.direct'),
      ),
    );
  }
}
