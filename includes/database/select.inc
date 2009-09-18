<?php
// $Id$

/**
 * @ingroup database
 * @{
 */

/**
 * Interface for extendable query objects.
 *
 * "Extenders" follow the "Decorator" OOP design pattern.  That is, they wrap
 * and "decorate" another object.  In our case, they implement the same interface
 * as select queries and wrap a select query, to which they delegate almost all
 * operations.  Subclasses of this class may implement additional methods or
 * override existing methods as appropriate.  Extenders may also wrap other
 * extender objects, allowing for arbitrarily complex "enhanced" queries.
 */
interface QueryExtendableInterface {

  /**
   * Enhance this object by wrapping it in an extender object.
   *
   * @param $extender_name
   *   The base name of the extending class.  The base name will be checked
   *   against the current database connection to allow driver-specific subclasses
   *   as well, using the same logic as the query objects themselves.  For example,
   *   PagerDefault_mysql is the MySQL-specific override for PagerDefault.
   * @return
   *   The extender object, which now contains a reference to this object.
   */
  public function extend($extender_name);
}

/**
 * Interface definition for a Select Query object.
 */
interface SelectQueryInterface extends QueryConditionInterface, QueryAlterableInterface, QueryExtendableInterface, QueryPlaceholderInterface {

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
   * Compiles and returns an associative array of the arguments for this prepared statement.
   *
   * @param $queryPlaceholder
   *   When collecting the arguments of a subquery, the main placeholder
   *   object should be passed as this parameter.
   *
   * @return
   *   An associative array of all placeholder arguments for this query.
   */
  public function getArguments(QueryPlaceholderInterface $queryPlaceholder = NULL);

  /* Query building operations */

  /**
   * Sets this query to be DISTINCT.
   *
   * @param $distinct
   *   TRUE to flag this query DISTINCT, FALSE to disable it.
   * @return
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
   * @return
   *   The called object.
   */
  public function fields($table_alias, array $fields = array());

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
  public function addExpression($expression, $alias = NULL, $arguments = array());

  /**
   * Default Join against another table in the database.
   *
   * This method is a convenience method for innerJoin().
   *
   * @param $table
   *   The table against which to join.
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function join($table, $alias = NULL, $condition = NULL, $arguments = array());

  /**
   * Inner Join against another table in the database.
   *
   * @param $table
   *   The table against which to join.
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function innerJoin($table, $alias = NULL, $condition = NULL, $arguments = array());

  /**
   * Left Outer Join against another table in the database.
   *
   * @param $table
   *   The table against which to join.
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = array());

  /**
   * Right Outer Join against another table in the database.
   *
   * @param $table
   *   The table against which to join.
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function rightJoin($table, $alias = NULL, $condition = NULL, $arguments = array());

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
   * @param $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table. If omitted,
   *   one will be dynamically generated.
   * @param $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table.
   * @param $arguments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = array());

  /**
   * Orders the result set by a given field.
   *
   * If called multiple times, the query will order by each specified field in the
   * order this method is called.
   *
   * @param $field
   *   The field on which to order.
   * @param $direction
   *   The direction to sort. Legal values are "ASC" and "DESC".
   * @return
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
   * @return
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
   * @param $limit
   *   The number of records to return from the result set.
   * @return
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
   * @return
   *   The called object.
   */
  public function union(SelectQueryInterface $query, $type = '');

  /**
   * Groups the result set by the specified field.
   *
   * @param $field
   *   The field on which to group. This should be the field as aliased.
   * @return
   *   The called object.
   */
  public function groupBy($field);

  /**
   * Get the equivalent COUNT query of this query as a new query object.
   *
   * @return
   *   A new SelectQuery object with no fields or expressions besides COUNT(*).
   */
  public function countQuery();

  /**
   * Indicates if preExecute() has already been called on that object.
   */
  public function isPrepared();

