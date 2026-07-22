<?php

namespace Drupal\Core\Database\Query;

/**
 * General class for an abstracted INSERT query.
 *
 * @ingroup database
 */
abstract class Insert extends Query implements \Countable {

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
   * @return int|null|string
   *   The last insert ID of the query, if one exists. If the query was given
   *   multiple sets of values to insert, the return value is undefined. If no
   *   fields are specified, this method will do nothing and return NULL. That
   *   That makes it safe to use in multi-insert loops.
   */
  abstract public function execute();

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
      // This behavior can be overridden by calling fields() manually as only
      // the first call to fields() does have an effect.
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
