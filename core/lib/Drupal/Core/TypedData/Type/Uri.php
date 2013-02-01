<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Uri.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedData;

/**
 * The URI data type.
 *
 * The plain value of a URI is an absolute URI represented as PHP string.
 */
class Uri extends TypedData {

  /**
   * The data value.
   *
   * @var string
   */
  protected $value;
}
