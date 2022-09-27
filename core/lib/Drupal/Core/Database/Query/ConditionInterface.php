<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;

/**
 * Interface for a conditional clause in a query.
 */
interface ConditionInterface {

  /**
   * Helper function: builds the most common conditional clauses.
   *
   * This method takes 1 to 3 parameters.
   *
   * If called with 1 parameter, it should be a ConditionInterface that in
   * itself forms a valid where clause. Use e.g. to build clauses with nested
   * ANDs and ORs.
   *
   * If called with 2 parameters, they are taken as $field and $value with
   * $operator having a value of =.
   *
   * Do not use this method to test for NULL values. Instead, use
   * QueryConditionInterface::isNull() or QueryConditionInterface::isNotNull().
   *
   * To improve readability, the operators EXISTS and NOT EXISTS have their own
   * utility method defined.
   *
   * Drupal considers LIKE case insensitive and the following is often used
   * to tell the database that case insensitive equivalence is desired:
   * @code
   * \Drupal::database()->select('users')
   *  ->condition('name', $injected_connection->escapeLike($name), 'LIKE')
   * @endcode
   * Use 'LIKE BINARY' instead of 'LIKE' for case sensitive queries.
   *
   * Note: When using MySQL, the exact behavior also depends on the used
   * collation. if the field is set to binary, then a LIKE condition will also
   * be case sensitive and when a case insensitive collation is used, the =
   * operator will also be case insensitive.
   *
   * @param string|\Drupal\Core\Database\Query\ConditionInterface $field
   *   The name of the field to check. This can also be QueryConditionInterface
   *   in itself. Use where(), if you would like to add a more complex condition
   *   involving operators or functions, or an already compiled condition.
   * @param string|array|\Drupal\Core\Database\Query\SelectInterface|null $value
   *   The value to test the field against. In most cases, and depending on the
   *   operator, this will be a scalar or an array. As SQL accepts select
   *   queries on any place where a scalar value or set is expected, $value may
   *   also be a SelectInterface or an array of SelectInterfaces. If $operator
   *   is a unary operator, e.g. IS NULL, $value will be ignored and should be
   *   null. If the operator requires a subquery, e.g. EXISTS, the $field will
   *   be ignored and $value should be a SelectInterface object.
   * @param string|null $operator
   *   The operator to use. Supported for all supported databases are at least:
   *   - The comparison operators =, <>, <, <=, >, >=.
   *   - The operators (NOT) BETWEEN, (NOT) IN, (NOT) EXISTS, (NOT) LIKE.
   *   Other operators (e.g. LIKE, BINARY) may or may not work. Defaults to =.
   *
   * @return $this
   *   The called object.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   *   If passed invalid arguments, such as an empty array as $value.
   *
   * @see \Drupal\Core\Database\Query\ConditionInterface::isNull()
   * @see \Drupal\Core\Database\Query\ConditionInterface::isNotNull()
   * @see \Drupal\Core\Database\Query\ConditionInterface::exists()
   * @see \Drupal\Core\Database\Query\ConditionInterface::notExist()
   * @see \Drupal\Core\Database\Query\ConditionInterface::where()
   */
  public function condition($field, $value = NULL, $operator = '=');

  /**
   * Adds an arbitrary WHERE clause to the query.
   *
   * @param string $snippet
   *   A portion of a WHERE clause as a prepared statement. It must use named
   *   placeholders, not ? placeholders. The caller is responsible for providing
   *   unique placeholders that do not interfere with the placeholders generated
   *   by this QueryConditionInterface object.
   * @param array $args
   *   An associative array of arguments keyed by the named placeholders.
   *
   * @return $this
   *   The called object.
   */
  public function where($snippet, $args = []);

  /**
   * Sets a condition that the specified field be NULL.
   *
   * @param string|\Drupal\Core\Database\Query\SelectInterface $field
   *   The name of the field or a subquery to check.
   *
   * @return $this
   *   The called object.
   */
  public function isNull($field);

  /**
   * Sets a condition that the specified field be NOT NULL.
   *
   * @param string|\Drupal\Core\Database\Query\SelectInterface $field
   *   The name of the field or a subquery to check.
   *
   * @return $this
   *   The called object.
   */
  public function isNotNull($field);

  /**
   * Sets a condition that the specified subquery returns values.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $select
   *   The subquery that must contain results.
   *
   * @return $this
   *   The called object.
   */
  public function exists(SelectInterface $select);

  /**
   * Sets a condition that the specified subquery returns no values.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $select
   *   The subquery that must not contain results.
   *
   * @return $this
   *   The called object.
   */
  public function notExists(SelectInterface $select);

  /**
   * Sets a condition that is always false.
   *
   * @return $this
   */
  public function alwaysFalse();

  /**
   * Gets the, possibly nested, list of conditions in this conditional clause.
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
   *
   * @return array
   *   The, possibly nested, list of all conditions (by reference).
   */
  public function &conditions();

  /**
   * Gets a complete list of all values to insert into the prepared statement.
   *
   * @return array
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
   * @return bool
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
   *   - OR: this is the equivalent of orConditionGroup().
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
