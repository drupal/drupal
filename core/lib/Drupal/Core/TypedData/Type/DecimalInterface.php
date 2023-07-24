<?php

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\PrimitiveInterface;

/**
 * Interface for decimal numbers.
 *
 * The plain value of a decimal is a PHP string. For setting the value
 * any PHP variable that casts to an numeric string may be passed.
 *
 * @ingroup typed_data
 */
interface DecimalInterface extends PrimitiveInterface {

}