  /**
   * Generic preparation and validation for a SELECT query.
   *
   * @return
   *   TRUE if the validation was successful, FALSE if not.
   */
  public function preExecute(SelectQueryInterface $query = NULL);

  /**
   * Clone magic method.
   *
   * Select queries have dependent objects that must be deep-cloned.  The
   * connection object itself, however, should not be cloned as that would
   * duplicate the connection itself.
   */
  public function __clone();
}

/**
 * The base extender class for Select queries.
 */
class SelectQueryExtender implements SelectQueryInterface {

  /**
   * The SelectQuery object we are extending/decorating.
   *
   * @var SelectQueryInterface
   */
  protected $query;

  /**
   * The connection object on which to run this query.
   *
   * @var DatabaseConnection
   */
  protected $connection;

  /**
   * The placeholder counter.
   */
  protected $placeholder = 0;

  public function __construct(SelectQueryInterface $query, DatabaseConnection $connection) {
    $this->query = $query;
    $this->connection = $connection;
  }

  /* Implementations of QueryPlaceholderInterface. */

  public function nextPlaceholder() {
    return $this->placeholder++;
  }

  /* Implementations of QueryAlterableInterface. */

  public function addTag($tag) {
    $this->query->addTag($tag);
    return $this;
  }

  public function hasTag($tag) {
    return $this->query->hasTag($tag);
  }

  public function hasAllTags() {
    return call_user_func_array(array($this->query, 'hasAllTags', func_get_args()));
  }

  public function hasAnyTag() {
    return call_user_func_array(array($this->query, 'hasAnyTags', func_get_args()));
  }

  public function addMetaData($key, $object) {
    $this->query->addMetaData($key, $object);
    return $this;
  }

  public function getMetaData($key) {
    return $this->query->getMetaData($key);
  }

  /* Implementations of QueryConditionInterface for the WHERE clause. */

  public function condition($field, $value = NULL, $operator = NULL) {
    $this->query->condition($field, $value, $operator);
    return $this;
  }

  public function &conditions() {
    return $this->query->conditions();
  }

  public function arguments() {
    return $this->query->arguments();
  }

  public function where($snippet, $args = array()) {
    $this->query->where($snippet, $args);
    return $this;
  }

  public function compile(DatabaseConnection $connection, QueryPlaceholderInterface $queryPlaceholder = NULL) {
    return $this->condition->compile($connection, isset($queryPlaceholder) ? $queryPlaceholder : $this);
  }

  /* Implmeentations of QueryConditionInterface for the HAVING clause. */

  public function havingCondition($field, $value = NULL, $operator = '=') {
    $this->query->condition($field, $value, $operator, $num_args);
    return $this;
  }

  public function &havingConditions() {
    return $this->having->conditions();
  }

  public function havingArguments() {
    return $this->having->arguments();
  }

  public function having($snippet, $args = array()) {
    $this->query->having($snippet, $args);
    return $this;
  }

  public function havingCompile(DatabaseConnection $connection) {
    return $this->query->havingCompile($connection);
  }

  /* Implementations of QueryExtendableInterface. */

  public function extend($extender_name) {
    $override_class = $this->connection->driver();
    if (class_exists($override_class)) {
      $extender_name = $override_class;
    }
    return new $extender_name($this, $this->connection);
  }

  /* Alter accessors to expose the query data to alter hooks. */

  public function &getFields() {
    return $this->query->getFields();
  }

  public function &getExpressions() {
    return $this->query->getExpressions();
  }

  public function &getOrderBy() {
    return $this->query->getOrderBy();
  }

  public function &getTables() {
    return $this->query->getTables();
  }

  public function &getUnion() {
    return $this->query->getUnion();
  }

  public function getArguments(QueryPlaceholderInterface $queryPlaceholder = NULL) {
    return $this->query->getArguments($queryPlaceholder);
  }

  public function isPrepared() {
    return $this->query->isPrepared();
  }

