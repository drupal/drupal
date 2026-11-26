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
   * @return never
   *   This method only throws exceptions.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   */
  public function handleStatementException(\Exception $exception, string $sql, array $options = []): never {
    if (!$exception instanceof \PDOException) {
      throw $exception;
    }
    $this->rethrowNormalizedException($exception, $exception->getCode(), $exception->errorInfo[1] ?? NULL, $sql, [], $options);
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
   * @return never
   *   This method only throws exceptions.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   */
  public function handleExecutionException(\Exception $exception, StatementInterface $statement, array $arguments = [], array $options = []): never {
    if (!$exception instanceof \PDOException) {
      throw $exception;
    }
    $this->rethrowNormalizedException($exception, $exception->getCode(), $exception->errorInfo[1] ?? NULL, $statement->getQueryString(), $arguments, $options);
  }

  /**
   * Rethrows a native database exception as a Drupal database exception.
   *
   * Wrap the exception in another exception, because PHP does not allow
   * overriding Exception::getMessage(). Its message is the extra database
   * debug information.
   *
   * Database drivers override this method to convert their native error codes
   * to more specific exceptions.
   *
   * @param \Exception $exception
   *   The exception to be handled.
   * @param int|string $sqlState
   *   The SQLSTATE error condition.
   * @param int|null $errorCode
   *   The native error code of the database, if available.
   * @param string $queryString
   *   The SQL query string.
   * @param array $arguments
   *   An array of arguments for the prepared statement.
   * @param array $options
   *   An associative array of options to control how the database operation is
   *   run.
   *
   * @return never
   *   This method only throws exceptions.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
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
    // Match all SQLSTATE 23xxx errors.
    if (str_starts_with((string) $sqlState, '23')) {
      throw new IntegrityConstraintViolationException($message, (int) $sqlState, $exception, $info);
    }
    throw new DatabaseExceptionWrapper($message, 0, $exception, $info);
  }

}
