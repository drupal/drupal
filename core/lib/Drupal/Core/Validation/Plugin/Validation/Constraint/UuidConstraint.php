<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Constraints\UuidValidator;

/**
 * Validates a UUID.
 */
#[Constraint(
  id: 'Uuid',
  label: new TranslatableMarkup('Universally Unique Identifier', [], ['context' => 'Validation'])
)]
class UuidConstraint extends Uuid {

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return UuidValidator::class;
  }

}
