<?php

/**
 * @ingroup database
 * @{
 */

/**
 * @file
 * Non-specific Database query code. Used by all engines.
 */

/**
 * Interface for a conditional clause in a query.
 */
interface QueryConditionInterface {

  /**
   * Helper function: builds the most common conditional clauses.
   *
   * This method can take a variable number of parameters. If called with two
   * parameters, they are taken as $field and $value with $operator having a
   * value of IN if $value is an array and = otherwise.
   *
   * Do not use this method to test for NULL values. Instead, use
   * QueryConditionInterface::isNull() or QueryConditionInterface::isNotNull().
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
   *   complex options such as IN, LIKE, or BETWEEN. Defaults to IN if $value is
   *   an array, and = otherwise.
   *
   * @return QueryConditionInterface
   *   The called object.
   *
   * @see QueryConditionInterface::isNull()
   * @see QueryConditionInterface::isNotNull()
   */
  public function condition($field, $value = NULL, $operator = NULL);

  /**
   * Adds an arbitrary WHERE clause to the query.
   *
   * @param $snippet
   *   A portion of a WHERE clause as a prepared statement. It must use named
   *   placeholders, not ? placeholders.
   * @param $args
   *   An associative array of arguments.
   *
   * @return QueryConditionInterface
   *   The called object.
   */
  public function where($snippet, $args = array());

  /**
   * Sets a condition that the specified field be NULL.
   *
   * @param $field
   *   The name of the field to check.
   *
   * @return QueryConditionInterface
   *   The called object.
   */
  public function isNull($field);

  /**
   * Sets a condition that the specified field be NOT NULL.
   *
   * @param $field
   *   The name of the field to check.
   *
   * @return QueryConditionInterface
   *   The called object.
   */
  public function isNotNull($field);

  /**
   * Sets a condition that the specified subquery returns values.
   * 
   * @param SelectQueryInterface $select
   *   The subquery that must contain results.
   *
   * @return QueryConditionInterface
   *   The called object.
   */
  public function exists(SelectQueryInterface $select);
  
  /**
   * Sets a condition that the specified subquery returns no values.
   * 
   * @param SelectQueryInterface $select
   *   The subquery that must not contain results.
   *
   * @return QueryConditionInterface
   *   The called object.
   */
  public function notExists(SelectQueryInterface $select);
  
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
  public function compile(DatabaseConnection $connection, QueryPlaceholderInterface $queryPlaceholder);

  /**
   * Check whether a condition has been previously compiled.
   *
   * @return
   *   TRUE if the condition has been previously compiled.
   */
  public function compiled();
}


/**
 * Interface for a query that can be manipulated via an alter hook.
 */
interface QueryAlterableInterface {

  /**
   * Adds a tag to a query.
   *
   * Tags are strings that identify a query. A query may have any number of
   * tags. Tags are used to mark a query so that alter hooks may decide if they
   * wish to take action. Tags should be all lower-case and contain only
   * letters, numbers, and underscore, and start with a letter. That is, they
   * should follow the same rules as PHP identifiers in general.
   *
   * @param $tag
   *   The tag to add.
   *
   * @return QueryAlterableInterface
   *   The called object.
   */
  public function addTag($tag);

  /**
   * Determines if a given query has a given tag.
   *
   * @param $tag
   *   The tag to check.
   *
   * @return
   *   TRUE if this query has been marked with this tag, FALSE otherwise.
   */
  public function hasTag($tag);

  /**
   * Determines if a given query has all specified tags.
   *
   * @param $tags
   *   A variable number of arguments, one for each tag to check.
   *
   * @return
   *   TRUE if this query has been marked with all specified tags, FALSE
   *   otherwise.
   */
  public function hasAllTags();

  /**
   * Determines if a given query has any specified tag.
   *
   * @param $tags
   *   A variable number of arguments, one for each tag to check.
   *
   * @return
   *   TRUE if this query has been marked with at least one of the specified
   *   tags, FALSE otherwise.
   */
  public function hasAnyTag();

  /**
   * Adds additional metadata to the query.
   *
   * Often, a query may need to provide additional contextual data to alter
   * hooks. Alter hooks may then use that information to decide if and how
   * to take action.
   *
   * @param $key
   *   The unique identifier for this piece of metadata. Must be a string that
   *   follows the same rules as any other PHP identifier.
   * @param $object
   *   The additional data to add to the query. May be any valid PHP variable.
   *
   * @return QueryAlterableInterface
   *   The called object.
   */
  public function addMetaData($key, $object);

