<?php

/**
 * @file
 * Contains \Drupal\edit_test\Plugin\InPlaceEditor\WysiwygEditor.
 */

namespace Drupal\edit_test\Plugin\InPlaceEditor;

use Drupal\edit\EditorBase;
use Drupal\edit\Annotation\InPlaceEditor;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines the wysiwyg editor.
 *
 * @InPlaceEditor(
 *   id = "wysiwyg",
 *   alternativeTo = {"direct"},
 *   module = "edit_test"
 * )
 */
class WysiwygEditor extends EditorBase {

  /**
   * Implements \Drupal\edit\EditPluginInterface::isCompatible().
   */
  function isCompatible(FieldInstance $instance, array $items) {
    $field = field_info_field($instance['field_name']);

    // This editor is incompatible with multivalued fields.
    if ($field['cardinality'] != 1) {
      return FALSE;
    }
    // This editor is compatible with processed ("rich") text fields; but only
    // if there is a currently active text format and that text format is the
    // 'full_html' text format.
    elseif (!empty($instance['settings']['text_processing'])) {
      $format_id = $items[0]['format'];
      if (isset($format_id) && $format_id === 'full_html') {
        return TRUE;
      }
      return FALSE;
    }
  }

  /**
   * Implements \Drupal\edit\EditPluginInterface::getMetadata().
   */
  function getMetadata(FieldInstance $instance, array $items) {
    $format_id = $items[0]['format'];
    $metadata['format'] = $format_id;
    return $metadata;
  }

  /**
   * Implements \Drupal\edit\EditPluginInterface::getAttachments().
   */
  public function getAttachments() {
    return array(
      'library' => array(
        array('edit_test', 'not-existing-wysiwyg'),
      ),
    );
  }
}
