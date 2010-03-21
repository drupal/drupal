<?php
// $Id$

/**
 * @file
 * Base classes for the database layer.
 */

/**
 * @defgroup database Database abstraction layer
 * @{
 * Allow the use of different database servers using the same code base.
 *
 * Drupal provides a database abstraction layer to provide developers with
 * the ability to support multiple database servers easily. The intent of
 * this layer is to preserve the syntax and power of SQL as much as possible,
 * but also allow developers a way to leverage more complex functionality in
 * a unified way. It also provides a structured interface for dynamically
 * constructing queries when appropriate, and enforcing security checks and
 * similar good practices.
 *
 * The system is built atop PHP's PDO (PHP Data Objects) database API and
 * inherits much of its syntax and semantics.
 *
 * Most Drupal database SELECT queries are performed by a call to db_query() or
 * db_query_range(). Module authors should also consider using the PagerDefault
 * Extender for queries that return results that need to be presented on
 * multiple pages, and the Tablesort Extender for generating appropriate queries
 * for sortable tables.
 *
 * For example, one might wish to return a list of the most recent 10 nodes
 * authored by a given user. Instead of directly issuing the SQL query
 * @code
 * SELECT n.nid, n.title, n.created FROM node n WHERE n.uid = $uid LIMIT 0, 10;
 * @endcode
 * one would instead call the Drupal functions:
 * @code
 * $result = db_query_range('SELECT n.nid, n.title, n.created
 *   FROM {node} n WHERE n.uid = :uid', array(':uid' => $uid), 0, 10);
 * foreach($result as $record) {
 *   // Perform operations on $node->title, etc. here.
 * }
 * @endcode
 * Curly braces are used around "node" to provide table prefixing via
 * DatabaseConnection::prefixTables(). The explicit use of a user ID is pulled
 * out into an argument passed to db_query() so that SQL injection attacks
 * from user input can be caught and nullified. The LIMIT syntax varies between
 * database servers, so that is abstracted into db_query_range() arguments.
 * Finally, note the PDO-based ability to foreach() over the result set.
 *
 * All queries are passed as a prepared statement string. A
 * prepared statement is a "template" of a query that omits literal or variable
 * values in favor of placeholders. The values to place into those
 * placeholders are passed separately, and the database driver handles
 * inserting the values into the query in a secure fashion. That means you
 * should never quote or string-escape a value to be inserted into the query.
 *
 * There are two formats for placeholders: named and unnamed. Named placeholders
 * are strongly preferred in all cases as they are more flexible and
 * self-documenting. Named placeholders should start with a colon ":" and can be
 * followed by one or more letters, numbers or underscores.
 *
 * Named placeholders begin with a colon followed by a unique string. Example:
 * @code
 * SELECT nid, title FROM {node} WHERE uid=:uid;
 * @endcode
 *
 * ":uid" is a placeholder that will be replaced with a literal value when
 * the query is executed. A given placeholder label cannot be repeated in a
 * given query, even if the value should be the same. When using named
 * placeholders, the array of arguments to the query must be an associative
 * array where keys are a placeholder label (e.g., :uid) and the value is the
 * corresponding value to use. The array may be in any order.
 *
 * Unnamed placeholders are simply a question mark. Example:
 * @code
 * SELECT nid, title FROM {node} WHERE uid=?;
 * @endcode
 *
 * In this case, the array of arguments must be an indexed array of values to
 * use in the exact same order as the placeholders in the query.
 *
 * Note that placeholders should be a "complete" value. For example, when
 * running a LIKE query the SQL wildcard character, %, should be part of the
 * value, not the query itself. Thus, the following is incorrect:
 * @code
 * SELECT nid, title FROM {node} WHERE title LIKE :title%;
 * @endcode
 * It should instead read:
 * @code
 * SELECT nid, title FROM {node} WHERE title LIKE :title;
 * @endcode
 * and the value for :title should include a % as appropriate. Again, note the
 * lack of quotation marks around :title. Because the value is not inserted
 * into the query as one big string but as an explicitly separate value, the
 * database server knows where the query ends and a value begins. That is
 * considerably more secure against SQL injection than trying to remember
 * which values need quotation marks and string escaping and which don't.
 *
 * INSERT, UPDATE, and DELETE queries need special care in order to behave
 * consistently across all different databases. Therefore, they use a special
 * object-oriented API for defining a query structurally. For example, rather
 * than:
 * @code
 * INSERT INTO node (nid, title, body) VALUES (1, 'my title', 'my body');
 * @endcode
 * one would instead write:
 * @code
 * $fields = array('nid' => 1, 'title' => 'my title', 'body' => 'my body');
 * db_insert('node')->fields($fields)->execute();
 * @endcode
 * This method allows databases that need special data type handling to do so,
 * while also allowing optimizations such as multi-insert queries. UPDATE and
 * DELETE queries have a similar pattern.
 *
 * Drupal also supports transactions, including a transparent fallback for
 * databases that do not support transactions. To start a new transaction,
 * simply call $txn = db_transaction(); in your own code. The transaction will
 * remain open for as long as the variable $txn remains in scope.  When $txn is
 * destroyed, the transaction will be committed.  If your transaction is nested
 * inside of another then Drupal will track each transaction and only commit
 * the outer-most transaction when the last transaction object goes out out of
 * scope, that is, all relevant queries completed successfully.
 *
 * Example:
 * @code
 * function my_transaction_function() {
 *   // The transaction opens here.
 *   $txn = db_transaction();
 *
 *   try {
 *     $id = db_insert('example')
 *       ->fields(array(
 *         'field1' => 'mystring',
 *         'field2' => 5,
 *       ))
 *       ->execute();
 *
 *     my_other_function($id);
 *
 *     return $id;
 *   }
 *   catch (Exception $e) {
 *     // Something went wrong somewhere, so flag the entire transaction to
 *     // roll back instead of getting committed.  It doesn't actually roll back
 *     // yet, just gets flagged to do so.
 *     $txn->rollback();
 *   }
 *
 *   // $txn goes out of scope here.  If there was a problem, it rolls back
 *   // automatically.  If not, it commits automatically.
 * }
 *
 * function my_other_function($id) {
 *   // The transaction is still open here.
 *
 *   if ($id % 2 == 0) {
 *     db_update('example')
 *       ->condition('id', $id)
 *       ->fields(array('field2' => 10))
 *       ->execute();
 *   }
 * }
 * @endcode
 */


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
abstract class DatabaseConnection extends PDO {

  /**
   * The database target this connection is for.
   *
   * We need this information for later auditing and logging.
   *
   * @var string
   */
  protected $target = NULL;

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
   * @var int
   */
  protected $transactionLayers = 0;

  /**
   * Whether or not the active transaction (if any) will be rolled back.
   *
   * @var boolean
   */
  protected $willRollback;

  /**
   * Array of argument arrays for logging post-rollback.
   *
   * @var array
   */
  protected $rollbackLogs = array();

  /**
   * The name of the Select class for this connection.
   *
   * Normally this and the following class names would be static variables,
   * but statics in methods are still global and shared by all instances.
   *
   * @var string
   */
  protected $selectClass = NULL;

  /**
   * The name of the Delete class for this connection.
   *
   * @var string
   */
  protected $deleteClass = NULL;

