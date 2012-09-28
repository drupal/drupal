<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\Type\Integer.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedDataInterface;

/**
 * The integer data type.
 *
 * The plain value of an integer is a regular PHP integer. For setting the value
 * any PHP variable that casts to an integer may be passed.
 */
class Integer extends TypedData implements TypedDataInterface {

  /**
   * The data value.
   *
   * @var integer
   */
  protected $value;

  /**
   * Implements TypedDataInterface::setValue().
   */
  public function setValue($value) {
    $this->value = isset($value) ? (int) $value : $value;
  }

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate() {
    // TODO: Implement validate() method.
  }
}
