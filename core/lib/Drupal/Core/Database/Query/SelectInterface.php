<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;

/**
 * Interface definition for a Select Query object.
 *
 * @ingroup database
 */
interface SelectInterface extends ConditionInterface, AlterableInterface, ExtendableInterface, PlaceholderInterface {

  /* Alter accessors to expose the query data to alter hooks. */

  /**
   * Returns a reference to the fields array for this query.
   *
   * Because this method returns by reference, alter hooks may edit the fields
   * array directly to make their changes. If just adding fields, however, the
   * use of addField() is preferred.
   *
   * Note that this method must be called by reference as well:
   *
   * @code
   * $fields =& $query->getFields();
   * @endcode
   *
   * @return
   *   A reference to the fields array structure.
   */
  public function &getFields();

  /**
   * Returns a reference to the expressions array for this query.
   *
   * Because this method returns by reference, alter hooks may edit the expressions
   * array directly to make their changes. If just adding expressions, however, the
   * use of addExpression() is preferred.
   *
   * Note that this method must be called by reference as well:
   *
   * @code
   * $fields =& $query->getExpressions();
   * @endcode
   *
   * @return
   *   A reference to the expression array structure.
   */
  public function &getExpressions();

  /**
   * Returns a reference to the order by array for this query.
   *
   * Because this method returns by reference, alter hooks may edit the order-by
   * array directly to make their changes. If just adding additional ordering
   * fields, however, the use of orderBy() is preferred.
   *
   * Note that this method must be called by reference as well:
   *
   * @code
   * $fields =& $query->getOrderBy();
   * @endcode
   *
   * @return
   *   A reference to the expression array structure.
   */
  public function &getOrderBy();

  /**
   * Returns a reference to the group-by array for this query.
   *
   * Because this method returns by reference, alter hooks may edit the group-by
   * array directly to make their changes. If just adding additional grouping
   * fields, however, the use of groupBy() is preferred.
   *
   * Note that this method must be called by reference as well:
   *
   * @code
   * $fields =& $query->getGroupBy();
   * @endcode
   *
   * @return
   *   A reference to the group-by array structure.
   */
  public function &getGroupBy();

  /**
   * Returns a reference to the tables array for this query.
   *
   * Because this method returns by reference, alter hooks may edit the tables
   * array directly to make their changes. If just adding tables, however, the
   * use of the join() methods is preferred.
   *
   * Note that this method must be called by reference as well:
   *
   * @code
   * $fields =& $query->getTables();
   * @endcode
   *
   * @return
   *   A reference to the tables array structure.
   */
  public function &getTables();

  /**
   * Returns a reference to the union queries for this query. This include
   * queries for UNION, UNION ALL, and UNION DISTINCT.
   *
   * Because this method returns by reference, alter hooks may edit the tables
   * array directly to make their changes. If just adding union queries,
   * however, the use of the union() method is preferred.
   *
   * Note that this method must be called by reference as well:
   *
   * @code
   * $fields =& $query->getUnion();
   * @endcode
   *
   * @return
   *   A reference to the union query array structure.
   */
  public function &getUnion();

  /**
   * Escapes characters that work as wildcard characters in a LIKE pattern.
   *
   * @param $string
   *   The string to escape.
   *
   * @return string
   *   The escaped string.
   *
   * @see \Drupal\Core\Database\Connection::escapeLike()
   */
  public function escapeLike($string);

  /**
   * Escapes a field name string.
   *
   * Force all field names to be strictly alphanumeric-plus-underscore.
   * For some database drivers, it may also wrap the field name in
   * database-specific escape characters.
   *
   * @param string $string
   *   An unsanitized field name.
   *
   * @return
   *   The sanitized field name string.
   */
  public function escapeField($string);

  /**
   * Compiles and returns an associative array of the arguments for this prepared statement.
   *
   * @param $queryPlaceholder
   *   When collecting the arguments of a subquery, the main placeholder
   *   object should be passed as this parameter.
   *
   * @return
   *   An associative array of all placeholder arguments for this query.
   */
  public function getArguments(PlaceholderInterface $queryPlaceholder = NULL);

  /* Query building operations */

