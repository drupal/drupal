<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraintValidator.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityChanged constraint.
 */
class EntityChangedConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (isset($entity)) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if (!$entity->isNew()) {
        $saved_entity = \Drupal::entityManager()->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());

        if ($saved_entity && $saved_entity->getChangedTime() > $entity->getChangedTime()) {
          $this->context->addViolation($constraint->message);
        }
      }
    }
  }

}
