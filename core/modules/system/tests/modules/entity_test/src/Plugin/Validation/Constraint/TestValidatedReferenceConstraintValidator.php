<?php

declare(strict_types=1);

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
  public function validate($items, Constraint $constraint): void {
    if (!isset($items)) {
      return;
    }
    foreach ($items as $item) {
      $violations = $item->entity->validate();
      if ($violations->count()) {
        // Add the reason for the validation failure to the current context.
        $this->context->buildViolation($constraint->message)->addViolation();
      }
    }
  }

}
