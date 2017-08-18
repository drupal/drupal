<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Constraints\UuidValidator;

/**
 * Validates a UUID.
 *
 * @Constraint(
 *   id = "Uuid",
 *   label = @Translation("Universally Unique Identifier", context = "Validation"),
 * )
 */
class UuidConstraint extends Uuid {

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return UuidValidator::class;
  }

}
