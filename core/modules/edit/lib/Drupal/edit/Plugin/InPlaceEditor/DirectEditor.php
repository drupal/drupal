<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditor\DirectEditor.
 */

namespace Drupal\edit\Plugin\InPlaceEditor;

use Drupal\edit\EditorBase;
use Drupal\edit\Annotation\InPlaceEditor;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines the direct editor.
 *
 * @InPlaceEditor(
 *   id = "direct",
 *   module = "edit"
 * )
 */
class DirectEditor extends EditorBase {

  /**
   * Implements \Drupal\edit\EditPluginInterface::isCompatible().
   *
   * @todo The processed text logic is too coupled to text fields. Figure out
   *   how to generalize to other textual field types.
   */
  function isCompatible(FieldInstance $instance, array $items) {
    $field = field_info_field($instance['field_name']);

    // This editor is incompatible with multivalued fields.
    if ($field['cardinality'] != 1) {
      return FALSE;
    }
    // This editor is incompatible with processed ("rich") text fields.
    elseif (!empty($instance['settings']['text_processing'])) {
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
