<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Float.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedData;

/**
 * The float data type.
 *
 * The plain value of a float is a regular PHP float. For setting the value
 * any PHP variable that casts to a float may be passed.
 */
class Float extends TypedData {

  /**
   * The data value.
   *
   * @var float
   */
  protected $value;
}
