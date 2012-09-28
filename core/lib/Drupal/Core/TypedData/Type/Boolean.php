<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\Type\Boolean.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedDataInterface;

/**
 * The boolean data type.
 *
 * The plain value of a boolean is a regular PHP boolean. For setting the value
 * any PHP variable that casts to a boolean may be passed.
 */
class Boolean extends TypedData implements TypedDataInterface {

  /**
   * The data value.
   *
   * @var boolean
   */
  protected $value;

  /**
   * Implements TypedDataInterface::setValue().
   */
  public function setValue($value) {
    $this->value = isset($value) ? (bool) $value : $value;
  }

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate() {
    // TODO: Implement validate() method.
  }
}
