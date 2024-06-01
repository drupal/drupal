<?php

namespace Drupal\Core\Database;

use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionFailureEvent;
use Drupal\Core\Database\Event\StatementExecutionStartEvent;

// cSpell:ignore maxlen driverdata INOUT

/**
 * StatementInterface iterator implementation.
 *
 * This class is meant to be generic enough for any type of database clients,
 * even if all Drupal core database drivers currently use PDO clients. We
 * implement \Iterator instead of \IteratorAggregate to allow iteration to be
 * kept in sync with the underlying database resultset cursor. PDO is not able
 * to execute a database operation while a cursor is open on the result of an
 * earlier select query, so Drupal by default uses buffered queries setting
 * \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY to TRUE on the connection. This forces
 * the query to return all the results in a buffer local to the client library,
 * potentially leading to memory issues in case of large datasets being
 * returned by a query. Other database clients, however, could allow
 * multithread queries, or developers could disable buffered queries in PDO:
 * in that case, this class prevents the resultset to be entirely fetched in
 * PHP memory (that an \IteratorAggregate implementation would force) and
 * therefore optimize memory usage while iterating the resultset.
 */
class StatementWrapperIterator implements \Iterator, StatementInterface {

  use StatementIteratorTrait;
  use FetchModeTrait;

  /**
   * The client database Statement object.
   *
   * For a \PDO client connection, this will be a \PDOStatement object.
   */
  protected object $clientStatement;

  /**
   * Constructs a StatementWrapperIterator object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Drupal database connection object.
   * @param object $clientConnection
   *   Client database connection object, for example \PDO.
   * @param string $query
   *   The SQL query string.
   * @param array $options
   *   Array of query options.
   * @param bool $rowCountEnabled
   *   (optional) Enables counting the rows matched. Defaults to FALSE.
   */
  public function __construct(
    protected readonly Connection $connection,
    object $clientConnection,
    string $query,
    array $options,
    protected readonly bool $rowCountEnabled = FALSE,
  ) {
    $this->clientStatement = $clientConnection->prepare($query, $options);
    $this->setFetchMode(\PDO::FETCH_OBJ);
  }

