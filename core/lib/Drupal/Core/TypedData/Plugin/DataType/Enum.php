<?php

declare(strict_types=1);

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\PrimitiveBase;

/**
 * The "enum" data type.
 *
 * @ingroup typed_data
 */
#[DataType(
  id: "enum",
  label: new TranslatableMarkup("Enum"),
)]
class Enum extends PrimitiveBase {

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();
    $constraints[] = $this->getTypedDataManager()
      ->getValidationConstraintManager()
      ->create('Enum', []);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->getValue();
  }

}