  public function preExecute(SelectQueryInterface $query = NULL) {
    // If no query object is passed in, use $this.
    if (is_null($query)) {
      $query = $this;
    }

    return $this->query->preExecute($query);
  }

  public function execute() {
    // By calling preExecute() here, we force it to preprocess the extender
    // object rather than just the base query object.  That means
    // hook_query_alter() gets access to the extended object.
    if (!$this->preExecute($this)) {
      return NULL;
    }

    return $this->query->execute();
  }

  public function distinct($distinct = TRUE) {
    $this->query->distinct($distinct);
    return $this;
  }

  public function addField($table_alias, $field, $alias = NULL) {
    return $this->query->addField($table_alias, $field, $alias);
  }

  public function fields($table_alias, array $fields = array()) {
    $this->query->fields($table_alias, $fields);
    return $this;
  }

  public function addExpression($expression, $alias = NULL, $arguments = array()) {
    return $this->query->addExpression($expression, $alias, $arguments);
  }

  public function join($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->join($table, $alias, $condition, $arguments);
  }

  public function innerJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->innerJoin($table, $alias, $condition, $arguments);
  }

  public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->leftJoin($table, $alias, $condition, $arguments);
  }

  public function rightJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->rightJoin($table, $alias, $condition, $arguments);
  }

  public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->addJoin($type, $table, $alias, $condition, $arguments);
  }

  public function orderBy($field, $direction = 'ASC') {
    $this->query->orderBy($field, $direction);
    return $this;
  }

  public function orderRandom() {
    $this->query->orderRandom();
    return $this;
  }

  public function range($start = NULL, $length = NULL) {
    $this->query->range($start, $length);
    return $this;
  }

  public function union(SelectQueryInterface $query, $type = '') {
    $this->query->union($query, $type);
    return $this;
  }

  public function groupBy($field) {
    $this->query->groupBy($field);
    return $this;
  }

  public function countQuery() {
    // Create our new query object that we will mutate into a count query.
    $count = clone($this);

    // Zero-out existing fields and expressions.
    $fields =& $count->getFields();
    $fields = array();
    $expressions =& $count->getExpressions();
    $expressions = array();

    // Also remove 'all_fields' statements, which are expanded into tablename.*
    // when the query is executed.
    $tables = &$count->getTables();
    foreach ($tables as $alias => &$table) {
      unset($table['all_fields']);
    }

    // Ordering a count query is a waste of cycles, and breaks on some
    // databases anyway.
    $orders = &$count->getOrderBy();
    $orders = array();

    // COUNT() is an expression, so we add that back in.
    $count->addExpression('COUNT(*)');

    return $count;
  }

  function isNull($field) {
    $this->query->isNull($field);
    return $this;
  }

  function isNotNull($field) {
    $this->query->isNotNull($field);
    return $this;
  }

  public function __toString() {
    return (string)$this->query;
  }

  public function __clone() {
    // We need to deep-clone the query we're wrapping, which in turn may
    // deep-clone other objects.  Exciting!
    $this->query = clone($this->query);
  }

  /**
   * Magic override for undefined methods.
   *
   * If one extender extends another extender, then methods in the inner extender
   * will not be exposed on the outer extender.  That's because we cannot know
   * in advance what those methods will be, so we cannot provide wrapping
   * implementations as we do above.  Instead, we use this slower catch-all method
   * to handle any additional methods.
   */
  public function __call($method, $args) {
    $return = call_user_func_array(array($this->query, $method), $args);

    // Some methods will return the called object as part of a fluent interface.
    // Others will return some useful value.  If it's a value, then the caller
    // probably wants that value.  If it's the called object, then we instead
    // return this object.  That way we don't "lose" an extender layer when
    // chaining methods together.
    if ($return instanceof SelectQueryInterface) {
      return $this;
    }
    else {
      return $return;
    }
  }
}

/**
 * Query builder for SELECT statements.
 */
class SelectQuery extends Query implements SelectQueryInterface {

