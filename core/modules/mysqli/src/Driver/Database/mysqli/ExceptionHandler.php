<?php

declare(strict_types=1);

namespace Drupal\mysqli\Driver\Database\mysqli;

use Drupal\Core\Database\StatementInterface;
use Drupal\mysql\Driver\Database\mysql\ExceptionHandler as BaseMySqlExceptionHandler;

/**
 * MySQLi database exception handler class.
 */
class ExceptionHandler extends BaseMySqlExceptionHandler {

  /**
   * {@inheritdoc}
   */
  public function handleExecutionException(\Exception $exception, StatementInterface $statement, array $arguments = [], array $options = []): void {
    // Close the client statement to release handles.
    if ($statement->hasClientStatement()) {
      $statement->getClientStatement()->close();
    }

    if (!($exception instanceof \mysqli_sql_exception)) {
      throw $exception;
    }
    $this->rethrowNormalizedException($exception, $exception->getSqlState(), $exception->getCode(), $statement->getQueryString(), $arguments);
  }

}