  /**
   * The name of the Truncate class for this connection.
   *
   * @var string
   */
  protected $truncateClass = NULL;

  /**
   * The name of the Insert class for this connection.
   *
   * @var string
   */
  protected $insertClass = NULL;

  /**
   * The name of the Merge class for this connection.
   *
   * @var string
   */
  protected $mergeClass = NULL;

  /**
   * The name of the Update class for this connection.
   *
   * @var string
   */
  protected $updateClass = NULL;

  /**
   * The name of the Transaction class for this connection.
   *
   * @var string
   */
  protected $transactionClass = NULL;

  /**
   * The name of the Statement class for this connection.
   *
   * @var string
   */
  protected $statementClass = 'DatabaseStatementBase';

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

  function __construct($dsn, $username, $password, $driver_options = array()) {
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
   *   See http://www.php.net/PDOStatement-fetch
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
    global $db_prefix;

    if (is_array($db_prefix)) {
      if (array_key_exists('default', $db_prefix)) {
        $tmp = $db_prefix;
        unset($tmp['default']);
        foreach ($tmp as $key => $val) {
          $sql = strtr($sql, array('{' . $key . '}' => $val . $key));
        }
        return strtr($sql, array('{' => $db_prefix['default'] , '}' => ''));
      }
      else {
        foreach ($db_prefix as $key => $val) {
          $sql = strtr($sql, array('{' . $key . '}' => $val . $key));
        }
        return strtr($sql, array('{' => '' , '}' => ''));
      }
    }
    else {
      return strtr($sql, array('{' => $db_prefix , '}' => ''));
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
      _db_check_install_needed();
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

    foreach ($args as $key => $data) {
      // If the placeholder value to insert is an array, assume that we need
      // to expand it out into a comma-delimited set of placeholders.
      if (is_array($data)) {
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
        // preg_replace is a little bit slower than str_replace, but it is
        // necessary to ensure the replacement does not affect placeholders
        // that start with the same exact text. For example, if the query
        // contains the placeholders :foo and :foobar, and :foo has an array
        // of values, using str_replace would affect both placeholders, but
        // using the following preg_replace would only affect :foo because it
        // is followed by a non-word character.
        $query = preg_replace('#' . $key . '\b#', implode(', ', array_keys($new_keys)), $query);

        // Update the args array with the new placeholders.
        unset($args[$key]);
        $args += $new_keys;

        $modified = TRUE;
      }
    }

    return $modified;
  }

  /**
   * Prepares and returns a SELECT query object with the specified ID.
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
    if (empty($this->selectClass)) {
      $this->selectClass = 'SelectQuery_' . $this->driver();
      if (!class_exists($this->selectClass)) {
        $this->selectClass = 'SelectQuery';
      }
    }
    $class = $this->selectClass;
    // new is documented as the highest precedence operator so this will
    // create a class named $class and pass the arguments into the constructor,
    // instead of calling a function named $class with the arguments listed and
    // then creating using the return value as the class name.
    return new $class($table, $alias, $this, $options);
  }

  /**
   * Prepares and returns an INSERT query object with the specified ID.
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
    if (empty($this->insertClass)) {
      $this->insertClass = 'InsertQuery_' . $this->driver();
      if (!class_exists($this->insertClass)) {
        $this->insertClass = 'InsertQuery';
      }
    }
    $class = $this->insertClass;
    return new $class($this, $table, $options);
  }

  /**
   * Prepares and returns a MERGE query object with the specified ID.
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
    if (empty($this->mergeClass)) {
      $this->mergeClass = 'MergeQuery_' . $this->driver();
      if (!class_exists($this->mergeClass)) {
        $this->mergeClass = 'MergeQuery';
      }
    }
    $class = $this->mergeClass;
    return new $class($this, $table, $options);
  }


  /**
   * Prepares and returns an UPDATE query object with the specified ID.
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
    if (empty($this->updateClass)) {
      $this->updateClass = 'UpdateQuery_' . $this->driver();
      if (!class_exists($this->updateClass)) {
        $this->updateClass = 'UpdateQuery';
      }
    }
    $class = $this->updateClass;
    return new $class($this, $table, $options);
  }

  /**
   * Prepares and returns a DELETE query object with the specified ID.
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
    if (empty($this->deleteClass)) {
      $this->deleteClass = 'DeleteQuery_' . $this->driver();
      if (!class_exists($this->deleteClass)) {
        $this->deleteClass = 'DeleteQuery';
      }
    }
    $class = $this->deleteClass;
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
    if (empty($this->truncateClass)) {
      $this->truncateClass = 'TruncateQuery_' . $this->driver();
      if (!class_exists($this->truncateClass)) {
        $this->truncateClass = 'TruncateQuery';
      }
    }
    $class = $this->truncateClass;
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
      $class_type = 'DatabaseSchema_' . $this->driver();
      $this->schema = new $class_type($this);
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
    return preg_replace('/[^A-Za-z0-9_]+/', '', $table);
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
    return ($this->transactionLayers > 0);
  }

  /**
   * Returns a new DatabaseTransaction object on this connection.
   *
   * @see DatabaseTransaction
   */
  public function startTransaction() {
    if (empty($this->transactionClass)) {
      $this->transactionClass = 'DatabaseTransaction_' . $this->driver();
      if (!class_exists($this->transactionClass)) {
        $this->transactionClass = 'DatabaseTransaction';
      }
    }
    return new $this->transactionClass($this);
  }

  /**
   * Schedules the current transaction for rollback.
   *
   * This method throws an exception if no transaction is active.
   *
   * @param $type
   *   The category to which the rollback message belongs.
   * @param $message
   *   The message to store in the log. Keep $message translatable
   *   by not concatenating dynamic values into it! Variables in the
   *   message should be added by using placeholder strings alongside
   *   the variables argument to declare the value of the placeholders.
   * @param $variables
   *   Array of variables to replace in the message on display or
   *   NULL if message is already translated or not possible to
   *   translate.
   * @param $severity
   *   The severity of the message, as per RFC 3164.
   * @param $link
   *   A link to associate with the message.
   *
   * @see DatabaseTransaction::rollback()
   * @see watchdog()
   */
  public function rollback($type = NULL, $message = NULL, $variables = array(), $severity = NULL, $link = NULL) {
    if ($this->transactionLayers == 0) {
      throw new NoActiveTransactionException();
    }

    // Set the severity to the configured default if not specified.
    if (!isset($severity)) {
      $logging = Database::getLoggingCallback();
      if (is_array($logging)) {
        $severity = $logging['default_severity'];
      }
    }

    // Record in an array to send to the log after transaction rollback.
    // Messages written directly to a log (with a database back-end) will roll
    // back during the following transaction rollback. This is an array because
    // rollback could be requested multiple times during a transaction, and all
    // such errors ought to be logged.
    if (isset($message)) {
      $this->rollbackLogs[] = array(
        'type' => $type,
        'message' => $message,
        'variables' => $variables,
        'severity' => $severity,
        'link' => $link,
      );
    }

    $this->willRollback = TRUE;
  }

  /**
   * Determines if this transaction will roll back.
   *
   * Use this function to skip further operations if the current transaction
   * is already scheduled to roll back. Throws an exception if no transaction
   * is active.
   *
   * @return
   *   TRUE if the transaction will roll back, FALSE otherwise.
   */
  public function willRollback() {
    if ($this->transactionLayers == 0) {
      throw new NoActiveTransactionException();
    }

    return $this->willRollback;
  }

  /**
   * Increases the depth of transaction nesting.
   *
   * If no transaction is already active, we begin a new transaction.
   *
   * @see DatabaseTransaction
   */
  public function pushTransaction() {
    ++$this->transactionLayers;

    if ($this->transactionLayers == 1) {
      if ($this->supportsTransactions()) {
        parent::beginTransaction();
      }
    }
  }

  /**
   * Decreases the depth of transaction nesting.
   *
   * This function first attempts to decrease the number of layers of
   * transaction nesting by one. If there was no active transaction, the
   * function throws an exception. If this was the last transaction layer, the
   * function either rolls back or commits the transaction, depending on whether
   * the transaction was marked for rollback or not.
   *
   * @see DatabaseTransaction
   */
  public function popTransaction() {
    if ($this->transactionLayers == 0) {
      throw new NoActiveTransactionException();
    }

    --$this->transactionLayers;

    if ($this->transactionLayers == 0) {
      if ($this->willRollback) {
        // Reset the rollback status so that the next transaction starts clean.
        $this->willRollback = FALSE;

        // Reset the error log.
        $rollback_logs = $this->rollbackLogs;
        $this->rollbackLogs = array();

        $logging = Database::getLoggingCallback();
        $logging_callback = NULL;
        if (is_array($logging)) {
          $logging_callback = $logging['callback'];
        }

        if ($this->supportsTransactions()) {
          parent::rollBack();
        }
        else {
          if (isset($logging_callback)) {
            // Log the failed rollback.
            $logging_callback('database', 'Explicit rollback failed: not supported on active connection.', array(), $logging['error_severity']);
          }

          // It would be nice to throw an exception here if logging failed,
          // but throwing exceptions in destructors is not supported.
        }

        if (isset($logging_callback)) {
          // Play back the logged errors to the specified logging callback post-
          // rollback.
          foreach ($rollback_logs as $log_item) {
            $logging_callback($log_item['type'], $log_item['message'], $log_item['variables'], $log_item['severity'], $log_item['link']);
          }
        }
      }
      elseif ($this->supportsTransactions()) {
        parent::commit();
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
   * Returns the type of the database being accessed.
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
   * @see DatabaseTransaction
   */
  public function commit() {
    throw new ExplicitTransactionsNotSupportedException();
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

/**
 * Primary front-controller for the database system.
 *
 * This class is uninstantiatable and un-extendable. It acts to encapsulate
 * all control and shepherding of database connections into a single location
 * without the use of globals.
 */
abstract class Database {

  /**
   * Flag to indicate a query call should simply return NULL.
   *
   * This is used for queries that have no reasonable return value anyway, such
   * as INSERT statements to a table without a serial primary key.
   */
  const RETURN_NULL = 0;

  /**
   * Flag to indicate a query call should return the prepared statement.
   */
  const RETURN_STATEMENT = 1;

  /**
   * Flag to indicate a query call should return the number of affected rows.
   */
  const RETURN_AFFECTED = 2;

  /**
   * Flag to indicate a query call should return the "last insert id".
   */
  const RETURN_INSERT_ID = 3;

  /**
   * An nested array of all active connections. It is keyed by database name
   * and target.
   *
   * @var array
   */
  static protected $connections = array();

  /**
   * A processed copy of the database connection information from settings.php.
   *
   * @var array
   */
  static protected $databaseInfo = NULL;

  /**
   * A list of key/target credentials to simply ignore.
   *
   * @var array
   */
  static protected $ignoreTargets = array();

  /**
   * The key of the currently active database connection.
   *
   * @var string
   */
  static protected $activeKey = 'default';

  /**
   * An array of active query log objects.
   *
   * Every connection has one and only one logger object for all targets and
   * logging keys.
   *
   * array(
   *   '$db_key' => DatabaseLog object.
   * );
   *
   * @var array
   */
  static protected $logs = array();

  /**
   * A logging function callback array.
   *
   * The function must accept the same function signature as Drupal's
   * watchdog(). The array contains key/value pairs for callback (string),
   * default_severity (int), and error_severity (int).
   *
   * @var string
   */
  static protected $logging_callback = NULL;

  /**
   * Starts logging a given logging key on the specified connection.
   *
   * @param $logging_key
   *   The logging key to log.
   * @param $key
   *   The database connection key for which we want to log.
   *
   * @return DatabaseLog
   *   The query log object. Note that the log object does support richer
   *   methods than the few exposed through the Database class, so in some
   *   cases it may be desirable to access it directly.
   *
   * @see DatabaseLog
   */
  final public static function startLog($logging_key, $key = 'default') {
    if (empty(self::$logs[$key])) {
      self::$logs[$key] = new DatabaseLog($key);

      // Every target already active for this connection key needs to have the
      // logging object associated with it.
      if (!empty(self::$connections[$key])) {
        foreach (self::$connections[$key] as $connection) {
          $connection->setLogger(self::$logs[$key]);
        }
      }
    }

    self::$logs[$key]->start($logging_key);
    return self::$logs[$key];
  }

  /**
   * Sets a logging callback for notices and errors.
   *
   * @param $logging_callback
   *   The function to use as the logging callback.
   * @param $logging_default_severity
   *   The default severity level to use for logged messages.
   * @param $logging_error_severity
   *   The severity level to use for logging error messages.
   *
   * @see watchdog()
   */
  final public static function setLoggingCallback($callback, $default_severity, $error_severity) {
    self::$logging_callback = array(
      'callback' => $callback,
      'default_severity' => $default_severity,
      'error_severity' => $error_severity,
    );
  }

  /**
   * Gets the logging callback for notices and errors.
   *
   * @return
   *   An array with the logging callback and severity levels.
   *
   * @see watchdog()
   */
  final public static function getLoggingCallback() {
    return self::$logging_callback;
  }

  /**
   * Retrieves the queries logged on for given logging key.
   *
   * This method also ends logging for the specified key. To get the query log
   * to date without ending the logger request the logging object by starting
   * it again (which does nothing to an open log key) and call methods on it as
   * desired.
   *
   * @param $logging_key
   *   The logging key to log.
   * @param $key
   *   The database connection key for which we want to log.
   *
   * @return array
   *   The query log for the specified logging key and connection.
   *
   * @see DatabaseLog
   */
  final public static function getLog($logging_key, $key = 'default') {
    if (empty(self::$logs[$key])) {
      return NULL;
    }
    $queries = self::$logs[$key]->get($logging_key);
    self::$logs[$key]->end($logging_key);
    return $queries;
  }

  /**
   * Gets the connection object for the specified database key and target.
   *
   * @param $target
   *   The database target name.
   * @param $key
   *   The database connection key. Defaults to NULL which means the active key.
   *
   * @return DatabaseConnection
   *   The corresponding connection object.
   */
  final public static function getConnection($target = 'default', $key = NULL) {
    if (!isset($key)) {
      // By default, we want the active connection, set in setActiveConnection.
      $key = self::$activeKey;
    }
    // If the requested target does not exist, or if it is ignored, we fall back
    // to the default target. The target is typically either "default" or
    // "slave", indicating to use a slave SQL server if one is available. If
    // it's not available, then the default/master server is the correct server
    // to use.
    if (!empty(self::$ignoreTargets[$key][$target]) || !isset(self::$databaseInfo[$key][$target])) {
      $target = 'default';
    }

    if (!isset(self::$connections[$key][$target])) {
      // If necessary, a new connection is opened.
      self::$connections[$key][$target] = self::openConnection($key, $target);
    }

    return isset(self::$connections[$key][$target]) ? self::$connections[$key][$target] : NULL;
  }

  /**
   * Determines if there is an active connection.
   *
   * Note that this method will return FALSE if no connection has been
   * established yet, even if one could be.
   *
   * @return
   *   TRUE if there is at least one database connection established, FALSE
   *   otherwise.
   */
  final public static function isActiveConnection() {
    return !empty(self::$activeKey) && !empty(self::$connections) && !empty(self::$connections[self::$activeKey]);
  }

  /**
   * Sets the active connection to the specified key.
   *
   * @return
   *   The previous database connection key.
   */
  final public static function setActiveConnection($key = 'default') {
    if (empty(self::$databaseInfo)) {
      self::parseConnectionInfo();
    }

    if (!empty(self::$databaseInfo[$key])) {
      $old_key = self::$activeKey;
      self::$activeKey = $key;
      return $old_key;
    }
  }

  /**
   * Process the configuration file for database information.
   */
  final public static function parseConnectionInfo() {
    global $databases;

    _db_check_install_needed();

    $databaseInfo = $databases;
    foreach ($databaseInfo as $index => $info) {
      foreach ($databaseInfo[$index] as $target => $value) {
        // If there is no "driver" property, then we assume it's an array of
        // possible connections for this target. Pick one at random. That allows
        //  us to have, for example, multiple slave servers.
        if (empty($value['driver'])) {
          $databaseInfo[$index][$target] = $databaseInfo[$index][$target][mt_rand(0, count($databaseInfo[$index][$target]) - 1)];
        }
      }
    }

    if (!is_array(self::$databaseInfo)) {
      self::$databaseInfo = $databaseInfo;
    }

    // Merge the new $databaseInfo into the existing.
    // array_merge_recursive() cannot be used, as it would make multiple
    // database, user, and password keys in the same database array.
    else {
      foreach ($databaseInfo as $database_key => $database_values) {
        foreach ($database_values as $target => $target_values) {
          self::$databaseInfo[$database_key][$target] = $target_values;
        }
      }
    }
  }

  /**
   * Adds database connection information for a given key/target.
   *
   * This method allows the addition of new connection credentials at runtime.
   * Under normal circumstances the preferred way to specify database
   * credentials is via settings.php. However, this method allows them to be
   * added at arbitrary times, such as during unit tests, when connecting to
   * admin-defined third party databases, etc.
   *
   * If the given key/target pair already exists, this method will be ignored.
   *
   * @param $key
   *   The database key.
   * @param $target
   *   The database target name.
   * @param $info
   *   The database connection information, as it would be defined in
   *   settings.php. Note that the structure of this array will depend on the
   *   database driver it is connecting to.
   */
  public static function addConnectionInfo($key, $target, $info) {
    if (empty(self::$databaseInfo[$key][$target])) {
      self::$databaseInfo[$key][$target] = $info;
    }
  }

  /**
   * Gets information on the specified database connection.
   *
   * @param $connection
   *   The connection key for which we want information.
   */
  final public static function getConnectionInfo($key = 'default') {
    if (empty(self::$databaseInfo)) {
      self::parseConnectionInfo();
    }

    if (!empty(self::$databaseInfo[$key])) {
      return self::$databaseInfo[$key];
    }

  }

  /**
   * Opens a connection to the server specified by the given key and target.
   *
   * @param $key
   *   The database connection key, as specified in settings.php. The default is
   *   "default".
   * @param $target
   *   The database target to open.
   */
  final protected static function openConnection($key, $target) {
    global $db_prefix;

    if (empty(self::$databaseInfo)) {
      self::parseConnectionInfo();
    }
    try {
      // If the requested database does not exist then it is an unrecoverable
      // error.
      if (!isset(self::$databaseInfo[$key])) {
        throw new Exception('DB does not exist');
      }

      if (!$driver = self::$databaseInfo[$key][$target]['driver']) {
        throw new Exception('Drupal is not set up');
      }

      // We cannot rely on the registry yet, because the registry requires an
      // open database connection.
      $driver_class = 'DatabaseConnection_' . $driver;
      require_once DRUPAL_ROOT . '/includes/database/' . $driver . '/database.inc';
      $new_connection = new $driver_class(self::$databaseInfo[$key][$target]);
      $new_connection->setTarget($target);

      // If we have any active logging objects for this connection key, we need
      // to associate them with the connection we just opened.
      if (!empty(self::$logs[$key])) {
        $new_connection->setLogger(self::$logs[$key]);
      }

      // We need to pass around the simpletest database prefix in the request
      // and we put that in the user_agent header. The header HMAC was already
      // validated in bootstrap.inc.
      if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/^(simpletest\d+);/", $_SERVER['HTTP_USER_AGENT'], $matches)) {
        $db_prefix_string = is_array($db_prefix) ? $db_prefix['default'] : $db_prefix;
        $db_prefix = $db_prefix_string . $matches[1];
      }
      return $new_connection;
    }
    catch (Exception $e) {
      // It is extremely rare that an exception will be generated here other
      // than when installing. We therefore intercept it and try the installer,
      // passing on the exception otherwise.
      _db_check_install_needed();
      throw $e;
    }
  }

  /**
   * Closes a connection to the server specified by the given key and target.
   *
   * @param $target
   *   The database target name.  Defaults to NULL meaning that all target
   *   connections will be closed.
   * @param $key
   *   The database connection key. Defaults to NULL which means the active key.
   */
  public static function closeConnection($target = NULL, $key = NULL) {
    // Gets the active connection by default.
    if (!isset($key)) {
      $key = self::$activeKey;
    }
    // To close the connection, we need to unset the static variable.
    if (isset($target)) {
      unset(self::$connections[$key][$target]);
    }
    else {
      unset(self::$connections[$key]);
    }
  }

  /**
   * Instructs the system to temporarily ignore a given key/target.
   *
   * At times we need to temporarily disable slave queries. To do so, call this
   * method with the database key and the target to disable. That database key
   * will then always fall back to 'default' for that key, even if it's defined.
   *
   * @param $key
   *   The database connection key.
   * @param $target
   *   The target of the specified key to ignore.
   */
  public static function ignoreTarget($key, $target) {
    self::$ignoreTargets[$key][$target] = TRUE;
  }

}

/**
 * Exception for when popTransaction() is called with no active transaction.
 */
class NoActiveTransactionException extends Exception { }

/**
 * Exception to deny attempts to explicitly manage transactions.
 *
 * This exception will be thrown when the PDO connection commit() is called.
 * Code should never call this method directly.
 */
class ExplicitTransactionsNotSupportedException extends Exception { }

/**
 * Exception thrown for merge queries that do not make semantic sense.
 *
 * There are many ways that a merge query could be malformed.  They should all
 * throw this exception and set an appropriately descriptive message.
 */
class InvalidMergeQueryException extends Exception {}

/**
 * Exception thrown if an insert query specifies a field twice.
 *
 * It is not allowed to specify a field as default and insert field, this
 * exception is thrown if that is the case.
 */
class FieldsOverlapException extends Exception {}

/**
 * Exception thrown if an insert query doesn't specify insert or default fields.
 */
class NoFieldsException extends Exception {}

/**
 * A wrapper class for creating and managing database transactions.
 *
 * Not all databases or database configurations support transactions. For
 * example, MySQL MyISAM tables do not. It is also easy to begin a transaction
 * and then forget to commit it, which can lead to connection errors when
 * another transaction is started.
 *
 * This class acts as a wrapper for transactions. To begin a transaction,
 * simply instantiate it. When the object goes out of scope and is destroyed
 * it will automatically commit. It also will check to see if the specified
 * connection supports transactions. If not, it will simply skip any transaction
 * commands, allowing user-space code to proceed normally. The only difference
 * is that rollbacks won't actually do anything.
 *
 * In the vast majority of cases, you should not instantiate this class
 * directly. Instead, call ->startTransaction(), from the appropriate connection
 * object.
 */
class DatabaseTransaction {

  /**
   * The connection object for this transaction.
   *
   * @var DatabaseConnection
   */
  protected $connection;

  public function __construct(DatabaseConnection &$connection) {
    $this->connection = &$connection;
    $this->connection->pushTransaction();
  }

  public function __destruct() {
    $this->connection->popTransaction();
  }

  /**
   * Rolls back the current transaction.
   *
   * This is just a wrapper method to rollback whatever transaction stack we are
   * currently in, which is managed by the connection object itself.
   *
   * @param $type
   *   The category to which the rollback message belongs.
   * @param $message
   *   The message to store in the log. Keep $message translatable by not
   *   concatenating dynamic values into it! Variables in the message should be
   *   added by using placeholder strings alongside the variable's argument to
   *   declare the value of the placeholders.
   * @param $variables
   *   Array of variables to replace in the message on display or NULL if the
   *   message is already translated or not possible to translate.
   * @param $severity
   *   The severity of the message, as per RFC 3164.
   * @param $link
   *   A link to associate with the message.
   *
   * @see DatabaseConnection::rollback()
   * @see watchdog()
   */
  public function rollback($type = NULL, $message = NULL, $variables = array(), $severity = NULL, $link = NULL) {
    if (!isset($severity)) {
      $logging = Database::getLoggingCallback();
      if (is_array($logging)) {
        $severity = $logging['default_severity'];
      }
    }
    $this->connection->rollback($type, $message, $variables, $severity, $link);
  }

  /**
   * Determines if this transaction will roll back.
   */
  public function willRollback() {
    return $this->connection->willRollback();
  }

}

/**
 * A prepared statement.
 *
 * Some methods in that class are purposely commented out. Due to a change in
 * how PHP defines PDOStatement, we can't define a signature for those methods
 * that will work the same way between versions older than 5.2.6 and later
 * versions.
 *
 * Please refer to http://bugs.php.net/bug.php?id=42452 for more details.
 *
 * Child implementations should either extend PDOStatement:
 * @code
 * class DatabaseStatement_oracle extends PDOStatement implements DatabaseStatementInterface {}
 * @endcode
 * or implement their own class, but in that case they will also have to
 * implement the Iterator or IteratorArray interfaces before
 * DatabaseStatementInterface:
 * @code
 * class DatabaseStatement_oracle implements Iterator, DatabaseStatementInterface {}
 * @endcode
 */
interface DatabaseStatementInterface extends Traversable {

  /**
   * Executes a prepared statement
   *
   * @param $args
   *   An array of values with as many elements as there are bound parameters in
   *   the SQL statement being executed.
   * @param $options
   *   An array of options for this query.
   *
   * @return
   *   TRUE on success, or FALSE on failure.
   */
  public function execute($args = array(), $options = array());

  /**
   * Gets the query string of this statement.
   *
   * @return
   *   The query string, in its form with placeholders.
   */
  public function getQueryString();

  /**
   * Returns the number of rows affected by the last SQL statement.
   *
   * @return
   *   The number of rows affected by the last DELETE, INSERT, or UPDATE
   *   statement executed.
   */
  public function rowCount();

  /**
   * Sets the default fetch mode for this statement.
   *
   * See http://php.net/manual/en/pdo.constants.php for the definition of the
   * constants used.
   *
   * @param $mode
   *   One of the PDO::FETCH_* constants.
   * @param $a1
   *   An option depending of the fetch mode specified by $mode:
   *   - for PDO::FETCH_COLUMN, the index of the column to fetch
   *   - for PDO::FETCH_CLASS, the name of the class to create
   *   - for PDO::FETCH_INTO, the object to add the data to
   * @param $a2
   *   If $mode is PDO::FETCH_CLASS, the optional arguments to pass to the
   *   constructor.
   */
  // public function setFetchMode($mode, $a1 = NULL, $a2 = array());

  /**
   * Fetches the next row from a result set.
   *
   * See http://php.net/manual/en/pdo.constants.php for the definition of the
   * constants used.
   *
   * @param $mode
   *   One of the PDO::FETCH_* constants.
   *   Default to what was specified by setFetchMode().
   * @param $cursor_orientation
   *   Not implemented in all database drivers, don't use.
   * @param $cursor_offset
   *   Not implemented in all database drivers, don't use.
   *
   * @return
   *   A result, formatted according to $mode.
   */
  // public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL);

  /**
   * Returns a single field from the next record of a result set.
   *
   * @param $index
   *   The numeric index of the field to return. Defaults to the first field.
   *
   * @return
   *   A single field from the next record.
   */
  public function fetchField($index = 0);

  /**
   * Fetches the next row and returns it as an object.
   *
   * The object will be of the class specified by DatabaseStatementInterface::setFetchMode()
   * or stdClass if not specified.
   */
  // public function fetchObject();

  /**
   * Fetches the next row and returns it as an associative array.
   *
   * This method corresponds to PDOStatement::fetchObject(), but for associative
   * arrays. For some reason PDOStatement does not have a corresponding array
   * helper method, so one is added.
   *
   * @return
   *   An associative array.
   */
  public function fetchAssoc();

  /**
   * Returns an array containing all of the result set rows.
   *
   * @param $mode
   *   One of the PDO::FETCH_* constants.
   * @param $column_index
   *   If $mode is PDO::FETCH_COLUMN, the index of the column to fetch.
   * @param $constructor_arguments
   *   If $mode is PDO::FETCH_CLASS, the arguments to pass to the constructor.
   *
   * @return
   *   An array of results.
   */
  // function fetchAll($mode = NULL, $column_index = NULL, array $constructor_arguments);

  /**
   * Returns an entire single column of a result set as an indexed array.
   *
   * Note that this method will run the result set to the end.
   *
   * @param $index
   *   The index of the column number to fetch.
   *
   * @return
   *   An indexed array.
   */
  public function fetchCol($index = 0);

  /**
   * Returns the entire result set as a single associative array.
   *
   * This method is only useful for two-column result sets. It will return an
   * associative array where the key is one column from the result set and the
   * value is another field. In most cases, the default of the first two columns
   * is appropriate.
   *
   * Note that this method will run the result set to the end.
   *
   * @param $key_index
   *   The numeric index of the field to use as the array key.
   * @param $value_index
   *   The numeric index of the field to use as the array value.
   *
   * @return
   *   An associative array.
   */
  public function fetchAllKeyed($key_index = 0, $value_index = 1);

  /**
   * Returns the result set as an associative array keyed by the given field.
   *
   * If the given key appears multiple times, later records will overwrite
   * earlier ones.
   *
   * @param $key
   *   The name of the field on which to index the array.
   * @param $fetch
   *   The fetchmode to use. If set to PDO::FETCH_ASSOC, PDO::FETCH_NUM, or
   *   PDO::FETCH_BOTH the returned value with be an array of arrays. For any
   *   other value it will be an array of objects.
   *
   * @return
   *   An associative array.
   */
  public function fetchAllAssoc($key, $fetch = PDO::FETCH_OBJ);
}

/**
 * Default implementation of DatabaseStatementInterface.
 *
 * PDO allows us to extend the PDOStatement class to provide additional
 * functionality beyond that offered by default. We do need extra
 * functionality. By default, this class is not driver-specific. If a given
 * driver needs to set a custom statement class, it may do so in its
 * constructor.
 *
 * @see http://us.php.net/pdostatement
 */
class DatabaseStatementBase extends PDOStatement implements DatabaseStatementInterface {

  /**
   * Reference to the database connection object for this statement.
   *
   * The name $dbh is inherited from PDOStatement.
   *
   * @var DatabaseConnection
   */
  public $dbh;

  protected function __construct($dbh) {
    $this->dbh = $dbh;
    $this->setFetchMode(PDO::FETCH_OBJ);
  }

  public function execute($args = array(), $options = array()) {
    if (isset($options['fetch'])) {
      if (is_string($options['fetch'])) {
        // Default to an object. Note: db fields will be added to the object
        // before the constructor is run. If you need to assign fields after
        // the constructor is run, see http://drupal.org/node/315092.
        $this->setFetchMode(PDO::FETCH_CLASS, $options['fetch']);
      }
      else {
        $this->setFetchMode($options['fetch']);
      }
    }

    $logger = $this->dbh->getLogger();
    if (!empty($logger)) {
      $query_start = microtime(TRUE);
    }

    $return = parent::execute($args);

    if (!empty($logger)) {
      $query_end = microtime(TRUE);
      $logger->log($this, $args, $query_end - $query_start);
    }

    return $return;
  }

  public function getQueryString() {
    return $this->queryString;
  }

  public function fetchCol($index = 0) {
    return $this->fetchAll(PDO::FETCH_COLUMN, $index);
  }

  public function fetchAllAssoc($key, $fetch = PDO::FETCH_OBJ) {
    $return = array();
    $this->setFetchMode($fetch);
    if (in_array($fetch, array(PDO::FETCH_ASSOC, PDO::FETCH_NUM, PDO::FETCH_BOTH))) {
      foreach ($this as $record) {
        $return[$record[$key]] = $record;
      }
    }
    else {
      foreach ($this as $record) {
        $return[$record->$key] = $record;
      }
    }
    return $return;
  }

  public function fetchAllKeyed($key_index = 0, $value_index = 1) {
    $return = array();
    $this->setFetchMode(PDO::FETCH_NUM);
    foreach ($this as $record) {
      $return[$record[$key_index]] = $record[$value_index];
    }
    return $return;
  }

  public function fetchField($index = 0) {
    // Call PDOStatement::fetchColumn to fetch the field.
    return $this->fetchColumn($index);
  }

  public function fetchAssoc() {
    // Call PDOStatement::fetch to fetch the row.
    return $this->fetch(PDO::FETCH_ASSOC);
  }
}

/**
 * Empty implementation of a database statement.
 *
 * This class satisfies the requirements of being a database statement/result
 * object, but does not actually contain data.  It is useful when developers
 * need to safely return an "empty" result set without connecting to an actual
 * database.  Calling code can then treat it the same as if it were an actual
 * result set that happens to contain no records.
 *
 * @see SearchQuery
 */
class DatabaseStatementEmpty implements Iterator, DatabaseStatementInterface {

  public function execute($args = array(), $options = array()) {
    return FALSE;
  }

  public function getQueryString() {
    return '';
  }

  public function rowCount() {
    return 0;
  }

  public function setFetchMode($mode, $a1 = NULL, $a2 = array()) {
    return;
  }

  public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL) {
    return NULL;
  }

  public function fetchField($index = 0) {
    return NULL;
  }

  public function fetchObject() {
    return NULL;
  }

  public function fetchAssoc() {
    return NULL;
  }

  function fetchAll($mode = NULL, $column_index = NULL, array $constructor_arguments = array()) {
    return array();
  }

  public function fetchCol($index = 0) {
    return array();
  }

  public function fetchAllKeyed($key_index = 0, $value_index = 1) {
    return array();
  }

  public function fetchAllAssoc($key, $fetch = PDO::FETCH_OBJ) {
    return array();
  }

  /* Implementations of Iterator. */

  public function current() {
    return NULL;
  }

  public function key() {
    return NULL;
  }

  public function rewind() {
    // Nothing to do: our DatabaseStatement can't be rewound.
  }

  public function next() {
    // Do nothing, since this is an always-empty implementation.
  }

  public function valid() {
    return FALSE;
  }
}


/**
 * The following utility functions are simply convenience wrappers.
 *
 * They should never, ever have any database-specific code in them.
 */

/**
 * Executes an arbitrary query string against the active database.
 *
 * Do not use this function for INSERT, UPDATE, or DELETE queries. Those should
 * be handled via the appropriate query builder factory. Use this function for
 * SELECT queries that do not require a query builder.
 *
 * @param $query
 *   The prepared statement query to run. Although it will accept both named and
 *   unnamed placeholders, named placeholders are strongly preferred as they are
 *   more self-documenting.
 * @param $args
 *   An array of values to substitute into the query. If the query uses named
 *   placeholders, this is an associative array in any order. If the query uses
 *   unnamed placeholders (?), this is an indexed array and the order must match
 *   the order of placeholders in the query string.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return DatabaseStatementInterface
 *   A prepared statement object, already executed.
 *
 * @see DatabaseConnection::defaultOptions()
 */
function db_query($query, array $args = array(), array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }

  return Database::getConnection($options['target'])->query($query, $args, $options);
}

/**
 * Executes a query against the active database, restricted to a range.
 *
 * @param $query
 *   The prepared statement query to run. Although it will accept both named and
 *   unnamed placeholders, named placeholders are strongly preferred as they are
 *   more self-documenting.
 * @param $from
 *   The first record from the result set to return.
 * @param $count
 *   The number of records to return from the result set.
 * @param $args
 *   An array of values to substitute into the query. If the query uses named
 *   placeholders, this is an associative array in any order. If the query uses
 *   unnamed placeholders (?), this is an indexed array and the order must match
 *   the order of placeholders in the query string.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return DatabaseStatementInterface
 *   A prepared statement object, already executed.
 *
 * @see DatabaseConnection::defaultOptions()
 */
function db_query_range($query, $from, $count, array $args = array(), array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }

  return Database::getConnection($options['target'])->queryRange($query, $from, $count, $args, $options);
}

/**
 * Executes a query string and saves the result set to a temporary table.
 *
 * The execution of the query string happens against the active database.
 *
 * @param $query
 *   The prepared statement query to run. Although it will accept both named and
 *   unnamed placeholders, named placeholders are strongly preferred as they are
 *   more self-documenting.
 * @param $args
 *   An array of values to substitute into the query. If the query uses named
 *   placeholders, this is an associative array in any order. If the query uses
 *   unnamed placeholders (?), this is an indexed array and the order must match
 *   the order of placeholders in the query string.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return
 *   The name of the temporary table.
 *
 * @see DatabaseConnection::defaultOptions()
 */
function db_query_temporary($query, array $args = array(), array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }

  return Database::getConnection($options['target'])->queryTemporary($query, $args, $options);
}

/**
 * Returns a new InsertQuery object for the active database.
 *
 * @param $table
 *   The table into which to insert.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return InsertQuery
 *   A new InsertQuery object for this connection.
 */
function db_insert($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'slave') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->insert($table, $options);
}

