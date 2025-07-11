<?php

declare(strict_types=1);

namespace Drupal\package_manager\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the IsExecutable constraint.
 */
final class IsExecutableConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    assert($constraint instanceof IsExecutableConstraint);

    if ($value === NULL || is_executable($value)) {
      return;
    }
    $this->context->addViolation($constraint->message, ['@path' => $value]);
  }

}
