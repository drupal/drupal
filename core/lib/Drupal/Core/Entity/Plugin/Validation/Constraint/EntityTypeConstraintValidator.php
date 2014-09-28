<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityTypeConstraintValidator.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityType constraint.
 */
class EntityTypeConstraintValidator extends ConstraintValidator {

  /**
   * Implements \Symfony\Component\Validator\ConstraintValidatorInterface::validate().
   */
  public function validate($entity_adapter, Constraint $constraint) {
    if (!isset($entity_adapter)) {
      return;
    }

    // @todo The $entity_adapter parameter passed to this function should always
    //   be a typed data object, but due to a bug, the unwrapped entity is
    //   passed for the computed entity property of entity reference fields.
    //   Remove this after fixing that in https://www.drupal.org/node/2346373.
    if (!$entity_adapter instanceof TypedDataInterface) {
      $entity = $entity_adapter;
    }
    else {
      $entity = $entity_adapter->getValue();
    }

    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    if ($entity->getEntityTypeId() != $constraint->type) {
      $this->context->addViolation($constraint->message, array('%type' => $constraint->type));
    }
  }

}
