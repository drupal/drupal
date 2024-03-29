<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityHasField constraint.
 */
class EntityHasFieldConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint): void {
    if (!isset($entity)) {
      return;
    }

    /** @var \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityHasFieldConstraint $constraint */
    if (!($entity instanceof FieldableEntityInterface)) {
      $this->context->addViolation($constraint->notFieldableMessage);
      return;
    }

    if (!$entity->hasField($constraint->field_name)) {
      $this->context->addViolation($constraint->message, [
        '%field_name' => $constraint->field_name,
      ]);
    }
  }

}
