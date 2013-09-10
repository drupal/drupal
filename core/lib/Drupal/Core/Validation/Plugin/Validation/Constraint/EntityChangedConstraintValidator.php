<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\EntityChangedConstraintValidator.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityChangedInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityChanged constraint.
 */
class EntityChangedConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (isset($value)) {
      // We are on the field item level, so we need to go two levels up for the
      // entity object.
      $entity = $this->context->getMetadata()->getTypedData()->getParent()->getParent();
      if (!$entity->isNew()) {
        $saved_entity = \Drupal::entityManager()->getStorageController($entity->entityType())->loadUnchanged($entity->id());

        if ($saved_entity && ($saved_entity instanceof EntityChangedInterface) && ($saved_entity->getChangedTime() > $value)) {
          $this->context->addViolation($constraint->message);
        }
      }
    }
  }

}