  /**
   * The fields to SELECT.
   *
   * @var array
   */
  protected $fields = array();

  /**
   * The expressions to SELECT as virtual fields.
   *
   * @var array
   */
  protected $expressions = array();

  /**
   * The tables against which to JOIN.
   *
   * This property is a nested array. Each entry is an array representing
   * a single table against which to join. The structure of each entry is:
   *
   * array(
   *   'type' => $join_type (one of INNER, LEFT OUTER, RIGHT OUTER),
   *   'table' => $table,
   *   'alias' => $alias_of_the_table,
   *   'condition' => $condition_clause_on_which_to_join,
   *   'arguments' => $array_of_arguments_for_placeholders_in_the condition.
   *   'all_fields' => TRUE to SELECT $alias.*, FALSE or NULL otherwise.
   * )
   *
   * If $table is a string, it is taken as the name of a table. If it is
   * a SelectQuery object, it is taken as a subquery.
   *
   * @var array
   */
  protected $tables = array();

  /**
   * The fields by which to order this query.
   *
   * This is an associative array. The keys are the fields to order, and the value
   * is the direction to order, either ASC or DESC.
   *
   * @var array
   */
  protected $order = array();

  /**
   * The fields by which to group.
   *
   * @var array
   */
  protected $group = array();

  /**
   * The conditional object for the WHERE clause.
   *
   * @var DatabaseCondition
   */
  protected $where;

  /**
   * The conditional object for the HAVING clause.
   *
   * @var DatabaseCondition
   */
  protected $having;

  /**
   * Whether or not this query should be DISTINCT
   *
   * @var boolean
   */
  protected $distinct = FALSE;

  /**
   * The range limiters for this query.
   *
   * @var array
   */
  protected $range;

  /**
   * An array whose elements specify a query to UNION, and the UNION type. The
   * 'type' key may be '', 'ALL', or 'DISTINCT' to represent a 'UNION',
   * 'UNION ALL', or 'UNION DISTINCT' statement, respectively.
   *
   * All entries in this array will be applied from front to back, with the
   * first query to union on the right of the original query, the second union
   * to the right of the first, etc.
   *
   * @var array
   */
  protected $union = array();

  /**
   * Indicates if preExecute() has already been called.
   * @var boolean
   */
  protected $prepared = FALSE;

  public function __construct($table, $alias = NULL, DatabaseConnection $connection, $options = array()) {
    $options['return'] = Database::RETURN_STATEMENT;
    parent::__construct($connection, $options);
    $this->where = new DatabaseCondition('AND');
    $this->having = new DatabaseCondition('AND');
    $this->addJoin(NULL, $table, $alias);
  }

  /* Implementations of QueryAlterableInterface. */

  public function addTag($tag) {
    $this->alterTags[$tag] = 1;
    return $this;
  }

  public function hasTag($tag) {
    return isset($this->alterTags[$tag]);
  }

  public function hasAllTags() {
    return !(boolean)array_diff(func_get_args(), array_keys($this->alterTags));
  }

  public function hasAnyTag() {
    return (boolean)array_intersect(func_get_args(), array_keys($this->alterTags));
  }

  public function addMetaData($key, $object) {
    $this->alterMetaData[$key] = $object;
    return $this;
  }

  public function getMetaData($key) {
    return isset($this->alterMetaData[$key]) ? $this->alterMetaData[$key] : NULL;
  }

  /* Implementations of QueryConditionInterface for the WHERE clause. */

  public function condition($field, $value = NULL, $operator = '=') {
    if (!isset($num_args)) {
      $num_args = func_num_args();
    }
    $this->where->condition($field, $value, $operator, $num_args);
    return $this;
  }

  public function &conditions() {
    return $this->where->conditions();
  }

  public function arguments() {
    return $this->where->arguments();
  }

  public function where($snippet, $args = array()) {
    $this->where->where($snippet, $args);
    return $this;
  }

  public function isNull($field) {
    $this->where->isNull($field);
    return $this;
  }

