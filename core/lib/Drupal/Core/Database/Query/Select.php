<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;

/**
 * Query builder for SELECT statements.
 *
 * @ingroup database
 */
class Select extends Query implements SelectInterface {

  use QueryConditionTrait;

  /**
   * The fields to SELECT.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * The expressions to SELECT as virtual fields.
   *
   * @var array
   */
  protected $expressions = [];

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
   *   'condition' => $join_condition (string or Condition object),
   *   'arguments' => $array_of_arguments_for_placeholders_in_the condition.
   *   'all_fields' => TRUE to SELECT $alias.*, FALSE or NULL otherwise.
   * )
   *
   * If $table is a string, it is taken as the name of a table. If it is
   * a Select query object, it is taken as a subquery.
   *
   * If $join_condition is a Condition object, any arguments should be
   * incorporated into the object; a separate array of arguments does not
   * need to be provided.
   *
   * @var array
   */
  protected $tables = [];

  /**
   * The fields by which to order this query.
   *
   * This is an associative array. The keys are the fields to order, and the value
   * is the direction to order, either ASC or DESC.
   *
   * @var array
   */
  protected $order = [];

  /**
   * The fields by which to group.
   *
   * @var array
   */
  protected $group = [];

  /**
   * The conditional object for the HAVING clause.
   *
   * @var \Drupal\Core\Database\Query\Condition
   */
  protected $having;

  /**
   * Whether or not this query should be DISTINCT.
   *
   * @var bool
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
  protected $union = [];

  /**
   * Indicates if preExecute() has already been called.
   * @var bool
   */
  protected $prepared = FALSE;

  /**
   * The FOR UPDATE status.
   *
   * @var bool
   */
  protected $forUpdate = FALSE;

  /**
   * Constructs a Select object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   * @param string $table
   *   The name of the table that is being queried.
   * @param string $alias
   *   The alias for the table.
   * @param array $options
   *   Array of query options.
   */
  public function __construct(Connection $connection, $table, $alias = NULL, $options = []) {
    $options['return'] = Database::RETURN_STATEMENT;
    parent::__construct($connection, $options);
    $conjunction = isset($options['conjunction']) ? $options['conjunction'] : 'AND';
    $this->condition = $this->connection->condition($conjunction);
    $this->having = $this->connection->condition($conjunction);
    $this->addJoin(NULL, $table, $alias);
  }

  /**
   * {@inheritdoc}
   */
  public function addTag($tag) {
    $this->alterTags[$tag] = 1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTag($tag) {
    return isset($this->alterTags[$tag]);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAllTags() {
    return !(boolean) array_diff(func_get_args(), array_keys($this->alterTags));
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnyTag() {
    return (boolean) array_intersect(func_get_args(), array_keys($this->alterTags));
  }

  /**
   * {@inheritdoc}
   */
  public function addMetaData($key, $object) {
    $this->alterMetaData[$key] = $object;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaData($key) {
    return isset($this->alterMetaData[$key]) ? $this->alterMetaData[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function arguments() {
    if (!$this->compiled()) {
      return NULL;
    }

    $args = $this->condition->arguments() + $this->having->arguments();

    foreach ($this->tables as $table) {
      if ($table['arguments']) {
        $args += $table['arguments'];
      }
      // If this table is a subquery, grab its arguments recursively.
      if ($table['table'] instanceof SelectInterface) {
        $args += $table['table']->arguments();
      }
      // If the join condition is an object, grab its arguments recursively.
      if (!empty($table['condition']) && $table['condition'] instanceof ConditionInterface) {
        $args += $table['condition']->arguments();
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

  /**
   * {@inheritdoc}
   */
  public function compile(Connection $connection, PlaceholderInterface $queryPlaceholder) {
    $this->condition->compile($connection, $queryPlaceholder);
    $this->having->compile($connection, $queryPlaceholder);

    foreach ($this->tables as $table) {
      // If this table is a subquery, compile it recursively.
      if ($table['table'] instanceof SelectInterface) {
        $table['table']->compile($connection, $queryPlaceholder);
      }
      // Make sure join conditions are also compiled.
      if (!empty($table['condition']) && $table['condition'] instanceof ConditionInterface) {
        $table['condition']->compile($connection, $queryPlaceholder);
      }
    }

    // If there are any dependent queries to UNION, compile it recursively.
    foreach ($this->union as $union) {
      $union['query']->compile($connection, $queryPlaceholder);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function compiled() {
    if (!$this->condition->compiled() || !$this->having->compiled()) {
      return FALSE;
    }

    foreach ($this->tables as $table) {
      // If this table is a subquery, check its status recursively.
      if ($table['table'] instanceof SelectInterface) {
        if (!$table['table']->compiled()) {
          return FALSE;
        }
      }
      if (!empty($table['condition']) && $table['condition'] instanceof ConditionInterface) {
        if (!$table['condition']->compiled()) {
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

  /**
   * {@inheritdoc}
   */
  public function havingCondition($field, $value = NULL, $operator = NULL) {
    $this->having->condition($field, $value, $operator);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &havingConditions() {
    return $this->having->conditions();
  }

  /**
   * {@inheritdoc}
   */
  public function havingArguments() {
    return $this->having->arguments();
  }

  /**
   * {@inheritdoc}
   */
  public function having($snippet, $args = []) {
    $this->having->where($snippet, $args);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function havingCompile(Connection $connection) {
    $this->having->compile($connection, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function extend($extender_name) {
    $parts = explode('\\', $extender_name);
    $class = end($parts);
    $driver_class = $this->connection->getDriverClass($class);
    if ($driver_class !== $class) {
      return new $driver_class($this, $this->connection);
    }
    return new $extender_name($this, $this->connection);
  }

  /**
   * {@inheritdoc}
   */
  public function havingIsNull($field) {
    $this->having->isNull($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function havingIsNotNull($field) {
    $this->having->isNotNull($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function havingExists(SelectInterface $select) {
    $this->having->exists($select);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function havingNotExists(SelectInterface $select) {
    $this->having->notExists($select);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function forUpdate($set = TRUE) {
    if (isset($set)) {
      $this->forUpdate = $set;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getFields() {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function &getExpressions() {
    return $this->expressions;
  }

  /**
   * {@inheritdoc}
   */
  public function &getOrderBy() {
    return $this->order;
  }

  /**
   * {@inheritdoc}
   */
  public function &getGroupBy() {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function &getTables() {
    return $this->tables;
  }

  /**
   * {@inheritdoc}
   */
  public function &getUnion() {
    return $this->union;
  }

  /**
   * {@inheritdoc}
   */
  public function escapeLike($string) {
    return $this->connection->escapeLike($string);
  }

  /**
   * {@inheritdoc}
   */
  public function escapeField($string) {
    return $this->connection->escapeField($string);
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments(PlaceholderInterface $queryPlaceholder = NULL) {
    if (!isset($queryPlaceholder)) {
      $queryPlaceholder = $this;
    }
    $this->compile($this->connection, $queryPlaceholder);
    return $this->arguments();
  }

  /**
   * {@inheritdoc}
   */
  public function isPrepared() {
    return $this->prepared;
  }

  /**
   * {@inheritdoc}
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
      // Many contrib modules as well as Entity Reference in core assume that
      // query tags used for access-checking purposes follow the pattern
      // $entity_type . '_access'. But this is not the case for taxonomy terms,
      // since the core Taxonomy module used to add term_access instead of
      // taxonomy_term_access to its queries. Provide backwards compatibility
      // by adding both tags here instead of attempting to fix all contrib
      // modules in a coordinated effort.
      // TODO:
      // - Extract this mechanism into a hook as part of a public (non-security)
      //   issue.
      // - Emit E_USER_DEPRECATED if term_access is used.
      //   https://www.drupal.org/node/2575081
      $term_access_tags = ['term_access' => 1, 'taxonomy_term_access' => 1];
      if (array_intersect_key($this->alterTags, $term_access_tags)) {
        $this->alterTags += $term_access_tags;
      }
      $hooks = ['query'];
      foreach ($this->alterTags as $tag => $value) {
        $hooks[] = 'query_' . $tag;
      }
      \Drupal::moduleHandler()->alter($hooks, $query);
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

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // If validation fails, simply return NULL.
    // Note that validation routines in preExecute() may throw exceptions instead.
    if (!$this->preExecute()) {
      return NULL;
    }

    $args = $this->getArguments();
    return $this->connection->query((string) $this, $args, $this->queryOptions);
  }

  /**
   * {@inheritdoc}
   */
  public function distinct($distinct = TRUE) {
    $this->distinct = $distinct;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
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

    $this->fields[$alias] = [
      'field' => $field,
      'table' => $table_alias,
      'alias' => $alias,
    ];

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function fields($table_alias, array $fields = []) {
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
   * {@inheritdoc}
   */
  public function addExpression($expression, $alias = NULL, $arguments = []) {
    if (empty($alias)) {
      $alias = 'expression';
    }

    $alias_candidate = $alias;
    $count = 2;
    while (!empty($this->expressions[$alias_candidate])) {
      $alias_candidate = $alias . '_' . $count++;
    }
    $alias = $alias_candidate;

    $this->expressions[$alias] = [
      'expression' => $expression,
      'alias' => $alias,
      'arguments' => $arguments,
    ];

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function join($table, $alias = NULL, $condition = NULL, $arguments = []) {
    return $this->addJoin('INNER', $table, $alias, $condition, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function innerJoin($table, $alias = NULL, $condition = NULL, $arguments = []) {
    return $this->addJoin('INNER', $table, $alias, $condition, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = []) {
    return $this->addJoin('LEFT OUTER', $table, $alias, $condition, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = []) {
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

    $this->tables[$alias] = [
      'join type' => $type,
      'table' => $table,
      'alias' => $alias,
      'condition' => $condition,
      'arguments' => $arguments,
    ];

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function orderBy($field, $direction = 'ASC') {
    // Only allow ASC and DESC, default to ASC.
    $direction = strtoupper($direction) == 'DESC' ? 'DESC' : 'ASC';
    $this->order[$field] = $direction;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function orderRandom() {
    $alias = $this->addExpression('RAND()', 'random_field');
    $this->orderBy($alias);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function range($start = NULL, $length = NULL) {
    $this->range = $start !== NULL ? ['start' => $start, 'length' => $length] : [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
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

    $this->union[] = [
      'type' => $type,
      'query' => $query,
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function groupBy($field) {
    $this->group[$field] = $field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function countQuery() {
    $count = $this->prepareCountQuery();

    $query = $this->connection->select($count, NULL, $this->queryOptions);
    $query->addExpression('COUNT(*)');

    return $query;
  }

  /**
   * Prepares a count query from the current query object.
   *
   * @return \Drupal\Core\Database\Query\Select
   *   A new query object ready to have COUNT(*) performed on it.
   */
  protected function prepareCountQuery() {
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
      foreach ($count->tables as &$table) {
        unset($table['all_fields']);
      }
    }

    // If we've just removed all fields from the query, make sure there is at
    // least one so that the query still runs.
    $count->addExpression('1');

    // Ordering a count query is a waste of cycles, and breaks on some
    // databases anyway.
    $orders = &$count->getOrderBy();
    $orders = [];

    if ($count->distinct && !empty($group_by)) {
      // If the query is distinct and contains a GROUP BY, we need to remove the
      // distinct because SQL99 does not support counting on distinct multiple fields.
      $count->distinct = FALSE;
    }

    // If there are any dependent queries to UNION, prepare each of those for
    // the count query also.
    foreach ($count->union as &$union) {
      $union['query'] = $union['query']->prepareCountQuery();
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
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
    $fields = [];
    foreach ($this->tables as $alias => $table) {
      if (!empty($table['all_fields'])) {
        $fields[] = $this->connection->escapeAlias($alias) . '.*';
      }
    }
    foreach ($this->fields as $field) {
      // Note that $field['table'] holds the table_alias.
      // @see \Drupal\Core\Database\Query\Select::addField
      $table = isset($field['table']) ? $field['table'] . '.' : '';
      // Always use the AS keyword for field aliases, as some
      // databases require it (e.g., PostgreSQL).
      $fields[] = $this->connection->escapeField($table . $field['field']) . ' AS ' . $this->connection->escapeAlias($field['alias']);
    }
    foreach ($this->expressions as $expression) {
      $fields[] = $expression['expression'] . ' AS ' . $this->connection->escapeAlias($expression['alias']);
    }
    $query .= implode(', ', $fields);

    // FROM - We presume all queries have a FROM, as any query that doesn't won't need the query builder anyway.
    $query .= "\nFROM";
    foreach ($this->tables as $table) {
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
        $table_string = $this->connection->escapeTable($table['table']);
        // Do not attempt prefixing cross database / schema queries.
        if (strpos($table_string, '.') === FALSE) {
          $table_string = '{' . $table_string . '}';
        }
      }

      // Don't use the AS keyword for table aliases, as some
      // databases don't support it (e.g., Oracle).
      $query .= $table_string . ' ' . $this->connection->escapeAlias($table['alias']);

      if (!empty($table['condition'])) {
        $query .= ' ON ' . (string) $table['condition'];
      }
    }

    // WHERE
    if (count($this->condition)) {
      // There is an implicit string cast on $this->condition.
      $query .= "\nWHERE " . $this->condition;
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

    // UNION is a little odd, as the select queries to combine are passed into
    // this query, but syntactically they all end up on the same level.
    if ($this->union) {
      foreach ($this->union as $union) {
        $query .= ' ' . $union['type'] . ' ' . (string) $union['query'];
      }
    }

    // ORDER BY
    if ($this->order) {
      $query .= "\nORDER BY ";
      $fields = [];
      foreach ($this->order as $field => $direction) {
        $fields[] = $this->connection->escapeField($field) . ' ' . $direction;
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

    if ($this->forUpdate) {
      $query .= ' FOR UPDATE';
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function __clone() {
    parent::__clone();

    // On cloning, also clone the dependent objects. However, we do not
    // want to clone the database connection object as that would duplicate the
    // connection itself.

    $this->condition = clone($this->condition);
    $this->having = clone($this->having);
    foreach ($this->union as $key => $aggregate) {
      $this->union[$key]['query'] = clone($aggregate['query']);
    }
    foreach ($this->tables as $alias => $table) {
      if ($table['table'] instanceof SelectInterface) {
        $this->tables[$alias]['table'] = clone $table['table'];
      }
    }
  }

}
