<?php

namespace Drupal\sqlite\Driver\Database\sqlite;

use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Database\Statement\PdoTrait;
use Drupal\Core\Database\Statement\PrefetchedResult;
use Drupal\Core\Database\Statement\StatementBase;

/**
 * SQLite implementation of \Drupal\Core\Database\StatementInterface.
 *
 * The PDO SQLite driver only closes SELECT statements when the PDOStatement
 * destructor is called and SQLite does not allow data change (INSERT, UPDATE
 * etc) on a table which has open SELECT statements. Here we buffer all the
 * data and don't have those limitations.
 */
class Statement extends StatementBase {

  use PdoTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\sqlite\Driver\Database\sqlite\SqliteConnection $clientConnection
   *   Client database connection object, for example \PDO.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param string $queryString
   *   The query string.
   * @param array $driverOptions
   *   (optional) Driver-specific options.
   * @param bool $rowCountEnabled
   *   (optional) Enables counting the rows matched. Defaults to FALSE.
   */
  public function __construct(
    SqliteConnection $clientConnection,
    Connection $connection,
    string $queryString,
    protected array $driverOptions = [],
    bool $rowCountEnabled = FALSE,
  ) {
    parent::__construct($connection, $clientConnection, $queryString, $rowCountEnabled);
  }

  /**
   * {@inheritdoc}
   *
   * The PDO SQLite layer doesn't replace numeric placeholders in queries
   * correctly, and this makes numeric expressions (such as COUNT(*) >= :count)
   * fail. We replace numeric placeholders in the query ourselves to work
   * around this bug.
   *
   * See http://bugs.php.net/bug.php?id=45259 for more details.
   */
  protected function getStatement(string $query, ?array &$args = []): object {
    if (is_array($args) && !empty($args)) {
      // Check if $args is a simple numeric array.
      if (range(0, count($args) - 1) === array_keys($args)) {
        // In that case, we have unnamed placeholders.
        $count = 0;
        $new_args = [];
        foreach ($args as $value) {
          if (is_float($value) || is_int($value)) {
            if (is_float($value)) {
              // Force the conversion to float so as not to loose precision
              // in the automatic cast.
              $value = sprintf('%F', $value);
            }
            $query = substr_replace($query, $value, strpos($query, '?'), 1);
          }
          else {
            $placeholder = ':db_statement_placeholder_' . $count++;
            $query = substr_replace($query, $placeholder, strpos($query, '?'), 1);
            $new_args[$placeholder] = $value;
          }
        }
        $args = $new_args;
      }
      else {
        // Else, this is using named placeholders.
        foreach ($args as $placeholder => $value) {
          if (is_float($value) || is_int($value)) {
            if (is_float($value)) {
              // Force the conversion to float so as not to loose precision
              // in the automatic cast.
              $value = sprintf('%F', $value);
            }

            // We will remove this placeholder from the query as PDO throws an
            // exception if the number of placeholders in the query and the
            // arguments does not match.
            unset($args[$placeholder]);
            // PDO allows placeholders to not be prefixed by a colon. See
            // http://marc.info/?l=php-internals&m=111234321827149&w=2 for
            // more.
            if ($placeholder[0] != ':') {
              $placeholder = ":$placeholder";
            }
            // When replacing the placeholders, make sure we search for the
            // exact placeholder. For example, if searching for
            // ':db_placeholder_1', do not replace ':db_placeholder_11'.
            $query = preg_replace('/' . preg_quote($placeholder, NULL) . '\b/', $value, $query);
          }
        }
      }
    }

    return $this->clientConnection->prepare($query);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($args = [], $options = []) {
    $args ??= [];
    assert(!isset($options['fetch']) || $options['fetch'] instanceof FetchAs || is_string($options['fetch']), 'The "fetch" option passed to execute() must contain a FetchAs enum case or a string. See https://www.drupal.org/node/3488338');
    $fetchAs = $options['fetch'] ?? NULL;

    try {
      $return = $this->executeAndPrefetch($args);
    }
    catch (\PDOException $e) {
      // The database schema might be changed by another process in between the
      // time that the statement was prepared and the time the statement was run
      // (e.g. usually happens when running tests). In this case, we need to
      // re-run the query.
      // @see http://www.sqlite.org/faq.html#q15
      // @see http://www.sqlite.org/rescode.html#schema
      if (!empty($e->errorInfo[1]) && $e->errorInfo[1] === 17) {
        // The schema has changed. SQLite specifies that we must resend the
        // query.
        $return = $this->executeAndPrefetch($args);
      }
      else {
        // Rethrow the exception.
        throw $e;
      }
    }

    if ($fetchAs) {
      if (is_string($fetchAs)) {
        $this->setFetchMode(FetchAs::ClassObject, $fetchAs);
      }
      else {
        $this->setFetchMode($fetchAs);
      }
    }

    return $return;
  }

  /**
   * Executes the statement and prefetches the result.
   *
   * @param array $args
   *   An array of values with as many elements as there are bound parameters in
   *   the SQL statement being executed.
   *
   * @return bool
   *   TRUE on success, or FALSE on failure.
   */
  protected function executeAndPrefetch(array $args): bool {
    $startEvent = $this->dispatchStatementExecutionStartEvent($args);

    // Prepare and execute the statement.
    try {
      $this->clientStatement = $this->getStatement($this->queryString, $args);
      $return = $this->clientExecute($args);
    }
    catch (\Exception $e) {
      // In case of failure, cleanup and exit early - without preprocessing the
      // exception.
      $this->dispatchStatementExecutionFailureEvent($startEvent, $e);
      $this->clientStatement = NULL;
      throw $e;
    }

    // Fetch all the data from the reply, in order to release any lock as soon
    // as possible. Then, destroy the client statement.
    $this->result = new PrefetchedResult(
      $this->fetchMode,
      $this->fetchOptions,
      $this->clientFetchAll(FetchAs::Associative),
      $this->rowCountEnabled ? $this->clientRowCount() : NULL,
    );
    $this->clientStatement = NULL;
    $this->markResultsetIterable($return);

    $this->dispatchStatementExecutionEndEvent($startEvent);
    return $return;
  }

}