  public function isNotNull($field) {
    $this->where->isNotNull($field);
    return $this;
  }

  public function compile(DatabaseConnection $connection, QueryPlaceholderInterface $queryPlaceholder = NULL) {
    return $this->where->compile($connection, isset($queryPlaceholder) ? $queryPlaceholder : $this);
  }

  /* Implmeentations of QueryConditionInterface for the HAVING clause. */

  public function havingCondition($field, $value = NULL, $operator = '=') {
    if (!isset($num_args)) {
      $num_args = func_num_args();
    }
    $this->having->condition($field, $value, $operator, $num_args);
    return $this;
  }

  public function &havingConditions() {
    return $this->having->conditions();
  }

  public function havingArguments() {
    return $this->having->arguments();
  }

  public function having($snippet, $args = array()) {
    $this->having->where($snippet, $args);
    return $this;
  }

  public function havingCompile(DatabaseConnection $connection) {
    return $this->having->compile($connection, $this);
  }

  /* Implementations of QueryExtendableInterface. */

  public function extend($extender_name) {
    $override_class = __CLASS__ . $this->connection->driver();
    if (class_exists($override_class)) {
      $extender_name = $override_class;
    }
    return new $extender_name($this, $this->connection);
  }

  public function havingIsNull($field) {
    $this->having->isNull($field);
    return $this;
  }

  public function havingIsNotNull($field) {
    $this->having->isNotNull($field);
    return $this;
  }


  /* Alter accessors to expose the query data to alter hooks. */

  public function &getFields() {
    return $this->fields;
  }

  public function &getExpressions() {
    return $this->expressions;
  }

  public function &getOrderBy() {
    return $this->order;
  }

  public function &getTables() {
    return $this->tables;
  }

  public function &getUnion() {
    return $this->union;
  }

  public function getArguments(QueryPlaceholderInterface $queryPlaceholder = NULL) {
    if (!isset($queryPlaceholder)) {
      $queryPlaceholder = $this;
    }
    $this->where->compile($this->connection, $queryPlaceholder);
    $this->having->compile($this->connection, $queryPlaceholder);
    $args = $this->where->arguments() + $this->having->arguments();

    foreach ($this->tables as $table) {
      if ($table['arguments']) {
        $args += $table['arguments'];
      }
      // If this table is a subquery, grab its arguments recursively.
      if ($table['table'] instanceof SelectQueryInterface) {
        $args += $table['table']->getArguments($queryPlaceholder);
      }
    }

    foreach ($this->expressions as $expression) {
      if ($expression['arguments']) {
        $args += $expression['arguments'];
      }
    }

    // If there are any dependent queries to UNION,
    // incorporate their arguments recursively.
    foreach ($this->union as $union) {
      $args += $union['query']->getArguments($queryPlaceholder);
    }

    return $args;
  }

  /**
   * Indicates if preExecute() has already been called on that object.
   */
  public function isPrepared() {
    return $this->prepared;
  }

  /**
   * Generic preparation and validation for a SELECT query.
   *
   * @return
   *   TRUE if the validation was successful, FALSE if not.
   */
  public function preExecute(SelectQueryInterface $query = NULL) {
    // If no query object is passed in, use $this.
    if (is_null($query)) {
      $query = $this;
    }

    // Only execute this once.
    if ($query->isPrepared()) {
      return TRUE;
    }

    // Modules may alter all queries or only those having a particular tag.
    drupal_alter('query', $query);
    if (isset($this->alterTags)) {
      foreach ($this->alterTags as $tag => $value) {
        drupal_alter("query_$tag", $query);
      }
    }
    return $this->prepared = TRUE;
  }

  public function execute() {
    // If validation fails, simply return NULL.
    // Note that validation routines in preExecute() may throw exceptions instead.
    if (!$this->preExecute()) {
      return NULL;
    }

    $args = $this->getArguments();

    if (!empty($this->range)) {
      return $this->connection->queryRange((string)$this, $this->range['start'], $this->range['length'], $args, $this->queryOptions);
    }
    return $this->connection->query((string)$this, $args, $this->queryOptions);
  }

