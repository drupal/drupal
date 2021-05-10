<?php

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\ExceptionHandler as BaseExceptionHandler;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\StatementInterface;

/**
 * MySql database exception handler class.
 */
class ExceptionHandler extends BaseExceptionHandler {

  /**
   * {@inheritdoc}
   */
  public function handleExecutionException(\Exception $exception, StatementInterface $statement, array $arguments = [], array $options = []): void {
    if (array_key_exists('throw_exception', $options)) {
      @trigger_error('Passing a \'throw_exception\' option to ' . __METHOD__ . ' is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Always catch exceptions. See https://www.drupal.org/node/3201187', E_USER_DEPRECATED);
      if (!($options['throw_exception'])) {
        return;
      }
    }

    if ($exception instanceof \PDOException) {
      // Wrap the exception in another exception, because PHP does not allow
      // overriding Exception::getMessage(). Its message is the extra database
      // debug information.
      $code = is_int($exception->getCode()) ? $exception->getCode() : 0;

      // If a max_allowed_packet error occurs the message length is truncated.
      // This should prevent the error from recurring if the exception is logged
      // to the database using dblog or the like.
      if (($exception->errorInfo[1] ?? NULL) === 1153) {
        $message = Unicode::truncateBytes($exception->getMessage(), Connection::MIN_MAX_ALLOWED_PACKET);
        throw new DatabaseExceptionWrapper($message, $code, $exception);
      }

      $message = $exception->getMessage() . ": " . $statement->getQueryString() . "; " . print_r($arguments, TRUE);

      // SQLSTATE 23xxx errors indicate an integrity constraint violation. Also,
      // in case of attempted INSERT of a record with an undefined column and no
      // default value indicated in schema, MySql returns a 1364 error code.
      if (
        substr($exception->getCode(), -6, -3) == '23' ||
        ($exception->errorInfo[1] ?? NULL) === 1364
      ) {
        throw new IntegrityConstraintViolationException($message, $code, $exception);
      }

      throw new DatabaseExceptionWrapper($message, 0, $exception);
    }

    throw $exception;
  }

}
