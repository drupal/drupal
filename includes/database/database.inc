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
 * db_query_range(). Module authors should also consider using pager_query() for
 * queries that return results that need to be presented on multiple pages, and
 * tablesort_sql() for generating appropriate queries for sortable tables.
 *
 * For example, one might wish to return a list of the most recent 10 nodes
 * authored by a given user. Instead of directly issuing the SQL query
 * @code
 *   SELECT n.nid, n.title, n.created FROM node n WHERE n.uid = $uid LIMIT 0, 10;
 * @endcode
 * one would instead call the Drupal functions:
 * @code
 *   $result = db_query_range('SELECT n.nid, n.title, n.created
 *     FROM {node} n WHERE n.uid = :uid', array(':uid' => $uid), 0, 10);
 *   foreach($result as $record) {
 *     // Perform operations on $node->title, etc. here.
 *   }
 * @endcode
 * Curly braces are used around "node" to provide table prefixing via
 * DatabaseConnection::prefixTables(). The explicit use of a user ID is pulled
 * out into an argument passed to db_query() so that SQL injection attacks
 * from user input can be caught and nullified. The LIMIT syntax varies between
 * database servers, so that is abstracted into db_query_range() arguments.
 * Finally, note the PDO-based ability to foreach() over the result set.
 *
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
 * SELECT nid, title FROM {node} WHERE uid=:uid
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
 * SELECT nid, title FROM {node} WHERE uid=?
 * @endcode
 *
 * In this case, the array of arguments must be an indexed array of values to
 * use in the exact same order as the placeholders in the query.
 *
 * Note that placeholders should be a "complete" value. For example, when
 * running a LIKE query the SQL wildcard character, %, should be part of the
 * value, not the query itself. Thus, the following is incorrect:
 *
 * @code
 * SELECT nid, title FROM {node} WHERE title LIKE :title%
 * @endcode
 *
 * It should instead read:
 *
 * @code
 * SELECT nid, title FROM {node} WHERE title LIKE :title
 * @endcode
 *
 * and the value for :title should include a % as appropriate. Again, note the
 * lack of quotation marks around :title. Because the value is not inserted
 * into the query as one big string but as an explicitly separate value, the
 * database server knows where the query ends and a value begins. That is
 * considerably more secure against SQL injection than trying to remember
 * which values need quotation marks and string escaping and which don't.
 *
 *
 * INSERT, UPDATE, and DELETE queries need special care in order to behave
 * consistently across all different databases. Therefore, they use a special
 * object-oriented API for defining a query structurally. For example, rather than
 * @code
 * INSERT INTO node (nid, title, body) VALUES (1, 'my title', 'my body')
 * @endcode
 * one would instead write:
 * @code
 * $fields = array('nid' => 1, 'title' => 'my title', 'body' => 'my body');
 * db_insert('my_table')->fields($fields)->execute();
 * @endcode
 * This method allows databases that need special data type handling to do so,
 * while also allowing optimizations such as multi-insert queries. UPDATE and
 * DELETE queries have a similar pattern.
 *
 *
 * Drupal also supports transactions, including a transparent fallback for
 * databases that do not support transactions. To start a new transaction,
 * simply call $txn = db_transaction(): in your own code. The transaction will
 * remain open for as long as the variable $txn remains in scope.  When $txn is
 * destroyed, the transaction will be committed.  If your transaction is nested
 * inside of another then Drupal will track each transaction and only commit
 * the outer-most transaction when the last transaction object goes out out of
 * scope, that is, all relevant queries completed successfully.
 *
 * Example:
 *
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
 *
 */


/**
 * Base Database API class.
 *
 * This class provides a Drupal-specific extension of the PDO database abstraction class in PHP.
 * Every database driver implementation must provide a concrete implementation of it to support
 * special handling required by that database.
 *
 * @link http://php.net/manual/en/book.pdo.php
 */
abstract class DatabaseConnection extends PDO {

  /**
   * Reference to the last statement that was executed.
   *
   * We only need this for the legacy db_affected_rows() call, which will be removed.
   *
   * @var DatabaseStatementInterface
   * @todo Remove this variable.
   */
  public $lastStatement;

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
   * Cache of prepared statements.
   *
   * This cache only lasts as long as the current page request, so it's not
   * as useful as it could be, but every little bit helps.
   *
   * @var Array
   */
  protected $preparedStatements = array();

  /**
   * Track the number of "layers" of transactions currently active.
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
  protected $willRollBack;

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
   * Return the default query options for any given query.
   *
   * A given query can be customized with a number of option flags in an
   * associative array.
   *
   *   target - The database "target" against which to execute a query. Valid
   *   values are "default" or "slave". The system will first try to open a
   *   connection to a database specified with the user-supplied key. If one
   *   is not available, it will silently fall back to the "default" target.
   *   If multiple databases connections are specified with the same target,
   *   one will be selected at random for the duration of the request.
   *
   *   fetch - This element controls how rows from a result set will be returned.
   *   legal values include PDO::FETCH_ASSOC, PDO::FETCH_BOTH, PDO::FETCH_OBJ,
   *   PDO::FETCH_NUM, or a string representing the name of a class. If a string
   *   is specified, each record will be fetched into a new object of that class.
   *   The behavior of all other values is defined by PDO. See
   *   http://www.php.net/PDOStatement-fetch
   *
   *   return - Depending on the type of query, different return values may be
   *   meaningful. This directive instructs the system which type of return
   *   value is desired. The system will generally set the correct value
   *   automatically, so it is extremely rare that a module developer will ever
   *   need to specify this value. Setting it incorrectly will likely lead to
   *   unpredictable results or fatal errors. Legal values include:
   *
   *     Database::RETURN_STATEMENT - Return the prepared statement object for the
   *     query. This is usually only meaningful for SELECT queries, where the
   *     statement object is how one accesses the result set returned by the query.
   *
   *     Database::RETURN_AFFECTED - Return the number of rows affected by an
   *     UPDATE or DELETE query. Be aware that means the number of rows
   *     actually changed, not the number of rows matched by the WHERE clause.
   *
   *     Database::RETURN_INSERT_ID - Return the sequence ID (primary key)
   *     created by an INSERT statement on a table that contains a serial column.
   *
   *     Database::RETURN_NULL - Do not return anything, as there is no
   *     meaningful value to return. That is the case for INSERT queries on
   *     tables that do not contain a serial column.
   *
   *   throw_exception - By default, the database system will catch any errors
   *   on a query as an Exception, log it, and then rethrow it so that code
   *   further up the call chain can take an appropriate action. To suppress
   *   that behavior and simply return NULL on failure, set this option to FALSE.
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
   * Append a database prefix to all tables in a query.
   *
   * Queries sent to Drupal should wrap all table names in curly brackets. This
   * function searches for this syntax and adds Drupal's table prefix to all
   * tables, allowing Drupal to coexist with other systems in the same database
   * and/or schema if necessary.
   *
   * @param $sql
   *   A string containing a partial or entire SQL query.
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
   * Prepare a query string and return the prepared statement.
   *
   * This method caches prepared statements, reusing them when
   * possible. It also prefixes tables names enclosed in curly-braces.
   *
   * @param $query
   *   The query string as SQL, with curly-braces surrounding the
   *   table names.
   * @param $cache
   *   Whether or not to cache the prepared statement for later reuse in this
   *   same request.  Usually we want to, but queries that require preprocessing
   *   cannot be safely cached.
   * @return
   *   A PDO prepared statement ready for its execute() method.
   */
  public function prepareQuery($query, $cache = TRUE) {
    $query = $this->prefixTables($query);
    if (empty($this->preparedStatements[$query])) {
      // Call PDO::prepare.
      $this->preparedStatements[$query] = parent::prepare($query);
    }
    return $this->preparedStatements[$query];
  }

  /**
   * Tell this connection object what its target value is.
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
   * Associate a logging object with this connection.
   *
   * @param $logger
   *   The logging object we want to use.
   */
  public function setLogger(DatabaseLog $logger) {
    $this->logger = $logger;
  }

  /**
   * Get the current logging object for this connection.
   *
   * @return
   *   The current logging object for this connection. If there isn't one,
   *   NULL is returned.
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Create the appropriate sequence name for a given table and serial field.
   *
   * This information is exposed to all database drivers, although it is only
   * useful on some of them. This method is table prefix-aware.
   *
   * @param $table
   *   The table name to use for the sequence.
   * @param $field
   *   The field name to use for the sequence.
   * @return
   *   A table prefix-parsed string for the sequence name.
   */
  public function makeSequenceName($table, $field) {
    return $this->prefixTables('{' . $table . '}_' . $field . '_seq');
  }

  /**
   * Executes a query string against the database.
   *
   * This method provides a central handler for the actual execution
   * of every query. All queries executed by Drupal are executed as
   * PDO prepared statements.
   *
   * @param $query
   *   The query to execute. In most cases this will be a string containing
   *   an SQL query with placeholders. An already-prepared instance of
   *   DatabaseStatementInterface may also be passed in order to allow calling code
   *   to manually bind variables to a query. If a DatabaseStatementInterface
   *   is passed, the $args array will be ignored.
   *
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
   * @return
   *   This method will return one of: The executed statement, the number of
   *   rows affected by the query (not the number matched), or the generated
   *   insert id of the last query, depending on the value of $options['return'].
   *   Typically that value will be set by default or a query builder and should
   *   not be set by a user. If there is an error, this method will return NULL
   *   and may throw an exception if $options['throw_exception'] is TRUE.
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
        $modified = $this->expandArguments($query, $args);
        $stmt = $this->prepareQuery($query, !$modified);
        $stmt->execute($args, $options);
      }

      // Depending on the type of query we may need to return a different value.
      // See DatabaseConnection::defaultOptions() for a description of each value.
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
   * Expand out shorthand placeholders.
   *
   * Drupal supports an alternate syntax for doing arrays of values.  We therefore
   * need to expand them out into a full, executable query string.
   *
   * @param $query
   *   The query string to modify.
   * @param $args
   *   The arguments for the query.
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
   * Prepare and return a SELECT query object with the specified ID.
   *
   * @see SelectQuery
   * @param $table
   *   The base table for this query, that is, the first table in the FROM
   *   clause. This table will also be used as the "base" table for query_alter
   *   hook implementations.
   * @param $alias
   *   The alias of the base table of this query.
   * @param $options
   *   An array of options on the query.
   * @return
   *   A new SelectQuery object.
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
   * Prepare and return an INSERT query object with the specified ID.
   *
   * @see InsertQuery
   * @param $options
   *   An array of options on the query.
   * @return
   *   A new InsertQuery object.
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
   * Prepare and return a MERGE query object with the specified ID.
   *
   * @see MergeQuery
   * @param $options
   *   An array of options on the query.
   * @return
   *   A new MergeQuery object.
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
   * Prepare and return an UPDATE query object with the specified ID.
   *
   * @see UpdateQuery
   * @param $options
   *   An array of options on the query.
   * @return
   *   A new UpdateQuery object.
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
   * Prepare and return a DELETE query object with the specified ID.
   *
   * @see DeleteQuery
   * @param $options
   *   An array of options on the query.
   * @return
   *   A new DeleteQuery object.
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
   * Prepare and return a TRUNCATE query object.
   *
   * @see TruncateQuery
   * @param $options
   *   An array of options on the query.
   * @return
   *   A new DeleteQuery object.
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
   * Returns a DatabaseSchema object for manipulating the schema of this database.
   *
   * This method will lazy-load the appropriate schema library file.
   *
   * @return
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
   * Determine if there is an active transaction open.
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
   * @param $required
   *   If executing an operation that absolutely must use transactions, specify
   *   TRUE for this parameter. If the connection does not support transactions,
   *   this method will throw an exception and the operation will not be possible.
   * @see DatabaseTransaction
   */
  public function startTransaction($required = FALSE) {
    if ($required && !$this->supportsTransactions()) {
      throw new TransactionsNotSupportedException();
    }

    if (empty($this->transactionClass)) {
      $this->transactionClass = 'DatabaseTransaction_' . $this->driver();
      if (!class_exists($this->transactionClass)) {
        $this->transactionClass = 'DatabaseTransaction';
      }
    }
    return new $this->transactionClass($this);
  }

  /**
   * Schedule the current transaction for rollback.
   *
   * This method throws an exception if no transaction is active.
   */
  public function rollBack() {
    if ($this->transactionLayers == 0) {
      throw new NoActiveTransactionException();
    }

    $this->willRollBack = TRUE;
  }

  /**
   * Determine if this transaction will roll back.
   *
   * Use this function to skip further operations if the current transaction
   * is already scheduled to roll back. Throws an exception if no transaction
   * is active.
   *
   * @return
   *   TRUE if the transaction will roll back, FALSE otherwise.
   */
  public function willRollBack() {
    if ($this->transactionLayers == 0) {
      throw new NoActiveTransactionException();
    }

    return $this->willRollBack;
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

      // Reset any scheduled rollback
      $this->willRollBack = FALSE;
    }
  }

  /**
   * Decreases the depth of transaction nesting, committing or rolling back if necessary.
   *
   * If we pop off the last transaction layer, then we either commit or roll back
   * the transaction as necessary.  If no transaction is active, we throw
   * an exception.
   *
   * @see DatabaseTransaction
   */
  public function popTransaction() {
    if ($this->transactionLayers == 0) {
      throw new NoActiveTransactionException();
    }

    --$this->transactionLayers;

    if ($this->transactionLayers == 0 && $this->supportsTransactions()) {
      if ($this->willRollBack) {
        parent::rollBack();
      }
      else {
        parent::commit();
      }
    }
  }

  /**
   * Runs a limited-range query on this database object.
   *
   * Use this as a substitute for ->query() when a subset of the query is to be
   * returned.
   * User-supplied arguments to the query should be passed in as separate parameters
   * so that they can be properly escaped to avoid SQL injection attacks.
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
   * @return
   *   A database query result resource, or NULL if the query was not executed
   *   correctly.
   */
  abstract public function queryRange($query, array $args, $from, $count, array $options = array());

  /**
   * Generate a temporary table name.
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
   * request.
   * User-supplied arguments to the query should be passed in as separate parameters
   * so that they can be properly escaped to avoid SQL injection attacks.
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
   * @return
   *   The name of the temporary table.
   */
  abstract function queryTemporary($query, array $args, array $options = array());

  /**
   * Returns the type of database driver.
   *
   * This is not necessarily the same as the type of the database itself.
   * For instance, there could be two MySQL drivers, mysql and mysql_mock.
   * This function would return different values for each, but both would
   * return "mysql" for databaseType().
   */
  abstract public function driver();

  /**
   * Determine if this driver supports transactions.
   *
   * @return
   *   TRUE if this connection supports transactions, FALSE otherwise.
   */
  public function supportsTransactions() {
    return $this->transactionSupport;
  }

  /**
   * Determine if this driver supports transactional DDL.
   *
   * DDL queries are those that change the schema, such as ALTER queries.
   *
   * @return
   *   TRUE if this connection supports transactions for DDL queries, FALSE otherwise.
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
   * @see DatabaseCondition::compile()
   * @param $operator
   *   The condition operator, such as "IN", "BETWEEN", etc. Case-sensitive.
   * @return
   *   The extra handling directives for the specified operator, or NULL.
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
}

/**
 * Primary front-controller for the database system.
 *
 * This class is uninstantiatable and un-extendable. It acts to encapsulate
 * all control and shepherding of database connections into a single location
 * without the use of globals.
 *
 */
abstract class Database {

  /**
   * Flag to indicate a query call should simply return NULL.
   *
   * This is used for queries that have no reasonable return value
   * anyway, such as INSERT statements to a table without a serial
   * primary key.
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
   * An nested array of all active connections. It is keyed by database name and target.
   *
   * @var array
   */
  static protected $connections = array();

  /**
   * A processed copy of the database connection information from settings.php
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
   * Every connection has one and only one logger object for all targets
   * and logging keys.
   *
   * array(
   *   '$db_key' => DatabaseLog object.
   * );
   *
   * @var array
   */
  static protected $logs = array();

  /**
   * Start logging a given logging key on the specified connection.
   *
   * @see DatabaseLog
   * @param $logging_key
   *   The logging key to log.
   * @param $key
   *   The database connection key for which we want to log.
   * @return
   *   The query log object. Note that the log object does support richer
   *   methods than the few exposed through the Database class, so in some
   *   cases it may be desirable to access it directly.
   */
  final public static function startLog($logging_key, $key = 'default') {
    if (empty(self::$logs[$key])) {
      self::$logs[$key] = new DatabaseLog($key);

      // Every target already active for this connection key needs to have
      // the logging object associated with it.
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
   * Retrieve the queries logged on for given logging key.
   *
   * This method also ends logging for the specified key. To get the query log
   * to date without ending the logger request the logging object by starting
   * it again (which does nothing to an open log key) and call methods on it as
   * desired.
   *
   * @see DatabaseLog
   * @param $logging_key
   *   The logging key to log.
   * @param $key
   *   The database connection key for which we want to log.
   * @return
   *   The query log for the specified logging key and connection.
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
   *   The database connection key. Defaults to NULL which means the active
   *   key.
   * @return
   *   The corresponding connection object.
   */
  final public static function getConnection($target = 'default', $key = NULL) {
    if (!isset($key)) {
      // By default, we want the active connection, set in setActiveConnection.
      $key = self::$activeKey;
    }
    // If the requested target does not exist, or if it is ignored, we fall back
    // to the default target. The target is typically either "default" or "slave",
    // indicating to use a slave SQL server if one is available. If it's not
    // available, then the default/master server is the correct server to use.
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
   * Determine if there is an active connection.
   *
   * Note that this method will return FALSE if no connection has been established
   * yet, even if one could be.
   *
   * @return
   *   TRUE if there is at least one database connection established, FALSE otherwise.
   */
  final public static function isActiveConnection() {
    return !empty(self::$activeKey) && !empty(self::$connections) && !empty(self::$connections[self::$activeKey]);
  }

  /**
   * Set the active connection to the specified key.
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
  final protected static function parseConnectionInfo() {
    global $databases;

    _db_check_install_needed();

    $databaseInfo = $databases;
    foreach ($databaseInfo as $index => $info) {
      foreach ($databaseInfo[$index] as $target => $value) {
        // If there is no "driver" property, then we assume it's an array of
        // possible connections for this target. Pick one at random. That
        // allows us to have, for example, multiple slave servers.
        if (empty($value['driver'])) {
          $databaseInfo[$index][$target] = $databaseInfo[$index][$target][mt_rand(0, count($databaseInfo[$index][$target]) - 1)];
        }
      }
    }

    self::$databaseInfo = $databaseInfo;
  }

  /**
   * Add database connection info for a given key/target.
   *
   * This method allows the addition of new connection credentials at runtime.
   * Under normal circumstances the preferred way to specify database credentials
   * is via settings.php. However, this method allows them to be added at
   * arbitrary times, such as during unit tests, when connecting to admin-defined
   * third party databases, etc.
   *
   * If the given key/target pair already exists, this method will be ignored.
   *
   * @param $key
   *   The database key.
   * @param $target
   *   The database target name.
   * @param $info
   *   The database connection information, as it would be defined in settings.php.
   *   Note that the structure of this array will depend on the database driver
   *   it is connecting to.
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
   * Open a connection to the server specified by the given key and target.
   *
   * @param $key
   *   The database connection key, as specified in settings.php. The default
   *   is "default".
   * @param $target
   *   The database target to open.
   */
  final protected static function openConnection($key, $target) {
    global $db_prefix;

    if (empty(self::$databaseInfo)) {
      self::parseConnectionInfo();
    }
    try {
      // If the requested database does not exist then it is an unrecoverable error.
      if (!isset(self::$databaseInfo[$key])) {
        throw new Exception('DB does not exist');
      }

      if (!$driver = self::$databaseInfo[$key][$target]['driver']) {
        throw new Exception('Drupal is not set up');
      }

      // We cannot rely on the registry yet, because the registry requires
      // an open database connection.
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
      // and we put that in the user_agent header.
      if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/^simpletest\d+$/", $_SERVER['HTTP_USER_AGENT'])) {     
        $db_prefix .= $_SERVER['HTTP_USER_AGENT'];
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
   * Instruct the system to temporarily ignore a given key/target.
   *
   * At times we need to temporarily disable slave queries. To do so,
   * call this method with the database key and the target to disable.
   * That database key will then always fall back to 'default' for that
   * key, even if it's defined.
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
 * Exception to mark databases that do not support transations.
 *
 * This exception will be thrown when a transaction is started that does not
 * allow for the "silent fallback" of no transaction and the database connection
 * in use does not support transactions. The calling code must then take
 * appropriate action.
 */
class TransactionsNotSupportedException extends Exception { }

/**
 * Exception to throw when popTransaction() is called when no transaction is active.
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
 * In the vast majority of cases, you should not instantiate this class directly.
 * Instead, call ->startTransaction(), from the appropriate connection object.
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

}

/**
 * A prepared statement.
 *
 * Some methods in that class are purposely commented out. Due to a change in
 * how PHP defines PDOStatement, we can't define a signature for those methods that
 * will work the same way between versions older than 5.2.6 and later versions.
 *
 * Please refer to http://bugs.php.net/bug.php?id=42452 for more details.
 *
 * Child implementations should either extend PDOStatement:
 * @code
 * class DatabaseStatement_oracle extends PDOStatement implements DatabaseStatementInterface {}
 * @endcode
 *
 * or implement their own class, but in that case they will also have to implement
 * the Iterator or IteratorArray interfaces before DatabaseStatementInterface:
 * @code
 * class DatabaseStatement_oracle implements Iterator, DatabaseStatementInterface {}
 * @endcode
 */
interface DatabaseStatementInterface extends Traversable {

  /**
   * Executes a prepared statement
   *
   * @param $args
   *   An array of values with as many elements as there are bound parameters in the SQL statement being executed.
   * @param $options
   *   An array of options for this query.
   * @return
   *   TRUE on success, or FALSE on failure.
   */
  public function execute($args = array(), $options = array());

  /**
   * Get the query string of that statement.
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
   *   statement executed
   */
  public function rowCount();

  /**
   * Set the default fetch mode for this statement.
   *
   * See http://php.net/manual/en/pdo.constants.php for the definition of the
   * constants used.
   *
   * @param $mode
   *   One of the PDO::FETCH_* constants.
   * @param $a1
   *   An option depending of the fetch mode specified by $mode:
   *    - for PDO::FETCH_COLUMN, it is the index of the column to fetch,
   *    - for PDO::FETCH_CLASS, it is the name of the class to create, and
   *    - for PDO::FETCH_INTO, it is the object to add the data to.
   * @param $a2
   *  In case of when mode is PDO::FETCH_CLASS, the optional arguments to
   *  pass to the constructor.
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
   * @return
   *   A result, formatted according to $mode.
   */
  // public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL);

  /**
   * Return a single field out of the current
   *
   * @param $index
   *   The numeric index of the field to return. Defaults to the first field.
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
   * This method corresponds to PDOStatement::fetchObject(),
   * but for associative arrays. For some reason PDOStatement does
   * not have a corresponding array helper method, so one is added.
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
   * @return
   *   An indexed array.
   */
  public function fetchCol($index = 0);

  /**
   * Returns the entire result set as a single associative array.
   *
   * This method is only useful for two-column result sets. It will return
   * an associative array where the key is one column from the result set
   * and the value is another field. In most cases, the default of the first two
   * columns is appropriate.
   *
   * Note that this method will run the result set to the end.
   *
   * @param $key_index
   *   The numeric index of the field to use as the array key.
   * @param $value_index
   *   The numeric index of the field to use as the array value.
   * @return
   *   An associative array.
   */
  public function fetchAllKeyed($key_index = 0, $value_index = 1);

  /**
   * Returns an entire result set as an associative array keyed by the named field.
   *
   * If the given key appears multiple times, later records will overwrite
   * earlier ones.
   *
   * Note that this method will run the result set to the end.
   *
   * @param $key
   *   The name of the field on which to index the array.
   * @param $fetch
   *   The fetchmode to use. If set to PDO::FETCH_ASSOC, PDO::FETCH_NUM, or
   *   PDO::FETCH_BOTH the returned value with be an array of arrays. For any
   *   other value it will be an array of objects.
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
 * driver needs to set a custom statement class, it may do so in its constructor.
 *
 * @link http://us.php.net/pdostatement
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
    $this->dbh->lastStatement = $this;

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
 * The following utility functions are simply convenience wrappers.
 * They should never, ever have any database-specific code in them.
 */

/**
 * Execute an arbitrary query string against the active database.
 *
 * Do not use this function for INSERT, UPDATE, or DELETE queries. Those should
 * be handled via the appropriate query builder factory. Use this function for
 * SELECT queries that do not require a query builder.
 *
 * @see DatabaseConnection::defaultOptions()
 * @param $query
 *   The prepared statement query to run. Although it will accept both
 *   named and unnamed placeholders, named placeholders are strongly preferred
 *   as they are more self-documenting.
 * @param $args
 *   An array of values to substitute into the query. If the query uses named
 *   placeholders, this is an associative array in any order. If the query uses
 *   unnamed placeholders (?), this is an indexed array and the order must match
 *   the order of placeholders in the query string.
 * @param $options
 *   An array of options to control how the query operates.
 * @return
 *   A prepared statement object, already executed.
 */
function db_query($query, $args = array(), $options = array()) {
  if (!is_array($args)) {
    $args = func_get_args();
    array_shift($args);
  }
  list($query, $args, $options) = _db_query_process_args($query, $args, $options);

  return Database::getConnection($options['target'])->query($query, $args, $options);
}

/**
 * Execute an arbitrary query string against the active database, restricted to a specified range.
 *
 * @see DatabaseConnection::defaultOptions()
 * @param $query
 *   The prepared statement query to run. Although it will accept both
 *   named and unnamed placeholders, named placeholders are strongly preferred
 *   as they are more self-documenting.
 * @param $args
 *   An array of values to substitute into the query. If the query uses named
 *   placeholders, this is an associative array in any order. If the query uses
 *   unnamed placeholders (?), this is an indexed array and the order must match
 *   the order of placeholders in the query string.
 * @param $from
 *   The first record from the result set to return.
 * @param $limit
 *   The number of records to return from the result set.
 * @param $options
 *   An array of options to control how the query operates.
 * @return
 *   A prepared statement object, already executed.
 */
function db_query_range($query, $args, $from = 0, $count = 0, $options = array()) {
  if (!is_array($args)) {
    $args = func_get_args();
    array_shift($args);
    $count = array_pop($args);
    $from = array_pop($args);
  }
  list($query, $args, $options) = _db_query_process_args($query, $args, $options);

  return Database::getConnection($options['target'])->queryRange($query, $args, $from, $count, $options);
}

/**
 * Execute a query string against the active database and save the result set to a temp table.
 *
 * @see DatabaseConnection::defaultOptions()
 * @param $query
 *   The prepared statement query to run. Although it will accept both
 *   named and unnamed placeholders, named placeholders are strongly preferred
 *   as they are more self-documenting.
 * @param $args
 *   An array of values to substitute into the query. If the query uses named
 *   placeholders, this is an associative array in any order. If the query uses
 *   unnamed placeholders (?), this is an indexed array and the order must match
 *   the order of placeholders in the query string.
 * @param $options
 *   An array of options to control how the query operates.
 * @return
 *   The name of the temporary table.
 */
function db_query_temporary($query, $args, $options = array()) {
  if (!is_array($args)) {
    $args = func_get_args();
    array_shift($args);
  }
  list($query, $args, $options) = _db_query_process_args($query, $args, $options);

  return Database::getConnection($options['target'])->queryTemporary($query, $args, $options);
}

/**
 * Returns a new InsertQuery object for the active database.
 *
 * @param $table
 *   The table into which to insert.
 * @param $options
 *   An array of options to control how the query operates.
 * @return
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
 * @return
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
 * @return
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
 * @return
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
 * @return
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
 * @return
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
 *   support.  If set to TRUE and the active database does not support transactions
 *   a TransactionsNotSupportedException exception will be thrown.
 * @param $options
 *   An array of options to control how the transaction operates.  Only the
 *   target key has any meaning in this case.
 * @return
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
 * @returns
 *   The key of the formerly active database.
 */
function db_set_active($key = 'default') {
  return Database::setActiveConnection($key);
}

/**
 * Determine if there is an active connection.
 *
 * Note that this method will return FALSE if no connection has been established
 * yet, even if one could be.
 *
 * @return
 *   TRUE if there is at least one database connection established, FALSE otherwise.
 */
function db_is_active() {
  return Database::isActiveConnection();
}

/**
 * Restrict a dynamic table, column or constraint name to safe characters.
 *
 * Only keeps alphanumeric and underscores.
 *
 * @param $table
 *   The table name to escape.
 * @return
 *   The escaped table name as a string.
 */
function db_escape_table($table) {
  return Database::getConnection()->escapeTable($table);
}

/**
 * Perform an SQL query and return success or failure.
 *
 * @param $sql
 *   A string containing a complete SQL query. %-substitution
 *   parameters are not supported.
 * @return
 *   An array containing the keys:
 *     success: a boolean indicating whether the query succeeded
 *     query: the SQL query executed, passed through check_plain()
 */
function update_sql($sql) {
  $result = Database::getConnection()->query($sql);
  return array('success' => $result !== FALSE, 'query' => check_plain($sql));
}

/**
 * Generate placeholders for an array of query arguments of a single type.
 *
 * Given a Schema API field type, return correct %-placeholders to
 * embed in a query
 *
 * @todo This may be possible to remove in favor of db_select().
 * @param $arguments
 *   An array with at least one element.
 * @param $type
 *   The Schema API type of a field (e.g. 'int', 'text', or 'varchar').
 */
function db_placeholders($arguments, $type = 'int') {
  $placeholder = db_type_placeholder($type);
  return implode(',', array_fill(0, count($arguments), $placeholder));
}

/**
 * Wraps the given table.field entry with a DISTINCT(). The wrapper is added to
 * the SELECT list entry of the given query and the resulting query is returned.
 * This function only applies the wrapper if a DISTINCT doesn't already exist in
 * the query.
 *
 * @todo Remove this.
 * @param $table
 *   Table containing the field to set as DISTINCT
 * @param $field
 *   Field to set as DISTINCT
 * @param $query
 *   Query to apply the wrapper to
 * @return
 *   SQL query with the DISTINCT wrapper surrounding the given table.field.
 */
function db_distinct_field($table, $field, $query) {
  return Database::getConnection()->distinctField($table, $field, $query);
}

/**
 * Retrieve the name of the currently active database driver, such as
 * "mysql" or "pgsql".
 *
 * @return The name of the currently active database driver.
 */
function db_driver() {
  return Database::getConnection()->driver();
}

/**
 * @} End of "defgroup database".
 */


/**
 * @ingroup schemaapi
 * @{
 */


/**
 * Create a new table from a Drupal table definition.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $name
 *   The name of the table to create.
 * @param $table
 *   A Schema API table definition array.
 */
function db_create_table(&$ret, $name, $table) {
  return Database::getConnection()->schema()->createTable($ret, $name, $table);
}

/**
 * Return an array of field names from an array of key/index column specifiers.
 *
 * This is usually an identity function but if a key/index uses a column prefix
 * specification, this function extracts just the name.
 *
 * @param $fields
 *   An array of key/index column specifiers.
 * @return
 *   An array of field names.
 */
function db_field_names($fields) {
  return Database::getConnection()->schema()->fieldNames($fields);
}

/**
 * Check if a table exists.
 */
function db_table_exists($table) {
  return Database::getConnection()->schema()->tableExists($table);
}

/**
 * Check if a column exists in the given table.
 */
function db_column_exists($table, $column) {
  return Database::getConnection()->schema()->columnExists($table, $column);
}

/**
 * Find all tables that are like the specified base table name.
 *
 * @param $table_expression
 *   An SQL expression, for example "simpletest%" (without the quotes).
 *   BEWARE: this is not prefixed, the caller should take care of that.
 * @return
 *   Array, both the keys and the values are the matching tables.
 */
function db_find_tables($table_expression) {
  return Database::getConnection()->schema()->findTables($table_expression);
}

/**
 * Given a Schema API field type, return the correct %-placeholder.
 *
 * Embed the placeholder in a query to be passed to db_query and and pass as an
 * argument to db_query a value of the specified type.
 *
 * @todo Remove this after all queries are converted to type-agnostic form.
 * @param $type
 *   The Schema API type of a field.
 * @return
 *   The placeholder string to embed in a query for that type.
 */
function db_type_placeholder($type) {
  switch ($type) {
    case 'varchar':
    case 'char':
    case 'text':
    case 'datetime':
      return '\'%s\'';

    case 'numeric':
      // Numeric values are arbitrary precision numbers. Syntactically, numerics
      // should be specified directly in SQL. However, without single quotes
      // the %s placeholder does not protect against non-numeric characters such
      // as spaces which would expose us to SQL injection.
      return '%n';

    case 'serial':
    case 'int':
      return '%d';

    case 'float':
      return '%f';

    case 'blob':
      return '%b';
  }

  // There is no safe value to return here, so return something that
  // will cause the query to fail.
  return 'unsupported type ' . $type . 'for db_type_placeholder';
}


function _db_create_keys_sql($spec) {
  return Database::getConnection()->schema()->createKeysSql($spec);
}

/**
 * This maps a generic data type in combination with its data size
 * to the engine-specific data type.
 */
function db_type_map() {
  return Database::getConnection()->schema()->getFieldTypeMap();
}

/**
 * Rename a table.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be renamed.
 * @param $new_name
 *   The new name for the table.
 */
function db_rename_table(&$ret, $table, $new_name) {
  return Database::getConnection()->schema()->renameTable($ret, $table, $new_name);
}

/**
 * Drop a table.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be dropped.
 */
function db_drop_table(&$ret, $table) {
  return Database::getConnection()->schema()->dropTable($ret, $table);
}

/**
 * Add a new field to a table.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   Name of the table to be altered.
 * @param $field
 *   Name of the field to be added.
 * @param $spec
 *   The field specification array, as taken from a schema definition.
 *   The specification may also contain the key 'initial', the newly
 *   created field will be set to the value of the key in all rows.
 *   This is most useful for creating NOT NULL columns with no default
 *   value in existing tables.
 * @param $keys_new
 *   Optional keys and indexes specification to be created on the
 *   table along with adding the field. The format is the same as a
 *   table specification but without the 'fields' element. If you are
 *   adding a type 'serial' field, you MUST specify at least one key
 *   or index including it in this array. See db_change_field() for more
 *   explanation why.
 * @see db_change_field()
 */
function db_add_field(&$ret, $table, $field, $spec, $keys_new = array()) {
  return Database::getConnection()->schema()->addField($ret, $table, $field, $spec, $keys_new);
}

/**
 * Drop a field.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be dropped.
 */
function db_drop_field(&$ret, $table, $field) {
  return Database::getConnection()->schema()->dropField($ret, $table, $field);
}

/**
 * Set the default value for a field.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be altered.
 * @param $default
 *   Default value to be set. NULL for 'default NULL'.
 */
function db_field_set_default(&$ret, $table, $field, $default) {
  return Database::getConnection()->schema()->fieldSetDefault($ret, $table, $field, $default);
}

/**
 * Set a field to have no default value.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be altered.
 */
function db_field_set_no_default(&$ret, $table, $field) {
  return Database::getConnection()->schema()->fieldSetNoDefault($ret, $table, $field);
}

/**
 * Add a primary key.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $fields
 *   Fields for the primary key.
 */
function db_add_primary_key(&$ret, $table, $fields) {
  return Database::getConnection()->schema()->addPrimaryKey($ret, $table, $fields);
}

/**
 * Drop the primary key.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 */
function db_drop_primary_key(&$ret, $table) {
  return Database::getConnection()->schema()->dropPrimaryKey($ret, $table);
}

/**
 * Add a unique key.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the key.
 * @param $fields
 *   An array of field names.
 */
function db_add_unique_key(&$ret, $table, $name, $fields) {
  return Database::getConnection()->schema()->addUniqueKey($ret, $table, $name, $fields);
}

/**
 * Drop a unique key.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the key.
 */
function db_drop_unique_key(&$ret, $table, $name) {
  return Database::getConnection()->schema()->dropUniqueKey($ret, $table, $name);
}

/**
 * Add an index.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the index.
 * @param $fields
 *   An array of field names.
 */
function db_add_index(&$ret, $table, $name, $fields) {
  return Database::getConnection()->schema()->addIndex($ret, $table, $name, $fields);
}

/**
 * Drop an index.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the index.
 */
function db_drop_index(&$ret, $table, $name) {
  return Database::getConnection()->schema()->dropIndex($ret, $table, $name);
}

/**
 * Change a field definition.
 *
 * IMPORTANT NOTE: To maintain database portability, you have to explicitly
 * recreate all indices and primary keys that are using the changed field.
 *
 * That means that you have to drop all affected keys and indexes with
 * db_drop_{primary_key,unique_key,index}() before calling db_change_field().
 * To recreate the keys and indices, pass the key definitions as the
 * optional $keys_new argument directly to db_change_field().
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
 * and you want to change foo.bar to be type serial, leaving it as the
 * primary key. The correct sequence is:
 * @code
 * db_drop_primary_key($ret, 'foo');
 * db_change_field($ret, 'foo', 'bar', 'bar',
 *   array('type' => 'serial', 'not null' => TRUE),
 *   array('primary key' => array('bar')));
 * @endcode
 *
 * The reasons for this are due to the different database engines:
 *
 * On PostgreSQL, changing a field definition involves adding a new field
 * and dropping an old one which* causes any indices, primary keys and
 * sequences (from serial-type fields) that use the changed field to be dropped.
 *
 * On MySQL, all type 'serial' fields must be part of at least one key
 * or index as soon as they are created. You cannot use
 * db_add_{primary_key,unique_key,index}() for this purpose because
 * the ALTER TABLE command will fail to add the column without a key
 * or index specification. The solution is to use the optional
 * $keys_new argument to create the key or index at the same time as
 * field.
 *
 * You could use db_add_{primary_key,unique_key,index}() in all cases
 * unless you are converting a field to be type serial. You can use
 * the $keys_new argument in all cases.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   Name of the table.
 * @param $field
 *   Name of the field to change.
 * @param $field_new
 *   New name for the field (set to the same as $field if you don't want to change the name).
 * @param $spec
 *   The field specification for the new field.
 * @param $keys_new
 *   Optional keys and indexes specification to be created on the
 *   table along with changing the field. The format is the same as a
 *   table specification but without the 'fields' element.
 */

function db_change_field(&$ret, $table, $field, $field_new, $spec, $keys_new = array()) {
  return Database::getConnection()->schema()->changeField($ret, $table, $field, $field_new, $spec, $keys_new);
}

/**
 * @} End of "ingroup schemaapi".
 */

/**
 * Prints a themed maintenance page with the 'Site offline' text,
 * adding the provided error message in the case of 'display_errors'
 * set to on. Ends the page request; no return.
 */
function _db_error_page($error = '') {
  global $db_type;
  drupal_init_language();
  drupal_maintenance_theme();
  drupal_set_header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
  drupal_set_title('Site offline');
}

/**
 * @ingroup database-legacy
 *
 * These functions are no longer necessary, as the DatabaseStatementInterface interface
 * offers this and much more functionality. They are kept temporarily for backward
 * compatibility during conversion and should be removed as soon as possible.
 *
 * @{
 */

function db_fetch_object(DatabaseStatementInterface $statement) {
  return $statement->fetch(PDO::FETCH_OBJ);
}

function db_fetch_array(DatabaseStatementInterface $statement) {
  return $statement->fetch(PDO::FETCH_ASSOC);
}

function db_result(DatabaseStatementInterface $statement) {
  return $statement->fetchField();
}

/**
 * Redirect the user to the installation script if Drupal has not been
 * installed yet (i.e., if no $databases array has been defined in the
 * settings file) and we are not already there. Otherwise, do nothing.
 */
function _db_check_install_needed() {
  global $databases;
  if (empty($databases) && !function_exists('install_main')) {
    include_once DRUPAL_ROOT . '/includes/install.inc';
    install_goto('install.php');
  }
}

/**
 * Backward-compatibility utility.
 *
 * This function should be removed after all queries have been converted
 * to the new API. It is temporary only.
 *
 * @todo Remove this once the query conversion is complete.
 */
function _db_query_process_args($query, $args, $options) {

  if (!is_array($options)) {
    $options = array();
  }
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }

  // Temporary backward-compatibility hacks. Remove later.
  $old_query = $query;
  $query = str_replace(array('%n', '%d', '%f', '%b', "'%s'", '%s'), '?', $old_query);
  if ($old_query !== $query) {
    $args = array_values($args);  // The old system allowed named arrays, but PDO doesn't if you use ?.
  }

  // A large number of queries pass FALSE or empty-string for
  // int/float fields because the previous version of db_query()
  // casted them to int/float, resulting in 0. MySQL PDO happily
  // accepts these values as zero but PostgreSQL PDO does not, and I
  // do not feel like tracking down and fixing every such query at
  // this time.
  if (preg_match_all('/%([dsfb])/', $old_query, $m) > 0) {
    foreach ($m[1] as $idx => $char) {
      switch ($char) {
        case 'd':
          $args[$idx] = (int) $args[$idx];
          break;
        case 'f':
          $args[$idx] = (float) $args[$idx];
          break;
      }
    }
  }

  return array($query, $args, $options);
}


/**
 * Returns the last insert id.
 *
 * @todo Remove this function when all queries have been ported to db_insert().
 * @param $table
 *   The name of the table you inserted into.
 * @param $field
 *   The name of the autoincrement field.
 */
function db_last_insert_id($table, $field) {
  $sequence_name = Database::getConnection()->makeSequenceName($table, $field);
  return Database::getConnection()->lastInsertId($sequence_name);
}

/**
 * Determine the number of rows changed by the preceding query.
 *
 * This may not work, actually, without some tricky temp code.
 *
 * @todo Remove this function when all queries have been ported to db_update().
 */
function db_affected_rows() {
  $statement = Database::getConnection()->lastStatement;
  if (!$statement) {
    return 0;
  }
  return $statement->rowCount();
}

/**
 * Helper function for db_rewrite_sql.
 *
 * Collects JOIN and WHERE statements via hook_db_rewrite_sql()
 * Decides whether to select primary_key or DISTINCT(primary_key)
 *
 * @todo Remove this function when all code has been converted to query_alter.
 * @param $query
 *   Query to be rewritten.
 * @param $primary_table
 *   Name or alias of the table which has the primary key field for this query.
 *   Typical table names would be: {block}, {comment}, {forum}, {node},
 *   {menu}, {taxonomy_term_data} or {taxonomy_vocabulary}. However, in most cases the usual
 *   table alias (b, c, f, n, m, t or v) is used instead of the table name.
 * @param $primary_field
 *   Name of the primary field.
 * @param $args
 *   Array of additional arguments.
 * @return
 *   An array: join statements, where statements, field or DISTINCT(field).
 */
function _db_rewrite_sql($query = '', $primary_table = 'n', $primary_field = 'nid', $args = array()) {
  $where = array();
  $join = array();
  $distinct = FALSE;
  foreach (module_implements('db_rewrite_sql') as $module) {
    $result = module_invoke($module, 'db_rewrite_sql', $query, $primary_table, $primary_field, $args);
    if (isset($result) && is_array($result)) {
      if (isset($result['where'])) {
        $where[] = $result['where'];
      }
      if (isset($result['join'])) {
        $join[] = $result['join'];
      }
      if (isset($result['distinct']) && $result['distinct']) {
        $distinct = TRUE;
      }
    }
    elseif (isset($result)) {
      $where[] = $result;
    }
  }

  $where = empty($where) ? '' : '(' . implode(') AND (', $where) . ')';
  $join = empty($join) ? '' : implode(' ', $join);

  return array($join, $where, $distinct);
}

/**
 * Rewrites node, taxonomy and comment queries. Use it for listing queries. Do not
 * use FROM table1, table2 syntax, use JOIN instead.
 *
 * @todo Remove this function when all code has been converted to query_alter.
 * @param $query
 *   Query to be rewritten.
 * @param $primary_table
 *   Name or alias of the table which has the primary key field for this query.
 *   Typical table names would be: {block}, {comment}, {forum}, {node},
 *   {menu}, {taxonomy_term_data} or {taxonomy_vocabulary}. However, it is more common to use the
 *   the usual table aliases: b, c, f, n, m, t or v.
 * @param $primary_field
 *   Name of the primary field.
 * @param $args
 *   An array of arguments, passed to the implementations of hook_db_rewrite_sql.
 * @return
 *   The original query with JOIN and WHERE statements inserted from
 *   hook_db_rewrite_sql implementations. nid is rewritten if needed.
 */
function db_rewrite_sql($query, $primary_table = 'n', $primary_field = 'nid',  $args = array()) {
  list($join, $where, $distinct) = _db_rewrite_sql($query, $primary_table, $primary_field, $args);

  if ($distinct) {
    $query = db_distinct_field($primary_table, $primary_field, $query);
  }

  if (!empty($where) || !empty($join)) {
    $pattern = '{
      # Beginning of the string
      ^
      ((?P<anonymous_view>
        # Everything within this set of parentheses is named "anonymous view"
        (?:
          [^()]++                   # anything not parentheses
        |
          \( (?P>anonymous_view) \)          # an open parenthesis, more "anonymous view" and finally a close parenthesis.
        )*
      )[^()]+WHERE)
    }x';
    preg_match($pattern, $query, $matches);
    if ($where) {
      $n = strlen($matches[1]);
      $second_part = substr($query, $n);
      $first_part = substr($matches[1], 0, $n - 5) . " $join WHERE $where AND ( ";
      foreach (array('GROUP', 'ORDER', 'LIMIT') as $needle) {
        $pos = strrpos($second_part, $needle);
        if ($pos !== FALSE) {
          // All needles are five characters long.
          $pos += 5;
          break;
        }
      }
      if ($pos === FALSE) {
        $query = $first_part . $second_part . ')';
      }
      else {
        $query = $first_part . substr($second_part, 0, -$pos) . ')' . substr($second_part, -$pos);
      }
    }
    else {
      $query = $matches[1] . " $join " . substr($query, strlen($matches[1]));
    }
  }

  return $query;
}


/**
 * @} End of "ingroup database-legacy".
 */
