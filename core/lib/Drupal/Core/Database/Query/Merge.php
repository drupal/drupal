<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;

/**
 * General class for an abstracted MERGE query operation.
 *
 * An ANSI SQL:2003 compatible database would run the following query:
 *
 * @code
 * MERGE INTO table_name_1 USING table_name_2 ON (condition)
 *   WHEN MATCHED THEN
 *   UPDATE SET column1 = value1 [, column2 = value2 ...]
 *   WHEN NOT MATCHED THEN
 *   INSERT (column1 [, column2 ...]) VALUES (value1 [, value2 ...
 * @endcode
 *
 * Other databases (most notably MySQL, PostgreSQL and SQLite) will emulate
 * this statement by running a SELECT and then INSERT or UPDATE.
 *
 * By default, the two table names are identical and they are passed into the
 * the constructor. table_name_2 can be specified by the
 * MergeQuery::conditionTable() method. It can be either a string or a
 * subquery.
 *
 * The condition is built exactly like SelectQuery or UpdateQuery conditions,
 * the UPDATE query part is built similarly like an UpdateQuery and finally the
 * INSERT query part is built similarly like an InsertQuery. However, both
 * UpdateQuery and InsertQuery has a fields method so
 * MergeQuery::updateFields() and MergeQuery::insertFields() needs to be called
 * instead. MergeQuery::fields() can also be called which calls both of these
 * methods as the common case is to use the same column-value pairs for both
 * INSERT and UPDATE. However, this is not mandatory. Another convenient
 * wrapper is MergeQuery::key() which adds the same column-value pairs to the
 * condition and the INSERT query part.
 *
 * Several methods (key(), fields(), insertFields()) can be called to set a
 * key-value pair for the INSERT query part. Subsequent calls for the same
 * fields override the earlier ones. The same is true for UPDATE and key(),
 * fields() and updateFields().
 */
class Merge extends Query implements ConditionInterface {

  use QueryConditionTrait;

  /**
   * Returned by execute() if an INSERT query has been executed.
   */
  const STATUS_INSERT = 1;

  /**
   * Returned by execute() if an UPDATE query has been executed.
   */
  const STATUS_UPDATE = 2;

  /**
   * The table to be used for INSERT and UPDATE.
   *
   * @var string
   */
  protected $table;

  /**
   * The table or subquery to be used for the condition.
   *
   * @var string
   */
  protected $conditionTable;

  /**
   * An array of fields on which to insert.
   *
   * @var array
   */
  protected $insertFields = [];

  /**
   * An array of fields which should be set to their database-defined defaults.
   *
   * Used on INSERT.
   *
   * @var array
   */
  protected $defaultFields = [];

  /**
   * An array of values to be inserted.
   *
   * @var string
   */
  protected $insertValues = [];

  /**
   * An array of fields that will be updated.
   *
   * @var array
   */
  protected $updateFields = [];

  /**
   * Array of fields to update to an expression in case of a duplicate record.
   *
   * @var array
   *
   * This variable is a nested array in the following format:
   * @code
   * <some field> => [
   *  'condition' => <condition to execute, as a string>,
   *  'arguments' => <array of arguments for condition, or NULL for none>,
   * ];
   * @endcode
   */
  protected $expressionFields = [];

  /**
   * Flag indicating whether an UPDATE is necessary.
   *
   * @var bool
   */
  protected $needsUpdate = FALSE;

  /**
   * Constructs a Merge object.
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
    $this->conditionTable = $table;
    $this->condition = $this->connection->condition('AND');
  }

  /**
   * Sets the table or subquery to be used for the condition.
   *
   * @param $table
   *   The table name or the subquery to be used. Use a Select query object to
   *   pass in a subquery.
   *
   * @return $this
   *   The called object.
   */
  protected function conditionTable($table) {
    $this->conditionTable = $table;
    return $this;
  }

  /**
   * Adds a set of field->value pairs to be updated.
   *
   * @param $fields
   *   An associative array of fields to write into the database. The array keys
   *   are the field names and the values are the values to which to set them.
   *
   * @return $this
   *   The called object.
   */
  public function updateFields(array $fields) {
    $this->updateFields = $fields;
    $this->needsUpdate = TRUE;
    return $this;
  }

  /**
   * Specifies fields to be updated as an expression.
   *
   * Expression fields are cases such as counter = counter + 1. This method
   * takes precedence over MergeQuery::updateFields() and its wrappers,
   * MergeQuery::key() and MergeQuery::fields().
   *
   * @param $field
   *   The field to set.
   * @param $expression
   *   The field will be set to the value of this expression. This parameter
   *   may include named placeholders.
   * @param $arguments
   *   If specified, this is an array of key/value pairs for named placeholders
   *   corresponding to the expression.
   *
   * @return $this
   *   The called object.
   */
  public function expression($field, $expression, ?array $arguments = NULL) {
    $this->expressionFields[$field] = [
      'expression' => $expression,
      'arguments' => $arguments,
    ];
    $this->needsUpdate = TRUE;
    return $this;
  }