  /**
   * Sets this query to be DISTINCT.
   *
   * @param $distinct
   *   TRUE to flag this query DISTINCT, FALSE to disable it.
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The called object.
   */
  public function distinct($distinct = TRUE);

  /**
   * Adds a field to the list to be SELECTed.
   *
   * @param $table_alias
   *   The name of the table from which the field comes, as an alias. Generally
   *   you will want to use the return value of join() here to ensure that it is
   *   valid.
   * @param $field
   *   The name of the field.
   * @param $alias
   *   The alias for this field. If not specified, one will be generated
   *   automatically based on the $table_alias and $field. The alias will be
   *   checked for uniqueness, so the requested alias may not be the alias
   *   that is assigned in all cases.
   * @return
   *   The unique alias that was assigned for this field.
   */
  public function addField($table_alias, $field, $alias = NULL);

  /**
   * Add multiple fields from the same table to be SELECTed.
   *
   * This method does not return the aliases set for the passed fields. In the
   * majority of cases that is not a problem, as the alias will be the field
   * name. However, if you do need to know the alias you can call getFields()
   * and examine the result to determine what alias was created. Alternatively,
   * simply use addField() for the few fields you care about and this method for
   * the rest.
   *
   * @param $table_alias
   *   The name of the table from which the field comes, as an alias. Generally
   *   you will want to use the return value of join() here to ensure that it is
   *   valid.
   * @param $fields
   *   An indexed array of fields present in the specified table that should be
   *   included in this query. If not specified, $table_alias.* will be generated
   *   without any aliases.
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The called object.
   */
  public function fields($table_alias, array $fields = []);

  /**
   * Adds an expression to the list of "fields" to be SELECTed.
   *
   * An expression can be any arbitrary string that is valid SQL. That includes
   * various functions, which may in some cases be database-dependent. This
   * method makes no effort to correct for database-specific functions.
   *
   * @param $expression
   *   The expression string. May contain placeholders.
   * @param $alias
   *   The alias for this expression. If not specified, one will be generated
   *   automatically in the form "expression_#". The alias will be checked for
   *   uniqueness, so the requested alias may not be the alias that is assigned
   *   in all cases.
   * @param $arguments
   *   Any placeholder arguments needed for this expression.
   * @return
   *   The unique alias that was assigned for this expression.
   */
  public function addExpression($expression, $alias = NULL, $arguments = []);

  /**
   * Default Join against another table in the database.
   *
   * This method is a convenience method for innerJoin().
   *
   * @param $table
   *   The table against which to join. May be a string or another SelectQuery
   *   object. If a query object is passed, it will be used as a subselect.
   *   Unless the table name starts with the database / schema name and a dot
   *   it will be prefixed.
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table. The token %alias can be used in this string to be replaced with
   *   the actual alias. This is useful when $alias is modified by the database
   *   system, for example, when joining the same table more than once.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function join($table, $alias = NULL, $condition = NULL, $arguments = []);

  /**
   * Inner Join against another table in the database.
   *
   * @param $table
   *   The table against which to join. May be a string or another SelectQuery
   *   object. If a query object is passed, it will be used as a subselect.
   *   Unless the table name starts with the database / schema name and a dot
   *   it will be prefixed.
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table. The token %alias can be used in this string to be replaced with
   *   the actual alias. This is useful when $alias is modified by the database
   *   system, for example, when joining the same table more than once.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function innerJoin($table, $alias = NULL, $condition = NULL, $arguments = []);

  /**
   * Left Outer Join against another table in the database.
   *
   * @param $table
   *   The table against which to join. May be a string or another SelectQuery
   *   object. If a query object is passed, it will be used as a subselect.
   *   Unless the table name starts with the database / schema name and a dot
   *   it will be prefixed.
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table. The token %alias can be used in this string to be replaced with
   *   the actual alias. This is useful when $alias is modified by the database
   *   system, for example, when joining the same table more than once.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = []);

  /**
   * Right Outer Join against another table in the database.
   *
   * @param $table
   *   The table against which to join. May be a string or another SelectQuery
   *   object. If a query object is passed, it will be used as a subselect.
   *   Unless the table name starts with the database / schema name and a dot
   *   it will be prefixed.
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table. The token %alias can be used in this string to be replaced with
   *   the actual alias. This is useful when $alias is modified by the database
   *   system, for example, when joining the same table more than once.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   *
   * @deprecated as of Drupal 8.1.x, will be removed in Drupal 9.0.0. Instead,
   *   change the query to use leftJoin(). For instance:
   *   db_query('A')->rightJoin('B') is identical to
   *   db_query('B')->leftJoin('A'). This functionality has been deprecated
   *   because SQLite does not support it.
   */
  public function rightJoin($table, $alias = NULL, $condition = NULL, $arguments = []);

