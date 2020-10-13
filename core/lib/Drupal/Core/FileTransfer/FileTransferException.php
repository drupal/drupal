<?php

namespace Drupal\Core\FileTransfer;

/**
 * Exception thrown for file transfer errors.
 */
class FileTransferException extends \RuntimeException {

  /**
   * Arguments to be used in this exception.
   *
   * @var array
   */
  public $arguments;

  /**
   * Constructs a FileTransferException object.
   *
   * @param string $message
   *   Exception message.
   * @param int $code
   *   Exception code.
   * @param array $arguments
   *   Arguments to be used in this exception.
   */
  public function __construct($message, $code = 0, $arguments = []) {
    parent::__construct($message, $code);
    $this->arguments = $arguments;
  }

}
