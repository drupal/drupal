<?php

/**
 * @file
 * Definition of Drupal\Core\FileTransfer\FileTransferException.
 */

namespace Drupal\Core\FileTransfer;

/**
 * FileTransferException class.
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
  function __construct($message, $code = 0, $arguments = array()) {
    parent::__construct($message, $code);
    $this->arguments = $arguments;
  }
}