  /**
   * Join against another table in the database.
   *
   * This method does the "hard" work of queuing up a table to be joined against.
   * In some cases, that may include dipping into the Schema API to find the necessary
   * fields on which to join.
   *
   * @param $type
   *   The type of join. Typically one one of INNER, LEFT OUTER, and RIGHT OUTER.
   * @param $table
   *   The table against which to join. May be a string or another SelectQuery
   *   object. If a query object is passed, it will be used as a subselect.
   *   Unless the table name starts with the database / schema name and a dot
   *   it will be prefixed.
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table. If omitted,
   *   one will be dynamically generated.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table. The token %alias can be used in this string to be replaced with
   *   the actual alias. This is useful when $alias is modified by the database
   *   system, for example, when joining the same table more than once.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = []);

  /**
   * Orders the result set by a given field.
   *
   * If called multiple times, the query will order by each specified field in the
   * order this method is called.
   *
   * If the query uses DISTINCT or GROUP BY conditions, fields or expressions
   * that are used for the order must be selected to be compatible with some
   * databases like PostgreSQL. The PostgreSQL driver can handle simple cases
   * automatically but it is suggested to explicitly specify them. Additionally,
   * when ordering on an alias, the alias must be added before orderBy() is
   * called.
   *
   * @param $field
   *   The field on which to order. The field is escaped for security so only
   *   valid field and alias names are possible. To order by an expression, add
   *   the expression with addExpression() first and then use the alias to order
   *   on.
   *
   *   Example:
   *   <code>
   *   $query->addExpression('SUBSTRING(thread, 1, (LENGTH(thread) - 1))', 'order_field');
   *   $query->orderBy('order_field', 'ASC');
   *   </code>
   * @param $direction
   *   The direction to sort. Legal values are "ASC" and "DESC". Any other value
   *   will be converted to "ASC".
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The called object.
   */
  public function orderBy($field, $direction = 'ASC');

  /**
   * Orders the result set by a random value.
   *
   * This may be stacked with other orderBy() calls. If so, the query will order
   * by each specified field, including this one, in the order called. Although
   * this method may be called multiple times on the same query, doing so
   * is not particularly useful.
   *
   * Note: The method used by most drivers may not scale to very large result
   * sets. If you need to work with extremely large data sets, you may create
   * your own database driver by subclassing off of an existing driver and
   * implementing your own randomization mechanism. See
   *
   * http://jan.kneschke.de/projects/mysql/order-by-rand/
   *
   * for an example of such an alternate sorting mechanism.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The called object
   */
  public function orderRandom();

  /**
   * Restricts a query to a given range in the result set.
   *
   * If this method is called with no parameters, will remove any range
   * directives that have been set.
   *
   * @param $start
   *   The first record from the result set to return. If NULL, removes any
   *   range directives that are set.
   * @param $length
   *   The number of records to return from the result set.
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The called object.
   */
  public function range($start = NULL, $length = NULL);

  /**
   * Add another Select query to UNION to this one.
   *
   * Union queries consist of two or more queries whose
   * results are effectively concatenated together. Queries
   * will be UNIONed in the order they are specified, with
   * this object's query coming first. Duplicate columns will
   * be discarded. All forms of UNION are supported, using
   * the second '$type' argument.
   *
   * Note: All queries UNIONed together must have the same
   * field structure, in the same order. It is up to the
   * caller to ensure that they match properly. If they do
   * not, an SQL syntax error will result.
   *
   * @param $query
   *   The query to UNION to this query.
   * @param $type
   *   The type of UNION to add to the query. Defaults to plain
   *   UNION.
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The called object.
   */
  public function union(SelectInterface $query, $type = '');