/**
 * Returns a new MergeQuery object for the active database.
 *
 * @param $table
 *   The table into which to merge.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return MergeQuery
 *   A new MergeQuery object for this connection.
 */
function db_merge($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'slave') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->merge($table, $options);
}

/**
 * Returns a new UpdateQuery object for the active database.
 *
 * @param $table
 *   The table to update.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return UpdateQuery
 *   A new UpdateQuery object for this connection.
 */
function db_update($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'slave') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->update($table, $options);
}

/**
 * Returns a new DeleteQuery object for the active database.
 *
 * @param $table
 *   The table from which to delete.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return DeleteQuery
 *   A new DeleteQuery object for this connection.
 */
function db_delete($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'slave') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->delete($table, $options);
}

/**
 * Returns a new TruncateQuery object for the active database.
 *
 * @param $table
 *   The table from which to delete.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return TruncateQuery
 *   A new TruncateQuery object for this connection.
 */
function db_truncate($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'slave') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->truncate($table, $options);
}

/**
 * Returns a new SelectQuery object for the active database.
 *
 * @param $table
 *   The base table for this query. May be a string or another SelectQuery
 *   object. If a query object is passed, it will be used as a subselect.
 * @param $alias
 *   The alias for the base table of this query.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return SelectQuery
 *   A new SelectQuery object for this connection.
 */
