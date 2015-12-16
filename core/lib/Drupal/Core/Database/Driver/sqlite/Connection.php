<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\sqlite\Connection.
 */

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\Connection as DatabaseConnection;

/**
 * SQLite implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends DatabaseConnection {

  /**
   * Error code for "Unable to open database file" error.
   */
  const DATABASE_NOT_FOUND = 14;

  /**
   * Whether or not the active transaction (if any) will be rolled back.
   *
   * @var bool
   */
  protected $willRollback;

  /**
   * All databases attached to the current database. This is used to allow
   * prefixes to be safely handled without locking the table
   *
   * @var array
   */
  protected $attachedDatabases = array();

  /**
   * Whether or not a table has been dropped this request: the destructor will
   * only try to get rid of unnecessary databases if there is potential of them
   * being empty.
   *
   * This variable is set to public because Schema needs to
   * access it. However, it should not be manually set.
   *
   * @var bool
   */
  var $tableDropped = FALSE;

  /**
   * Constructs a \Drupal\Core\Database\Driver\sqlite\Connection object.
   */
  public function __construct(\PDO $connection, array $connection_options) {
    // We don't need a specific PDOStatement class here, we simulate it in
    // static::prepare().
    $this->statementClass = NULL;

    parent::__construct($connection, $connection_options);

    // This driver defaults to transaction support, except if explicitly passed FALSE.
    $this->transactionSupport = $this->transactionalDDLSupport = !isset($connection_options['transactions']) || $connection_options['transactions'] !== FALSE;

    $this->connectionOptions = $connection_options;

    // Attach one database for each registered prefix.
    $prefixes = $this->prefixes;
    foreach ($prefixes as &$prefix) {
      // Empty prefix means query the main database -- no need to attach anything.
      if (!empty($prefix)) {
        // Only attach the database once.
        if (!isset($this->attachedDatabases[$prefix])) {
          $this->attachedDatabases[$prefix] = $prefix;
          if ($connection_options['database'] === ':memory:') {
            // In memory database use ':memory:' as database name. According to
            // http://www.sqlite.org/inmemorydb.html it will open a unique
            // database so attaching it twice is not a problem.
            $this->query('ATTACH DATABASE :database AS :prefix', array(':database' => $connection_options['database'], ':prefix' => $prefix));
          }
          else {
            $this->query('ATTACH DATABASE :database AS :prefix', array(':database' => $connection_options['database'] . '-' . $prefix, ':prefix' => $prefix));
          }
        }

        // Add a ., so queries become prefix.table, which is proper syntax for
        // querying an attached database.
        $prefix .= '.';
      }
    }
    // Regenerate the prefixes replacement table.
    $this->setPrefix($prefixes);
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = array()) {
    // Allow PDO options to be overridden.
    $connection_options += array(
      'pdo' => array(),
    );
    $connection_options['pdo'] += array(
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      // Convert numeric values to strings when fetching.
      \PDO::ATTR_STRINGIFY_FETCHES => TRUE,
    );
    $pdo = new \PDO('sqlite:' . $connection_options['database'], '', '', $connection_options['pdo']);

    // Create functions needed by SQLite.
    $pdo->sqliteCreateFunction('if', array(__CLASS__, 'sqlFunctionIf'));
    $pdo->sqliteCreateFunction('greatest', array(__CLASS__, 'sqlFunctionGreatest'));
    $pdo->sqliteCreateFunction('pow', 'pow', 2);
    $pdo->sqliteCreateFunction('exp', 'exp', 1);
    $pdo->sqliteCreateFunction('length', 'strlen', 1);
    $pdo->sqliteCreateFunction('md5', 'md5', 1);
    $pdo->sqliteCreateFunction('concat', array(__CLASS__, 'sqlFunctionConcat'));
    $pdo->sqliteCreateFunction('concat_ws', array(__CLASS__, 'sqlFunctionConcatWs'));
    $pdo->sqliteCreateFunction('substring', array(__CLASS__, 'sqlFunctionSubstring'), 3);
    $pdo->sqliteCreateFunction('substring_index', array(__CLASS__, 'sqlFunctionSubstringIndex'), 3);
    $pdo->sqliteCreateFunction('rand', array(__CLASS__, 'sqlFunctionRand'));
    $pdo->sqliteCreateFunction('regexp', array(__CLASS__, 'sqlFunctionRegexp'));

    // SQLite does not support the LIKE BINARY operator, so we overload the
    // non-standard GLOB operator for case-sensitive matching. Another option
    // would have been to override another non-standard operator, MATCH, but
    // that does not support the NOT keyword prefix.
    $pdo->sqliteCreateFunction('glob', array(__CLASS__, 'sqlFunctionLikeBinary'));

    // Create a user-space case-insensitive collation with UTF-8 support.
    $pdo->sqliteCreateCollation('NOCASE_UTF8', array('Drupal\Component\Utility\Unicode', 'strcasecmp'));

    // Execute sqlite init_commands.
    if (isset($connection_options['init_commands'])) {
      $pdo->exec(implode('; ', $connection_options['init_commands']));
    }

    return $pdo;
  }


  /**
   * Destructor for the SQLite connection.
   *
   * We prune empty databases on destruct, but only if tables have been
   * dropped. This is especially needed when running the test suite, which
   * creates and destroy databases several times in a row.
   */
  public function __destruct() {
    if ($this->tableDropped && !empty($this->attachedDatabases)) {
      foreach ($this->attachedDatabases as $prefix) {
        // Check if the database is now empty, ignore the internal SQLite tables.
        try {
          $count = $this->query('SELECT COUNT(*) FROM ' . $prefix . '.sqlite_master WHERE type = :type AND name NOT LIKE :pattern', array(':type' => 'table', ':pattern' => 'sqlite_%'))->fetchField();

          // We can prune the database file if it doesn't have any tables.
          if ($count == 0) {
            // Detaching the database fails at this point, but no other queries
            // are executed after the connection is destructed so we can simply
            // remove the database file.
            unlink($this->connectionOptions['database'] . '-' . $prefix);
          }
        }
        catch (\Exception $e) {
          // Ignore the exception and continue. There is nothing we can do here
          // to report the error or fail safe.
        }
      }
    }
  }

  /**
   * Gets all the attached databases.
   *
   * @return array
   *   An array of attached database names.
   *
   * @see \Drupal\Core\Database\Driver\sqlite\Connection::__construct()
   */
  public function getAttachedDatabases() {
    return $this->attachedDatabases;
  }

  /**
   * SQLite compatibility implementation for the IF() SQL function.
   */
  public static function sqlFunctionIf($condition, $expr1, $expr2 = NULL) {
    return $condition ? $expr1 : $expr2;
  }

  /**
   * SQLite compatibility implementation for the GREATEST() SQL function.
   */
  public static function sqlFunctionGreatest() {
    $args = func_get_args();
    foreach ($args as $v) {
      if (!isset($v)) {
        unset($args);
      }
    }
    if (count($args)) {
      return max($args);
    }
    else {
      return NULL;
    }
  }

  /**
   * SQLite compatibility implementation for the CONCAT() SQL function.
   */
  public static function sqlFunctionConcat() {
    $args = func_get_args();
    return implode('', $args);
  }

  /**
   * SQLite compatibility implementation for the CONCAT_WS() SQL function.
   *
   * @see http://dev.mysql.com/doc/refman/5.6/en/string-functions.html#function_concat-ws
   */
  public static function sqlFunctionConcatWs() {
    $args = func_get_args();
    $separator = array_shift($args);
    // If the separator is NULL, the result is NULL.
    if ($separator === FALSE || is_null($separator)) {
      return NULL;
    }
    // Skip any NULL values after the separator argument.
    $args = array_filter($args, function ($value) {
      return !is_null($value);
    });
    return implode($separator, $args);
  }

  /**
   * SQLite compatibility implementation for the SUBSTRING() SQL function.
   */
  public static function sqlFunctionSubstring($string, $from, $length) {
    return substr($string, $from - 1, $length);
  }

  /**
   * SQLite compatibility implementation for the SUBSTRING_INDEX() SQL function.
   */
  public static function sqlFunctionSubstringIndex($string, $delimiter, $count) {
    // If string is empty, simply return an empty string.
    if (empty($string)) {
      return '';
    }
    $end = 0;
    for ($i = 0; $i < $count; $i++) {
      $end = strpos($string, $delimiter, $end + 1);
      if ($end === FALSE) {
        $end = strlen($string);
      }
    }
    return substr($string, 0, $end);
  }

  /**
   * SQLite compatibility implementation for the RAND() SQL function.
   */
  public static function sqlFunctionRand($seed = NULL) {
    if (isset($seed)) {
      mt_srand($seed);
    }
    return mt_rand() / mt_getrandmax();
  }

  /**
   * SQLite compatibility implementation for the REGEXP SQL operator.
   *
   * The REGEXP operator is natively known, but not implemented by default.
   *
   * @see http://www.sqlite.org/lang_expr.html#regexp
   */
  public static function sqlFunctionRegexp($pattern, $subject) {
    // preg_quote() cannot be used here, since $pattern may contain reserved
    // regular expression characters already (such as ^, $, etc). Therefore,
    // use a rare character as PCRE delimiter.
    $pattern = '#' . addcslashes($pattern, '#') . '#i';
    return preg_match($pattern, $subject);
  }

  /**
   * SQLite compatibility implementation for the LIKE BINARY SQL operator.
   *
   * SQLite supports case-sensitive LIKE operations through the
   * 'case_sensitive_like' PRAGMA statement, but only for ASCII characters, so
   * we have to provide our own implementation with UTF-8 support.
   *
   * @see https://sqlite.org/pragma.html#pragma_case_sensitive_like
   * @see https://sqlite.org/lang_expr.html#like
   */
  public static function sqlFunctionLikeBinary($pattern, $subject) {
    // Replace the SQL LIKE wildcard meta-characters with the equivalent regular
    // expression meta-characters and escape the delimiter that will be used for
    // matching.
    $pattern = str_replace(array('%', '_'), array('.*?', '.'), preg_quote($pattern, '/'));
    return preg_match('/^' . $pattern . '$/', $subject);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare($statement, array $driver_options = array()) {
    return new Statement($this->connection, $this, $statement, $driver_options);
  }

  /**
   * {@inheritdoc}
   */
  protected function handleQueryException(\PDOException $e, $query, array $args = array(), $options = array()) {
    // The database schema might be changed by another process in between the
    // time that the statement was prepared and the time the statement was run
    // (e.g. usually happens when running tests). In this case, we need to
    // re-run the query.
    // @see http://www.sqlite.org/faq.html#q15
    // @see http://www.sqlite.org/rescode.html#schema
    if (!empty($e->errorInfo[1]) && $e->errorInfo[1] === 17) {
      return $this->query($query, $args, $options);
    }

    parent::handleQueryException($e, $query, $args, $options);
  }

  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    return $this->query($query . ' LIMIT ' . (int) $from . ', ' . (int) $count, $args, $options);
  }

  public function queryTemporary($query, array $args = array(), array $options = array()) {
    // Generate a new temporary table name and protect it from prefixing.
    // SQLite requires that temporary tables to be non-qualified.
    $tablename = $this->generateTemporaryTableName();
    $prefixes = $this->prefixes;
    $prefixes[$tablename] = '';
    $this->setPrefix($prefixes);

    $this->query('CREATE TEMPORARY TABLE ' . $tablename . ' AS ' . $query, $args, $options);
    return $tablename;
  }

  public function driver() {
    return 'sqlite';
  }

  public function databaseType() {
    return 'sqlite';
  }

  /**
   * Overrides \Drupal\Core\Database\Connection::createDatabase().
   *
   * @param string $database
   *   The name of the database to create.
   *
   * @throws \Drupal\Core\Database\DatabaseNotFoundException
   */
  public function createDatabase($database) {
    // Verify the database is writable.
    $db_directory = new \SplFileInfo(dirname($database));
    if (!$db_directory->isDir() && !drupal_mkdir($db_directory->getPathName(), 0755, TRUE)) {
      throw new DatabaseNotFoundException('Unable to create database directory ' . $db_directory->getPathName());
    }
  }

  public function mapConditionOperator($operator) {
    // We don't want to override any of the defaults.
    static $specials = array(
      'LIKE' => array('postfix' => " ESCAPE '\\'"),
      'NOT LIKE' => array('postfix' => " ESCAPE '\\'"),
      'LIKE BINARY' => array('postfix' => " ESCAPE '\\'", 'operator' => 'GLOB'),
      'NOT LIKE BINARY' => array('postfix' => " ESCAPE '\\'", 'operator' => 'NOT GLOB'),
    );
    return isset($specials[$operator]) ? $specials[$operator] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareQuery($query) {
    return $this->prepare($this->prefixTables($query));
  }

  public function nextId($existing_id = 0) {
    $this->startTransaction();
    // We can safely use literal queries here instead of the slower query
    // builder because if a given database breaks here then it can simply
    // override nextId. However, this is unlikely as we deal with short strings
    // and integers and no known databases require special handling for those
    // simple cases. If another transaction wants to write the same row, it will
    // wait until this transaction commits. Also, the return value needs to be
    // set to RETURN_AFFECTED as if it were a real update() query otherwise it
    // is not possible to get the row count properly.
    $affected = $this->query('UPDATE {sequences} SET value = GREATEST(value, :existing_id) + 1', array(
      ':existing_id' => $existing_id,
    ), array('return' => Database::RETURN_AFFECTED));
    if (!$affected) {
      $this->query('INSERT INTO {sequences} (value) VALUES (:existing_id + 1)', array(
        ':existing_id' => $existing_id,
      ));
    }
    // The transaction gets committed when the transaction object gets destroyed
    // because it gets out of scope.
    return $this->query('SELECT value FROM {sequences}')->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getFullQualifiedTableName($table) {
    $prefix = $this->tablePrefix($table);

    // Don't include the SQLite database file name as part of the table name.
    return $prefix . $table;
  }

}
