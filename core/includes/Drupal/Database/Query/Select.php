<?php

namespace Drupal\Database\Query;

use Drupal\Database\Database;
use Drupal\Database\Connection;


/**
 * Query builder for SELECT statements.
 */
class Select extends Query implements SelectInterface {

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

  /**
   * The FOR UPDATE status
   */
  protected $forUpdate = FALSE;

  public function __construct($table, $alias = NULL, Connection $connection, $options = array()) {
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

  public function condition($field, $value = NULL, $operator = NULL) {
    $this->where->condition($field, $value, $operator);
    return $this;
  }

  public function &conditions() {
    return $this->where->conditions();
  }

  public function arguments() {
    if (!$this->compiled()) {
      return NULL;
    }

    $args = $this->where->arguments() + $this->having->arguments();

    foreach ($this->tables as $table) {
      if ($table['arguments']) {
        $args += $table['arguments'];
      }
      // If this table is a subquery, grab its arguments recursively.
      if ($table['table'] instanceof SelectInterface) {
        $args += $table['table']->arguments();
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
      $args += $union['query']->arguments();
    }

    return $args;
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

  public function exists(SelectInterface $select) {
    $this->where->exists($select);
    return $this;
  }

  public function notExists(SelectInterface $select) {
    $this->where->notExists($select);
    return $this;
  }

  public function compile(Connection $connection, PlaceholderInterface $queryPlaceholder) {
    $this->where->compile($connection, $queryPlaceholder);
    $this->having->compile($connection, $queryPlaceholder);

    foreach ($this->tables as $table) {
      // If this table is a subquery, compile it recursively.
      if ($table['table'] instanceof SelectInterface) {
        $table['table']->compile($connection, $queryPlaceholder);
      }
    }

    // If there are any dependent queries to UNION, compile it recursively.
    foreach ($this->union as $union) {
      $union['query']->compile($connection, $queryPlaceholder);
    }
  }

  public function compiled() {
    if (!$this->where->compiled() || !$this->having->compiled()) {
      return FALSE;
    }

    foreach ($this->tables as $table) {
      // If this table is a subquery, check its status recursively.
      if ($table['table'] instanceof SelectInterface) {
        if (!$table['table']->compiled()) {
          return FALSE;
        }
      }
    }

    foreach ($this->union as $union) {
      if (!$union['query']->compiled()) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /* Implementations of QueryConditionInterface for the HAVING clause. */

  public function havingCondition($field, $value = NULL, $operator = NULL) {
    $this->having->condition($field, $value, $operator);
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

  public function havingCompile(Connection $connection) {
    return $this->having->compile($connection, $this);
  }

  /* Implementations of QueryExtendableInterface. */

  public function extend($extender_name) {
    $override_class = $extender_name . '_' . $this->connection->driver();
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

  public function havingExists(SelectInterface $select) {
    $this->having->exists($select);
    return $this;
  }

  public function havingNotExists(SelectInterface $select) {
    $this->having->notExists($select);
    return $this;
  }

  public function forUpdate($set = TRUE) {
    if (isset($set)) {
      $this->forUpdate = $set;
    }
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

  public function &getGroupBy() {
    return $this->group;
  }

  public function &getTables() {
    return $this->tables;
  }

  public function &getUnion() {
    return $this->union;
  }

  public function getArguments(PlaceholderInterface $queryPlaceholder = NULL) {
    if (!isset($queryPlaceholder)) {
      $queryPlaceholder = $this;
    }
    $this->compile($this->connection, $queryPlaceholder);
    return $this->arguments();
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
  public function preExecute(SelectInterface $query = NULL) {
    // If no query object is passed in, use $this.
    if (!isset($query)) {
      $query = $this;
    }

    // Only execute this once.
    if ($query->isPrepared()) {
      return TRUE;
    }

    // Modules may alter all queries or only those having a particular tag.
    if (isset($this->alterTags)) {
      $hooks = array('query');
      foreach ($this->alterTags as $tag => $value) {
        $hooks[] = 'query_' . $tag;
      }
      drupal_alter($hooks, $query);
    }

    $this->prepared = TRUE;

    // Now also prepare any sub-queries.
    foreach ($this->tables as $table) {
      if ($table['table'] instanceof SelectInterface) {
        $table['table']->preExecute();
      }
    }

    foreach ($this->union as $union) {
      $union['query']->preExecute();
    }

    return $this->prepared;
  }

  public function execute() {
    // If validation fails, simply return NULL.
    // Note that validation routines in preExecute() may throw exceptions instead.
    if (!$this->preExecute()) {
      return NULL;
    }

    $args = $this->getArguments();
    return $this->connection->query((string) $this, $args, $this->queryOptions);
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
    if (!empty($this->fields[$alias])) {
      $alias = $table_alias . '_' . $field;
    }

    // If that is already used, just add a counter until we find an unused alias.
    $alias_candidate = $alias;
    $count = 2;
    while (!empty($this->fields[$alias_candidate])) {
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
      if ($table instanceof SelectInterface) {
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

    if (is_string($condition)) {
      $condition = str_replace('%alias', $alias, $condition);
    }

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

  public function union(SelectInterface $query, $type = '') {
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
    $this->group[$field] = $field;
    return $this;
  }

  public function countQuery() {
    // Create our new query object that we will mutate into a count query.
    $count = clone($this);

    $group_by = $count->getGroupBy();
    $having = $count->havingConditions();

    if (!$count->distinct && !isset($having[0])) {
      // When not executing a distinct query, we can zero-out existing fields
      // and expressions that are not used by a GROUP BY or HAVING. Fields
      // listed in a GROUP BY or HAVING clause need to be present in the
      // query.
      $fields =& $count->getFields();
      foreach (array_keys($fields) as $field) {
        if (empty($group_by[$field])) {
          unset($fields[$field]);
        }
      }

      $expressions =& $count->getExpressions();
      foreach (array_keys($expressions) as $field) {
        if (empty($group_by[$field])) {
          unset($expressions[$field]);
        }
      }

      // Also remove 'all_fields' statements, which are expanded into tablename.*
      // when the query is executed.
      foreach ($count->tables as $alias => &$table) {
        unset($table['all_fields']);
      }
    }

    // If we've just removed all fields from the query, make sure there is at
    // least one so that the query still runs.
    $count->addExpression('1');

    // Ordering a count query is a waste of cycles, and breaks on some
    // databases anyway.
    $orders = &$count->getOrderBy();
    $orders = array();

    if ($count->distinct && !empty($group_by)) {
      // If the query is distinct and contains a GROUP BY, we need to remove the
      // distinct because SQL99 does not support counting on distinct multiple fields.
      $count->distinct = FALSE;
    }

    $query = $this->connection->select($count);
    $query->addExpression('COUNT(*)');

    return $query;
  }

  public function __toString() {
    // For convenience, we compile the query ourselves if the caller forgot
    // to do it. This allows constructs like "(string) $query" to work. When
    // the query will be executed, it will be recompiled using the proper
    // placeholder generator anyway.
    if (!$this->compiled()) {
      $this->compile($this->connection, $this);
    }

    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // SELECT
    $query = $comments . 'SELECT ';
    if ($this->distinct) {
      $query .= 'DISTINCT ';
    }

    // FIELDS and EXPRESSIONS
    $fields = array();
    foreach ($this->tables as $alias => $table) {
      if (!empty($table['all_fields'])) {
        $fields[] = $this->connection->escapeTable($alias) . '.*';
      }
    }
    foreach ($this->fields as $alias => $field) {
      // Always use the AS keyword for field aliases, as some
      // databases require it (e.g., PostgreSQL).
      $fields[] = (isset($field['table']) ? $this->connection->escapeTable($field['table']) . '.' : '') . $this->connection->escapeField($field['field']) . ' AS ' . $this->connection->escapeAlias($field['alias']);
    }
    foreach ($this->expressions as $alias => $expression) {
      $fields[] = $expression['expression'] . ' AS ' . $this->connection->escapeAlias($expression['alias']);
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
      if ($table['table'] instanceof SelectInterface) {
        // Run preparation steps on this sub-query before converting to string.
        $subquery = $table['table'];
        $subquery->preExecute();
        $table_string = '(' . (string) $subquery . ')';
      }
      else {
        $table_string = '{' . $this->connection->escapeTable($table['table']) . '}';
      }

      // Don't use the AS keyword for table aliases, as some
      // databases don't support it (e.g., Oracle).
      $query .=  $table_string . ' ' . $this->connection->escapeTable($table['alias']);

      if (!empty($table['condition'])) {
        $query .= ' ON ' . $table['condition'];
      }
    }

    // WHERE
    if (count($this->where)) {
      // There is an implicit string cast on $this->condition.
      $query .= "\nWHERE " . $this->where;
    }

    // GROUP BY
    if ($this->group) {
      $query .= "\nGROUP BY " . implode(', ', $this->group);
    }

    // HAVING
    if (count($this->having)) {
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

    // RANGE
    // There is no universal SQL standard for handling range or limit clauses.
    // Fortunately, all core-supported databases use the same range syntax.
    // Databases that need a different syntax can override this method and
    // do whatever alternate logic they need to.
    if (!empty($this->range)) {
      $query .= "\nLIMIT " . (int) $this->range['length'] . " OFFSET " . (int) $this->range['start'];
    }

    // UNION is a little odd, as the select queries to combine are passed into
    // this query, but syntactically they all end up on the same level.
    if ($this->union) {
      foreach ($this->union as $union) {
        $query .= ' ' . $union['type'] . ' ' . (string) $union['query'];
      }
    }

    if ($this->forUpdate) {
      $query .= ' FOR UPDATE';
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
