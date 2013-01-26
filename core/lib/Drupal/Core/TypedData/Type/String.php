<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\String.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedData;

/**
 * The string data type.
 *
 * The plain value of a string is a regular PHP string. For setting the value
 * any PHP variable that casts to a string may be passed.
 */
class String extends TypedData {

  /**
   * The data value.
   *
   * @var string
   */
  protected $value;

  /**
   * Overrides TypedData::setValue().
   */
  public function setValue($value) {
    $this->value = isset($value) ? (string) $value : $value;
  }
}