function db_select($table, $alias = NULL, array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->select($table, $alias, $options);
}

/**
 * Returns a new transaction object for the active database.
 *
 * @param $required
 *   TRUE if the calling code will not function properly without transaction
 *   support.  If set to TRUE and the active database does not support
 *   transactions, a TransactionsNotSupportedException exception will be thrown.
 * @param $options
 *   An array of options to control how the transaction operates.  Only the
 *   target key has any meaning in this case.
 *
 * @return DatabaseTransaction
 *   A new DatabaseTransaction object for this connection.
 */
function db_transaction($required = FALSE, Array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->startTransaction($required);
}

/**
 * Sets a new active database.
 *
 * @param $key
 *   The key in the $databases array to set as the default database.
 *
 * @return
 *   The key of the formerly active database.
 */
function db_set_active($key = 'default') {
  return Database::setActiveConnection($key);
}

/**
 * Determines if there is an active connection.
 *
 * Note that this method will return FALSE if no connection has been established
 * yet, even if one could be.
 *
 * @return
 *   TRUE if there is at least one database connection established, FALSE
 *   otherwise.
 */
function db_is_active() {
  return Database::isActiveConnection();
}

/**
 * Restricts a dynamic table, column, or constraint name to safe characters.
 *
 * Only keeps alphanumeric and underscores.
 *
 * @param $table
 *   The table name to escape.
 *
 * @return
 *   The escaped table name as a string.
 */