  /**
   * Retrieves a given piece of metadata.
   *
   * @param $key
   *   The unique identifier for the piece of metadata to retrieve.
   *
   * @return
   *   The previously attached metadata object, or NULL if one doesn't exist.
   */
  public function getMetaData($key);
}

/**
 * Interface for a query that accepts placeholders.
 */
interface QueryPlaceholderInterface {

  /**
   * Returns a unique identifier for this object.
   */
  public function uniqueIdentifier();

  /**
   * Returns the next placeholder ID for the query.
   *
   * @return
   *   The next available placeholder ID as an integer.
   */
  public function nextPlaceholder();
}

/**
 * Base class for query builders.
 *
 * Note that query builders use PHP's magic __toString() method to compile the
 * query object into a prepared statement.
 */
abstract class Query implements QueryPlaceholderInterface {

  /**
   * The connection object on which to run this query.
   *
   * @var DatabaseConnection
   */
  protected $connection;

  /**
   * The target of the connection object.
   * 
   * @var string
   */
  protected $connectionTarget;

  /**
   * The key of the connection object.
   * 
   * @var string
   */
  protected $connectionKey;

  /**
   * The query options to pass on to the connection object.
   *
   * @var array
   */
  protected $queryOptions;

  /**
   * A unique identifier for this query object.
   */
  protected $uniqueIdentifier;

  /**
   * The placeholder counter.
   */
  protected $nextPlaceholder = 0;

  /**
   * An array of comments that can be prepended to a query.
   *
   * @var array
   */
  protected $comments = array();

  /**
   * Constructs a Query object.
   *
   * @param DatabaseConnection $connection
   *   Database connection object.
   * @param array $options
   *   Array of query options.
   */
  public function __construct(DatabaseConnection $connection, $options) {
    $this->uniqueIdentifier = uniqid('', TRUE);

    $this->connection = $connection;
    $this->connectionKey = $this->connection->getKey();
    $this->connectionTarget = $this->connection->getTarget();

    $this->queryOptions = $options;
  }

  /**
   * Implements the magic __sleep function to disconnect from the database.
   */
  public function __sleep() {
    $keys = get_object_vars($this);
    unset($keys['connection']);
    return array_keys($keys);
  }

  /**
   * Implements the magic __wakeup function to reconnect to the database.
   */
  public function __wakeup() {
    $this->connection = Database::getConnection($this->connectionTarget, $this->connectionKey);
  }

  /**
   * Implements the magic __clone function.
   */
  public function __clone() {
    $this->uniqueIdentifier = uniqid('', TRUE);
  }

  /**
   * Runs the query against the database.
   */
  abstract protected function execute();

  /**
   * Implements PHP magic __toString method to convert the query to a string.
   *
   * The toString operation is how we compile a query object to a prepared
   * statement.
   *
   * @return
   *   A prepared statement query string for this object.
   */
  abstract public function __toString();

  /**
   * Returns a unique identifier for this object.
   */
  public function uniqueIdentifier() {
    return $this->uniqueIdentifier;
  }

  /**
   * Gets the next placeholder value for this query object.
   *
   * @return int
   *   Next placeholder value.
   */
  public function nextPlaceholder() {
    return $this->nextPlaceholder++;
  }

  /**
   * Adds a comment to the query.
   *
   * By adding a comment to a query, you can more easily find it in your
   * query log or the list of active queries on an SQL server. This allows
   * for easier debugging and allows you to more easily find where a query
   * with a performance problem is being generated.
   *
   * The comment string will be sanitized to remove * / and other characters
   * that may terminate the string early so as to avoid SQL injection attacks.
   *
   * @param $comment
   *   The comment string to be inserted into the query.
   *
   * @return Query
   *   The called object.
   */
  public function comment($comment) {
    $this->comments[] = $comment;
    return $this;
  }

  /**
   * Returns a reference to the comments array for the query.
   *
   * Because this method returns by reference, alter hooks may edit the comments
   * array directly to make their changes. If just adding comments, however, the
   * use of comment() is preferred.
   *
   * Note that this method must be called by reference as well:
   * @code
   * $comments =& $query->getComments();
   * @endcode
   *
   * @return
   *   A reference to the comments array structure.
   */
  public function &getComments() {
    return $this->comments;
  }
}

/**
 * General class for an abstracted INSERT query.
 */
class InsertQuery extends Query {

  /**
   * The table on which to insert.
   *
   * @var string
   */
  protected $table;

  /**
   * An array of fields on which to insert.
   *
   * @var array
   */
  protected $insertFields = array();

  /**
   * An array of fields that should be set to their database-defined defaults.
   *
   * @var array
   */
  protected $defaultFields = array();

