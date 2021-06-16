<?php

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\PrimitiveInterface;

/**
 * Interface for floating-point numbers.
 *
 * The plain value of a float is a regular PHP float. For setting the value
 * any PHP variable that casts to a float may be passed.
 *
 * @ingroup typed_data
 */
interface FloatInterface extends PrimitiveInterface {

}
