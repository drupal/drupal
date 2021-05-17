<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;

/**
 * General class for an abstracted UPDATE operation.
 *
 * @ingroup database
 */
class Update extends Query implements ConditionInterface {

  use QueryConditionTrait;

  /**
   * The table to update.
   *
   * @var string
   */
  protected $table;

  /**
   * An array of fields that will be updated.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * An array of values to update to.
   *
   * @var array
   */
  protected $arguments = [];

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
  protected $expressionFields = [];

  /**
   * Constructs an Update query object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Connection object.
   * @param string $table
   *   Name of the table to associate with this query.
   * @param array $options
   *   Array of database options.
   */
  public function __construct(Connection $connection, $table, array $options = []) {
    $options['return'] = Database::RETURN_AFFECTED;
    parent::__construct($connection, $options);
    $this->table = $table;

    $this->condition = $this->connection->condition('AND');
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
  public function fields(array $fields) {
    $this->fields = $fields;
    return $this;
  }

  /**
   * Specifies fields to be updated as an expression.
   *
   * Expression fields are cases such as counter=counter+1. This method takes
   * precedence over fields().
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
  public function expression($field, $expression, array $arguments = NULL) {
    $this->expressionFields[$field] = [
      'expression' => $expression,
      'arguments' => $arguments,
    ];

    return $this;
  }

  /**
   * Executes the UPDATE query.
   *
   * @return
   *   The number of rows matched by the update query. This includes rows that
   *   actually didn't have to be updated because the values didn't change.
   */
  public function execute() {
    // Expressions take priority over literal fields, so we process those first
    // and remove any literal fields that conflict.
    $fields = $this->fields;
    $update_values = [];
    foreach ($this->expressionFields as $field => $data) {
      if (!empty($data['arguments'])) {
        $update_values += $data['arguments'];
      }
      if ($data['expression'] instanceof SelectInterface) {
        $data['expression']->compile($this->connection, $this);
        $update_values += $data['expression']->arguments();
      }
      unset($fields[$field]);
    }

    // Because we filter $fields the same way here and in __toString(), the
    // placeholders will all match up properly.
    $max_placeholder = 0;
    foreach ($fields as $value) {
      $update_values[':db_update_placeholder_' . ($max_placeholder++)] = $value;
    }

    if (count($this->condition)) {
      $this->condition->compile($this->connection, $this);
      $update_values = array_merge($update_values, $this->condition->arguments());
    }

    $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions, TRUE);
    try {
      $stmt->execute($update_values, $this->queryOptions);
      return $stmt->rowCount();
    }
    catch (\Exception $e) {
      $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, $update_values, $this->queryOptions);
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

    // Expressions take priority over literal fields, so we process those first
    // and remove any literal fields that conflict.
    $fields = $this->fields;
    $update_fields = [];
    foreach ($this->expressionFields as $field => $data) {
      if ($data['expression'] instanceof SelectInterface) {
        // Compile and cast expression subquery to a string.
        $data['expression']->compile($this->connection, $this);
        $data['expression'] = ' (' . $data['expression'] . ')';
      }
      $update_fields[] = $this->connection->escapeField($field) . '=' . $data['expression'];
      unset($fields[$field]);
    }

    $max_placeholder = 0;
    foreach ($fields as $field => $value) {
      $update_fields[] = $this->connection->escapeField($field) . '=:db_update_placeholder_' . ($max_placeholder++);
    }

    $query = $comments . 'UPDATE {' . $this->connection->escapeTable($this->table) . '} SET ' . implode(', ', $update_fields);

    if (count($this->condition)) {
      $this->condition->compile($this->connection, $this);
      // There is an implicit string cast on $this->condition.
      $query .= "\nWHERE " . $this->condition;
    }

    return $query;
  }

}