  /**
   * A nested array of values to insert.
   *
   * $insertValues is an array of arrays. Each sub-array is either an
   * associative array whose keys are field names and whose values are field
   * values to insert, or a non-associative array of values in the same order
   * as $insertFields.
   *
   * Whether multiple insert sets will be run in a single query or multiple
   * queries is left to individual drivers to implement in whatever manner is
   * most appropriate. The order of values in each sub-array must match the
   * order of fields in $insertFields.
   *
   * @var array
   */
  protected $insertValues = array();

  /**
   * A SelectQuery object to fetch the rows that should be inserted.
   *
   * @var SelectQueryInterface
   */
  protected $fromQuery;

  /**
   * Constructs an InsertQuery object.
   *
   * @param DatabaseConnection $connection
   *   A DatabaseConnection object.
   * @param string $table
   *   Name of the table to associate with this query.
   * @param array $options
   *   Array of database options.
   */
  public function __construct($connection, $table, array $options = array()) {
    if (!isset($options['return'])) {
      $options['return'] = Database::RETURN_INSERT_ID;
    }
    parent::__construct($connection, $options);
    $this->table = $table;
  }

  /**
   * Adds a set of field->value pairs to be inserted.
   *
   * This method may only be called once. Calling it a second time will be
   * ignored. To queue up multiple sets of values to be inserted at once,
   * use the values() method.
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
   * @return InsertQuery
   *   The called object.
   */
  public function fields(array $fields, array $values = array()) {
    if (empty($this->insertFields)) {
      if (empty($values)) {
        if (!is_numeric(key($fields))) {
          $values = array_values($fields);
          $fields = array_keys($fields);
        }
      }
      $this->insertFields = $fields;
      if (!empty($values)) {
        $this->insertValues[] = $values;
      }
    }

    return $this;
  }

  /**
   * Adds another set of values to the query to be inserted.
   *
   * If $values is a numeric-keyed array, it will be assumed to be in the same
   * order as the original fields() call. If it is associative, it may be
   * in any order as long as the keys of the array match the names of the
   * fields.
   *
   * @param $values
   *   An array of values to add to the query.
   *
   * @return InsertQuery
   *   The called object.
   */
  public function values(array $values) {
    if (is_numeric(key($values))) {
      $this->insertValues[] = $values;
    }
    else {
      // Reorder the submitted values to match the fields array.
      foreach ($this->insertFields as $key) {
        $insert_values[$key] = $values[$key];
      }
      // For consistency, the values array is always numerically indexed.
      $this->insertValues[] = array_values($insert_values);
    }
    return $this;
  }

  /**
   * Specifies fields for which the database defaults should be used.
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
   * @return InsertQuery
   *   The called object.
   */
  public function useDefaults(array $fields) {
    $this->defaultFields = $fields;
    return $this;
  }

  /**
   * Sets the fromQuery on this InsertQuery object.
   *
   * @param SelectQueryInterface $query
   *   The query to fetch the rows that should be inserted.
   *
   * @return InsertQuery
   *   The called object.
   */
  public function from(SelectQueryInterface $query) {
    $this->fromQuery = $query;
    return $this;
  }

  /**
   * Executes the insert query.
   *
   * @return
   *   The last insert ID of the query, if one exists. If the query
   *   was given multiple sets of values to insert, the return value is
   *   undefined. If no fields are specified, this method will do nothing and
   *   return NULL. That makes it safe to use in multi-insert loops.
   */
  public function execute() {
    // If validation fails, simply return NULL. Note that validation routines
    // in preExecute() may throw exceptions instead.
    if (!$this->preExecute()) {
      return NULL;
    }

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (!empty($this->fromQuery)) {
      $sql = (string) $this;
      // The SelectQuery may contain arguments, load and pass them through.
      return $this->connection->query($sql, $this->fromQuery->getArguments(), $this->queryOptions);
    }

    $last_insert_id = 0;

    // Each insert happens in its own query in the degenerate case. However,
    // we wrap it in a transaction so that it is atomic where possible. On many
    // databases, such as SQLite, this is also a notable performance boost.
    $transaction = $this->connection->startTransaction();

    try {
      $sql = (string) $this;
      foreach ($this->insertValues as $insert_values) {
        $last_insert_id = $this->connection->query($sql, $insert_values, $this->queryOptions);
      }
    }
    catch (Exception $e) {
      // One of the INSERTs failed, rollback the whole batch.
      $transaction->rollback();
      // Rethrow the exception for the calling code.
      throw $e;
    }

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = array();

    // Transaction commits here where $transaction looses scope.

    return $last_insert_id;
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

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);

