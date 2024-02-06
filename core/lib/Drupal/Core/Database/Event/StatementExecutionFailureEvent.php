<?php

namespace Drupal\Core\Database\Event;

/**
 * Represents the failure of a statement execution as an event.
 */
class StatementExecutionFailureEvent extends StatementExecutionEndEvent {

  /**
   * Constructor.
   *
   * See 'Customizing database settings' in settings.php for an explanation of
   * the $key and $target connection values.
   *
   * @param int $statementObjectId
   *   The id of the StatementInterface object as returned by spl_object_id().
   * @param string $key
   *   The database connection key.
   * @param string $target
   *   The database connection target.
   * @param string $queryString
   *   The SQL statement string being executed, with placeholders.
   * @param array $args
   *   The placeholders' replacement values.
   * @param array $caller
   *   A normalized debug backtrace entry representing the last non-db method
   *   called.
   * @param float $startTime
   *   The time of the statement execution start.
   * @param string $exceptionClass
   *   The class of the exception that was thrown.
   * @param int|string $exceptionCode
   *   The code of the exception that was thrown.
   * @param string $exceptionMessage
   *   The message of the exception that was thrown.
   */
  public function __construct(
    int $statementObjectId,
    string $key,
    string $target,
    string $queryString,
    array $args,
    array $caller,
    float $startTime,
    public readonly string $exceptionClass,
    public readonly int|string $exceptionCode,
    public readonly string $exceptionMessage,
  ) {
    parent::__construct($statementObjectId, $key, $target, $queryString, $args, $caller, $startTime);
  }

}
