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
   * Explicit updates to be done in case of insert failure.
   *
   * If the insert part of the upsert fails, the default behavior is to update
   * the existing record with the values passed in. However, in some cases you
   * may want to see what is the value of a column of the existing record, and
   * manipulate that - typically, counters or the like. This array contains
   * such overrides.
   *
   * @var array<string,\Drupal\Core\Database\Query\UpsertUpdateExpression>
   */
  protected array $updateExpressions = [];

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
   * Adds a set of column->value pairs to be inserted.
   *
   * This method may only be called once. Calling it a second time will be
   * ignored. To queue up multiple sets of values to be inserted at once,
   * use the values() method.
   *
   * @param array<string|int,string|\Drupal\Core\Database\Query\UpsertUpdateExpression>|array<string,string|int|float|bool|array> $fields
   *   An array of columns on which to insert. This array may be indexed or
   *   associative. If indexed, the array is taken to be the list of columns.
   *   If associative, the keys of the array are taken to be the columns and
   *   the values are taken to be corresponding values to insert. If a
   *   $values argument is provided, $fields must be indexed.
   *   In the indexed form, an entry may map a column name to an
   *   UpsertUpdateExpression. When the row already exists, that expression is
   *   run for the column in place of overwriting it with the insert value.
   * @param list<string|int|float|bool|array> $values
   *   (optional) An array of values to insert into the database. The values
   *   must be specified in the same order as the $fields array.
   *
   * @return $this
   *   The called object.
   *
   * @throws \InvalidArgumentException
   *   If an update expression is not keyed by its column name, or if a column
   *   name maps to an insert value while the $fields array is not fully
   *   associative.
   */
  public function fields(array $fields, array $values = []): static {
    if (empty($this->insertFields)) {
      if (empty($values)) {
        $isFieldsAssociative = TRUE;
        array_walk($fields, function (mixed $value, int|string $key) use (&$isFieldsAssociative): void {
          if (is_numeric($key) || (is_string($key) && $value instanceof UpsertUpdateExpression)) {
            $isFieldsAssociative = FALSE;
          }
        });
        if ($isFieldsAssociative) {
          $values = array_values($fields);
          $fields = array_keys($fields);
        }
      }
      foreach ($fields as $key => $def) {
        if ($def instanceof UpsertUpdateExpression) {
          if (!is_string($key)) {
            throw new \InvalidArgumentException('An update expression must be keyed by its column name.');
          }
          $this->insertFields[] = $key;
          $this->updateExpressions[$key] = $def;
        }
        elseif (is_string($key)) {
          throw new \InvalidArgumentException(sprintf('The entry for "%s" must be a column name or an update expression keyed by column name. Pass insert values via the $values argument or the values() method.', $key));
        }
        else {
          $this->insertFields[] = $def;
        }
      }
      if (!empty($values)) {
        $this->values($values);
      }
    }

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
    foreach ($this->updateExpressions as $update_expression) {
      foreach ($update_expression->arguments as $argument => $value) {
        $values[$argument] = $value;
      }
    }

    $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions, TRUE);
    try {
      $stmt->execute($values, $this->queryOptions);
      // Re-initialize the values array so that we can re-use this query.
      $this->insertValues = [];
      return $stmt->rowCount();
    }
    catch (\Exception $e) {
      $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, $values, $this->queryOptions);
    }
  }

}
