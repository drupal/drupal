<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraintValidator.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

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
      /** @var $entity \Drupal\Core\Entity\EntityInterface */
      $entity = $this->context->getMetadata()->getTypedData()->getEntity();
      if (!$entity->isNew()) {
        $saved_entity = \Drupal::entityManager()->getStorageController($entity->getEntityTypeId())->loadUnchanged($entity->id());

        if ($saved_entity && ($saved_entity instanceof EntityChangedInterface) && ($saved_entity->getChangedTime() > $value)) {
          $this->context->addViolation($constraint->message);
        }
      }
    }
  }

}