  /**
   * Returns the client-level database statement object.
   *
   * This method should normally be used only within database driver code.
   *
   * @return object
   *   The client-level database statement, for example \PDOStatement.
   */
  public function getClientStatement(): object {
    return $this->clientStatement;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnectionTarget(): string {
    return $this->connection->getTarget();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($args = [], $options = []) {
    if (isset($options['fetch'])) {
      if (is_string($options['fetch'])) {
        // \PDO::FETCH_PROPS_LATE tells __construct() to run before properties
        // are added to the object.
        $this->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $options['fetch']);
      }
      else {
        $this->setFetchMode($options['fetch']);
      }
    }

    if ($this->connection->isEventEnabled(StatementExecutionStartEvent::class)) {
      $startEvent = new StatementExecutionStartEvent(
        spl_object_id($this),
        $this->connection->getKey(),
        $this->connection->getTarget(),
        $this->getQueryString(),
        $args ?? [],
        $this->connection->findCallerFromDebugBacktrace()
      );
      $this->connection->dispatchEvent($startEvent);
    }

    try {
      $return = $this->clientStatement->execute($args);
      $this->markResultsetIterable($return);
    }
    catch (\Exception $e) {
      if (isset($startEvent) && $this->connection->isEventEnabled(StatementExecutionFailureEvent::class)) {
        $this->connection->dispatchEvent(new StatementExecutionFailureEvent(
          $startEvent->statementObjectId,
          $startEvent->key,
          $startEvent->target,
          $startEvent->queryString,
          $startEvent->args,
          $startEvent->caller,
          $startEvent->time,
          get_class($e),
          $e->getCode(),
          $e->getMessage(),
        ));
      }
      throw $e;
    }

    if (isset($startEvent) && $this->connection->isEventEnabled(StatementExecutionEndEvent::class)) {
      $this->connection->dispatchEvent(new StatementExecutionEndEvent(
        $startEvent->statementObjectId,
        $startEvent->key,
        $startEvent->target,
        $startEvent->queryString,
        $startEvent->args,
        $startEvent->caller,
        $startEvent->time
      ));
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryString() {
    return $this->clientStatement->queryString;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchCol($index = 0) {
    return $this->fetchAll(\PDO::FETCH_COLUMN, $index);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllAssoc($key, $fetch = NULL) {
    if (isset($fetch)) {
      if (is_string($fetch)) {
        $this->setFetchMode(\PDO::FETCH_CLASS, $fetch);
      }
      else {
        $this->setFetchMode($fetch);
      }
    }

    // Return early if the statement was already fully traversed.
    if (!$this->isResultsetIterable) {
      return [];
    }

    // Once the while loop is completed, the resultset is marked so not to
    // allow more fetching.
    $return = [];
    while ($record = $this->fetch()) {
      $recordKey = is_object($record) ? $record->$key : $record[$key];
      $return[$recordKey] = $record;
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllKeyed($key_index = 0, $value_index = 1) {
    $this->setFetchMode(\PDO::FETCH_NUM);

    // Return early if the statement was already fully traversed.
    if (!$this->isResultsetIterable) {
      return [];
    }

    // Once the while loop is completed, the resultset is marked so not to
    // allow more fetching.
    $return = [];
    while ($record = $this->fetch()) {
      $return[$record[$key_index]] = $record[$value_index];
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchField($index = 0) {
    // Call \PDOStatement::fetchColumn to fetch the field.
    $column = $this->clientStatement->fetchColumn($index);

    if ($column === FALSE) {
      $this->markResultsetFetchingComplete();
      return FALSE;
    }

    $this->setResultsetCurrentRow($column);
    return $column;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAssoc() {
    return $this->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchObject(?string $class_name = NULL, array $constructor_arguments = []) {
    if ($class_name) {
      $row = $this->clientStatement->fetchObject($class_name, $constructor_arguments);
    }
    else {
      $row = $this->clientStatement->fetchObject();
    }

    if ($row === FALSE) {
      $this->markResultsetFetchingComplete();
      return FALSE;
    }

    $this->setResultsetCurrentRow($row);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function rowCount() {
    // SELECT query should not use the method.
    if ($this->rowCountEnabled) {
      return $this->clientStatement->rowCount();
    }
    else {
      throw new RowCountException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFetchMode($mode, $a1 = NULL, $a2 = []) {
    assert(in_array($mode, $this->supportedFetchModes), 'Fetch mode ' . ($this->fetchModeLiterals[$mode] ?? $mode) . ' is not supported. Use supported modes only.');

    // Call \PDOStatement::setFetchMode to set fetch mode.
    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to be pass the exact number of arguments we where given.
    return match(func_num_args()) {
      1 => $this->clientStatement->setFetchMode($mode),
      2 => $this->clientStatement->setFetchMode($mode, $a1),
      default => $this->clientStatement->setFetchMode($mode, $a1, $a2),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL) {
    assert(!isset($mode) || in_array($mode, $this->supportedFetchModes), 'Fetch mode ' . ($this->fetchModeLiterals[$mode] ?? $mode) . ' is not supported. Use supported modes only.');

    // Call \PDOStatement::fetchAll to fetch all rows.
    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to pass the exact number of arguments we were given.
    $row = match(func_num_args()) {
      0 => $this->clientStatement->fetch(),
      1 => $this->clientStatement->fetch($mode),
      2 => $this->clientStatement->fetch($mode, $cursor_orientation),
      default => $this->clientStatement->fetch($mode, $cursor_orientation, $cursor_offset),
    };

    if ($row === FALSE) {
      $this->markResultsetFetchingComplete();
      return FALSE;
    }

    $this->setResultsetCurrentRow($row);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll($mode = NULL, $column_index = NULL, $constructor_arguments = NULL) {
    assert(!isset($mode) || in_array($mode, $this->supportedFetchModes), 'Fetch mode ' . ($this->fetchModeLiterals[$mode] ?? $mode) . ' is not supported. Use supported modes only.');

    // Call \PDOStatement::fetchAll to fetch all rows.
    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to be pass the exact number of arguments we where given.
    $return = match(func_num_args()) {
      0 => $this->clientStatement->fetchAll(),
      1 => $this->clientStatement->fetchAll($mode),
      2 => $this->clientStatement->fetchAll($mode, $column_index),
      default => $this->clientStatement->fetchAll($mode, $column_index, $constructor_arguments),
    };

    $this->markResultsetFetchingComplete();

    return $return;
  }

}
