<?php

namespace Drupal\image\Plugin\InPlaceEditor;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\quickedit\Plugin\InPlaceEditorBase;

/**
 * Defines the image text in-place editor.
 *
 * @InPlaceEditor(
 *   id = "image"
 * )
 */
class Image extends InPlaceEditorBase {

  /**
   * {@inheritdoc}
   */
  public function isCompatible(FieldItemListInterface $items) {
    $field_definition = $items->getFieldDefinition();

    // This editor is only compatible with single-value image fields.
    return $field_definition->getFieldStorageDefinition()->getCardinality() === 1
      && $field_definition->getType() === 'image';
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return [
      'library' => [
        'image/quickedit.inPlaceEditor.image',
      ],
    ];
  }

}
