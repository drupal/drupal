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

  /**
   * Log a query to all active logging keys.
   *
   * @param \Drupal\Core\Database\StatementInterface $statement
   *   The prepared statement object to log.
   * @param array $args
   *   The arguments passed to the statement object.
   * @param float $time
   *   The time the query took to execute as a float (in seconds with
   *   microsecond precision).
   * @param float $start
   *   The time the query started as a float (in seconds since the Unix epoch
   *   with microsecond precision).
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use
   *   ::logFromEvent().
   *
   * @see https://www.drupal.org/node/3328053
   */
  public function log(StatementInterface $statement, $args, $time, ?float $start = NULL) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use ::logFromEvent(). See https://www.drupal.org/node/3328053', E_USER_DEPRECATED);
    foreach (array_keys($this->queryLog) as $key) {
      $this->queryLog[$key][] = [
        'query' => $statement->getQueryString(),
        'args' => $args,
        'target' => $statement->getConnectionTarget(),
        'caller' => $this->findCaller(),
        'time' => $time,
        'start' => $start,
      ];
    }
  }

  /**
   * Determine the routine that called this query.
   *
   * Traversing the call stack from the very first call made during the
   * request, we define "the routine that called this query" as the last entry
   * in the call stack that is not any method called from the namespace of the
   * database driver, is not inside the Drupal\Core\Database namespace and does
   * have a file (which excludes call_user_func_array(), anonymous functions
   * and similar). That makes the climbing logic very simple, and handles the
   * variable stack depth caused by the query builders.
   *
   * See the @link http://php.net/debug_backtrace debug_backtrace() @endlink
   * function.
   *
   * @return array|null
   *   This method returns a stack trace entry similar to that generated by
   *   debug_backtrace(). However, it flattens the trace entry and the trace
   *   entry before it so that we get the function and args of the function that
   *   called into the database system, not the function and args of the
   *   database call itself.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use
   *   Connection::findCallerFromDebugBacktrace().
   *
   * @see https://www.drupal.org/node/3328053
   */
  public function findCaller() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use Connection::findCallerFromDebugBacktrace(). See https://www.drupal.org/node/3328053', E_USER_DEPRECATED);
    $driver_namespace = Database::getConnectionInfo($this->connectionKey)['default']['namespace'];
    $stack = static::removeDatabaseEntries($this->getDebugBacktrace(), $driver_namespace);

    // Return the first function call whose stack entry has a 'file' key, that
    // is, it is not a callback or a closure.
    for ($i = 0; $i < count($stack); $i++) {
      if (!empty($stack[$i]['file'])) {
        return [
          'file' => $stack[$i]['file'],
          'line' => $stack[$i]['line'],
          'function' => $stack[$i + 1]['function'],
          'class' => $stack[$i + 1]['class'] ?? NULL,
          'type' => $stack[$i + 1]['type'] ?? NULL,
          'args' => $stack[$i + 1]['args'] ?? [],
        ];
      }
    }
  }

  /**
   * Removes database related calls from a backtrace array.
   *
   * @param array $backtrace
   *   A standard PHP backtrace. Passed by reference.
   * @param string $driver_namespace
   *   The PHP namespace of the database driver.
   *
   * @return array
   *   The cleaned backtrace array.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use
   *   Connection::removeDatabaseEntriesFromDebugBacktrace().
   *
   * @see https://www.drupal.org/node/3328053
   */
  public static function removeDatabaseEntries(array $backtrace, string $driver_namespace): array {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use Connection::removeDatabaseEntriesFromDebugBacktrace(). See https://www.drupal.org/node/3328053', E_USER_DEPRECATED);
    return Connection::removeDatabaseEntriesFromDebugBacktrace($backtrace, $driver_namespace);
  }

  /**
   * Gets the debug backtrace.
   *
   * Wraps the debug_backtrace function to allow mocking results in PHPUnit
   * tests.
   *
   * @return array[]
   *   The debug backtrace.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is
   *   no replacement.
   *
   * @see https://www.drupal.org/node/3328053
   */
  protected function getDebugBacktrace() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3328053', E_USER_DEPRECATED);
    return debug_backtrace();
  }

}
