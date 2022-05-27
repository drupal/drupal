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
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use the
 *   Drupal\quickedit\Plugin\InPlaceEditor\Image in-place editor instead.
 *
 * @see https://www.drupal.org/node/3271848
 */
class Image extends InPlaceEditorBase {

  /**
   * Constructs a Image object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error('Drupal\image\Plugin\InPlaceEditor\Image is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use Drupal\quickedit\Plugin\InPlaceEditor\Image instead. See https://www.drupal.org/node/3271848', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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
