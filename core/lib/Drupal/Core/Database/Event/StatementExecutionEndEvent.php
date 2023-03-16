<?php

namespace Drupal\Core\Database\Event;

/**
 * Represents the end of a statement execution as an event.
 */
class StatementExecutionEndEvent extends DatabaseEvent {

  /**
   * Constructs a StatementExecutionEndEvent object.
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
   */
  public function __construct(
    public readonly int $statementObjectId,
    public readonly string $key,
    public readonly string $target,
    public readonly string $queryString,
    public readonly array $args,
    public readonly array $caller,
    public readonly float $startTime,
  ) {
    parent::__construct();
  }

  /**
   * Gets the query execution elapsed time.
   *
   * @return float
   *   The elapsed time.
   */
  public function getElapsedTime(): float {
    return $this->time - $this->startTime;
  }

}
