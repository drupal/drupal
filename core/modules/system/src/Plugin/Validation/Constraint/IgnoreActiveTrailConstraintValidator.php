<?php

declare(strict_types=1);

namespace Drupal\system\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for the IgnoreActiveTrail constraint.
 */
class IgnoreActiveTrailConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof IgnoreActiveTrailConstraint);
    if (!is_array($value)) {
      throw new UnexpectedTypeException($value, 'array');
    }
    if (!empty($value['ignore_active_trail'])
      && ((isset($value['level']) && $value['level'] > 1) || (empty($value['expand_all_items']) && $value['depth'] != 1))) {
      $this->context->addViolation($constraint->message);
    }
  }

}
