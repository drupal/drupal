<?php

namespace Drupal\sqlite\Driver\Database\sqlite;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Exception\SchemaTableAlreadyExistsException;
use Drupal\Core\Database\Exception\SchemaTableDoesNotExistException;
use Drupal\Core\Database\ExceptionHandler as BaseExceptionHandler;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\SqlExecutionInfo;

/**
 * SQLite database exception handler class.
 */
class ExceptionHandler extends BaseExceptionHandler {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Database\Exception\SchemaTableAlreadyExistsException
   * @throws \Drupal\Core\Database\Exception\SchemaTableDoesNotExistException
   */
  protected function rethrowNormalizedException(
    \Exception $exception,
    int|string $sqlState,
    ?int $errorCode,
    string $queryString,
    array $arguments,
    array $options = [],
  ): never {
    $info = new SqlExecutionInfo($queryString, $arguments, $options);
    $message = $info->appendToMessage($exception->getMessage());
    $code = (int) $sqlState;

    // Match all SQLSTATE 23xxx errors.
    if (str_starts_with((string) $sqlState, '23')) {
      throw new IntegrityConstraintViolationException($message, $code, $exception, $info);
    }

    // SQLite reports table errors with SQLITE_ERROR and specific messages.
    if ($errorCode === 1) {
      $detail = $exception->errorInfo[2] ?? '';
      if (str_starts_with($detail, 'no such table: ')) {
        throw new SchemaTableDoesNotExistException($message, $code, $exception, $info);
      }
      if (preg_match('/^table .+ already exists$/D', $detail)) {
        throw new SchemaTableAlreadyExistsException($message, $code, $exception, $info);
      }
    }

    throw new DatabaseExceptionWrapper($message, 0, $exception, $info);
  }

}
