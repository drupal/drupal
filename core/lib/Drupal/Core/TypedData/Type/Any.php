<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Any.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedData;

/**
 * The "any" data type.
 *
 * The "any" data type does not implement a list or complex data interface, nor
 * is it mappable to any primitive type. Thus, it may contain any PHP data for
 * which no further metadata is available.
 */
class Any extends TypedData {

  /**
   * The data value.
   *
   * @var mixed
   */
  protected $value;
}
