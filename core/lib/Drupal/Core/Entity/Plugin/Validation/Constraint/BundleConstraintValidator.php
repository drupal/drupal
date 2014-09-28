<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\BundleConstraintValidator.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Bundle constraint.
 */
class BundleConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
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

    if (!in_array($entity->bundle(), $constraint->getBundleOption())) {
      $this->context->addViolation($constraint->message, array('%bundle' => implode(', ', $constraint->getBundleOption())));
    }
  }

}
