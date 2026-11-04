<?php

namespace Drupal\file\Upload;

/**
 * Provides an exception for upload validation errors.
 */
class FileValidationException extends \RuntimeException {

  /**
   * Constructs a new FileValidationException.
   *
   * @param string $message
   *   The message.
   * @param string $fileName
   *   The file name.
   * @param array $errors
   *   The validation errors.
   */
  public function __construct(
    string $message,
    protected string $fileName,
    protected array $errors,
  ) {
    parent::__construct($message, 0, NULL);
  }

  /**
   * Gets the file name.
   *
   * @return string
   *   The file name.
   */
  public function getFilename(): string {
    return $this->fileName;
  }

  /**
   * Gets the errors.
   *
   * @return array
   *   The errors.
   */
  public function getErrors(): array {
    return $this->errors;
  }

}
