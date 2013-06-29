<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\String.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\TypedData\TypedData;

/**
 * The string data type.
 *
 * The plain value of a string is a regular PHP string. For setting the value
 * any PHP variable that casts to a string may be passed.
 *
 * @DataType(
 *   id = "string",
 *   label = @Translation("String"),
 *   primitive_type = 2
 * )
 */
class String extends TypedData {

  /**
   * The data value.
   *
   * @var string
   */
  protected $value;
}
