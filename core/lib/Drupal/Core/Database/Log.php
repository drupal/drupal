<?php

namespace Drupal\Core\Database;

use Drupal\Core\Database\Event\StatementExecutionEndEvent;

/**
 * Database query logger.
 *
 * We log queries in a separate object rather than in the connection object
 * because we want to be able to see all queries sent to a given database, not
 * database target. If we logged the queries in each connection object we
 * would not be able to track what queries went to which target.
 *
 * Every connection has one and only one logging object on it for all targets
 * and logging keys.
 */
class Log {

  /**
   * Cache of logged queries. This will only be used if the query logger is enabled.
   *
   * The structure for the logging array is as follows:
   *
   * array(
   *   $logging_key = array(
   *     array('query' => '', 'args' => array(), 'caller' => '', 'target' => '', 'time' => 0, 'start' => 0),
   *     array('query' => '', 'args' => array(), 'caller' => '', 'target' => '', 'time' => 0, 'start' => 0),
   *   ),
   * );
   *
   * @var array
   */
  protected $queryLog = [];

  /**
   * The connection key for which this object is logging.
   *
   * @var string
   */
  protected $connectionKey = 'default';

  /**
   * Constructor.
   *
   * @param $key
   *   The database connection key for which to enable logging.
   */
  public function __construct($key = 'default') {
    $this->connectionKey = $key;
  }

  /**
   * Begin logging queries to the specified connection and logging key.
   *
   * If the specified logging key is already running this method does nothing.
   *
   * @param $logging_key
   *   The identification key for this log request. By specifying different
   *   logging keys we are able to start and stop multiple logging runs
   *   simultaneously without them colliding.
   */
  public function start($logging_key) {
    if (empty($this->queryLog[$logging_key])) {
      $this->clear($logging_key);
    }
  }

  /**
   * Retrieve the query log for the specified logging key so far.
   *
   * @param $logging_key
   *   The logging key to fetch.
   *
   * @return array
   *   An indexed array of all query records for this logging key.
   */
  public function get($logging_key) {
    return $this->queryLog[$logging_key];
  }

  /**
   * Empty the query log for the specified logging key.
   *
   * This method does not stop logging, it simply clears the log. To stop
   * logging, use the end() method.
   *
   * @param $logging_key
   *   The logging key to empty.
   */
  public function clear($logging_key) {
    $this->queryLog[$logging_key] = [];
  }

  /**
   * Stop logging for the specified logging key.
   *
   * @param $logging_key
   *   The logging key to stop.
   */
  public function end($logging_key) {
    unset($this->queryLog[$logging_key]);
  }

  /**
   * Log a query to all active logging keys, from a statement execution event.
   *
   * @param \Drupal\Core\Database\Event\StatementExecutionEndEvent $event
   *   The statement execution event.
   */
  public function logFromEvent(StatementExecutionEndEvent $event): void {
    foreach (array_keys($this->queryLog) as $key) {
      $this->queryLog[$key][] = [
        'query' => $event->queryString,
        'args' => $event->args,
        'target' => $event->target,
        'caller' => $event->caller,
        'time' => $event->getElapsedTime(),
        'start' => $event->startTime,
      ];
    }
  }

}
