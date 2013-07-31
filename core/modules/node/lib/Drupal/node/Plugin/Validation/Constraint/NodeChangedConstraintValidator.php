<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Validation\Constraint\NodeChangedConstraintValidator.
 */

namespace Drupal\node\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the NodeChanged constraint.
 */
class NodeChangedConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (isset($value)) {
      // We are on the field item level, so we need to go two levels up for the
      // node object.
      $node = $this->context->getMetadata()->getTypedData()->getParent()->getParent();
      $id = $node->id();
      $language = $node->language();
      if ($id && (node_last_changed($id, $language->id) > $value)) {
        $this->context->addViolation($constraint->message);
      }
    }
  }
}
