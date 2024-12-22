<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\Serialization\Attribute\JsonSchema;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Type\DecimalInterface;

/**
 * The decimal data type.
 *
 * Decimal type is stored as "decimal" in the relational database. Because PHP
 * does not have a primitive type decimal and using float can result in
 * unexpected rounding behavior, it is implemented and displayed as string.
 */
#[DataType(
  id: "decimal",
  label: new TranslatableMarkup("Decimal"),
)]
class DecimalData extends StringData implements DecimalInterface {

  /**
   * {@inheritdoc}
   */
  #[JsonSchema(['type' => 'string', 'format' => 'number'])]
  public function getCastedValue() {
    return $this->getString() ?: '0.0';
  }

}
