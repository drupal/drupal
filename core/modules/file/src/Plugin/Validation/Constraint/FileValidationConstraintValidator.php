<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Validation\Constraint\FileValidationConstraintValidator.
 */

namespace Drupal\file\Plugin\Validation\Constraint;

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
    $file = $value->get('entity')->getTarget()->getValue();
    // Get the validators.
    $validators = $value->getUploadValidators();
    // Checks that a file meets the criteria specified by the validators.
    if ($errors = file_validate($file, $validators)) {
      foreach ($errors as $error) {
        $this->context->addViolation($error);
      }
    }
  }

}
