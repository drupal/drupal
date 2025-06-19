<?php

namespace Drupal\Core\Database;

use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Database\Statement\PrefetchedResult;
use Drupal\Core\Database\Statement\StatementBase;

/**
 * An implementation of StatementInterface that prefetches all data.
 *
 * This class behaves very similar to a StatementWrapperIterator of a
 * \PDOStatement but as it always fetches every row it is possible to
 * manipulate those results.
 */
class StatementPrefetchIterator extends StatementBase {

  /**
   * Main data store.
   *
   * The resultset is stored as a FetchAs::Associative array.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   * the methods provided by Drupal\Core\Database\Statement\PrefetchedResult
   * instead.
   *
   * @see https://www.drupal.org/node/3510455
   */
  protected array $data = [];

  /**
   * The list of column names in this result set.
   *
   * @var string[]
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   * the methods provided by Drupal\Core\Database\Statement\PrefetchedResult
   * instead.
   *
   * @see https://www.drupal.org/node/3510455
   */
  protected ?array $columnNames = NULL;

  /**
   * The number of rows matched by the last query.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   * the methods provided by Drupal\Core\Database\Statement\PrefetchedResult
   * instead.
   *
   * @see https://www.drupal.org/node/3510455
   */
  protected ?int $rowCount = NULL;

  /**
   * Holds the default fetch style.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   * $fetchMode instead.
   *
   * @see https://www.drupal.org/node/3488338
   */
  protected int $defaultFetchStyle = \PDO::FETCH_OBJ;

  /**
   * Holds the default fetch mode.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   * $fetchMode instead.
   *
   * @see https://www.drupal.org/node/3510455
   */
  protected FetchAs $defaultFetchMode = FetchAs::Object;

  /**
   * Constructs a StatementPrefetchIterator object.
   *
   * @param object $clientConnection
   *   Client database connection object, for example \PDO.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param string $queryString
   *   The query string.
   * @param array $driverOptions
   *   Driver-specific options.
   * @param bool $rowCountEnabled
   *   (optional) Enables counting the rows matched. Defaults to FALSE.
   */
  public function __construct(
    object $clientConnection,
    Connection $connection,
    string $queryString,
    protected array $driverOptions = [],
    bool $rowCountEnabled = FALSE,
  ) {
    parent::__construct($connection, $clientConnection, $queryString, $rowCountEnabled);
  }

  /**
   * Returns the client-level database PDO statement object.
   *
   * This method should normally be used only within database driver code.
   *
   * @return \PDOStatement
   *   The client-level database PDO statement.
   *
   * @throws \RuntimeException
   *   If the client-level statement is not set.
   */
  public function getClientStatement(): \PDOStatement {
    if (isset($this->clientStatement)) {
      assert($this->clientStatement instanceof \PDOStatement);
      return $this->clientStatement;
    }
    throw new \LogicException('\\PDOStatement not initialized');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($args = [], $options = []) {
    if (isset($options['fetch']) && is_int($options['fetch'])) {
      @trigger_error("Passing the 'fetch' key as an integer to \$options in execute() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\Statement\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
    }

    $startEvent = $this->dispatchStatementExecutionStartEvent($args ?? []);

    // Prepare and execute the statement.
    try {
      $this->clientStatement = $this->getStatement($this->queryString, $args);
      $return = $this->clientExecute($args, $options);
    }
    catch (\Exception $e) {
      $this->dispatchStatementExecutionFailureEvent($startEvent, $e);
      unset($this->clientStatement);
      throw $e;
    }

    // Fetch all the data from the reply, in order to release any lock as soon
    // as possible. Then, destroy the client statement. See the documentation
    // of \Drupal\sqlite\Driver\Database\sqlite\Statement for an explanation.
    $this->result = new PrefetchedResult(
      $this->fetchMode,
      $this->fetchOptions,
      $this->clientFetchAll(FetchAs::Associative),
      $this->rowCountEnabled ? $this->clientRowCount() : NULL,
    );
    unset($this->clientStatement);
    $this->markResultsetIterable($return);

    if (isset($options['fetch'])) {
      if (is_string($options['fetch'])) {
        // Default to an object. Note: db fields will be added to the object
        // before the constructor is run. If you need to assign fields after
        // the constructor is run. See https://www.drupal.org/node/315092.
        $this->setFetchMode(FetchAs::ClassObject, $options['fetch']);
      }
      else {
        $this->setFetchMode($options['fetch']);
      }
    }

    $this->dispatchStatementExecutionEndEvent($startEvent);

    return $return;
  }

  /**
   * Grab a PDOStatement object from a given query and its arguments.
   *
   * Some drivers (including SQLite) will need to perform some preparation
   * themselves to get the statement right.
   *
   * @param string $query
   *   The query.
   * @param array|null $args
   *   An array of arguments. This can be NULL.
   *
   * @return object
   *   A PDOStatement object.
   */
  protected function getStatement(string $query, ?array &$args = []): object {
    return $this->connection->prepare($query, $this->driverOptions);
  }

  /**
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   *   ::fetchField() instead.
   *
   * @see https://www.drupal.org/node/3490312
   */
  public function fetchColumn($index = 0) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use ::fetchField() instead. See https://www.drupal.org/node/3490312', E_USER_DEPRECATED);
    return $this->fetchField($index);
  }

}
