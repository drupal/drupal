<?php

namespace Drupal\Core\Database;

/**
 * Wrapper base class for exceptions that provide additional debug information.
 *
 * Driver handlers wrap client exceptions, such as \PDOException or
 * \mysqli_sql_exception, and attach SQL execution information. Exceptions
 * raised by Drupal schema guards may have no previous throwable or information.
 */
class DatabaseExceptionWrapper extends \RuntimeException implements DatabaseException {

  public function __construct(
    string $message = "",
    int $code = 0,
    ?\Throwable $previous = NULL,
    public readonly ?SqlExecutionInfo $info = NULL,
  ) {
    parent::__construct($message, $code, $previous);
  }

}
