<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\edit\editor\DirectEditor.
 */

namespace Drupal\edit\Plugin\edit\editor;

use Drupal\edit\EditorBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\field\FieldInstance;

/**
 * Defines the "direct" Create.js PropertyEditor widget.
 *
 * @Plugin(
 *   id = "direct",
 *   jsClassName = "direct",
 *   module = "edit"
 * )
 */
class DirectEditor extends EditorBase {

  /**
   * Implements \Drupal\edit\EditorInterface::isCompatible().
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
   * Implements \Drupal\edit\EditorInterface::getAttachments().
   */
  public function getAttachments() {
    return array(
      'library' => array(
        array('edit', 'edit.editorWidget.direct'),
      ),
    );
  }
}
