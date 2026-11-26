<?php

namespace Drupal\mysql\Driver\Database\mysql;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\ExceptionHandler as BaseExceptionHandler;
use Drupal\Core\Database\Exception\SchemaPrimaryKeyMustBeDroppedException;
use Drupal\Core\Database\Exception\SchemaTableAlreadyExistsException;
use Drupal\Core\Database\Exception\SchemaTableColumnSizeTooLargeException;
use Drupal\Core\Database\Exception\SchemaTableDoesNotExistException;
use Drupal\Core\Database\Exception\SchemaTableKeyTooLargeException;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\SqlExecutionInfo;

/**
 * MySql database exception handler class.
 */
class ExceptionHandler extends BaseExceptionHandler {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   * @throws \Drupal\Core\Database\Exception\SchemaPrimaryKeyMustBeDroppedException
   * @throws \Drupal\Core\Database\Exception\SchemaTableAlreadyExistsException
   * @throws \Drupal\Core\Database\Exception\SchemaTableColumnSizeTooLargeException
   * @throws \Drupal\Core\Database\Exception\SchemaTableDoesNotExistException
   * @throws \Drupal\Core\Database\Exception\SchemaTableKeyTooLargeException
   */
  protected function rethrowNormalizedException(
    \Exception $exception,
    int|string $sqlState,
    ?int $errorCode,
    string $queryString,
    array $arguments,
    array $options = [],
  ): never {

    // SQLState could be 'HY000' which cannot be used as a $code argument for
    // exceptions. PDOException is contravariant in this case, but since we are
    // re-throwing an exception that inherits from \Exception, we need to
    // convert the code to an integer.
    // @see https://www.php.net/manual/en/class.exception.php
    // @see https://www.php.net/manual/en/class.pdoexception.php
    $code = (int) $sqlState;

    // If a max_allowed_packet error occurs the message length is truncated.
    // This should prevent the error from recurring if the exception is logged
    // to the database using dblog or the like.
    if ($errorCode === 1153) {
      $message = Unicode::truncateBytes($exception->getMessage(), Connection::MIN_MAX_ALLOWED_PACKET);
      // By definition, the statement and its arguments exceeded
      // max_allowed_packet. They are not retained, since exporting them would
      // re-expand what the truncation above exists to keep out of the output.
      $info = new SqlExecutionInfo(Unicode::truncateBytes($queryString, Connection::MIN_MAX_ALLOWED_PACKET), [], $options);
      // For the same reason the statement is not appended to the message here,
      // while it is for every other error below.
      throw new DatabaseExceptionWrapper($message, $code, $exception, $info);
    }

    $info = new SqlExecutionInfo($queryString, $arguments, $options);

    $message = $info->appendToMessage($exception->getMessage());

    // SQLSTATE 23xxx errors indicate an integrity constraint violation. Also,
    // in case of attempted INSERT of a record with an undefined column and no
    // default value indicated in schema, MySql returns a 1364 error code.
    if (substr($sqlState, -6, -3) == '23' || $errorCode === 1364) {
      throw new IntegrityConstraintViolationException($message, $code, $exception, $info);
    }

    match ($sqlState) {
      'HY000' => match ($errorCode) {
        4111 => throw new SchemaPrimaryKeyMustBeDroppedException($message, 0, $exception, $info),
        default => throw new DatabaseExceptionWrapper($message, 0, $exception, $info),
      },
      '42000', '42S01', '42S02' => match ($errorCode) {
        1050 => throw new SchemaTableAlreadyExistsException($message, $code, $exception, $info),
        1051, 1146 => throw new SchemaTableDoesNotExistException($message, $code, $exception, $info),
        1071 => throw new SchemaTableKeyTooLargeException($message, $code, $exception, $info),
        1074 => throw new SchemaTableColumnSizeTooLargeException($message, $code, $exception, $info),
        default => throw new DatabaseExceptionWrapper($message, 0, $exception, $info),
      },
      default => throw new DatabaseExceptionWrapper($message, 0, $exception, $info),
    };
  }

}
