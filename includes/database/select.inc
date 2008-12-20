<?php
// $Id$

/**
 * @ingroup database
 * @{
 */

/**
 * Query builder for SELECT statements.
 */
class SelectQuery extends Query implements QueryConditionInterface, QueryAlterableInterface {

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

  public function compile(DatabaseConnection $connection) {
    return $this->where->compile($connection);
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
    return $this->having->compile($connection);
  }

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
  public function &getFields() {
    return $this->fields;
  }

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
  public function &getExpressions() {
    return $this->expressions;
  }

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
  public function &getOrderBy() {
    return $this->order;
  }

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
  public function &getTables() {
    return $this->tables;
  }

  /**
   * Compiles and returns an associative array of the arguments for this prepared statement.
   *
   * @return
   *   An associative array of all placeholder arguments for this query.
   */
  public function getArguments() {
    $this->where->compile($this->connection);
    $this->having->compile($this->connection);
    $args = $this->where->arguments() + $this->having->arguments();
    foreach ($this->tables as $table) {
      if ($table['arguments']) {
        $args += $table['arguments'];
      }
      // If this table is a subquery, grab its arguments recursively.
      if ($table['table'] instanceof SelectQuery) {
        $args += $table['table']->getArguments();
      }
    }
    foreach ($this->expressions as $expression) {
      if ($expression['arguments']) {
        $args += $expression['arguments'];
      }
    }

    return $args;
  }

  public function execute() {
    drupal_alter('query', $this);

    $args = $this->getArguments();

    if (!empty($this->range)) {
      return $this->connection->queryRange((string)$this, $args, $this->range['start'], $this->range['length'], $this->queryOptions);
    }
    return $this->connection->query((string)$this, $args, $this->queryOptions);
  }

  /**
   * Sets this query to be DISTINCT.
   *
   * @param $distinct
   *   TRUE to flag this query DISTINCT, FALSE to disable it.
   * @return
   *   The called object.
   */
  public function distinct($distinct = TRUE) {
    $this->distinct = $distinct;
    return $this;
  }

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

  /**
   * Private list of aliases already attributed to expression fields.
   *
   * @var Array
   */
  private $expressionAliases = array();

  /**
   * Adds an expression to the list of "fields" to be SELECTed.
   *
   * An expression can be any arbitrary string that is valid SQL. That includes
   * various functions, which may in some cases be database-dependant. This
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
  public function join($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->addJoin('INNER', $table, $alias, $condition, $arguments);
  }

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
  public function innerJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->addJoin('INNER', $table, $alias, $condition, $arguments);
  }

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
  public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->addJoin('LEFT OUTER', $table, $alias, $condition, $arguments);
  }

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
  public function rightJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->addJoin('RIGHT OUTER', $table, $alias, $condition, $arguments);
  }

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
   * @param $argments
   *   An array of arguments to replace into the $condition of this join.
   * @return
   *   The unique alias that was assigned for this table.
   */
  public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = array()) {

    if (empty($alias)) {
      if ($table instanceof SelectQuery) {
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
  public function orderBy($field, $direction = 'ASC') {
    $this->order[$field] = $direction;
    return $this;
  }

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
  public function range($start = NULL, $length = NULL) {
    $this->range = func_num_args() ? array('start' => $start, 'length' => $length) : array();
    return $this;
  }

  /**
   * Groups the result set by the specified field.
   *
   * @param $field
   *   The field on which to group. This should be the field as aliased.
   * @return
   *   The called object.
   */
  public function groupBy($field) {
    $this->group[] = $field;
  }

  /**
   * Get the equivalent COUNT query of this query as a new query object.
   *
   * @return
   *   A new SelectQuery object with no fields or expressions besides COUNT(*).
   */
  public function countQuery() {
    // Shallow-clone this query. We don't want to duplicate any of the
    // referenced objects, so a shallow query is all we need.
    $count = clone($this);

    // Zero-out existing fields and expressions.
    $fields =& $count->getFields();
    $fields = array();
    $expressions =& $count->getExpressions();
    $expressions = array();

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
      if ($table['table'] instanceof SelectQuery) {
        $table_string = '(' . (string)$table['table'] .')';
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
      $this->where->compile($this->connection);
      // There is an implicit string cast on $this->condition.
      $query .= "\nWHERE " . $this->where;
    }

    // GROUP BY
    if ($this->group) {
      $query .= "\nGROUP BY " . implode(', ', $this->group);
    }

    // HAVING
    if (count($this->having)) {
      $this->having->compile($this->connection);
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

    return $query;
  }

  public function __clone() {
    // On cloning, also clone the conditional objects. However, we do not
    // want to clone the database connection object as that would duplicate the
    // connection itself.

    $this->where = clone($this->where);
    $this->having = clone($this->having);
  }
}

/**
 * @} End of "ingroup database".
 */
