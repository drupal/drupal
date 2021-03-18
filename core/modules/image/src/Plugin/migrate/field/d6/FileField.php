<?php

namespace Drupal\image\Plugin\migrate\field\d6;

use Drupal\file\Plugin\migrate\field\d6\FileField as DefaultFileField;

// cspell:ignore imagefield filefield

/**
 * Replacement plugin class for the Drupal 6 'filefield' field migration plugin.
 *
 * @see image_migrate_field_info_alter()
 */
class FileField extends DefaultFileField {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return ['imagefield_widget' => 'image_image'] + parent::getFieldWidgetMap();
  }

}
