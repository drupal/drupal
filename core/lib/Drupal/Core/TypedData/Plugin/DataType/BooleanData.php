<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

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
class BooleanData extends PrimitiveBase implements BooleanInterface {

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return (bool) $this->value;
  }
}