function db_escape_table($table) {
  return Database::getConnection()->escapeTable($table);
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
function db_like($string) {
  return Database::getConnection()->escapeLike($string);
}

/**
 * Retrieves the name of the currently active database driver.
 *
 * @return
 *   The name of the currently active database driver.
 */
function db_driver() {
  return Database::getConnection()->driver();
}

/**
 * Closes the active database connection.
 *
 * @param $options
 *   An array of options to control which connection is closed.  Only the target
 *   key has any meaning in this case.
 */
function db_close(array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = NULL;
  }
  Database::closeConnection($options['target']);
}

/**
 * Retrieves a unique id.
 *
 * Use this function if for some reason you can't use a serial field, normally a
 * serial field with db_last_insert_id is preferred.
 *
 * @param $existing_id
 *   After a database import, it might be that the sequences table is behind, so
 *   by passing in a minimum ID, it can be assured that we never issue the same
 *   ID.
 *
 * @return
 *   An integer number larger than any number returned before for this sequence.
 */
function db_next_id($existing_id = 0) {
  return Database::getConnection()->nextId($existing_id);
}

/**
 * @} End of "defgroup database".
 */


/**
 * @ingroup schemaapi
 * @{
 */

/**
 * Creates a new table from a Drupal table definition.
 *
 * @param $name
 *   The name of the table to create.
 * @param $table
 *   A Schema API table definition array.
 */