  /**
   * Adds a set of field->value pairs to be inserted.
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
   * @return $this
   *   The called object.
   */
  public function insertFields(array $fields, array $values = []) {
    if ($values) {
      $fields = array_combine($fields, $values);
    }
    $this->insertFields = $fields;
    return $this;
  }

  /**
   * Specifies fields for which the database-defaults should be used.
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
   * @return $this
   *   The called object.
   */
  public function useDefaults(array $fields) {
    $this->defaultFields = $fields;
    return $this;
  }

  /**
   * Sets common field-value pairs in the INSERT and UPDATE query parts.
   *
   * This method should only be called once. It may be called either
   * with a single associative array or two indexed arrays. If called
   * with an associative array, the keys are taken to be the fields
   * and the values are taken to be the corresponding values to set.
   * If called with two arrays, the first array is taken as the fields
   * and the second array is taken as the corresponding values.
   *
   * @param $fields
   *   An array of fields to insert, or an associative array of fields and
   *   values. The keys of the array are taken to be the fields and the values
   *   are taken to be corresponding values to insert.
   * @param $values
   *   An array of values to set into the database. The values must be
   *   specified in the same order as the $fields array.
   *
   * @return $this
   *   The called object.
   */
  public function fields(array $fields, array $values = []) {
    if ($values) {
      $fields = array_combine($fields, $values);
    }
    foreach ($fields as $key => $value) {
      $this->insertFields[$key] = $value;
      $this->updateFields[$key] = $value;
    }
    $this->needsUpdate = TRUE;
    return $this;
  }

  /**
   * Sets the key fields to be used as conditions for this query.
   *
   * This method should only be called once. It may be called either
   * with a single associative array or two indexed arrays. If called
   * with an associative array, the keys are taken to be the fields
   * and the values are taken to be the corresponding values to set.
   * If called with two arrays, the first array is taken as the fields
   * and the second array is taken as the corresponding values.
   *
   * The fields are copied to the condition of the query and the INSERT part.
   * If no other method is called, the UPDATE will become a no-op.
   *
   * @param $fields
   *   An array of fields to set, or an associative array of fields and values.
   * @param $values
   *   An array of values to set into the database. The values must be
   *   specified in the same order as the $fields array.
   *
   * @return $this
   */
  public function keys(array $fields, array $values = []) {
    if ($values) {
      $fields = array_combine($fields, $values);
    }
    foreach ($fields as $key => $value) {
      $this->insertFields[$key] = $value;
      $this->condition($key, $value);
    }
    return $this;
  }

  /**
   * Sets a single key field to be used as condition for this query.
   *
   * Same as \Drupal\Core\Database\Query\Merge::keys() but offering a signature
   * that is more natural for the case of a single key.
   *
   * @param string $field
   *   The name of the field to set.
   * @param mixed $value
   *   The value to set into the database.
   *
   * @return $this
   *
   * @see \Drupal\Core\Database\Query\Merge::keys()
   */
  public function key($field, $value = NULL) {
    assert(is_string($field));
    $this->keys([$field => $value]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // In the degenerate case, there is no string-able query as this operation
    // is potentially two queries.
    throw new \BadMethodCallException('The merge query can not be converted to a string');
  }

  /**
   * Executes the merge database query.
   *
   * @return int|null
   *   One of the following values:
   *   - Merge::STATUS_INSERT: If the entry does not already exist,
   *     and an INSERT query is executed.
   *   - Merge::STATUS_UPDATE: If the entry already exists,
   *     and an UPDATE query is executed.
   *
   * @throws \Drupal\Core\Database\Query\InvalidMergeQueryException
   *   When there are no conditions found to merge.
   */
  public function execute() {
    if (!count($this->condition)) {
      throw new InvalidMergeQueryException('Invalid merge query: no conditions');
    }

    $select = $this->connection->select($this->conditionTable)
      ->condition($this->condition);
    $select->addExpression('1');

    if (!$select->execute()->fetchField()) {
      try {
        $insert = $this->connection->insert($this->table)->fields($this->insertFields);
        if ($this->defaultFields) {
          $insert->useDefaults($this->defaultFields);
        }
        $insert->execute();
        return self::STATUS_INSERT;
      }
      catch (IntegrityConstraintViolationException $e) {
        // The insert query failed, maybe it's because a racing insert query
        // beat us in inserting the same row. Retry the select query, if it
        // returns a row, ignore the error and continue with the update
        // query below.
        if (!$select->execute()->fetchField()) {
          throw $e;
        }
      }
    }

    if ($this->needsUpdate) {
      $update = $this->connection->update($this->table)
        ->fields($this->updateFields)
        ->condition($this->condition);
      if ($this->expressionFields) {
        foreach ($this->expressionFields as $field => $data) {
          $update->expression($field, $data['expression'], $data['arguments']);
        }
      }
      $update->execute();
      return self::STATUS_UPDATE;
    }
    return NULL;
  }

}
