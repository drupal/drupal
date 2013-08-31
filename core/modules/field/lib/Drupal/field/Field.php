<?php

/**
 * @file
 * Contains \Drupal\field\Field.
 */

namespace Drupal\field;

/**
 * Static service container wrapper for Field.
 */
class Field {

  /**
   * Returns the field info service.
   *
   * @return \Drupal\field\FieldInfo
   *   Returns a field info object.
   */
  public static function fieldInfo() {
    return \Drupal::service('field.info');
  }

}