function db_create_table($name, $table) {
  return Database::getConnection()->schema()->createTable($name, $table);
}

/**
 * Returns an array of field names from an array of key/index column specifiers.
 *
 * This is usually an identity function but if a key/index uses a column prefix
 * specification, this function extracts just the name.
 *
 * @param $fields
 *   An array of key/index column specifiers.
 *
 * @return
 *   An array of field names.
 */
function db_field_names($fields) {
  return Database::getConnection()->schema()->fieldNames($fields);
}

/**
 * Checks if an index exists in the given table.
 *
 * @param $table
 *   The name of the table in drupal (no prefixing).
 * @param $name
 *   The name of the index in drupal (no prefixing).
 *
 * @return
 *   TRUE if the given index exists, otherwise FALSE.
 */
function db_index_exists($table, $name) {
  return Database::getConnection()->schema()->indexExists($table, $name);
}

/**
 * Checks if a table exists.
 *
 * @param $table
 *   The name of the table in drupal (no prefixing).
 *
 * @return
 *   TRUE if the given table exists, otherwise FALSE.
 */
function db_table_exists($table) {
  return Database::getConnection()->schema()->tableExists($table);
}

/**
 * Checks if a column exists in the given table.
 *
 * @param $table
 *   The name of the table in drupal (no prefixing).
 * @param $name
 *   The name of the column.
 *
 * @return
 *   TRUE if the given column exists, otherwise FALSE.
 */
