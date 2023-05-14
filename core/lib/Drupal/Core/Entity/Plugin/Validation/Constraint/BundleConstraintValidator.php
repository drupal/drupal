<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Bundle constraint.
 */
class BundleConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    if (!in_array($entity->bundle(), $constraint->getBundleOption())) {
      $this->context->addViolation($constraint->message, ['%bundle' => implode(', ', $constraint->getBundleOption())]);
    }
  }

}
