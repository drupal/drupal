<?php

namespace Drupal\file\Upload;

/**
 * Provides an exception for upload validation errors.
 */
class FileValidationException extends \RuntimeException {

  /**
   * The validation errors.
   *
   * @var array
   */
  protected $errors;

  /**
   * The file name.
   *
   * @var string
   */
  protected $fileName;

  /**
   * Constructs a new FileValidationException.
   *
   * @param string $message
   *   The message.
   * @param string $file_name
   *   The file name.
   * @param array $errors
   *   The validation errors.
   */
  public function __construct(string $message, string $file_name, array $errors) {
    parent::__construct($message, 0, NULL);
    $this->fileName = $file_name;
    $this->errors = $errors;
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
