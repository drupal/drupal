<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Provides a validator for the 'FileDescriptionRequired' constraint.
 */
class FileDescriptionRequiredValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($field_item, Constraint $constraint): void {
    /** @var \Drupal\file\Plugin\Field\FieldType\FileItem $field_item */
    if ($field_item->isEmpty()) {
      return;
    }

    $field_definition = $field_item->getFieldDefinition();
    if ($field_definition->getSetting('description_field_required')) {
      if (empty($field_item->getValue()['description'])) {
        $this->context->addViolation($constraint->message, [
          '@name' => $field_definition->getLabel(),
        ]);
      }
    }
  }

}
