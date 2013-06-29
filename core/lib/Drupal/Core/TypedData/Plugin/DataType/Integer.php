<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Integer.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\TypedData\TypedData;

/**
 * The integer data type.
 *
 * The plain value of an integer is a regular PHP integer. For setting the value
 * any PHP variable that casts to an integer may be passed.
 *
 * @DataType(
 *   id = "integer",
 *   label = @Translation("Integer"),
 *   primitive_type = 3
 * )
 */
class Integer extends TypedData {

  /**
   * The data value.
   *
   * @var integer
   */
  protected $value;
}
