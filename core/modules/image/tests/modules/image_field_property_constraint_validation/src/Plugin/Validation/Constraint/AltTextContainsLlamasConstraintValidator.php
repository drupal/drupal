<?php

declare(strict_types=1);

namespace Drupal\image_field_property_constraint_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the alt text contains llamas.
 */
final class AltTextContainsLlamasConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (is_string($value) && !str_contains(strtolower($value), 'llamas')) {
      $this->context->buildViolation($constraint->message)
        ->setInvalidValue($value)
        ->addViolation();
    }
  }

}
