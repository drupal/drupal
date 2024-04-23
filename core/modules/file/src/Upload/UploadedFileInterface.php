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
