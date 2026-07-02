<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;

/**
 * General class for an abstracted TRUNCATE operation.
 */
class Truncate extends Query implements QueryExecutionVoidInterface {

  /**
   * The table to truncate.
   *
   * @var string
   */
  protected $table;

  /**
   * Constructs a Truncate query object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Connection object.
   * @param string $table
   *   Name of the table to associate with this query.
   * @param array $options
   *   Array of database options.
   */
  public function __construct(Connection $connection, $table, array $options = []) {
    parent::__construct($connection, $options);
    $this->table = $table;
  }

  /**
   * Executes the TRUNCATE query.
   *
   * In most cases, TRUNCATE is not a transaction safe statement as it is a DDL
   * statement which results in an implicit COMMIT. When we are in a
   * transaction, fallback to the slower, but transactional, DELETE.
   * PostgreSQL also locks the entire table for a TRUNCATE strongly reducing
   * the concurrency with other transactions.
   *
   * @see https://learnsql.com/blog/difference-between-truncate-delete-and-drop-table-in-sql
   */
  public function execute(): void {
    $statement = $this->connection->prepareStatement((string) $this, $this->queryOptions, TRUE);
    try {
      $statement->execute([], $this->queryOptions);
    }
    catch (\Exception $e) {
      $this->connection->exceptionHandler()->handleExecutionException($e, $statement, [], $this->queryOptions);
    }
  }

  /**
   * Implements PHP magic __toString method to convert the query to a string.
   *
   * @return string
   *   The prepared statement.
   */
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // The statement actually built depends on whether a transaction is active.
    // @see ::execute()
    if ($this->connection->inTransaction()) {
      return $comments . 'DELETE FROM {' . $this->connection->escapeTable($this->table) . '}';
    }
    else {
      return $comments . 'TRUNCATE {' . $this->connection->escapeTable($this->table) . '} ';
    }
  }

}
