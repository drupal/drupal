<?php

/**
 * @file
 * Contains PHP5 version of the \AssertionError class.
 */

/**
 * Emulates PHP 7 AssertionError as closely as possible.
 *
 * This class is declared in the global namespace. It will only be included by
 * \Drupal\Component\Assertion\Handle for PHP5 since this class exists natively
 * in PHP 7. Note that in PHP 7 it extends from Error, not Exception, but that
 * isn't possible for PHP 5 - all exceptions must extend from exception.
 */
class AssertionError extends Exception {

  /**
   * {@inheritdoc}
   */
  public function __construct($message = '', $code = 0, Exception $previous = NULL, $file = '', $line = 0) {
    parent::__construct($message, $code, $previous);
    // Preserve the filename and line number of the assertion failure.
    $this->file = $file;
    $this->line = $line;
  }

}
