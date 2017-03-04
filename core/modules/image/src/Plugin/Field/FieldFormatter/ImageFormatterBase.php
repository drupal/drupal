<?php

namespace Drupal\image\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Base class for image file formatters.
 */
abstract class ImageFormatterBase extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    // Add the default image if needed.
    if ($items->isEmpty()) {
      $default_image = $this->getFieldSetting('default_image');
      // If we are dealing with a configurable field, look in both
      // instance-level and field-level settings.
      if (empty($default_image['uuid']) && $this->fieldDefinition instanceof FieldConfigInterface) {
        $default_image = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('default_image');
      }
      if (!empty($default_image['uuid']) && $file = \Drupal::entityManager()->loadEntityByUuid('file', $default_image['uuid'])) {
        // Clone the FieldItemList into a runtime-only object for the formatter,
        // so that the fallback image can be rendered without affecting the
        // field values in the entity being rendered.
        $items = clone $items;
        $items->setValue([
          'target_id' => $file->id(),
          'alt' => $default_image['alt'],
          'title' => $default_image['title'],
          'width' => $default_image['width'],
          'height' => $default_image['height'],
          'entity' => $file,
          '_loaded' => TRUE,
          '_is_default' => TRUE,
        ]);
        $file->_referringItem = $items[0];
      }
    }

    return parent::getEntitiesToView($items, $langcode);
  }

}
