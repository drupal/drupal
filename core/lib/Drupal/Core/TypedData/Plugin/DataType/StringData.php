<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\Serialization\Attribute\JsonSchema;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\PrimitiveBase;
use Drupal\Core\TypedData\Type\StringInterface;

/**
 * The string data type.
 *
 * The plain value of a string is a regular PHP string. For setting the value
 * any PHP variable that casts to a string may be passed.
 */
#[DataType(
  id: "string",
  label: new TranslatableMarkup("String")
)]
class StringData extends PrimitiveBase implements StringInterface {

  /**
   * {@inheritdoc}
   */
  #[JsonSchema(['type' => 'string'])]
  public function getCastedValue() {
    return $this->getString();
  }

}
