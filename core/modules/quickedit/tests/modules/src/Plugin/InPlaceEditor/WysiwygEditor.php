<?php

/**
 * @file
 * Contains \Drupal\quickedit_test\Plugin\InPlaceEditor\WysiwygEditor.
 */

namespace Drupal\quickedit_test\Plugin\InPlaceEditor;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\quickedit\Plugin\InPlaceEditorBase;

/**
 * Defines the 'wysiwyg' in-place editor.
 *
 * @InPlaceEditor(
 *   id = "wysiwyg",
 *   alternativeTo = {"plain_text"}
 * )
 */
class WysiwygEditor extends InPlaceEditorBase {

  /**
   * {@inheritdoc}
   */
  public function isCompatible(FieldItemListInterface $items) {
    $field_definition = $items->getFieldDefinition();

    // This editor is incompatible with multivalued fields.
    if ($field_definition->getFieldStorageDefinition()->getCardinality() != 1) {
      return FALSE;
    }
    // This editor is compatible with processed ("rich") text fields; but only
    // if there is a currently active text format and that text format is the
    // 'full_html' text format.
    elseif ($field_definition->getSetting('text_processing')) {
      if ($items[0]->format === 'full_html') {
        return TRUE;
      }
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(FieldItemListInterface $items) {
    $metadata['format'] = $items[0]->format;
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return array(
      'library' => array(
        'quickedit_test/not-existing-wysiwyg',
      ),
    );
  }

}
