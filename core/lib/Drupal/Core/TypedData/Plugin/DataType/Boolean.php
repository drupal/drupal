<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Boolean.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\TypedData\PrimitiveBase;
use Drupal\Core\TypedData\Type\BooleanInterface;

/**
 * The boolean data type.
 *
 * The plain value of a boolean is a regular PHP boolean. For setting the value
 * any PHP variable that casts to a boolean may be passed.
 *
 * @DataType(
 *   id = "boolean",
 *   label = @Translation("Boolean")
 * )
 */
class Boolean extends PrimitiveBase implements BooleanInterface {

}
