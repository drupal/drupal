<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\Connection;

/**
 * General class for an abstracted DELETE operation.
 *
 * @ingroup database
 */
class Delete extends Query implements ConditionInterface {

  use QueryConditionTrait;

  /**
   * The table from which to delete.
   *
   * @var string
   */
  protected $table;

  /**
   * Constructs a Delete object.
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

    $this->condition = $this->connection->condition('AND');
  }

  /**
   * Executes the DELETE query.
   *
   * @return int
   *   The number of rows affected by the delete query.
   */
  public function execute() {
    $values = [];
    if (count($this->condition)) {
      $this->condition->compile($this->connection, $this);
      $values = $this->condition->arguments();
    }

    try {
      $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions, TRUE);
      $stmt->execute($values, $this->queryOptions);
    }
    catch (\PDOException $e) {
      if ($this->queryOptions['throw_exception'] ?? TRUE) {
        $message = $e->getMessage() . ": " . (string) $this . "; ";
        // Match all SQLSTATE 23xxx errors.
        if (substr($e->getCode(), -6, -3) == '23') {
          throw new IntegrityConstraintViolationException($message, $e->getCode(), $e);
        }
        throw new DatabaseExceptionWrapper($message, 0, $e);
      }
      return NULL;
    }

    return $stmt->rowCount();
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

    $query = $comments . 'DELETE FROM {' . $this->connection->escapeTable($this->table) . '} ';

    if (count($this->condition)) {

      $this->condition->compile($this->connection, $this);
      $query .= "\nWHERE " . $this->condition;
    }

    return $query;
  }

}
