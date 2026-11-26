<?php

namespace Drupal\pgsql\Driver\Database\pgsql;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Exception\SchemaTableAlreadyExistsException;
use Drupal\Core\Database\Exception\SchemaTableDoesNotExistException;
use Drupal\Core\Database\ExceptionHandler as BaseExceptionHandler;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\SqlExecutionInfo;

/**
 * PostgreSQL database exception handler.
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
    if (str_starts_with((string) $sqlState, '23')) {
      throw new IntegrityConstraintViolationException($message, $code, $exception, $info);
    }
    match ($sqlState) {
      '42P01' => throw new SchemaTableDoesNotExistException($message, $code, $exception, $info),
      '42P07' => throw new SchemaTableAlreadyExistsException($message, $code, $exception, $info),
      default => throw new DatabaseExceptionWrapper($message, 0, $exception, $info),
    };
  }

}
