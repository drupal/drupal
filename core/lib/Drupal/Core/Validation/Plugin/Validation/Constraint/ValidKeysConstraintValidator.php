<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the ValidKeys constraint.
 */
class ValidKeysConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint) {
    assert($constraint instanceof ValidKeysConstraint);

    if (!is_array($value)) {
      throw new UnexpectedTypeException($value, 'array');
    }

    // Indexed arrays are invalid by definition. array_is_list() returns TRUE
    // for empty arrays, so only do this check if $value is not empty.
    if ($value && array_is_list($value)) {
      $this->context->addViolation($constraint->indexedArrayMessage);
      return;
    }

    $invalid_keys = array_diff(
      array_keys($value),
      $constraint->getAllowedKeys($this->context)
    );
    foreach ($invalid_keys as $key) {
      $this->context->addViolation($constraint->invalidKeyMessage, ['@key' => $key]);
    }
  }

}
