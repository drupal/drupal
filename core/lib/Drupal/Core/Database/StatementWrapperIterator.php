<?php

namespace Drupal\Core\Database;

use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Database\Statement\PdoResult;
use Drupal\Core\Database\Statement\StatementBase;

/**
 * StatementInterface iterator implementation.
 */
class StatementWrapperIterator extends StatementBase {

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
    Connection $connection,
    object $clientConnection,
    string $query,
    array $options,
    bool $rowCountEnabled = FALSE,
  ) {
    parent::__construct($connection, $clientConnection, $query, $rowCountEnabled);
    $this->clientStatement = $this->clientConnection->prepare($query, $options);
    $this->setFetchMode(FetchAs::Object);
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

    if (isset($options['fetch'])) {
      if (is_string($options['fetch'])) {
        $this->setFetchMode(FetchAs::ClassObject, $options['fetch']);
      }
      else {
        $this->setFetchMode($options['fetch']);
      }
    }

    $startEvent = $this->dispatchStatementExecutionStartEvent($args ?? []);

    try {
      $return = $this->clientExecute($args, $options);
      $this->result = new PdoResult(
        $this->fetchMode,
        $this->fetchOptions,
        $this->getClientStatement(),
      );
      $this->markResultsetIterable($return);
    }
    catch (\Exception $e) {
      $this->dispatchStatementExecutionFailureEvent($startEvent, $e);
      throw $e;
    }

    $this->dispatchStatementExecutionEndEvent($startEvent);

    return $return;
  }

}
