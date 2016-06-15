<?php

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
   */
  public function isCompatible(FieldItemListInterface $items) {
    $field_definition = $items->getFieldDefinition();

    // This editor is incompatible with multivalued fields.
    return $field_definition->getFieldStorageDefinition()->getCardinality() == 1;
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
