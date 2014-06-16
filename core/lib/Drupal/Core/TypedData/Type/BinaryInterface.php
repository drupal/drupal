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
 * http://php.net/manual/language.types.resource.php. For setting the value
 * a PHP file resource or a (absolute) stream resource URI may be passed.
 *
 * @ingroup typed_data
 */
interface BinaryInterface extends PrimitiveInterface {

}
