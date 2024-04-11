<?php

namespace Drupal\file\Upload;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Provides an interface for uploaded files.
 */
interface UploadedFileInterface {

  /**
   * Returns the original file name.
   *
   * The file name is extracted from the request that uploaded the file and as
   * such should not be considered a safe value.
   *
   * @return string
   *   The original file name supplied by the client.
   */
  public function getClientOriginalName(): string;

  /**
   * Returns whether the file was uploaded successfully.
   *
   * @return bool
   *   TRUE if the file has been uploaded with HTTP and no error occurred.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\file\Validation\UploadedFileValidatorInterface::validate()
   *   instead.
   * @see https://www.drupal.org/node/3375456
   */
  public function isValid(): bool;

  /**
   * Returns an informative upload error message.
   *
   * @return string
   *   The error message regarding a failed upload.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\file\Validation\UploadedFileValidatorInterface::validate()
   *   instead.
   *
   * @see https://www.drupal.org/node/3375456
   */
  public function getErrorMessage(): string;

  /**
   * Returns the upload error code.
   *
   * If the upload was successful, the constant UPLOAD_ERR_OK is returned.
   * Otherwise, one of the other UPLOAD_ERR_XXX constants is returned.
   *
   * @return int
   *   The upload error code.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\file\Validation\UploadedFileValidatorInterface::validate()
   *   instead.
   *
   * @see https://www.drupal.org/node/3375456
   */
  public function getError(): int;

  /**
   * Gets file size.
   *
   * @return int
   *   The filesize in bytes.
   *
   * @see https://www.php.net/manual/en/splfileinfo.getsize.php
   */
  public function getSize(): int;

  /**
   * Gets the absolute path to the file.
   *
   * @return string|false
   *   The path to the file, or FALSE if the file does not exist.
   *
   * @see https://php.net/manual/en/splfileinfo.getrealpath.php
   */
  public function getRealPath();

  /**
   * Gets the path to the file.
   *
   * @return string
   *   The path to the file.
   *
   * @see https://php.net/manual/en/splfileinfo.getpathname.php
   */
  public function getPathname(): string;

  /**
   * Gets the filename.
   *
   * @return string
   *   The filename.
   *
   * @see https://php.net/manual/en/splfileinfo.getfilename.php
   */
  public function getFilename(): string;

  /**
   * Validates the uploaded file information.
   *
   * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
   *   A validator object.
   * @param array $options
   *   Options to pass to a constraint.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   The list of violations.
   */
  public function validate(ValidatorInterface $validator, array $options = []): ConstraintViolationListInterface;

}