  public function distinct($distinct = TRUE) {
    $this->distinct = $distinct;
    return $this;
  }

  public function addField($table_alias, $field, $alias = NULL) {
    // If no alias is specified, first try the field name itself.
    if (empty($alias)) {
      $alias = $field;
    }

    // If that's already in use, try the table name and field name.
    if (!empty($this->tables[$alias])) {
      $alias = $table_alias . '_' . $field;
    }

    // If that is already used, just add a counter until we find an unused alias.
    $alias_candidate = $alias;
    $count = 2;
    while (!empty($this->tables[$alias_candidate])) {
      $alias_candidate = $alias . '_' . $count++;
    }
    $alias = $alias_candidate;

    $this->fields[$alias] = array(
      'field' => $field,
      'table' => $table_alias,
      'alias' => $alias,
    );

    return $alias;
  }

  public function fields($table_alias, array $fields = array()) {

    if ($fields) {
      foreach ($fields as $field) {
        // We don't care what alias was assigned.
        $this->addField($table_alias, $field);
      }
    }
    else {
      // We want all fields from this table.
      $this->tables[$table_alias]['all_fields'] = TRUE;
    }

    return $this;
  }

  public function addExpression($expression, $alias = NULL, $arguments = array()) {
    if (empty($alias)) {
      $alias = 'expression';
    }

    $alias_candidate = $alias;
    $count = 2;
    while (!empty($this->expressions[$alias_candidate])) {
      $alias_candidate = $alias . '_' . $count++;
    }
    $alias = $alias_candidate;

    $this->expressions[$alias] = array(
      'expression' => $expression,
      'alias' => $alias,
      'arguments' => $arguments,
    );

    return $alias;
  }

  public function join($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->addJoin('INNER', $table, $alias, $condition, $arguments);
  }

