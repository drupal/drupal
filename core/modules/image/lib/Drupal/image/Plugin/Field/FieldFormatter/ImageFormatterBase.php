<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\field\formatter\ImageFormatterBase.
 */

namespace Drupal\image\Plugin\Field\FieldFormatter;

use Drupal\field\FieldInstanceInterface;
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
        $fid = $this->getFieldSetting('default_image');
        // If we are dealing with a configurable field, look in both
        // instance-level and field-level settings.
        if (empty($fid) && $this->fieldDefinition instanceof FieldInstanceInterface) {
          $fid = $this->fieldDefinition->getField()->getFieldSetting('default_image');
        }

        if ($fid && ($file = file_load($fid))) {
          $items->setValue(array(array(
            'is_default' => TRUE,
            'alt' => '',
            'title' => '',
            'entity' => $file,
            'target_id' => $file->id(),
          )));
        }
      }
    }
  }

}
