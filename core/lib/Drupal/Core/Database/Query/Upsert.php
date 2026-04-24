<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;

/**
 * General class for an abstracted "Upsert" (UPDATE or INSERT) query operation.
 *
 * This class works like Insert except the rows will be set to the desired
 * values even if the key existed before. It supports both single-field and
 * composite (multi-field) unique or primary key constraints.
 */
abstract class Upsert extends Query implements \Countable {

  use InsertTrait;

  /**
   * The unique or primary key column(s) of the table.
   *
   * @var string[]
   */
  protected $key;

  /**
   * Constructs an Upsert object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Connection object.
   * @param string $table
   *   Name of the table to associate with this query.
   * @param array $options
   *   (optional) An array of database options.
   */
  public function __construct(Connection $connection, $table, array $options = []) {
    parent::__construct($connection, $options);
    $this->table = $table;
  }

  /**
   * Sets the unique / primary key field(s) to be used as condition.
   *
   * @param string|string[] $field
   *   The name of the field, or an array of field names for a composite key.
   *
   * @return $this
   */
  public function key(string|array $field) {
    $this->key = (array) $field;

    return $this;
  }

  /**
   * Preprocesses and validates the query.
   *
   * @return bool
   *   TRUE if the validation was successful, FALSE otherwise.
   *
   * @throws \Drupal\Core\Database\Query\NoUniqueFieldException
   * @throws \Drupal\Core\Database\Query\FieldsOverlapException
   * @throws \Drupal\Core\Database\Query\NoFieldsException
   */
  protected function preExecute() {
    // Confirm that the user set the unique/primary key of the table.
    if (!$this->key) {
      throw new NoUniqueFieldException('There is no unique field specified.');
    }

    // Confirm that the user did not try to specify an identical
    // field and default field.
    if (array_intersect($this->insertFields, $this->defaultFields)) {
      throw new FieldsOverlapException('You may not specify the same field to have a value and a schema-default value.');
    }

    // Don't execute query without fields.
    if (count($this->insertFields) + count($this->defaultFields) == 0) {
      throw new NoFieldsException('There are no fields available to insert with.');
    }

    // If no values have been added, silently ignore this query. This can happen
    // if values are added conditionally, so we don't want to throw an
    // exception.
    return isset($this->insertValues[0]) || $this->insertFields;
  }

  /**
   * Executes the UPSERT operation.
   *
   * @return int
   *   An integer indicating the number of rows affected by the operation. Do
   *   not rely on this value as a precise indication of the actual rows
   *   affected: different database engines return different values.
   */
  public function execute() {
    if (!$this->preExecute()) {
      return NULL;
    }

    $max_placeholder = 0;
    $values = [];
    foreach ($this->insertValues as $insert_values) {
      foreach ($insert_values as $value) {
        $values[':db_insert_placeholder_' . $max_placeholder++] = $value;
      }
    }

    $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions, TRUE);
    try {
      $stmt->execute($values, $this->queryOptions);
      $affected_rows = $stmt->rowCount();
    }
    catch (\Exception $e) {
      $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, $values, $this->queryOptions);
    }

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = [];

    return $affected_rows;
  }

}
