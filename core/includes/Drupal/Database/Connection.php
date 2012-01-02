<?php

namespace Drupal\Database;

use Drupal\Database\DatabaseTransactionNoActiveException;
use Drupal\Database\DatabaseTransactionOutOfOrderException;

use PDO;
use PDOException;

/**
 * Base Database API class.
 *
 * This class provides a Drupal-specific extension of the PDO database
 * abstraction class in PHP. Every database driver implementation must provide a
 * concrete implementation of it to support special handling required by that
 * database.
 *
 * @see http://php.net/manual/en/book.pdo.php
 */
abstract class Connection extends PDO {

  /**
   * The database target this connection is for.
   *
   * We need this information for later auditing and logging.
   *
   * @var string
   */
  protected $target = NULL;

  /**
   * The key representing this connection.
   *
   * The key is a unique string which identifies a database connection. A
   * connection can be a single server or a cluster of master and slaves (use
   * target to pick between master and slave).
   *
   * @var string
   */
  protected $key = NULL;

  /**
   * The current database logging object for this connection.
   *
   * @var DatabaseLog
   */
  protected $logger = NULL;

  /**
   * Tracks the number of "layers" of transactions currently active.
   *
   * On many databases transactions cannot nest.  Instead, we track
   * nested calls to transactions and collapse them into a single
   * transaction.
   *
   * @var array
   */
  protected $transactionLayers = array();

  /**
   * Index of what driver-specific class to use for various operations.
   *
   * @var array
   */
  protected $driverClasses = array();

  /**
   * The name of the Statement class for this connection.
   *
   * @var string
   */
  protected $statementClass = '\\Drupal\\Database\\DatabaseStatementBase';

  /**
   * Whether this database connection supports transactions.
   *
   * @var bool
   */
  protected $transactionSupport = TRUE;

  /**
   * Whether this database connection supports transactional DDL.
   *
   * Set to FALSE by default because few databases support this feature.
   *
   * @var bool
   */
  protected $transactionalDDLSupport = FALSE;

  /**
   * An index used to generate unique temporary table names.
   *
   * @var integer
   */
  protected $temporaryNameIndex = 0;

  /**
   * The connection information for this connection object.
   *
   * @var array
   */
  protected $connectionOptions = array();

  /**
   * The schema object for this connection.
   *
   * @var object
   */
  protected $schema = NULL;

  /**
   * The prefixes used by this database connection.
   *
   * @var array
   */
  protected $prefixes = array();

  /**
   * List of search values for use in prefixTables().
   *
   * @var array
   */
  protected $prefixSearch = array();

  /**
   * List of replacement values for use in prefixTables().
   *
   * @var array
   */
  protected $prefixReplace = array();

