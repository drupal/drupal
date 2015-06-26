<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Query\Merge.
 */

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Database;
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
   */
  protected $conditionTable;

  /**
   * An array of fields on which to insert.
   *
   * @var array
   */
  protected $insertFields = array();

  /**
   * An array of fields which should be set to their database-defined defaults.
   *
   * Used on INSERT.
   *
   * @var array
   */
  protected $defaultFields = array();

  /**
   * An array of values to be inserted.
   *
   * @var string
   */
  protected $insertValues = array();

  /**
   * An array of fields that will be updated.
   *
   * @var array
   */
  protected $updateFields = array();

  /**
   * Array of fields to update to an expression in case of a duplicate record.
   *
   * This variable is a nested array in the following format:
   * @code
   * <some field> => array(
   *  'condition' => <condition to execute, as a string>,
   *  'arguments' => <array of arguments for condition, or NULL for none>,
   * );
   * @endcode
   *
   * @var array
   */
  protected $expressionFields = array();

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
  public function __construct(Connection $connection, $table, array $options = array()) {
    $options['return'] = Database::RETURN_AFFECTED;
    parent::__construct($connection, $options);
    $this->table = $table;
    $this->conditionTable = $table;
    $this->condition = new Condition('AND');
  }

  /**
   * Sets the table or subquery to be used for the condition.
   *
   * @param $table
   *   The table name or the subquery to be used. Use a Select query object to
   *   pass in a subquery.
   *
   * @return \Drupal\Core\Database\Query\Merge
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
   * @return \Drupal\Core\Database\Query\Merge
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
   * takes precedence over MergeQuery::updateFields() and it's wrappers,
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
   * @return \Drupal\Core\Database\Query\Merge
   *   The called object.
   */
  public function expression($field, $expression, array $arguments = NULL) {
    $this->expressionFields[$field] = array(
      'expression' => $expression,
      'arguments' => $arguments,
    );
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
   * @return \Drupal\Core\Database\Query\Merge
   *   The called object.
   */
  public function insertFields(array $fields, array $values = array()) {
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
   * @return \Drupal\Core\Database\Query\Merge
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
   * @return \Drupal\Core\Database\Query\Merge
   *   The called object.
   */
  public function fields(array $fields, array $values = array()) {
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
  public function keys(array $fields, array $values = array()) {
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
    // @todo D9: Remove this backwards-compatibility shim.
    if (is_array($field)) {
      $this->keys($field, isset($value) ? $value : array());
    }
    else {
      $this->keys(array($field => $value));
    }
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::condition().
   */
  public function condition($field, $value = NULL, $operator = '=') {
    $this->condition->condition($field, $value, $operator);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::isNull().
   */
  public function isNull($field) {
    $this->condition->isNull($field);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::isNotNull().
   */
  public function isNotNull($field) {
    $this->condition->isNotNull($field);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::exists().
   */
  public function exists(SelectInterface $select) {
    $this->condition->exists($select);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::notExists().
   */
  public function notExists(SelectInterface $select) {
    $this->condition->notExists($select);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::conditions().
   */
  public function &conditions() {
    return $this->condition->conditions();
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::arguments().
   */
  public function arguments() {
    return $this->condition->arguments();
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::where().
   */
  public function where($snippet, $args = array()) {
    $this->condition->where($snippet, $args);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::compile().
   */
  public function compile(Connection $connection, PlaceholderInterface $queryPlaceholder) {
    return $this->condition->compile($connection, $queryPlaceholder);
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::compiled().
   */
  public function compiled() {
    return $this->condition->compiled();
  }

  /**
   * Implements PHP magic __toString method to convert the query to a string.
   *
   * In the degenerate case, there is no string-able query as this operation
   * is potentially two queries.
   *
   * @return string
   *   The prepared query statement.
   */
  public function __toString() {
  }

  public function execute() {
    // Default options for merge queries.
    $this->queryOptions += array(
      'throw_exception' => TRUE,
    );

    try {
      if (!count($this->condition)) {
        throw new InvalidMergeQueryException(t('Invalid merge query: no conditions'));
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
    }
    catch (\Exception $e) {
      if ($this->queryOptions['throw_exception']) {
        throw $e;
      }
      else {
        return NULL;
      }
    }
  }
}
