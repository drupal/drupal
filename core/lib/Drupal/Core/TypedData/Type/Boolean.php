<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Boolean.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedData;

/**
 * The boolean data type.
 *
 * The plain value of a boolean is a regular PHP boolean. For setting the value
 * any PHP variable that casts to a boolean may be passed.
 */
class Boolean extends TypedData {

  /**
   * The data value.
   *
   * @var boolean
   */
  protected $value;
}
