<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\PrimitiveBase;
use Drupal\Core\TypedData\Type\FloatInterface;

/**
 * The float data type.
 *
 * The plain value of a float is a regular PHP float. For setting the value
 * any PHP variable that casts to a float may be passed.
 *
 * @DataType(
 *   id = "float",
 *   label = @Translation("Float")
 * )
 */
class FloatData extends PrimitiveBase implements FloatInterface {

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return (float) $this->value;
  }

}
