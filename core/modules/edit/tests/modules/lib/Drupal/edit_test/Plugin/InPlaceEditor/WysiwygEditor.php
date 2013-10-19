<?php

/**
 * @file
 * Contains \Drupal\edit_test\Plugin\InPlaceEditor\WysiwygEditor.
 */

namespace Drupal\edit_test\Plugin\InPlaceEditor;

use Drupal\edit\EditorBase;
use Drupal\edit\Annotation\InPlaceEditor;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the wysiwyg editor.
 *
 * @InPlaceEditor(
 *   id = "wysiwyg",
 *   alternativeTo = {"direct"}
 * )
 */
class WysiwygEditor extends EditorBase {

  /**
   * {@inheritdoc}
   */
  function isCompatible(FieldDefinitionInterface $field_definition, array $items) {
    // This editor is incompatible with multivalued fields.
    if ($field_definition->getFieldCardinality() != 1) {
      return FALSE;
    }
    // This editor is compatible with processed ("rich") text fields; but only
    // if there is a currently active text format and that text format is the
    // 'full_html' text format.
    elseif ($field_definition->getFieldSetting('text_processing')) {
      $format_id = $items[0]['format'];
      if (isset($format_id) && $format_id === 'full_html') {
        return TRUE;
      }
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  function getMetadata(FieldDefinitionInterface $field_definition, array $items) {
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
