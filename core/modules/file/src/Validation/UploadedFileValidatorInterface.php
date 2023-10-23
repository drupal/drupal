<?php

declare(strict_types=1);

namespace Drupal\file\Validation;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Validator for uploaded files.
 */
interface UploadedFileValidatorInterface {

  /**
   * Validates an uploaded file.
   *
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile
   *   The uploaded file.
   * @param array $options
   *   An array of options accepted by
   *   \Drupal\file\Validation\Constraint\UploadedFileConstraint.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   The constraint violation list.
   */
  public function validate(UploadedFile $uploadedFile, array $options = []): ConstraintViolationListInterface;

}
