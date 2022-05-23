<?php

namespace Drupal\Core\Database;

/**
 * Base Database exception handler class.
 *
 * This class handles exceptions thrown by the database layer of a PDO-based
 * database connection. Database driver implementations can provide an
 * alternative implementation to support special handling required by that
 * database.
 */
class ExceptionHandler {

  /**
   * Handles exceptions thrown during the preparation of statement objects.
   *
   * @param \Exception $exception
   *   The exception to be handled.
   * @param string $sql
   *   The SQL statement that was requested to be prepared.
   * @param array $options
   *   An associative array of options to control how the database operation is
   *   run.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   */
  public function handleStatementException(\Exception $exception, string $sql, array $options = []): void {
    if ($exception instanceof \PDOException) {
      // Wrap the exception in another exception, because PHP does not allow
      // overriding Exception::getMessage(). Its message is the extra database
      // debug information.
      $message = $exception->getMessage() . ": " . $sql . "; ";
      throw new DatabaseExceptionWrapper($message, 0, $exception);
    }

    throw $exception;
  }

  /**
   * Handles exceptions thrown during execution of statement objects.
   *
   * @param \Exception $exception
   *   The exception to be handled.
   * @param \Drupal\Core\Database\StatementInterface $statement
   *   The statement object requested to be executed.
   * @param array $arguments
   *   An array of arguments for the prepared statement.
   * @param array $options
   *   An associative array of options to control how the database operation is
   *   run.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   */
  public function handleExecutionException(\Exception $exception, StatementInterface $statement, array $arguments = [], array $options = []): void {
    if ($exception instanceof \PDOException) {
      // Wrap the exception in another exception, because PHP does not allow
      // overriding Exception::getMessage(). Its message is the extra database
      // debug information.
      $message = $exception->getMessage() . ": " . $statement->getQueryString() . "; " . print_r($arguments, TRUE);
      // Match all SQLSTATE 23xxx errors.
      if (substr($exception->getCode(), -6, -3) == '23') {
        throw new IntegrityConstraintViolationException($message, $exception->getCode(), $exception);
      }
      throw new DatabaseExceptionWrapper($message, 0, $exception);
    }

    throw $exception;
  }

}
