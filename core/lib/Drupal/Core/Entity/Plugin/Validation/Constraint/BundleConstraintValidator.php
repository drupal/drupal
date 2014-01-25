<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\BundleConstraintValidator.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Bundle constraint.
 */
class BundleConstraintValidator extends ConstraintValidator {

  /**
   * Implements \Symfony\Component\Validator\ConstraintValidatorInterface::validate().
   */
  public function validate($entity, Constraint $constraint) {
    if (!empty($entity) && !in_array($entity->bundle(), $constraint->getBundleOption())) {
      $this->context->addViolation($constraint->message, array('%bundle' => implode(', ', $constraint->getBundleOption())));
    }
  }
}