  /**
   * Groups the result set by the specified field.
   *
   * @param $field
   *   The field on which to group. This should be the field as aliased.
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The called object.
   */
  public function groupBy($field);

  /**
   * Get the equivalent COUNT query of this query as a new query object.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A new SelectQuery object with no fields or expressions besides COUNT(*).
   */
  public function countQuery();

  /**
   * Indicates if preExecute() has already been called on that object.
   *
   * @return
   *   TRUE is this query has already been prepared, FALSE otherwise.
   */
  public function isPrepared();

  /**
   * Generic preparation and validation for a SELECT query.
   *
   * @return
   *   TRUE if the validation was successful, FALSE if not.
   */
  public function preExecute(SelectInterface $query = NULL);

  /**
   * Runs the query against the database.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   A prepared statement, or NULL if the query is not valid.
   */
  public function execute();

  /**
   * Helper function to build most common HAVING conditional clauses.
   *
   * This method can take a variable number of parameters. If called with two
   * parameters, they are taken as $field and $value with $operator having a value
   * of IN if $value is an array and = otherwise.
   *
   * @param $field
   *   The name of the field to check. If you would like to add a more complex
   *   condition involving operators or functions, use having().
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For more
   *   complex options, it is an array. The meaning of each element in the array is
   *   dependent on the $operator.
   * @param $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more complex
   *   options such as IN, LIKE, or BETWEEN. Defaults to IN if $value is an array
   *   = otherwise.
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The called object.
   */
  public function havingCondition($field, $value = NULL, $operator = NULL);

  /**
   * Gets a list of all conditions in the HAVING clause.
   *
   * This method returns by reference. That allows alter hooks to access the
   * data structure directly and manipulate it before it gets compiled.
   *
   * @return array
   *   An array of conditions.
   *
   * @see \Drupal\Core\Database\Query\ConditionInterface::conditions()
   */
  public function &havingConditions();

  /**
   * Gets a list of all values to insert into the HAVING clause.
   *
   * @return array
   *   An associative array of placeholders and values.
   */
  public function havingArguments();

  /**
   * Adds an arbitrary HAVING clause to the query.
   *
   * @param $snippet
   *   A portion of a HAVING clause as a prepared statement. It must use named
   *   placeholders, not ? placeholders.
   * @param $args
   *   (optional) An associative array of arguments.
   *
   * @return $this
   */
  public function having($snippet, $args = []);

  /**
   * Compiles the HAVING clause for later retrieval.
   *
   * @param $connection
   *   The database connection for which to compile the clause.
   */
  public function havingCompile(Connection $connection);

  /**
   * Sets a condition in the HAVING clause that the specified field be NULL.
   *
   * @param $field
   *   The name of the field to check.
   *
   * @return $this
   */
  public function havingIsNull($field);

  /**
   * Sets a condition in the HAVING clause that the specified field be NOT NULL.
   *
   * @param $field
   *   The name of the field to check.
   *
   * @return $this
   */
  public function havingIsNotNull($field);

  /**
   * Sets a HAVING condition that the specified subquery returns values.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $select
   *   The subquery that must contain results.
   *
   * @return $this
   */
  public function havingExists(SelectInterface $select);

  /**
   * Sets a HAVING condition that the specified subquery returns no values.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $select
   *   The subquery that must contain results.
   *
   * @return $this
   */
  public function havingNotExists(SelectInterface $select);

  /**
   * Clone magic method.
   *
   * Select queries have dependent objects that must be deep-cloned.  The
   * connection object itself, however, should not be cloned as that would
   * duplicate the connection itself.
   */
  public function __clone();

  /**
   * Add FOR UPDATE to the query.
   *
   * FOR UPDATE prevents the rows retrieved by the SELECT statement from being
   * modified or deleted by other transactions until the current transaction
   * ends. Other transactions that attempt UPDATE, DELETE, or SELECT FOR UPDATE
   * of these rows will be blocked until the current transaction ends.
   *
   * @param $set
   *   IF TRUE, FOR UPDATE will be added to the query, if FALSE then it won't.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The called object.
   */
  public function forUpdate($set = TRUE);

  /**
   * Returns a string representation of how the query will be executed in SQL.
   *
   * @return string
   *   The Select Query object expressed as a string.
   */
  public function __toString();

}