function db_column_exists($table, $column) {
  return Database::getConnection()->schema()->columnExists($table, $column);
}

/**
 * Finds all tables that are like the specified base table name.
 *
 * @param $table_expression
 *   An SQL expression, for example "simpletest%" (without the quotes).
 *   BEWARE: this is not prefixed, the caller should take care of that.
 *
 * @return
 *   Array, both the keys and the values are the matching tables.
 */
function db_find_tables($table_expression) {
  return Database::getConnection()->schema()->findTables($table_expression);
}

function _db_create_keys_sql($spec) {
  return Database::getConnection()->schema()->createKeysSql($spec);
}

/**
 * Renames a table.
 *
 * @param $table
 *   The table to be renamed.
 * @param $new_name
 *   The new name for the table.
 */
function db_rename_table($table, $new_name) {
  return Database::getConnection()->schema()->renameTable($table, $new_name);
}

/**
 * Drops a table.
 *
 * @param $table
 *   The table to be dropped.
 */
function db_drop_table($table) {
  return Database::getConnection()->schema()->dropTable($table);
}

/**
 * Adds a new field to a table.
 *
 * @param $table
 *   Name of the table to be altered.
 * @param $field
 *   Name of the field to be added.
 * @param $spec
 *   The field specification array, as taken from a schema definition. The
 *   specification may also contain the key 'initial'; the newly-created field
 *   will be set to the value of the key in all rows. This is most useful for
 *   creating NOT NULL columns with no default value in existing tables.
 * @param $keys_new
 *   Optional keys and indexes specification to be created on the table along
 *   with adding the field. The format is the same as a table specification, but
 *   without the 'fields' element. If you are adding a type 'serial' field, you
 *   MUST specify at least one key or index including it in this array. See
 *   db_change_field() for more explanation why.
 *
 * @see db_change_field()
 */
