<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\BinaryInterface.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\PrimitiveInterface;

/**
 * Interface for binary data.
 *
 * The plain value of binary data is a PHP file resource, see
 * http://php.net/manual/en/language.types.resource.php. For setting the value
 * a PHP file resource or a (absolute) stream resource URI may be passed.
 */
interface BinaryInterface extends PrimitiveInterface {

}
