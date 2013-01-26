<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Integer.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedData;

/**
 * The integer data type.
 *
 * The plain value of an integer is a regular PHP integer. For setting the value
 * any PHP variable that casts to an integer may be passed.
 */
class Integer extends TypedData {

  /**
   * The data value.
   *
   * @var integer
   */
  protected $value;

  /**
   * Overrides TypedData::setValue().
   */
  public function setValue($value) {
    $this->value = isset($value) ? (int) $value : $value;
  }
}
