<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityTypeConstraintValidator.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityType constraint.
 */
class EntityTypeConstraintValidator extends ConstraintValidator {

  /**
   * Implements \Symfony\Component\Validator\ConstraintValidatorInterface::validate().
   */
  public function validate($entity, Constraint $constraint) {

    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    if (!empty($entity) && $entity->getEntityTypeId() != $constraint->type) {
      $this->context->addViolation($constraint->message, array('%type' => $constraint->type));
    }
  }
}
