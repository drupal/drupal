<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Float.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\TypedData\TypedData;

/**
 * The float data type.
 *
 * The plain value of a float is a regular PHP float. For setting the value
 * any PHP variable that casts to a float may be passed.
 *
 * @DataType(
 *   id = "float",
 *   label = @Translation("Float"),
 *   primitive_type = 4
 * )
 */
class Float extends TypedData {

  /**
   * The data value.
   *
   * @var float
   */
  protected $value;
}
