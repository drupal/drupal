<?php

namespace Drupal\file\Validation;

use Drupal\file\FileInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Provides a file validator that supports a list of validations.
 */
interface FileValidatorInterface {

  /**
   * Validates a File with a list of validators.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file to validate.
   * @param array $validators
   *   An associative array of validators with:
   *   - key: the plugin ID of the file validation constraint.
   *   - value: an associative array of options to pass to the constraint.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   The violations list.
   */
  public function validate(FileInterface $file, array $validators): ConstraintViolationListInterface;

}
