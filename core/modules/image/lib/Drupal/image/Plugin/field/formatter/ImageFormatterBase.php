<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\field\formatter\ImageFormatterBase.
 */

namespace Drupal\image\Plugin\field\formatter;

use Drupal\file\Plugin\field\formatter\FileFormatterBase;

/**
 * Base class for image file formatters.
 */
abstract class ImageFormatterBase extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities, $langcode, array &$items) {
    parent::prepareView($entities, $langcode, $items);

    // If there are no files specified at all, use the default.
    foreach ($entities as $id => $entity) {
      if (empty($items[$id])) {
        $fid = array();
        $instance = field_info_instance($entity->entityType(), $this->fieldDefinition->getFieldName(), $entity->bundle());
        // Use the default for the instance if one is available.
        if (!empty($instance['settings']['default_image'])) {
          $fid = array($instance['settings']['default_image']);
        }
        // Otherwise, use the default for the field.
        // Note, that we have to bypass getFieldSetting() as this returns the
        // instance-setting default.
        elseif (($field = $this->fieldDefinition->getField()) && !empty($field->settings['default_image'])) {
          $fid = array($field->settings['default_image']);
        }

        // Add the default image if one is found.
        if ($fid && ($file = file_load($fid[0]))) {
          $items[$id][0] = array(
            'is_default' => TRUE,
            'alt' => '',
            'title' => '',
            'entity' => $file,
            'fid' => $file->id(),
          );
        }
      }
    }
  }

}
