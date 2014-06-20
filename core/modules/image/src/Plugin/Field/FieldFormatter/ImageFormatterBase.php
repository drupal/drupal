<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\field\formatter\ImageFormatterBase.
 */

namespace Drupal\image\Plugin\Field\FieldFormatter;

use Drupal\field\FieldInstanceConfigInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Base class for image file formatters.
 */
abstract class ImageFormatterBase extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {
    parent::prepareView($entities_items);

    // If there are no files specified at all, use the default.
    foreach ($entities_items as $items) {
      if ($items->isEmpty()) {
        // Add the default image if one is found.
        $default_image = $this->getFieldSetting('default_image');
        // If we are dealing with a configurable field, look in both
        // instance-level and field-level settings.
        if (empty($default_image['fid']) && $this->fieldDefinition instanceof FieldInstanceConfigInterface) {
          $default_image = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('default_image');
        }

        if (!empty($default_image['fid']) && ($file = file_load($default_image['fid']))) {
          $items->setValue(array(array(
            'is_default' => TRUE,
            'alt' => $default_image['alt'],
            'title' => $default_image['title'],
            'width' => $default_image['width'],
            'height' => $default_image['height'],
            'entity' => $file,
            'target_id' => $file->id(),
          )));
        }
      }
    }
  }

}
