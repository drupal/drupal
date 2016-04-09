<?php

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
        // A change to any other translation must add a violation to the current
        // translation because there might be untranslatable shared fields.
        if ($saved_entity && $saved_entity->getChangedTimeAcrossTranslations() > $entity->getChangedTimeAcrossTranslations()) {
          $this->context->addViolation($constraint->message);
        }
      }
    }
  }

}
