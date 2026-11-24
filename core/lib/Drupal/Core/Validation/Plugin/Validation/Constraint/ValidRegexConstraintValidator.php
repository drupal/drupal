<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validator for Valid Regex constraint.
 */
class ValidRegexConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof ValidRegexConstraint) {
      throw new UnexpectedTypeException($constraint, ValidRegexConstraint::class);
    }
    if ($value === NULL) {
      return;
    }

    if (!is_string($value) && !$value instanceof \Stringable) {
      throw new UnexpectedValueException($value, 'string|\Stringable');
    }

    $value = (string) $value;
    $valid_regex = @preg_match($value, "") !== FALSE;
    if (!$valid_regex || preg_last_error() !== PREG_NO_ERROR) {
      // The violation builder uses preg_match and will clear the error, so
      // capture it now.
      $message = preg_last_error_msg();
      $this->context->buildViolation($constraint->message)
        ->setParameter('@regex', $value)
        ->setParameter('@message', $message)
        ->addViolation();
    }
  }

}