    if (!empty($this->fromQuery)) {
      return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') ' . $this->fromQuery;
    }

    // For simplicity, we will use the $placeholders array to inject
    // default keywords even though they are not, strictly speaking,
    // placeholders for prepared statements.
    $placeholders = array();
    $placeholders = array_pad($placeholders, count($this->defaultFields), 'default');
    $placeholders = array_pad($placeholders, count($this->insertFields), '?');

    return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
  }

  /**
   * Preprocesses and validates the query.
   *
   * @return
   *   TRUE if the validation was successful, FALSE if not.
   *
   * @throws FieldsOverlapException
   * @throws NoFieldsException
   */
  public function preExecute() {
    // Confirm that the user did not try to specify an identical
    // field and default field.
    if (array_intersect($this->insertFields, $this->defaultFields)) {
      throw new FieldsOverlapException('You may not specify the same field to have a value and a schema-default value.');
    }

    if (!empty($this->fromQuery)) {
      // We have to assume that the used aliases match the insert fields.
      // Regular fields are added to the query before expressions, maintain the
      // same order for the insert fields.
      // This behavior can be overridden by calling fields() manually as only the
      // first call to fields() does have an effect.
      $this->fields(array_merge(array_keys($this->fromQuery->getFields()), array_keys($this->fromQuery->getExpressions())));
    }

    // Don't execute query without fields.
    if (count($this->insertFields) + count($this->defaultFields) == 0) {
      throw new NoFieldsException('There are no fields available to insert with.');
    }

    // If no values have been added, silently ignore this query. This can happen
    // if values are added conditionally, so we don't want to throw an
    // exception.
    if (!isset($this->insertValues[0]) && count($this->insertFields) > 0 && empty($this->fromQuery)) {
      return FALSE;
    }
    return TRUE;
  }
}

/**
 * General class for an abstracted DELETE operation.
 */
class DeleteQuery extends Query implements QueryConditionInterface {

  /**
   * The table from which to delete.
   *
   * @var string
   */
  protected $table;

  /**
   * The condition object for this query.
   *
   * Condition handling is handled via composition.
   *
   * @var DatabaseCondition
   */
  protected $condition;

  /**
   * Constructs a DeleteQuery object.
   *
   * @param DatabaseConnection $connection
   *   A DatabaseConnection object.
   * @param string $table
   *   Name of the table to associate with this query.
   * @param array $options
   *   Array of database options.
   */
  public function __construct(DatabaseConnection $connection, $table, array $options = array()) {
    $options['return'] = Database::RETURN_AFFECTED;
    parent::__construct($connection, $options);
    $this->table = $table;

    $this->condition = new DatabaseCondition('AND');
  }