  function __construct($dsn, $username, $password, $driver_options = array()) {
    // Initialize and prepare the connection prefix.
    $this->setPrefix(isset($this->connectionOptions['prefix']) ? $this->connectionOptions['prefix'] : '');

    // Because the other methods don't seem to work right.
    $driver_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

    // Call PDO::__construct and PDO::setAttribute.
    parent::__construct($dsn, $username, $password, $driver_options);

    // Set a specific PDOStatement class if the driver requires that.
    if (!empty($this->statementClass)) {
      $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array($this->statementClass, array($this)));
    }
  }

  /**
   * Returns the default query options for any given query.
   *
   * A given query can be customized with a number of option flags in an
   * associative array:
   * - target: The database "target" against which to execute a query. Valid
   *   values are "default" or "slave". The system will first try to open a
   *   connection to a database specified with the user-supplied key. If one
   *   is not available, it will silently fall back to the "default" target.
   *   If multiple databases connections are specified with the same target,
   *   one will be selected at random for the duration of the request.
   * - fetch: This element controls how rows from a result set will be
   *   returned. Legal values include PDO::FETCH_ASSOC, PDO::FETCH_BOTH,
   *   PDO::FETCH_OBJ, PDO::FETCH_NUM, or a string representing the name of a
   *   class. If a string is specified, each record will be fetched into a new
   *   object of that class. The behavior of all other values is defined by PDO.
   *   See http://php.net/manual/pdostatement.fetch.php
   * - return: Depending on the type of query, different return values may be
   *   meaningful. This directive instructs the system which type of return
   *   value is desired. The system will generally set the correct value
   *   automatically, so it is extremely rare that a module developer will ever
   *   need to specify this value. Setting it incorrectly will likely lead to
   *   unpredictable results or fatal errors. Legal values include:
   *   - Database::RETURN_STATEMENT: Return the prepared statement object for
   *     the query. This is usually only meaningful for SELECT queries, where
   *     the statement object is how one accesses the result set returned by the
   *     query.
   *   - Database::RETURN_AFFECTED: Return the number of rows affected by an
   *     UPDATE or DELETE query. Be aware that means the number of rows actually
   *     changed, not the number of rows matched by the WHERE clause.
   *   - Database::RETURN_INSERT_ID: Return the sequence ID (primary key)
   *     created by an INSERT statement on a table that contains a serial
   *     column.
   *   - Database::RETURN_NULL: Do not return anything, as there is no
   *     meaningful value to return. That is the case for INSERT queries on
   *     tables that do not contain a serial column.
   * - throw_exception: By default, the database system will catch any errors
   *   on a query as an Exception, log it, and then rethrow it so that code
   *   further up the call chain can take an appropriate action. To suppress
   *   that behavior and simply return NULL on failure, set this option to
   *   FALSE.
   *
   * @return
   *   An array of default query options.
   */
  protected function defaultOptions() {
    return array(
      'target' => 'default',
      'fetch' => PDO::FETCH_OBJ,
      'return' => Database::RETURN_STATEMENT,
      'throw_exception' => TRUE,
    );
  }

  /**
   * Returns the connection information for this connection object.
   *
   * Note that Database::getConnectionInfo() is for requesting information
   * about an arbitrary database connection that is defined. This method
   * is for requesting the connection information of this specific
   * open connection object.
   *
   * @return
   *   An array of the connection information. The exact list of
   *   properties is driver-dependent.
   */
  public function getConnectionOptions() {
    return $this->connectionOptions;
  }

  /**
   * Set the list of prefixes used by this database connection.
   *
   * @param $prefix
   *   The prefixes, in any of the multiple forms documented in
   *   default.settings.php.
   */
  protected function setPrefix($prefix) {
    if (is_array($prefix)) {
      $this->prefixes = $prefix + array('default' => '');
    }
    else {
      $this->prefixes = array('default' => $prefix);
    }

    // Set up variables for use in prefixTables(). Replace table-specific
    // prefixes first.
    $this->prefixSearch = array();
    $this->prefixReplace = array();
    foreach ($this->prefixes as $key => $val) {
      if ($key != 'default') {
        $this->prefixSearch[] = '{' . $key . '}';
        $this->prefixReplace[] = $val . $key;
      }
    }
    // Then replace remaining tables with the default prefix.
    $this->prefixSearch[] = '{';
    $this->prefixReplace[] = $this->prefixes['default'];
    $this->prefixSearch[] = '}';
    $this->prefixReplace[] = '';
  }

  /**
   * Appends a database prefix to all tables in a query.
   *
   * Queries sent to Drupal should wrap all table names in curly brackets. This
   * function searches for this syntax and adds Drupal's table prefix to all
   * tables, allowing Drupal to coexist with other systems in the same database
   * and/or schema if necessary.
   *
   * @param $sql
   *   A string containing a partial or entire SQL query.
   *
   * @return
   *   The properly-prefixed string.
   */
  public function prefixTables($sql) {
    return str_replace($this->prefixSearch, $this->prefixReplace, $sql);
  }

  /**
   * Find the prefix for a table.
   *
   * This function is for when you want to know the prefix of a table. This
   * is not used in prefixTables due to performance reasons.
   */
  public function tablePrefix($table = 'default') {
    if (isset($this->prefixes[$table])) {
      return $this->prefixes[$table];
    }
    else {
      return $this->prefixes['default'];
    }
  }

  /**
   * Prepares a query string and returns the prepared statement.
   *
   * This method caches prepared statements, reusing them when
   * possible. It also prefixes tables names enclosed in curly-braces.
   *
   * @param $query
   *   The query string as SQL, with curly-braces surrounding the
   *   table names.
   *
   * @return DatabaseStatementInterface
   *   A PDO prepared statement ready for its execute() method.
   */
  public function prepareQuery($query) {
    $query = $this->prefixTables($query);

    // Call PDO::prepare.
    return parent::prepare($query);
  }

  /**
   * Tells this connection object what its target value is.
   *
   * This is needed for logging and auditing. It's sloppy to do in the
   * constructor because the constructor for child classes has a different
   * signature. We therefore also ensure that this function is only ever
   * called once.
   *
   * @param $target
   *   The target this connection is for. Set to NULL (default) to disable
   *   logging entirely.
   */
  public function setTarget($target = NULL) {
    if (!isset($this->target)) {
      $this->target = $target;
    }
  }

  /**
   * Returns the target this connection is associated with.
   *
   * @return
   *   The target string of this connection.
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * Tells this connection object what its key is.
   *
   * @param $target
   *   The key this connection is for.
   */
  public function setKey($key) {
    if (!isset($this->key)) {
      $this->key = $key;
    }
  }

  /**
   * Returns the key this connection is associated with.
   *
   * @return
   *   The key of this connection.
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * Associates a logging object with this connection.
   *
   * @param $logger
   *   The logging object we want to use.
   */
  public function setLogger(DatabaseLog $logger) {
    $this->logger = $logger;
  }

  /**
   * Gets the current logging object for this connection.
   *
   * @return DatabaseLog
   *   The current logging object for this connection. If there isn't one,
   *   NULL is returned.
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Creates the appropriate sequence name for a given table and serial field.
   *
   * This information is exposed to all database drivers, although it is only
   * useful on some of them. This method is table prefix-aware.
   *
   * @param $table
   *   The table name to use for the sequence.
   * @param $field
   *   The field name to use for the sequence.
   *
   * @return
   *   A table prefix-parsed string for the sequence name.
   */
  public function makeSequenceName($table, $field) {
    return $this->prefixTables('{' . $table . '}_' . $field . '_seq');
  }

  /**
   * Flatten an array of query comments into a single comment string.
   *
   * The comment string will be sanitized to avoid SQL injection attacks.
   *
   * @param $comments
   *   An array of query comment strings.
   *
   * @return
   *   A sanitized comment string.
   */
  public function makeComment($comments) {
    if (empty($comments))
      return '';

    // Flatten the array of comments.
    $comment = implode('; ', $comments);

    // Sanitize the comment string so as to avoid SQL injection attacks.
    return '/* ' . $this->filterComment($comment) . ' */ ';
  }

  /**
   * Sanitize a query comment string.
   *
   * Ensure a query comment does not include strings such as "* /" that might
   * terminate the comment early. This avoids SQL injection attacks via the
   * query comment. The comment strings in this example are separated by a
   * space to avoid PHP parse errors.
   *
   * For example, the comment:
   * @code
   * db_update('example')
   *  ->condition('id', $id)
   *  ->fields(array('field2' => 10))
   *  ->comment('Exploit * / DROP TABLE node; --')
   *  ->execute()
   * @endcode
   *
   * Would result in the following SQL statement being generated:
   * @code
   * "/ * Exploit * / DROP TABLE node; -- * / UPDATE example SET field2=..."
   * @endcode
   *
   * Unless the comment is sanitised first, the SQL server would drop the
   * node table and ignore the rest of the SQL statement.
   *
   * @param $comment
   *   A query comment string.
   *
   * @return
   *   A sanitized version of the query comment string.
   */
  protected function filterComment($comment = '') {
    return preg_replace('/(\/\*\s*)|(\s*\*\/)/', '', $comment);
  }

  /**
   * Executes a query string against the database.
   *
   * This method provides a central handler for the actual execution of every
   * query. All queries executed by Drupal are executed as PDO prepared
   * statements.
   *
   * @param $query
   *   The query to execute. In most cases this will be a string containing
   *   an SQL query with placeholders. An already-prepared instance of
   *   DatabaseStatementInterface may also be passed in order to allow calling
   *   code to manually bind variables to a query. If a
   *   DatabaseStatementInterface is passed, the $args array will be ignored.
   *   It is extremely rare that module code will need to pass a statement
   *   object to this method. It is used primarily for database drivers for
   *   databases that require special LOB field handling.
   * @param $args
   *   An array of arguments for the prepared statement. If the prepared
   *   statement uses ? placeholders, this array must be an indexed array.
   *   If it contains named placeholders, it must be an associative array.
   * @param $options
   *   An associative array of options to control how the query is run. See
   *   the documentation for DatabaseConnection::defaultOptions() for details.
   *
   * @return DatabaseStatementInterface
   *   This method will return one of: the executed statement, the number of
   *   rows affected by the query (not the number matched), or the generated
   *   insert IT of the last query, depending on the value of
   *   $options['return']. Typically that value will be set by default or a
   *   query builder and should not be set by a user. If there is an error,
   *   this method will return NULL and may throw an exception if
   *   $options['throw_exception'] is TRUE.
   *
   * @throws PDOException
   */
  public function query($query, array $args = array(), $options = array()) {

    // Use default values if not already set.
    $options += $this->defaultOptions();

    try {
      // We allow either a pre-bound statement object or a literal string.
      // In either case, we want to end up with an executed statement object,
      // which we pass to PDOStatement::execute.
      if ($query instanceof DatabaseStatementInterface) {
        $stmt = $query;
        $stmt->execute(NULL, $options);
      }
      else {
        $this->expandArguments($query, $args);
        $stmt = $this->prepareQuery($query);
        $stmt->execute($args, $options);
      }

      // Depending on the type of query we may need to return a different value.
      // See DatabaseConnection::defaultOptions() for a description of each
      // value.
      switch ($options['return']) {
        case Database::RETURN_STATEMENT:
          return $stmt;
        case Database::RETURN_AFFECTED:
          return $stmt->rowCount();
        case Database::RETURN_INSERT_ID:
          return $this->lastInsertId();
        case Database::RETURN_NULL:
          return;
        default:
          throw new PDOException('Invalid return directive: ' . $options['return']);
      }
    }
    catch (PDOException $e) {
      if ($options['throw_exception']) {
        // Add additional debug information.
        if ($query instanceof DatabaseStatementInterface) {
          $e->query_string = $stmt->getQueryString();
        }
        else {
          $e->query_string = $query;
        }
        $e->args = $args;
        throw $e;
      }
      return NULL;
    }
  }

  /**
   * Expands out shorthand placeholders.
   *
   * Drupal supports an alternate syntax for doing arrays of values. We
   * therefore need to expand them out into a full, executable query string.
   *
   * @param $query
   *   The query string to modify.
   * @param $args
   *   The arguments for the query.
   *
   * @return
   *   TRUE if the query was modified, FALSE otherwise.
   */
  protected function expandArguments(&$query, &$args) {
    $modified = FALSE;

    // If the placeholder value to insert is an array, assume that we need
    // to expand it out into a comma-delimited set of placeholders.
    foreach (array_filter($args, 'is_array') as $key => $data) {
      $new_keys = array();
      foreach ($data as $i => $value) {
        // This assumes that there are no other placeholders that use the same
        // name.  For example, if the array placeholder is defined as :example
        // and there is already an :example_2 placeholder, this will generate
        // a duplicate key.  We do not account for that as the calling code
        // is already broken if that happens.
        $new_keys[$key . '_' . $i] = $value;
      }

      // Update the query with the new placeholders.
      // preg_replace is necessary to ensure the replacement does not affect
      // placeholders that start with the same exact text. For example, if the
      // query contains the placeholders :foo and :foobar, and :foo has an
      // array of values, using str_replace would affect both placeholders,
      // but using the following preg_replace would only affect :foo because
      // it is followed by a non-word character.
      $query = preg_replace('#' . $key . '\b#', implode(', ', array_keys($new_keys)), $query);

      // Update the args array with the new placeholders.
      unset($args[$key]);
      $args += $new_keys;

      $modified = TRUE;
    }

    return $modified;
  }

  /**
   * Gets the driver-specific override class if any for the specified class.
   *
   * @param string $class
   *   The class for which we want the potentially driver-specific class.
   * @return string
   *   The name of the class that should be used for this driver.
   */
  public function getDriverClass($class) {
    if (empty($this->driverClasses[$class])) {
      $driver = $this->driver();
      $driver_class = "Drupal\\Database\\Driver\\{$driver}\\{$class}";
      $this->driverClasses[$class] = class_exists($driver_class) ? $driver_class : $class;
    }
    return $this->driverClasses[$class];
  }

  /**
   * Prepares and returns a SELECT query object.
   *
   * @param $table
   *   The base table for this query, that is, the first table in the FROM
   *   clause. This table will also be used as the "base" table for query_alter
   *   hook implementations.
   * @param $alias
   *   The alias of the base table of this query.
   * @param $options
   *   An array of options on the query.
   *
   * @return SelectQueryInterface
   *   An appropriate SelectQuery object for this database connection. Note that
   *   it may be a driver-specific subclass of SelectQuery, depending on the
   *   driver.
   *
   * @see SelectQuery
   */
  public function select($table, $alias = NULL, array $options = array()) {
    $class = $this->getDriverClass('Select');
    return new $class($table, $alias, $this, $options);
  }

  /**
   * Prepares and returns an INSERT query object.
   *
   * @param $options
   *   An array of options on the query.
   *
   * @return InsertQuery
   *   A new InsertQuery object.
   *
   * @see InsertQuery
   */
  public function insert($table, array $options = array()) {
    $class = $this->getDriverClass('Insert');
    return new $class($this, $table, $options);
  }

  /**
   * Prepares and returns a MERGE query object.
   *
   * @param $options
   *   An array of options on the query.
   *
   * @return MergeQuery
   *   A new MergeQuery object.
   *
   * @see MergeQuery
   */
  public function merge($table, array $options = array()) {
    $class = $this->getDriverClass('Merge');
    return new $class($this, $table, $options);
  }


  /**
   * Prepares and returns an UPDATE query object.
   *
   * @param $options
   *   An array of options on the query.
   *
   * @return UpdateQuery
   *   A new UpdateQuery object.
   *
   * @see UpdateQuery
   */
  public function update($table, array $options = array()) {
    $class = $this->getDriverClass('Update');
    return new $class($this, $table, $options);
  }

  /**
   * Prepares and returns a DELETE query object.
   *
   * @param $options
   *   An array of options on the query.
   *
   * @return DeleteQuery
   *   A new DeleteQuery object.
   *
   * @see DeleteQuery
   */
  public function delete($table, array $options = array()) {
    $class = $this->getDriverClass('Delete');
    return new $class($this, $table, $options);
  }

  /**
   * Prepares and returns a TRUNCATE query object.
   *
   * @param $options
   *   An array of options on the query.
   *
   * @return TruncateQuery
   *   A new TruncateQuery object.
   *
   * @see TruncateQuery
   */
  public function truncate($table, array $options = array()) {
    $class = $this->getDriverClass('Truncate');
    return new $class($this, $table, $options);
  }

  /**
   * Returns a DatabaseSchema object for manipulating the schema.
   *
   * This method will lazy-load the appropriate schema library file.
   *
   * @return DatabaseSchema
   *   The DatabaseSchema object for this connection.
   */
  public function schema() {
    if (empty($this->schema)) {
      $class = $this->getDriverClass('DatabaseSchema');
      if (class_exists($class)) {
        $this->schema = new $class($this);
      }
    }
    return $this->schema;
  }

  /**
   * Escapes a table name string.
   *
   * Force all table names to be strictly alphanumeric-plus-underscore.
   * For some database drivers, it may also wrap the table name in
   * database-specific escape characters.
   *
   * @return
   *   The sanitized table name string.
   */
  public function escapeTable($table) {
    return preg_replace('/[^A-Za-z0-9_.]+/', '', $table);
  }

  /**
   * Escapes a field name string.
   *
   * Force all field names to be strictly alphanumeric-plus-underscore.
   * For some database drivers, it may also wrap the field name in
   * database-specific escape characters.
   *
   * @return
   *   The sanitized field name string.
   */
  public function escapeField($field) {
    return preg_replace('/[^A-Za-z0-9_.]+/', '', $field);
  }

  /**
   * Escapes an alias name string.
   *
   * Force all alias names to be strictly alphanumeric-plus-underscore. In
   * contrast to DatabaseConnection::escapeField() /
   * DatabaseConnection::escapeTable(), this doesn't allow the period (".")
   * because that is not allowed in aliases.
   *
   * @return
   *   The sanitized field name string.
   */
  public function escapeAlias($field) {
    return preg_replace('/[^A-Za-z0-9_]+/', '', $field);
  }

  /**
   * Escapes characters that work as wildcard characters in a LIKE pattern.
   *
   * The wildcard characters "%" and "_" as well as backslash are prefixed with
   * a backslash. Use this to do a search for a verbatim string without any
   * wildcard behavior.
   *
   * For example, the following does a case-insensitive query for all rows whose
   * name starts with $prefix:
   * @code
   * $result = db_query(
   *   'SELECT * FROM person WHERE name LIKE :pattern',
   *   array(':pattern' => db_like($prefix) . '%')
   * );
   * @endcode
   *
   * Backslash is defined as escape character for LIKE patterns in
   * DatabaseCondition::mapConditionOperator().
   *
   * @param $string
   *   The string to escape.
   *
   * @return
   *   The escaped string.
   */
  public function escapeLike($string) {
    return addcslashes($string, '\%_');
  }

  /**
   * Determines if there is an active transaction open.
   *
   * @return
   *   TRUE if we're currently in a transaction, FALSE otherwise.
   */
  public function inTransaction() {
    return ($this->transactionDepth() > 0);
  }

  /**
   * Determines current transaction depth.
   */
  public function transactionDepth() {
    return count($this->transactionLayers);
  }

  /**
   * Returns a new DatabaseTransaction object on this connection.
   *
   * @param $name
   *   Optional name of the savepoint.
   *
   * @see DatabaseTransaction
   */
  public function startTransaction($name = '') {
    $class = $this->getDriverClass('Transaction');
    return new $class($this, $name);
  }

  /**
   * Rolls back the transaction entirely or to a named savepoint.
   *
   * This method throws an exception if no transaction is active.
   *
   * @param $savepoint_name
   *   The name of the savepoint. The default, 'drupal_transaction', will roll
   *   the entire transaction back.
   *
   * @throws DatabaseTransactionNoActiveException
   *
   * @see DatabaseTransaction::rollback()
   */
  public function rollback($savepoint_name = 'drupal_transaction') {
    if (!$this->supportsTransactions()) {
      return;
    }
    if (!$this->inTransaction()) {
      throw new DatabaseTransactionNoActiveException();
    }
    // A previous rollback to an earlier savepoint may mean that the savepoint
    // in question has already been accidentally committed.
    if (!isset($this->transactionLayers[$savepoint_name])) {
      throw new DatabaseTransactionNoActiveException();
     }

    // We need to find the point we're rolling back to, all other savepoints
    // before are no longer needed. If we rolled back other active savepoints,
    // we need to throw an exception.
    $rolled_back_other_active_savepoints = FALSE;
    while ($savepoint = array_pop($this->transactionLayers)) {
      if ($savepoint == $savepoint_name) {
        // If it is the last the transaction in the stack, then it is not a
        // savepoint, it is the transaction itself so we will need to roll back
        // the transaction rather than a savepoint.
        if (empty($this->transactionLayers)) {
          break;
        }
        $this->query('ROLLBACK TO SAVEPOINT ' . $savepoint);
        $this->popCommittableTransactions();
        if ($rolled_back_other_active_savepoints) {
          throw new DatabaseTransactionOutOfOrderException();
        }
        return;
      }
      else {
        $rolled_back_other_active_savepoints = TRUE;
      }
    }
    parent::rollBack();
    if ($rolled_back_other_active_savepoints) {
      throw new DatabaseTransactionOutOfOrderException();
    }
  }

  /**
   * Increases the depth of transaction nesting.
   *
   * If no transaction is already active, we begin a new transaction.
   *
   * @throws DatabaseTransactionNameNonUniqueException
   *
   * @see DatabaseTransaction
   */
  public function pushTransaction($name) {
    if (!$this->supportsTransactions()) {
      return;
    }
    if (isset($this->transactionLayers[$name])) {
      throw new DatabaseTransactionNameNonUniqueException($name . " is already in use.");
    }
    // If we're already in a transaction then we want to create a savepoint
    // rather than try to create another transaction.
    if ($this->inTransaction()) {
      $this->query('SAVEPOINT ' . $name);
    }
    else {
      parent::beginTransaction();
    }
    $this->transactionLayers[$name] = $name;
  }

  /**
   * Decreases the depth of transaction nesting.
   *
   * If we pop off the last transaction layer, then we either commit or roll
   * back the transaction as necessary. If no transaction is active, we return
   * because the transaction may have manually been rolled back.
   *
   * @param $name
   *   The name of the savepoint
   *
   * @throws DatabaseTransactionNoActiveException
   * @throws DatabaseTransactionCommitFailedException
   *
   * @see DatabaseTransaction
   */
  public function popTransaction($name) {
    if (!$this->supportsTransactions()) {
      return;
    }
    // The transaction has already been committed earlier. There is nothing we
    // need to do. If this transaction was part of an earlier out-of-order
    // rollback, an exception would already have been thrown by
    // Database::rollback().
    if (!isset($this->transactionLayers[$name])) {
      return;
    }

    // Mark this layer as committable.
    $this->transactionLayers[$name] = FALSE;
    $this->popCommittableTransactions();
  }

  /**
   * Internal function: commit all the transaction layers that can commit.
   */
  protected function popCommittableTransactions() {
    // Commit all the committable layers.
    foreach (array_reverse($this->transactionLayers) as $name => $active) {
      // Stop once we found an active transaction.
      if ($active) {
        break;
      }

      // If there are no more layers left then we should commit.
      unset($this->transactionLayers[$name]);
      if (empty($this->transactionLayers)) {
        if (!parent::commit()) {
          throw new DatabaseTransactionCommitFailedException();
        }
      }
      else {
        $this->query('RELEASE SAVEPOINT ' . $name);
      }
    }
  }

  /**
   * Runs a limited-range query on this database object.
   *
   * Use this as a substitute for ->query() when a subset of the query is to be
   * returned. User-supplied arguments to the query should be passed in as
   * separate parameters so that they can be properly escaped to avoid SQL
   * injection attacks.
   *
   * @param $query
   *   A string containing an SQL query.
   * @param $args
   *   An array of values to substitute into the query at placeholder markers.
   * @param $from
   *   The first result row to return.
   * @param $count
   *   The maximum number of result rows to return.
   * @param $options
   *   An array of options on the query.
   *
   * @return DatabaseStatementInterface
   *   A database query result resource, or NULL if the query was not executed
   *   correctly.
   */
  abstract public function queryRange($query, $from, $count, array $args = array(), array $options = array());

  /**
   * Generates a temporary table name.
   *
   * @return
   *   A table name.
   */
  protected function generateTemporaryTableName() {
    return "db_temporary_" . $this->temporaryNameIndex++;
  }

  /**
   * Runs a SELECT query and stores its results in a temporary table.
   *
   * Use this as a substitute for ->query() when the results need to stored
   * in a temporary table. Temporary tables exist for the duration of the page
   * request. User-supplied arguments to the query should be passed in as
   * separate parameters so that they can be properly escaped to avoid SQL
   * injection attacks.
   *
   * Note that if you need to know how many results were returned, you should do
   * a SELECT COUNT(*) on the temporary table afterwards.
   *
   * @param $query
   *   A string containing a normal SELECT SQL query.
   * @param $args
   *   An array of values to substitute into the query at placeholder markers.
   * @param $options
   *   An associative array of options to control how the query is run. See
   *   the documentation for DatabaseConnection::defaultOptions() for details.
   *
   * @return
   *   The name of the temporary table.
   */
  abstract function queryTemporary($query, array $args = array(), array $options = array());

  /**
   * Returns the type of database driver.
   *
   * This is not necessarily the same as the type of the database itself. For
   * instance, there could be two MySQL drivers, mysql and mysql_mock. This
   * function would return different values for each, but both would return
   * "mysql" for databaseType().
   */
  abstract public function driver();

  /**
   * Returns the version of the database server.
   */
  public function version() {
    return $this->getAttribute(PDO::ATTR_SERVER_VERSION);
  }

  /**
   * Determines if this driver supports transactions.
   *
   * @return
   *   TRUE if this connection supports transactions, FALSE otherwise.
   */
  public function supportsTransactions() {
    return $this->transactionSupport;
  }

  /**
   * Determines if this driver supports transactional DDL.
   *
   * DDL queries are those that change the schema, such as ALTER queries.
   *
   * @return
   *   TRUE if this connection supports transactions for DDL queries, FALSE
   *   otherwise.
   */
  public function supportsTransactionalDDL() {
    return $this->transactionalDDLSupport;
  }

  /**
   * Returns the name of the PDO driver for this connection.
   */
  abstract public function databaseType();


  /**
   * Gets any special processing requirements for the condition operator.
   *
   * Some condition types require special processing, such as IN, because
   * the value data they pass in is not a simple value. This is a simple
   * overridable lookup function. Database connections should define only
   * those operators they wish to be handled differently than the default.
   *
   * @param $operator
   *   The condition operator, such as "IN", "BETWEEN", etc. Case-sensitive.
   *
   * @return
   *   The extra handling directives for the specified operator, or NULL.
   *
   * @see DatabaseCondition::compile()
   */
  abstract public function mapConditionOperator($operator);

  /**
   * Throws an exception to deny direct access to transaction commits.
   *
   * We do not want to allow users to commit transactions at any time, only
   * by destroying the transaction object or allowing it to go out of scope.
   * A direct commit bypasses all of the safety checks we've built on top of
   * PDO's transaction routines.
   *
   * @throws DatabaseTransactionExplicitCommitNotAllowedException
   *
   * @see DatabaseTransaction
   */
  public function commit() {
    throw new DatabaseTransactionExplicitCommitNotAllowedException();
  }

  /**
   * Retrieves an unique id from a given sequence.
   *
   * Use this function if for some reason you can't use a serial field. For
   * example, MySQL has no ways of reading of the current value of a sequence
   * and PostgreSQL can not advance the sequence to be larger than a given
   * value. Or sometimes you just need a unique integer.
   *
   * @param $existing_id
   *   After a database import, it might be that the sequences table is behind,
   *   so by passing in the maximum existing id, it can be assured that we
   *   never issue the same id.
   *
   * @return
   *   An integer number larger than any number returned by earlier calls and
   *   also larger than the $existing_id if one was passed in.
   */
  abstract public function nextId($existing_id = 0);
}
