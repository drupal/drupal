<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Statement;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionFailureEvent;
use Drupal\Core\Database\Event\StatementExecutionStartEvent;
use Drupal\Core\Database\FetchModeTrait;
use Drupal\Core\Database\RowCountException;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Database\StatementIteratorTrait;

/**
 * StatementInterface base implementation.
 *
 * This class is meant to be generic enough for any type of database client,
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
abstract class StatementBase implements \Iterator, StatementInterface {

  use FetchModeTrait;
  use PdoTrait;
  use StatementIteratorTrait;

  /**
   * The client database Statement object.
   *
   * For a \PDO client connection, this will be a \PDOStatement object.
   */
  protected ?object $clientStatement;

  /**
   * The results of a data query language (DQL) statement.
   */
  protected ?ResultBase $result = NULL;

  /**
   * Holds the default fetch mode.
   */
  protected FetchAs $fetchMode = FetchAs::Object;

  /**
   * Holds fetch options.
   *
   * @var array{'class': class-string, 'constructor_args': list<mixed>, 'column': int}
   */
  protected array $fetchOptions = [
    'class' => 'stdClass',
    'constructor_args' => [],
    'column' => 0,
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Drupal database connection object.
   * @param object $clientConnection
   *   Client database connection object, for example \PDO.
   * @param string $queryString
   *   The query string.
   * @param bool $rowCountEnabled
   *   (optional) Enables counting the rows matched. Defaults to FALSE.
   */
  public function __construct(
    protected readonly Connection $connection,
    protected readonly object $clientConnection,
    protected readonly string $queryString,
    protected readonly bool $rowCountEnabled = FALSE,
  ) {
  }

  /**
   * Determines if the client-level database statement object exists.
   *
   * This method should normally be used only within database driver code.
   *
   * @return bool
   *   TRUE if the client statement exists, FALSE otherwise.
   */
  public function hasClientStatement(): bool {
    return isset($this->clientStatement);
  }

  /**
   * Returns the client-level database statement object.
   *
   * This method should normally be used only within database driver code.
   *
   * @return object
   *   The client-level database statement.
   *
   * @throws \RuntimeException
   *   If the client-level statement is not set.
   */
  public function getClientStatement(): object {
    if ($this->hasClientStatement()) {
      return $this->clientStatement;
    }
    throw new \LogicException('Client statement not initialized');
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
  abstract public function execute($args = [], $options = []);

  /**
   * Dispatches an event informing that the statement execution begins.
   *
   * @param array $args
   *   An array of values with as many elements as there are bound parameters in
   *   the SQL statement being executed. This can be empty.
   *
   * @return \Drupal\Core\Database\Event\StatementExecutionStartEvent|null
   *   The dispatched event or NULL if event dispatching is not enabled.
   */
  protected function dispatchStatementExecutionStartEvent(array $args): ?StatementExecutionStartEvent {
    if ($this->connection->isEventEnabled(StatementExecutionStartEvent::class)) {
      $startEvent = new StatementExecutionStartEvent(
        spl_object_id($this),
        $this->connection->getKey(),
        $this->connection->getTarget(),
        $this->getQueryString(),
        $args,
        $this->connection->findCallerFromDebugBacktrace()
      );
      $this->connection->dispatchEvent($startEvent);
      return $startEvent;
    }
    return NULL;
  }

  /**
   * Dispatches an event informing that the statement execution succeeded.
   *
   * @param \Drupal\Core\Database\Event\StatementExecutionStartEvent|null $startEvent
   *   The start event or NULL if event dispatching is not enabled.
   */
  protected function dispatchStatementExecutionEndEvent(?StatementExecutionStartEvent $startEvent): void {
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
  }

  /**
   * Dispatches an event informing of the statement execution failure.
   *
   * @param \Drupal\Core\Database\Event\StatementExecutionStartEvent|null $startEvent
   *   The start event or NULL if event dispatching is not enabled.
   * @param \Exception $e
   *   The statement exception thrown.
   */
  protected function dispatchStatementExecutionFailureEvent(?StatementExecutionStartEvent $startEvent, \Exception $e): void {
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
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryString() {
    return $this->queryString;
  }

  /**
   * {@inheritdoc}
   */
  public function setFetchMode($mode, $a1 = NULL, $a2 = []) {
    if (is_int($mode)) {
      @trigger_error("Passing the \$mode argument as an integer to setFetchMode() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
      $mode = $this->pdoToFetchAs($mode);
    }
    assert($mode instanceof FetchAs);

    $this->fetchMode = $mode;
    switch ($mode) {
      case FetchAs::ClassObject:
        $this->fetchOptions['class'] = $a1;
        if ($a2) {
          $this->fetchOptions['constructor_args'] = $a2;
        }
        break;

      case FetchAs::Column:
        $this->fetchOptions['column'] = $a1;
        break;

    }

    // If the result object is missing, just do with the properties setting.
    try {
      if ($this->result) {
        return $this->result->setFetchMode($mode, $this->fetchOptions);
      }
      return TRUE;
    }
    catch (\LogicException) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($mode = NULL, $cursorOrientation = NULL, $cursorOffset = NULL) {
    if (is_int($mode)) {
      @trigger_error("Passing the \$mode argument as an integer to fetch() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
      $mode = $this->pdoToFetchAs($mode);
    }
    assert($mode === NULL || $mode instanceof FetchAs);

    $fetchOptions = match(func_num_args()) {
      0 => $this->fetchOptions,
      1 => $this->fetchOptions,
      2 => $this->fetchOptions + [
        'cursor_orientation' => $cursorOrientation,
      ],
      default => $this->fetchOptions + [
        'cursor_orientation' => $cursorOrientation,
        'cursor_offset' => $cursorOffset,
      ],
    };

    $row = $this->result->fetch($mode ?? $this->fetchMode, $fetchOptions);

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
  public function fetchObject(?string $className = NULL, array $constructorArguments = []) {
    $row = $className === NULL ?
      $this->result->fetch(FetchAs::Object, []) :
      $this->result->fetch(FetchAs::ClassObject, [
        'class' => $className,
        'constructor_args' => $constructorArguments,
      ]);

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
  public function fetchAssoc() {
    return $this->fetch(FetchAs::Associative);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchField($index = 0) {
    $column = $this->result->fetch(FetchAs::Column, ['column' => $index]);

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
  public function fetchAll($mode = NULL, $columnIndex = NULL, $constructorArguments = NULL) {
    if (is_int($mode)) {
      @trigger_error("Passing the \$mode argument as an integer to fetchAll() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
      $mode = $this->pdoToFetchAs($mode);
    }

    assert($mode === NULL || $mode instanceof FetchAs);

    $fetchMode = $mode ?? $this->fetchMode;
    if (isset($columnIndex)) {
      $this->fetchOptions['column'] = $columnIndex;
    }
    if (isset($constructorArguments)) {
      $this->fetchOptions['constructor_args'] = $constructorArguments;
    }

    $return = $this->result->fetchAll($fetchMode, $this->fetchOptions);

    $this->markResultsetFetchingComplete();

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchCol($index = 0) {
    return $this->fetchAll(FetchAs::Column, $index);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllAssoc($key, $fetch = NULL) {
    if (is_int($fetch)) {
      @trigger_error("Passing the \$fetch argument as an integer to fetchAllAssoc() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
      $fetch = $this->pdoToFetchAs($fetch);
    }
    assert($fetch === NULL || $fetch instanceof FetchAs);

    $result = $this->result->fetchAllAssoc($key, $fetch ?? $this->fetchMode, $this->fetchOptions);
    $this->markResultsetFetchingComplete();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllKeyed($keyIndex = 0, $valueIndex = 1) {
    $result = $this->result->fetchAllKeyed($keyIndex, $valueIndex);
    $this->markResultsetFetchingComplete();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function rowCount() {
    // SELECT query should not use the method.
    if ($this->rowCountEnabled) {
      return $this->result->rowCount();
    }
    else {
      throw new RowCountException();
    }
  }

}
