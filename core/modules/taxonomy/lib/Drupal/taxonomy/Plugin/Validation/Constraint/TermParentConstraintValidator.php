<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\Validation\Constraint\TermParentConstraintValidator.
 */

namespace Drupal\taxonomy\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TermParent constraint.
 */
class TermParentConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($field_item, Constraint $constraint) {
    if ($field_item) {
      $parent_term_id = $field_item->value;
      // If a non-0 parent term id is specified, ensure it corresponds to a real
      // term in the same vocabulary.
      if ($parent_term_id && !\Drupal::entityManager()->getStorageController('taxonomy_term')->loadByProperties(array('tid' => $parent_term_id, 'vid' => $field_item->getEntity()->vid->value))) {
        $this->context->addViolation($constraint->message, array('%id' => $parent_term_id));
      }
    }
  }
}
