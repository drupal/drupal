<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Type\DecimalInterface;

/**
 * The decimal data type.
 *
 * Decimal type is stored as "decimal" in the relational database. Because PHP
 * does not have a primitive type decimal and using float can result in
 * unexpected rounding behavior, it is implemented and displayed as string.
 *
 * @DataType(
 *   id = "decimal",
 *   label = @Translation("Decimal")
 * )
 */
class DecimalData extends StringData implements DecimalInterface {

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->getString() ?: '0.0';
  }

}