function db_add_field($table, $field, $spec, $keys_new = array()) {
  return Database::getConnection()->schema()->addField($table, $field, $spec, $keys_new);
}

/**
 * Drops a field.
 *
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be dropped.
 */
function db_drop_field($table, $field) {
  return Database::getConnection()->schema()->dropField($table, $field);
}

/**
 * Sets the default value for a field.
 *
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be altered.
 * @param $default
 *   Default value to be set. NULL for 'default NULL'.
 */
function db_field_set_default($table, $field, $default) {
  return Database::getConnection()->schema()->fieldSetDefault($table, $field, $default);
}

/**
 * Sets a field to have no default value.
 *
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be altered.
 */
function db_field_set_no_default($table, $field) {
  return Database::getConnection()->schema()->fieldSetNoDefault($table, $field);
}

/**
 * Adds a primary key to a database table.
 *
 * @param $table
 *   Name of the table to be altered.
 * @param $fields
 *   Array of fields for the primary key.
 */
function db_add_primary_key($table, $fields) {
  return Database::getConnection()->schema()->addPrimaryKey($table, $fields);
}

/**
 * Drops the primary key of a database table.
 *
 * @param $table
 *   Name of the table to be altered.
 */
function db_drop_primary_key($table) {
  return Database::getConnection()->schema()->dropPrimaryKey($table);
}

/**
 * Adds a unique key.
 *
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the key.
 * @param $fields
 *   An array of field names.
 */
function db_add_unique_key($table, $name, $fields) {
  return Database::getConnection()->schema()->addUniqueKey($table, $name, $fields);
}

/**
 * Drops a unique key.
 *
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the key.
 */
function db_drop_unique_key($table, $name) {
  return Database::getConnection()->schema()->dropUniqueKey($table, $name);
}

/**
 * Adds an index.
 *
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the index.
 * @param $fields
 *   An array of field names.
 */
function db_add_index($table, $name, $fields) {
  return Database::getConnection()->schema()->addIndex($table, $name, $fields);
}

/**
 * Drops an index.
 *
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the index.
 */
function db_drop_index($table, $name) {
  return Database::getConnection()->schema()->dropIndex($table, $name);
}

/**
 * Changes a field definition.
 *
 * IMPORTANT NOTE: To maintain database portability, you have to explicitly
 * recreate all indices and primary keys that are using the changed field.
 *
 * That means that you have to drop all affected keys and indexes with
 * db_drop_{primary_key,unique_key,index}() before calling db_change_field().
 * To recreate the keys and indices, pass the key definitions as the optional
 * $keys_new argument directly to db_change_field().
 *
 * For example, suppose you have:
 * @code
 * $schema['foo'] = array(
 *   'fields' => array(
 *     'bar' => array('type' => 'int', 'not null' => TRUE)
 *   ),
 *   'primary key' => array('bar')
 * );
 * @endcode
 * and you want to change foo.bar to be type serial, leaving it as the primary
 * key. The correct sequence is:
 * @code
 * db_drop_primary_key('foo');
 * db_change_field('foo', 'bar', 'bar',
 *   array('type' => 'serial', 'not null' => TRUE),
 *   array('primary key' => array('bar')));
 * @endcode
 *
 * The reasons for this are due to the different database engines:
 *
 * On PostgreSQL, changing a field definition involves adding a new field and
 * dropping an old one which causes any indices, primary keys and sequences
 * (from serial-type fields) that use the changed field to be dropped.
 *
 * On MySQL, all type 'serial' fields must be part of at least one key or index
 * as soon as they are created. You cannot use
 * db_add_{primary_key,unique_key,index}() for this purpose because the ALTER
 * TABLE command will fail to add the column without a key or index
 * specification. The solution is to use the optional $keys_new argument to
 * create the key or index at the same time as field.
 *
 * You could use db_add_{primary_key,unique_key,index}() in all cases unless you
 * are converting a field to be type serial. You can use the $keys_new argument
 * in all cases.
 *
 * @param $table
 *   Name of the table.
 * @param $field
 *   Name of the field to change.
 * @param $field_new
 *   New name for the field (set to the same as $field if you don't want to
 *   change the name).
 * @param $spec
 *   The field specification for the new field.
 * @param $keys_new
 *   Optional keys and indexes specification to be created on the table along
 *   with changing the field. The format is the same as a table specification
 *   but without the 'fields' element.
 */
function db_change_field($table, $field, $field_new, $spec, $keys_new = array()) {
  return Database::getConnection()->schema()->changeField($table, $field, $field_new, $spec, $keys_new);
}

/**
 * @} End of "ingroup schemaapi".
 */

/**
 * Sets a session variable specifying the lag time for ignoring a slave server.
 */
function db_ignore_slave() {
  $connection_info = Database::getConnectionInfo();
  // Only set ignore_slave_server if there are slave servers being used, which
  // is assumed if there are more than one.
  if (count($connection_info) > 1) {
    // Five minutes is long enough to allow the slave to break and resume
    // interrupted replication without causing problems on the Drupal site from
    // the old data.
    $duration = variable_get('maximum_replication_lag', 300);
    // Set session variable with amount of time to delay before using slave.
    $_SESSION['ignore_slave_server'] = REQUEST_TIME + $duration;
  }
}

/**
 * Redirects the user to the installation script.
 *
 * This will check if Drupal has not been installed yet (i.e., if no $databases
 * array has been defined in the settings.php file) and we are not already
 * installing. If both are true, the user is redirected to install.php.
 */
function _db_check_install_needed() {
  global $databases;
  if (empty($databases) && !drupal_installation_attempted()) {
    include_once DRUPAL_ROOT . '/includes/install.inc';
    install_goto('install.php');
  }
}
