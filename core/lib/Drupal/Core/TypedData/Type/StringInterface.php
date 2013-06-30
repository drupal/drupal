<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\StringInterface.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\PrimitiveInterface;

/**
 * Interface for strings.
 *
 * The plain value of a string is a regular PHP string. For setting the value
 * any PHP variable that casts to a string may be passed.
 */
interface StringInterface extends PrimitiveInterface {

}
