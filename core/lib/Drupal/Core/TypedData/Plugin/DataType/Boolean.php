<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Boolean.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\TypedData\TypedData;

/**
 * The boolean data type.
 *
 * The plain value of a boolean is a regular PHP boolean. For setting the value
 * any PHP variable that casts to a boolean may be passed.
 *
 * @DataType(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   primitive_type = 1
 * )
 */
class Boolean extends TypedData {

  /**
   * The data value.
   *
   * @var boolean
   */
  protected $value;
}
