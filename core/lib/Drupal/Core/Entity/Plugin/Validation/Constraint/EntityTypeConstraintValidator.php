<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityType constraint.
 */
class EntityTypeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint): void {
    if (!isset($entity)) {
      return;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity->getEntityTypeId() != $constraint->type) {
      $this->context->addViolation($constraint->message, ['%type' => $constraint->type]);
    }
  }

}
