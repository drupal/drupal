<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Bytes;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks that a file referenced in a file field is valid.
 */
class FileValidationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // Get the file to execute validators.
    $target = $value->get('entity')->getTarget();
    if (!$target) {
      return;
    }

    $file = $target->getValue();
    // Get the validators.
    $validators = $value->getUploadValidators();

    // Always respect the configured maximum file size.
    $field_settings = $value->getFieldDefinition()->getSettings();
    if (array_key_exists('max_filesize', $field_settings)) {
      $validators['file_validate_size'] = [Bytes::toNumber($field_settings['max_filesize'])];
    }
    else {
      // Do not validate the file size if it is not set explicitly.
      unset($validators['file_validate_size']);
    }

    // Checks that a file meets the criteria specified by the validators.
    if ($errors = file_validate($file, $validators)) {
      foreach ($errors as $error) {
        $this->context->addViolation($error);
      }
    }
  }

}
