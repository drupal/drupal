<?php

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates referenced entities.
 */
class TestValidatedReferenceConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!isset($items)) {
      return;
    }
    foreach ($items as $item) {
      $violations = $item->entity->validate();
      foreach ($violations as $violation) {
        // Add the reason for the validation failure to the current context.
        $this->context->buildViolation($constraint->message)->addViolation();
      }
    }
  }

}
