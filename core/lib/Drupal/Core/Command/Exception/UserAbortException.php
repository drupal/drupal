<?php

declare(strict_types=1);

namespace Drupal\Core\Command\Exception;

/**
 * Thrown when a user declines an interactive console confirmation prompt.
 */
class UserAbortException extends \RuntimeException {

  public function __construct(string $message = 'Operation cancelled by user.', int $code = 0, ?\Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }

}
