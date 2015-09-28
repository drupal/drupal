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
    /** @var \Drupal\Core\Field\FieldItemInterface $value */
    /** @var ValidReferenceConstraint $constraint */
    if (!isset($value)) {
      return;
    }
    // We don't use a regular NotNull constraint for the target_id property as
    // a NULL value is valid if the entity property contains an unsaved entity.
    // @see \Drupal\Core\TypedData\DataReferenceTargetDefinition::getConstraints
    if (!$value->isEmpty() && $value->target_id === NULL && !$value->entity->isNew()) {
      $this->context->addViolation($constraint->nullMessage);
      return;
    }
    $id = $value->get('target_id')->getValue();
    // '0' or NULL are considered valid empty references.
    if (empty($id)) {
      return;
    }
    $referenced_entity = $value->get('entity')->getValue();
    if (!$referenced_entity) {
      $type = $value->getFieldDefinition()->getSetting('target_type');
      $this->context->addViolation($constraint->message, array('%type' => $type, '%id' => $id));
    }
  }
}
