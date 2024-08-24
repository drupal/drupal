<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for the EntityTestEntityLevel constraint.
 */
class EntityTestEntityLevelValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if ($value->name->value === 'entity-level-violation') {
      $this->context->buildViolation($constraint->message)
        ->addViolation();
    }
    if ($value->name->value === 'entity-level-violation-with-path') {
      $this->context->buildViolation($constraint->message)
        ->atPath('test.form.element')
        ->addViolation();
    }
  }

}
