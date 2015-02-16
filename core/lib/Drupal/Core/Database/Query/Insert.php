<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Query\Insert
 */

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Database;

/**
 * General class for an abstracted INSERT query.
 *
 * @ingroup database
 */
class Insert extends Query {

  /**
   * The table on which to insert.
   *
   * @var string
   */
  protected $table;

  /**
   * An array of fields on which to insert.
   *
   * @var array
   */
  protected $insertFields = array();

  /**
   * An array of fields that should be set to their database-defined defaults.
   *
   * @var array
   */
  protected $defaultFields = array();

  /**
   * A nested array of values to insert.
   *
   * $insertValues is an array of arrays. Each sub-array is either an
   * associative array whose keys are field names and whose values are field
   * values to insert, or a non-associative array of values in the same order
   * as $insertFields.
   *
   * Whether multiple insert sets will be run in a single query or multiple
   * queries is left to individual drivers to implement in whatever manner is
   * most appropriate. The order of values in each sub-array must match the
   * order of fields in $insertFields.
   *
   * @var array
   */
  protected $insertValues = array();

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
  public function __construct($connection, $table, array $options = array()) {
    if (!isset($options['return'])) {
      $options['return'] = Database::RETURN_INSERT_ID;
    }
    parent::__construct($connection, $options);
    $this->table = $table;
  }

  /**
   * Adds a set of field->value pairs to be inserted.
   *
   * This method may only be called once. Calling it a second time will be
   * ignored. To queue up multiple sets of values to be inserted at once,
   * use the values() method.
   *
   * @param $fields
   *   An array of fields on which to insert. This array may be indexed or
   *   associative. If indexed, the array is taken to be the list of fields.
   *   If associative, the keys of the array are taken to be the fields and
   *   the values are taken to be corresponding values to insert. If a
   *   $values argument is provided, $fields must be indexed.
   * @param $values
   *   An array of fields to insert into the database. The values must be
   *   specified in the same order as the $fields array.
   *
   * @return \Drupal\Core\Database\Query\Insert
   *   The called object.
   */
  public function fields(array $fields, array $values = array()) {
    if (empty($this->insertFields)) {
      if (empty($values)) {
        if (!is_numeric(key($fields))) {
          $values = array_values($fields);
          $fields = array_keys($fields);
        }
      }
      $this->insertFields = $fields;
      if (!empty($values)) {
        $this->insertValues[] = $values;
      }
    }

    return $this;
  }

  /**
   * Adds another set of values to the query to be inserted.
   *
   * If $values is a numeric-keyed array, it will be assumed to be in the same
   * order as the original fields() call. If it is associative, it may be
   * in any order as long as the keys of the array match the names of the
   * fields.
   *
   * @param $values
   *   An array of values to add to the query.
   *
   * @return \Drupal\Core\Database\Query\Insert
   *   The called object.
   */
  public function values(array $values) {
    if (is_numeric(key($values))) {
      $this->insertValues[] = $values;
    }
    else {
      // Reorder the submitted values to match the fields array.
      foreach ($this->insertFields as $key) {
        $insert_values[$key] = $values[$key];
      }
      // For consistency, the values array is always numerically indexed.
      $this->insertValues[] = array_values($insert_values);
    }
    return $this;
  }

  /**
   * Specifies fields for which the database defaults should be used.
   *
   * If you want to force a given field to use the database-defined default,
   * not NULL or undefined, use this method to instruct the database to use
   * default values explicitly. In most cases this will not be necessary
   * unless you are inserting a row that is all default values, as you cannot
   * specify no values in an INSERT query.
   *
   * Specifying a field both in fields() and in useDefaults() is an error
   * and will not execute.
   *
   * @param $fields
   *   An array of values for which to use the default values
   *   specified in the table definition.
   *
   * @return \Drupal\Core\Database\Query\Insert
   *   The called object.
   */
  public function useDefaults(array $fields) {
    $this->defaultFields = $fields;
    return $this;
  }

  /**
   * Sets the fromQuery on this InsertQuery object.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to fetch the rows that should be inserted.
   *
   * @return \Drupal\Core\Database\Query\Insert
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

    // Each insert happens in its own query in the degenerate case. However,
    // we wrap it in a transaction so that it is atomic where possible. On many
    // databases, such as SQLite, this is also a notable performance boost.
    $transaction = $this->connection->startTransaction();

    try {
      $sql = (string) $this;
      foreach ($this->insertValues as $insert_values) {
        $last_insert_id = $this->connection->query($sql, $insert_values, $this->queryOptions);
      }
    }
    catch (\Exception $e) {
      // One of the INSERTs failed, rollback the whole batch.
      $transaction->rollback();
      // Rethrow the exception for the calling code.
      throw $e;
    }

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = array();

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
    $placeholders = array();
    $placeholders = array_pad($placeholders, count($this->defaultFields), 'default');
    $placeholders = array_pad($placeholders, count($this->insertFields), '?');

    return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
  }

  /**
   * Preprocesses and validates the query.
   *
   * @return
   *   TRUE if the validation was successful, FALSE if not.
   *
   * @throws \Drupal\Core\Database\Query\FieldsOverlapException
   * @throws \Drupal\Core\Database\Query\NoFieldsException
   */
  public function preExecute() {
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
