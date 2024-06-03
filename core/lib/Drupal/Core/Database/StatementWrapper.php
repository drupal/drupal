<?php

namespace Drupal\Core\Database;

use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionStartEvent;

// cSpell:ignore maxlen driverdata INOUT

@trigger_error('\Drupal\Core\Database\StatementWrapper is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Database\StatementWrapperIterator instead. See https://www.drupal.org/node/3265938', E_USER_DEPRECATED);

/**
 * Implementation of StatementInterface encapsulating PDOStatement.
 *
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use
 *   \Drupal\Core\Database\StatementWrapperIterator instead.
 *
 * @see https://www.drupal.org/node/3265938
 */
class StatementWrapper implements \IteratorAggregate, StatementInterface {

  /**
   * The Drupal database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The client database Statement object.
   *
   * For a \PDO client connection, this will be a \PDOStatement object.
   *
   * @var object
   */
  protected $clientStatement;

  /**
   * Is rowCount() execution allowed.
   *
   * @var bool
   */
  protected $rowCountEnabled = FALSE;

  /**
   * Constructs a StatementWrapper object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Drupal database connection object.
   * @param object $client_connection
   *   Client database connection object, for example \PDO.
   * @param string $query
   *   The SQL query string.
   * @param array $options
   *   Array of query options.
   * @param bool $row_count_enabled
   *   (optional) Enables counting the rows matched. Defaults to FALSE.
   */
  public function __construct(Connection $connection, $client_connection, string $query, array $options, bool $row_count_enabled = FALSE) {
    $this->connection = $connection;
    $this->clientStatement = $client_connection->prepare($query, $options);
    $this->rowCountEnabled = $row_count_enabled;
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
  public function getClientStatement() {
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

    $return = $this->clientStatement->execute($args);

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
    $return = [];
    if (isset($fetch)) {
      if (is_string($fetch)) {
        $this->setFetchMode(\PDO::FETCH_CLASS, $fetch);
      }
      else {
        $this->setFetchMode($fetch);
      }
    }

    foreach ($this as $record) {
      $record_key = is_object($record) ? $record->$key : $record[$key];
      $return[$record_key] = $record;
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllKeyed($key_index = 0, $value_index = 1) {
    $return = [];
    $this->setFetchMode(\PDO::FETCH_NUM);
    foreach ($this as $record) {
      $return[$record[$key_index]] = $record[$value_index];
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchField($index = 0) {
    // Call \PDOStatement::fetchColumn to fetch the field.
    return $this->clientStatement->fetchColumn($index);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAssoc() {
    // Call \PDOStatement::fetch to fetch the row.
    return $this->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchObject(?string $class_name = NULL, array $constructor_arguments = []) {
    if ($class_name) {
      return $this->clientStatement->fetchObject($class_name, $constructor_arguments);
    }
    return $this->clientStatement->fetchObject();
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
    // Call \PDOStatement::setFetchMode to set fetch mode.
    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to be pass the exact number of arguments we where given.
    switch (func_num_args()) {
      case 1:
        return $this->clientStatement->setFetchMode($mode);

      case 2:
        return $this->clientStatement->setFetchMode($mode, $a1);

      case 3:
      default:
        return $this->clientStatement->setFetchMode($mode, $a1, $a2);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL) {
    // Call \PDOStatement::fetchAll to fetch all rows.
    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to be pass the exact number of arguments we where given.
    switch (func_num_args()) {
      case 0:
        return $this->clientStatement->fetch();

      case 1:
        return $this->clientStatement->fetch($mode);

      case 2:
        return $this->clientStatement->fetch($mode, $cursor_orientation);

      case 3:
      default:
        return $this->clientStatement->fetch($mode, $cursor_orientation, $cursor_offset);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll($mode = NULL, $column_index = NULL, $constructor_arguments = NULL) {
    // Call \PDOStatement::fetchAll to fetch all rows.
    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to be pass the exact number of arguments we where given.
    switch (func_num_args()) {
      case 0:
        return $this->clientStatement->fetchAll();

      case 1:
        return $this->clientStatement->fetchAll($mode);

      case 2:
        return $this->clientStatement->fetchAll($mode, $column_index);

      case 3:
      default:
        return $this->clientStatement->fetchAll($mode, $column_index, $constructor_arguments);
    }
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function getIterator() {
    return new \ArrayIterator($this->fetchAll());
  }

}
