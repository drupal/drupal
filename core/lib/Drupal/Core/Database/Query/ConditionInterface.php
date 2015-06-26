<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Query\ConditionInterface.
 */

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;

/**
 * Interface for a conditional clause in a query.
 */
interface ConditionInterface {

  /**
   * Helper function: builds the most common conditional clauses.
   *
   * This method can take a variable number of parameters. If called with two
   * parameters, they are taken as $field and $value with $operator having a
   * value of =.
   *
   * Do not use this method to test for NULL values. Instead, use
   * QueryConditionInterface::isNull() or QueryConditionInterface::isNotNull().
   *
   * Drupal considers LIKE case insensitive and the following is often used
   * to tell the database that case insensitive equivalence is desired:
   * @code
   * db_select('users')
   *  ->condition('name', db_like($name), 'LIKE')
   * @endcode
   * Use 'LIKE BINARY' instead of 'LIKE' for case sensitive queries.
   *
   * Note: When using MySQL, the exact behavior also depends on the used
   * collation. if the field is set to binary, then a LIKE condition will also
   * be case sensitive and when a case insensitive collation is used, the =
   * operator will also be case insensitive.
   *
   * @param $field
   *   The name of the field to check. If you would like to add a more complex
   *   condition involving operators or functions, use where().
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar.
   *   For more complex options, it is an array. The meaning of each element in
   *   the array is dependent on the $operator.
   * @param $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more
   *   complex options such as IN, LIKE, LIKE BINARY, or BETWEEN. Defaults to =.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The called object.
   *
   * @see \Drupal\Core\Database\Query\ConditionInterface::isNull()
   * @see \Drupal\Core\Database\Query\ConditionInterface::isNotNull()
   */
  public function condition($field, $value = NULL, $operator = '=');

  /**
   * Adds an arbitrary WHERE clause to the query.
   *
   * @param $snippet
   *   A portion of a WHERE clause as a prepared statement. It must use named
   *   placeholders, not ? placeholders.
   * @param $args
   *   An associative array of arguments.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The called object.
   */
  public function where($snippet, $args = array());

  /**
   * Sets a condition that the specified field be NULL.
   *
   * @param $field
   *   The name of the field to check.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The called object.
   */
  public function isNull($field);

  /**
   * Sets a condition that the specified field be NOT NULL.
   *
   * @param $field
   *   The name of the field to check.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The called object.
   */
  public function isNotNull($field);

  /**
   * Sets a condition that the specified subquery returns values.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $select
   *   The subquery that must contain results.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The called object.
   */
  public function exists(SelectInterface $select);

  /**
   * Sets a condition that the specified subquery returns no values.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $select
   *   The subquery that must not contain results.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The called object.
   */
  public function notExists(SelectInterface $select);

  /**
   * Gets a complete list of all conditions in this conditional clause.
   *
   * This method returns by reference. That allows alter hooks to access the
   * data structure directly and manipulate it before it gets compiled.
   *
   * The data structure that is returned is an indexed array of entries, where
   * each entry looks like the following:
   * @code
   * array(
   *   'field' => $field,
   *   'value' => $value,
   *   'operator' => $operator,
   * );
   * @endcode
   *
   * In the special case that $operator is NULL, the $field is taken as a raw
   * SQL snippet (possibly containing a function) and $value is an associative
   * array of placeholders for the snippet.
   *
   * There will also be a single array entry of #conjunction, which is the
   * conjunction that will be applied to the array, such as AND.
   */
  public function &conditions();

  /**
   * Gets a complete list of all values to insert into the prepared statement.
   *
   * @return
   *   An associative array of placeholders and values.
   */
  public function arguments();

  /**
   * Compiles the saved conditions for later retrieval.
   *
   * This method does not return anything, but simply prepares data to be
   * retrieved via __toString() and arguments().
   *
   * @param $connection
   *   The database connection for which to compile the conditionals.
   * @param $queryPlaceholder
   *   The query this condition belongs to. If not given, the current query is
   *   used.
   */
  public function compile(Connection $connection, PlaceholderInterface $queryPlaceholder);

  /**
   * Check whether a condition has been previously compiled.
   *
   * @return
   *   TRUE if the condition has been previously compiled.
   */
  public function compiled();

  /**
   * Creates an object holding a group of conditions.
   *
   * See andConditionGroup() and orConditionGroup() for more.
   *
   * @param $conjunction
   *   - AND (default): this is the equivalent of andConditionGroup().
   *   - OR: this is the equivalent of andConditionGroup().
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   An object holding a group of conditions.
   */
  public function conditionGroupFactory($conjunction = 'AND');

  /**
   * Creates a new group of conditions ANDed together.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   */
  public function andConditionGroup();

  /**
   * Creates a new group of conditions ORed together.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   */
  public function orConditionGroup();
}
