<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraintValidator.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidReferenceConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!isset($value)) {
      return;
    }
    $id = $value->get('target_id')->getValue();
    // '0' or NULL are considered valid empty references.
    if (empty($id)) {
      return;
    }
    $referenced_entity = $value->get('entity')->getTarget();
    if (!$referenced_entity) {
      $type = $value->getFieldDefinition()->getSetting('target_type');
      $this->context->addViolation($constraint->message, array('%type' => $type, '%id' => $id));
    }
  }
}
