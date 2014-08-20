<?php
/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Validation\Constraint\FieldWidgetConstraintValidator.
 */

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the FieldWidgetConstraint constraint.
 */
class FieldWidgetConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($field_item, Constraint $constraint) {
    $this->context->addViolation($constraint->message);
  }

}
