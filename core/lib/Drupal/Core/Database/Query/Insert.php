<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Database;

/**
 * General class for an abstracted INSERT query.
 *
 * @ingroup database
 */
class Insert extends Query implements \Countable {

  use InsertTrait;

  /**
   * A SelectQuery object to fetch the rows that should be inserted.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $fromQuery;

  /**
   * Constructs an Insert object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Connection object.
   * @param string $table
   *   Name of the table to associate with this query.
   * @param array $options
   *   Array of database options.
   */
  public function __construct($connection, $table, array $options = []) {
    // @todo Remove $options['return'] in Drupal 11.
    // @see https://www.drupal.org/project/drupal/issues/3256524
    if (!isset($options['return'])) {
      $options['return'] = Database::RETURN_INSERT_ID;
    }
    parent::__construct($connection, $options);
    $this->table = $table;
  }

  /**
   * Sets the fromQuery on this InsertQuery object.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to fetch the rows that should be inserted.
   *
   * @return $this
   *   The called object.
   */
  public function from(SelectInterface $query) {
    $this->fromQuery = $query;
    return $this;
  }

  /**
   * Executes the insert query.
   *
   * @return
   *   The last insert ID of the query, if one exists. If the query was given
   *   multiple sets of values to insert, the return value is undefined. If no
   *   fields are specified, this method will do nothing and return NULL. That
   *   That makes it safe to use in multi-insert loops.
   */
  public function execute() {
    // If validation fails, simply return NULL. Note that validation routines
    // in preExecute() may throw exceptions instead.
    if (!$this->preExecute()) {
      return NULL;
    }

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (!empty($this->fromQuery)) {
      $sql = (string) $this;
      // The SelectQuery may contain arguments, load and pass them through.
      return $this->connection->query($sql, $this->fromQuery->getArguments(), $this->queryOptions);
    }

    $last_insert_id = 0;
    $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions);
    try {
      // Per https://en.wikipedia.org/wiki/Insert_%28SQL%29#Multirow_inserts,
      // not all databases implement SQL-92's standard syntax for multi-row
      // inserts. Therefore, in the degenerate case, execute a separate query
      // for each row, all within a single transaction for atomicity and
      // performance.
      $transaction = $this->connection->startTransaction();
      foreach ($this->insertValues as $insert_values) {
        $stmt->execute($insert_values, $this->queryOptions);
        $last_insert_id = $this->connection->lastInsertId();
      }
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        // One of the INSERTs failed, rollback the whole batch.
        $transaction->rollBack();
      }
      // Rethrow the exception for the calling code.
      throw $e;
    }

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = [];

    // Transaction commits here where $transaction looses scope.

    return $last_insert_id;
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

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);

    if (!empty($this->fromQuery)) {
      return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') ' . $this->fromQuery;
    }

    // For simplicity, we will use the $placeholders array to inject
    // default keywords even though they are not, strictly speaking,
    // placeholders for prepared statements.
    $placeholders = [];
    $placeholders = array_pad($placeholders, count($this->defaultFields), 'default');
    $placeholders = array_pad($placeholders, count($this->insertFields), '?');

    return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
  }

  /**
   * Preprocesses and validates the query.
   *
   * @return bool
   *   TRUE if the validation was successful, FALSE if not.
   *
   * @throws \Drupal\Core\Database\Query\FieldsOverlapException
   * @throws \Drupal\Core\Database\Query\NoFieldsException
   */
  protected function preExecute() {
    // Confirm that the user did not try to specify an identical
    // field and default field.
    if (array_intersect($this->insertFields, $this->defaultFields)) {
      throw new FieldsOverlapException('You may not specify the same field to have a value and a schema-default value.');
    }

    if (!empty($this->fromQuery)) {
      // We have to assume that the used aliases match the insert fields.
      // Regular fields are added to the query before expressions, maintain the
      // same order for the insert fields.
      // This behavior can be overridden by calling fields() manually as only the
      // first call to fields() does have an effect.
      $this->fields(array_merge(array_keys($this->fromQuery->getFields()), array_keys($this->fromQuery->getExpressions())));
    }
    else {
      // Don't execute query without fields.
      if (count($this->insertFields) + count($this->defaultFields) == 0) {
        throw new NoFieldsException('There are no fields available to insert with.');
      }
    }

    // If no values have been added, silently ignore this query. This can happen
    // if values are added conditionally, so we don't want to throw an
    // exception.
    if (!isset($this->insertValues[0]) && count($this->insertFields) > 0 && empty($this->fromQuery)) {
      return FALSE;
    }
    return TRUE;
  }

}
