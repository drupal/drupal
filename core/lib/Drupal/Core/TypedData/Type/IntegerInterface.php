<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\IntegerInterface.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\PrimitiveInterface;

/**
 * Interface for integer numbers.
 *
 * The plain value of an integer is a regular PHP integer. For setting the value
 * any PHP variable that casts to an integer may be passed.
 *
 * @ingroup typed_data
 */
interface IntegerInterface extends PrimitiveInterface {

}