  /**
   * Implements QueryConditionInterface::condition().
   */
  public function condition($field, $value = NULL, $operator = NULL) {
    $this->condition->condition($field, $value, $operator);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::isNull().
   */
  public function isNull($field) {
    $this->condition->isNull($field);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::isNotNull().
   */
  public function isNotNull($field) {
    $this->condition->isNotNull($field);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::exists().
   */
  public function exists(SelectQueryInterface $select) {
    $this->condition->exists($select);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::notExists().
   */
  public function notExists(SelectQueryInterface $select) {
    $this->condition->notExists($select);
    return $this;
  }
  
  /**
   * Implements QueryConditionInterface::conditions().
   */
  public function &conditions() {
    return $this->condition->conditions();
  }

  /**
   * Implements QueryConditionInterface::arguments().
   */
  public function arguments() {
    return $this->condition->arguments();
  }

  /**
   * Implements QueryConditionInterface::where().
   */
  public function where($snippet, $args = array()) {
    $this->condition->where($snippet, $args);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::compile().
   */
  public function compile(DatabaseConnection $connection, QueryPlaceholderInterface $queryPlaceholder) {
    return $this->condition->compile($connection, $queryPlaceholder);
  }

  /**
   * Implements QueryConditionInterface::compiled().
   */
  public function compiled() {
    return $this->condition->compiled();
  }

  /**
   * Executes the DELETE query.
   *
   * @return
   *   The return value is dependent on the database connection.
   */
  public function execute() {
    $values = array();
    if (count($this->condition)) {
      $this->condition->compile($this->connection, $this);
      $values = $this->condition->arguments();
    }

    return $this->connection->query((string) $this, $values, $this->queryOptions);
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

    $query = $comments . 'DELETE FROM {' . $this->connection->escapeTable($this->table) . '} ';

    if (count($this->condition)) {

      $this->condition->compile($this->connection, $this);
      $query .= "\nWHERE " . $this->condition;
    }

    return $query;
  }
}


/**
 * General class for an abstracted TRUNCATE operation.
 */
class TruncateQuery extends Query {

  /**
   * The table to truncate.
   *
   * @var string
   */
  protected $table;

  /**
   * Constructs a TruncateQuery object.
   *
   * @param DatabaseConnection $connection
   *   A DatabaseConnection object.
   * @param string $table
   *   Name of the table to associate with this query.
   * @param array $options
   *   Array of database options.
   */
  public function __construct(DatabaseConnection $connection, $table, array $options = array()) {
    $options['return'] = Database::RETURN_AFFECTED;
    parent::__construct($connection, $options);
    $this->table = $table;
  }

  /**
   * Implements QueryConditionInterface::compile().
   */
  public function compile(DatabaseConnection $connection, QueryPlaceholderInterface $queryPlaceholder) {
    return $this->condition->compile($connection, $queryPlaceholder);
  }

  /**
   * Implements QueryConditionInterface::compiled().
   */
  public function compiled() {
    return $this->condition->compiled();
  }

  /**
   * Executes the TRUNCATE query.
   *
   * @return
   *   Return value is dependent on the database type.
   */
  public function execute() {
    return $this->connection->query((string) $this, array(), $this->queryOptions);
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

    return $comments . 'TRUNCATE {' . $this->connection->escapeTable($this->table) . '} ';
  }
}

/**
 * General class for an abstracted UPDATE operation.
 */
class UpdateQuery extends Query implements QueryConditionInterface {

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
  protected $fields = array();

  /**
   * An array of values to update to.
   *
   * @var array
   */
  protected $arguments = array();

  /**
   * The condition object for this query.
   *
   * Condition handling is handled via composition.
   *
   * @var DatabaseCondition
   */
  protected $condition;

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
   * Constructs an UpdateQuery object.
   *
   * @param DatabaseConnection $connection
   *   A DatabaseConnection object.
   * @param string $table
   *   Name of the table to associate with this query.
   * @param array $options
   *   Array of database options.
   */
  public function __construct(DatabaseConnection $connection, $table, array $options = array()) {
    $options['return'] = Database::RETURN_AFFECTED;
    parent::__construct($connection, $options);
    $this->table = $table;

    $this->condition = new DatabaseCondition('AND');
  }

  /**
   * Implements QueryConditionInterface::condition().
   */
  public function condition($field, $value = NULL, $operator = NULL) {
    $this->condition->condition($field, $value, $operator);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::isNull().
   */
  public function isNull($field) {
    $this->condition->isNull($field);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::isNotNull().
   */
  public function isNotNull($field) {
    $this->condition->isNotNull($field);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::exists().
   */
  public function exists(SelectQueryInterface $select) {
    $this->condition->exists($select);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::notExists().
   */
  public function notExists(SelectQueryInterface $select) {
    $this->condition->notExists($select);
    return $this;
  }
  
  /**
   * Implements QueryConditionInterface::conditions().
   */
  public function &conditions() {
    return $this->condition->conditions();
  }

  /**
   * Implements QueryConditionInterface::arguments().
   */
  public function arguments() {
    return $this->condition->arguments();
  }

  /**
   * Implements QueryConditionInterface::where().
   */
  public function where($snippet, $args = array()) {
    $this->condition->where($snippet, $args);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::compile().
   */
  public function compile(DatabaseConnection $connection, QueryPlaceholderInterface $queryPlaceholder) {
    return $this->condition->compile($connection, $queryPlaceholder);
  }

  /**
   * Implements QueryConditionInterface::compiled().
   */
  public function compiled() {
    return $this->condition->compiled();
  }

  /**
   * Adds a set of field->value pairs to be updated.
   *
   * @param $fields
   *   An associative array of fields to write into the database. The array keys
   *   are the field names and the values are the values to which to set them.
   *
   * @return UpdateQuery
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
   * @return UpdateQuery
   *   The called object.
   */
  public function expression($field, $expression, array $arguments = NULL) {
    $this->expressionFields[$field] = array(
      'expression' => $expression,
      'arguments' => $arguments,
    );

    return $this;
  }

  /**
   * Executes the UPDATE query.
   *
   * @return
   *   The number of rows affected by the update.
   */
  public function execute() {

    // Expressions take priority over literal fields, so we process those first
    // and remove any literal fields that conflict.
    $fields = $this->fields;
    $update_values = array();
    foreach ($this->expressionFields as $field => $data) {
      if (!empty($data['arguments'])) {
        $update_values += $data['arguments'];
      }
      unset($fields[$field]);
    }

    // Because we filter $fields the same way here and in __toString(), the
    // placeholders will all match up properly.
    $max_placeholder = 0;
    foreach ($fields as $field => $value) {
      $update_values[':db_update_placeholder_' . ($max_placeholder++)] = $value;
    }

    if (count($this->condition)) {
      $this->condition->compile($this->connection, $this);
      $update_values = array_merge($update_values, $this->condition->arguments());
    }

    return $this->connection->query((string) $this, $update_values, $this->queryOptions);
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
    $update_fields = array();
    foreach ($this->expressionFields as $field => $data) {
      $update_fields[] = $field . '=' . $data['expression'];
      unset($fields[$field]);
    }

    $max_placeholder = 0;
    foreach ($fields as $field => $value) {
      $update_fields[] = $field . '=:db_update_placeholder_' . ($max_placeholder++);
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
 * INSERT and UPDATE. However, this is not mandatory. Another convinient
 * wrapper is MergeQuery::key() which adds the same column-value pairs to the
 * condition and the INSERT query part.
 *
 * Several methods (key(), fields(), insertFields()) can be called to set a
 * key-value pair for the INSERT query part. Subsequent calls for the same
 * fields override the earlier ones. The same is true for UPDATE and key(),
 * fields() and updateFields().
 */
class MergeQuery extends Query implements QueryConditionInterface {
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
   * @var boolean
   */
  protected $needsUpdate = FALSE;

  /**
  * Constructs a MergeQuery object.
  *
  * @param DatabaseConnection $connection
  *   A DatabaseConnection object.
  * @param string $table
  *   Name of the table to associate with this query.
  * @param array $options
  *   Array of database options.
  */
  public function __construct(DatabaseConnection $connection, $table, array $options = array()) {
    $options['return'] = Database::RETURN_AFFECTED;
    parent::__construct($connection, $options);
    $this->table = $table;
    $this->conditionTable = $table;
    $this->condition = new DatabaseCondition('AND');
  }

  /**
   * Sets the table or subquery to be used for the condition.
   *
   * @param $table
   *   The table name or the subquery to be used. Use a SelectQuery object to
   *   pass in a subquery.
   *
   * @return MergeQuery
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
   * @return MergeQuery
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
   * @return MergeQuery
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
   * @return MergeQuery
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
   * @return MergeQuery
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
   * @return MergeQuery
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
   * Sets the key field(s) to be used as conditions for this query.
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
   * @return MergeQuery
   *   The called object.
   */
  public function key(array $fields, array $values = array()) {
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
   * Implements QueryConditionInterface::condition().
   */
  public function condition($field, $value = NULL, $operator = NULL) {
    $this->condition->condition($field, $value, $operator);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::isNull().
   */
  public function isNull($field) {
    $this->condition->isNull($field);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::isNotNull().
   */
  public function isNotNull($field) {
    $this->condition->isNotNull($field);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::exists().
   */
  public function exists(SelectQueryInterface $select) {
    $this->condition->exists($select);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::notExists().
   */
  public function notExists(SelectQueryInterface $select) {
    $this->condition->notExists($select);
    return $this;
  }
  
  /**
   * Implements QueryConditionInterface::conditions().
   */
  public function &conditions() {
    return $this->condition->conditions();
  }

  /**
   * Implements QueryConditionInterface::arguments().
   */
  public function arguments() {
    return $this->condition->arguments();
  }

  /**
   * Implements QueryConditionInterface::where().
   */
  public function where($snippet, $args = array()) {
    $this->condition->where($snippet, $args);
    return $this;
  }

  /**
   * Implements QueryConditionInterface::compile().
   */
  public function compile(DatabaseConnection $connection, QueryPlaceholderInterface $queryPlaceholder) {
    return $this->condition->compile($connection, $queryPlaceholder);
  }

  /**
   * Implements QueryConditionInterface::compiled().
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
    // Wrap multiple queries in a transaction, if the database supports it.
    $transaction = $this->connection->startTransaction();
    try {
      if (!count($this->condition)) {
        throw new InvalidMergeQueryException(t('Invalid merge query: no conditions'));
      }
      $select = $this->connection->select($this->conditionTable)
        ->condition($this->condition)
        ->forUpdate();
      $select->addExpression('1');
      if (!$select->execute()->fetchField()) {
        try {
          $insert = $this->connection->insert($this->table)->fields($this->insertFields);
          if ($this->defaultFields) {
            $insert->useDefaults($this->defaultFields);
          }
          $insert->execute();
          return MergeQuery::STATUS_INSERT;
        }
        catch (Exception $e) {
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
        return MergeQuery::STATUS_UPDATE;
      }
    }
    catch (Exception $e) {
      // Something really wrong happened here, bubble up the exception to the
      // caller.
      $transaction->rollback();
      throw $e;
    }
    // Transaction commits here where $transaction looses scope.
  }
}

/**
 * Generic class for a series of conditions in a query.
 */
class DatabaseCondition implements QueryConditionInterface, Countable {

  /**
   * Array of conditions.
   *
   * @var array
   */
  protected $conditions = array();

  /**
   * Array of arguments.
   *
   * @var array
   */
  protected $arguments = array();

  /**
   * Whether the conditions have been changed.
   *
   * TRUE if the condition has been changed since the last compile.
   * FALSE if the condition has been compiled and not changed.
   *
   * @var bool
   */
  protected $changed = TRUE;

  /**
   * The identifier of the query placeholder this condition has been compiled against.
   */
  protected $queryPlaceholderIdentifier;

  /**
   * Constructs a DataBaseCondition object.
   *
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   */
  public function __construct($conjunction) {
    $this->conditions['#conjunction'] = $conjunction;
  }

  /**
   * Implements Countable::count().
   *
   * Returns the size of this conditional. The size of the conditional is the
   * size of its conditional array minus one, because one element is the the
   * conjunction.
   */
  public function count() {
    return count($this->conditions) - 1;
  }

  /**
   * Implements QueryConditionInterface::condition().
   */
  public function condition($field, $value = NULL, $operator = NULL) {
    if (!isset($operator)) {
      if (is_array($value)) {
        $operator = 'IN';
      }
      elseif (!isset($value)) {
        $operator = 'IS NULL';
      }
      else {
        $operator = '=';
      }
    }
    $this->conditions[] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    );

    $this->changed = TRUE;

    return $this;
  }

  /**
   * Implements QueryConditionInterface::where().
   */
  public function where($snippet, $args = array()) {
    $this->conditions[] = array(
      'field' => $snippet,
      'value' => $args,
      'operator' => NULL,
    );
    $this->changed = TRUE;

    return $this;
  }

  /**
   * Implements QueryConditionInterface::isNull().
   */
  public function isNull($field) {
    return $this->condition($field);
  }

  /**
   * Implements QueryConditionInterface::isNotNull().
   */
  public function isNotNull($field) {
    return $this->condition($field, NULL, 'IS NOT NULL');
  }

  /**
   * Implements QueryConditionInterface::exists().
   */
  public function exists(SelectQueryInterface $select) {
    return $this->condition('', $select, 'EXISTS');
  }
  
  /**
   * Implements QueryConditionInterface::notExists().
   */
  public function notExists(SelectQueryInterface $select) {
    return $this->condition('', $select, 'NOT EXISTS');
  }
  
  /**
   * Implements QueryConditionInterface::conditions().
   */
  public function &conditions() {
    return $this->conditions;
  }

  /**
   * Implements QueryConditionInterface::arguments().
   */
  public function arguments() {
    // If the caller forgot to call compile() first, refuse to run.
    if ($this->changed) {
      return NULL;
    }
    return $this->arguments;
  }

  /**
   * Implements QueryConditionInterface::compile().
   */
  public function compile(DatabaseConnection $connection, QueryPlaceholderInterface $queryPlaceholder) {
    // Re-compile if this condition changed or if we are compiled against a
    // different query placeholder object.
    if ($this->changed || isset($this->queryPlaceholderIdentifier) && ($this->queryPlaceholderIdentifier != $queryPlaceholder->uniqueIdentifier())) {
      $this->queryPlaceholderIdentifier = $queryPlaceholder->uniqueIdentifier();

      $condition_fragments = array();
      $arguments = array();

      $conditions = $this->conditions;
      $conjunction = $conditions['#conjunction'];
      unset($conditions['#conjunction']);
      foreach ($conditions as $condition) {
        if (empty($condition['operator'])) {
          // This condition is a literal string, so let it through as is.
          $condition_fragments[] = ' (' . $condition['field'] . ') ';
          $arguments += $condition['value'];
        }
        else {
          // It's a structured condition, so parse it out accordingly.
          // Note that $condition['field'] will only be an object for a dependent
          // DatabaseCondition object, not for a dependent subquery.
          if ($condition['field'] instanceof QueryConditionInterface) {
            // Compile the sub-condition recursively and add it to the list.
            $condition['field']->compile($connection, $queryPlaceholder);
            $condition_fragments[] = '(' . (string) $condition['field'] . ')';
            $arguments += $condition['field']->arguments();
          }
          else {
            // For simplicity, we treat all operators as the same data structure.
            // In the typical degenerate case, this won't get changed.
            $operator_defaults = array(
              'prefix' => '',
              'postfix' => '',
              'delimiter' => '',
              'operator' => $condition['operator'],
              'use_value' => TRUE,
            );
            $operator = $connection->mapConditionOperator($condition['operator']);
            if (!isset($operator)) {
              $operator = $this->mapConditionOperator($condition['operator']);
            }
            $operator += $operator_defaults;

            $placeholders = array();
            if ($condition['value'] instanceof SelectQueryInterface) {
              $condition['value']->compile($connection, $queryPlaceholder);
              $placeholders[] = (string) $condition['value'];
              $arguments += $condition['value']->arguments();
              // Subqueries are the actual value of the operator, we don't
              // need to add another below.
              $operator['use_value'] = FALSE;
            }
            // We assume that if there is a delimiter, then the value is an
            // array. If not, it is a scalar. For simplicity, we first convert
            // up to an array so that we can build the placeholders in the same way.
            elseif (!$operator['delimiter']) {
              $condition['value'] = array($condition['value']);
            }
            if ($operator['use_value']) {
              foreach ($condition['value'] as $value) {
                $placeholder = ':db_condition_placeholder_' . $queryPlaceholder->nextPlaceholder();
                $arguments[$placeholder] = $value;
                $placeholders[] = $placeholder;
              }
            }
            $condition_fragments[] = ' (' . $connection->escapeField($condition['field']) . ' ' . $operator['operator'] . ' ' . $operator['prefix'] . implode($operator['delimiter'], $placeholders) . $operator['postfix'] . ') ';
          }
        }
      }

      $this->changed = FALSE;
      $this->stringVersion = implode($conjunction, $condition_fragments);
      $this->arguments = $arguments;
    }
  }

  /**
   * Implements QueryConditionInterface::compiled().
   */
  public function compiled() {
    return !$this->changed;
  }

  /**
   * Implements PHP magic __toString method to convert the conditions to string.
   *
   * @return string
   *   A string version of the conditions.
   */
  public function __toString() {
    // If the caller forgot to call compile() first, refuse to run.
    if ($this->changed) {
      return NULL;
    }
    return $this->stringVersion;
  }

  /**
   * PHP magic __clone() method.
   *
   * Only copies fields that implement QueryConditionInterface. Also sets
   * $this->changed to TRUE.
   */
  function __clone() {
    $this->changed = TRUE;
    foreach ($this->conditions as $key => $condition) {
      if ($key !== '#conjunction' && $condition['field'] instanceOf QueryConditionInterface) {
        $this->conditions[$key]['field'] = clone($condition['field']);
      }
    }
  }

  /**
   * Gets any special processing requirements for the condition operator.
   *
   * Some condition types require special processing, such as IN, because
   * the value data they pass in is not a simple value. This is a simple
   * overridable lookup function.
   *
   * @param $operator
   *   The condition operator, such as "IN", "BETWEEN", etc. Case-sensitive.
   *
   * @return
   *   The extra handling directives for the specified operator, or NULL.
   */
  protected function mapConditionOperator($operator) {
    // $specials does not use drupal_static as its value never changes.
    static $specials = array(
      'BETWEEN' => array('delimiter' => ' AND '),
      'IN' => array('delimiter' => ', ', 'prefix' => ' (', 'postfix' => ')'),
      'NOT IN' => array('delimiter' => ', ', 'prefix' => ' (', 'postfix' => ')'),
      'EXISTS' => array('prefix' => ' (', 'postfix' => ')'),
      'NOT EXISTS' => array('prefix' => ' (', 'postfix' => ')'),
      'IS NULL' => array('use_value' => FALSE),
      'IS NOT NULL' => array('use_value' => FALSE),
      // Use backslash for escaping wildcard characters.
      'LIKE' => array('postfix' => " ESCAPE '\\\\'"),
      'NOT LIKE' => array('postfix' => " ESCAPE '\\\\'"),
      // These ones are here for performance reasons.
      '=' => array(),
      '<' => array(),
      '>' => array(),
      '>=' => array(),
      '<=' => array(),
    );
    if (isset($specials[$operator])) {
      $return = $specials[$operator];
    }
    else {
      // We need to upper case because PHP index matches are case sensitive but
      // do not need the more expensive drupal_strtoupper because SQL statements are ASCII.
      $operator = strtoupper($operator);
      $return = isset($specials[$operator]) ? $specials[$operator] : array();
    }

    $return += array('operator' => $operator);

    return $return;
  }

}

/**
 * @} End of "ingroup database".
 */