  public function innerJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->addJoin('INNER', $table, $alias, $condition, $arguments);
  }

  public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->addJoin('LEFT OUTER', $table, $alias, $condition, $arguments);
  }

  public function rightJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->addJoin('RIGHT OUTER', $table, $alias, $condition, $arguments);
  }

  public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = array()) {

    if (empty($alias)) {
      if ($table instanceof SelectQueryInterface) {
        $alias = 'subquery';
      }
      else {
        $alias = $table;
      }
    }

    $alias_candidate = $alias;
    $count = 2;
    while (!empty($this->tables[$alias_candidate])) {
      $alias_candidate = $alias . '_' . $count++;
    }
    $alias = $alias_candidate;

    $this->tables[$alias] = array(
      'join type' => $type,
      'table' => $table,
      'alias' => $alias,
      'condition' => $condition,
      'arguments' => $arguments,
    );

    return $alias;
  }

  public function orderBy($field, $direction = 'ASC') {
    $this->order[$field] = $direction;
    return $this;
  }

  public function orderRandom() {
    $alias = $this->addExpression('RAND()', 'random_field');
    $this->orderBy($alias);
    return $this;
  }

  public function range($start = NULL, $length = NULL) {
    $this->range = func_num_args() ? array('start' => $start, 'length' => $length) : array();
    return $this;
  }

  public function union(SelectQueryInterface $query, $type = '') {
    // Handle UNION aliasing.
    switch ($type) {
      // Fold UNION DISTINCT to UNION for better cross database support.
      case 'DISTINCT':
      case '':
        $type = 'UNION';
        break;
      
      case 'ALL':
        $type = 'UNION ALL';
      default:
    }

    $this->union[] = array(
      'type' => $type,
      'query' => $query,
    );

    return $this;
  }

  public function groupBy($field) {
    $this->group[] = $field;
    return $this;
  }

  public function countQuery() {
    // Create our new query object that we will mutate into a count query.
    $count = clone($this);

    // Zero-out existing fields and expressions.
    $fields =& $count->getFields();
    $fields = array();
    $expressions =& $count->getExpressions();
    $expressions = array();


    // Also remove 'all_fields' statements, which are expanded into tablename.*
    // when the query is executed.
    foreach ($count->tables as $alias => &$table) {
      unset($table['all_fields']);
    }

    // Ordering a count query is a waste of cycles, and breaks on some
    // databases anyway.
    $orders = &$count->getOrderBy();
    $orders = array();

    // COUNT() is an expression, so we add that back in.
    $count->addExpression('COUNT(*)');

    return $count;
  }

  public function __toString() {

    // SELECT
    $query = 'SELECT ';
    if ($this->distinct) {
      $query .= 'DISTINCT ';
    }

    // FIELDS and EXPRESSIONS
    $fields = array();
    foreach ($this->fields as $alias => $field) {
      // Always use the AS keyword for field aliases, as some
      // databases require it (e.g., PostgreSQL).
      $fields[] = (isset($field['table']) ? $field['table'] . '.' : '') . $field['field'] . ' AS ' . $field['alias'];
    }
    foreach ($this->expressions as $alias => $expression) {
      $fields[] = $expression['expression'] . ' AS ' . $expression['alias'];
    }
    foreach ($this->tables as $alias => $table) {
      if (!empty($table['all_fields'])) {
        $fields[] = $alias . '.*';
      }
    }
    $query .= implode(', ', $fields);


    // FROM - We presume all queries have a FROM, as any query that doesn't won't need the query builder anyway.
    $query .= "\nFROM ";
    foreach ($this->tables as $alias => $table) {
      $query .= "\n";
      if (isset($table['join type'])) {
        $query .= $table['join type'] . ' JOIN ';
      }

      // If the table is a subquery, compile it and integrate it into this query.
      if ($table['table'] instanceof SelectQueryInterface) {
        $table_string = '(' . (string)$table['table'] . ')';
      }
      else {
        $table_string = '{' . $this->connection->escapeTable($table['table']) . '}';
      }

      // Don't use the AS keyword for table aliases, as some
      // databases don't support it (e.g., Oracle).
      $query .=  $table_string . ' ' . $table['alias'];

      if (!empty($table['condition'])) {
        $query .= ' ON ' . $table['condition'];
      }
    }

    // WHERE
    if (count($this->where)) {
      $this->where->compile($this->connection, $this);
      // There is an implicit string cast on $this->condition.
      $query .= "\nWHERE " . $this->where;
    }

    // GROUP BY
    if ($this->group) {
      $query .= "\nGROUP BY " . implode(', ', $this->group);
    }

    // HAVING
    if (count($this->having)) {
      $this->having->compile($this->connection, $this);
      // There is an implicit string cast on $this->having.
      $query .= "\nHAVING " . $this->having;
    }

    // ORDER BY
    if ($this->order) {
      $query .= "\nORDER BY ";
      $fields = array();
      foreach ($this->order as $field => $direction) {
        $fields[] = $field . ' ' . $direction;
      }
      $query .= implode(', ', $fields);
    }

    // RANGE is database specific, so we can't do it here.

    // UNION is a little odd, as the select queries to combine are passed into
    // this query, but syntactically they all end up on the same level.
    if ($this->union) {
      foreach ($this->union as $union) {
        $query .= ' ' . $union['type'] . ' ' . (string) $union['query'];
      }
    }

    return $query;
  }

  public function __clone() {
    // On cloning, also clone the dependent objects. However, we do not
    // want to clone the database connection object as that would duplicate the
    // connection itself.

    $this->where = clone($this->where);
    $this->having = clone($this->having);
    foreach ($this->union as $key => $aggregate) {
      $this->union[$key]['query'] = clone($aggregate['query']);
    }
  }
}

/**
 * @} End of "ingroup database".
 */
