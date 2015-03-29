<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\sqlite\Connection
 */

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\TransactionNoActiveException;
use Drupal\Core\Database\TransactionNameNonUniqueException;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\Connection as DatabaseConnection;

/**
 * Specific SQLite implementation of DatabaseConnection.
 */
class Connection extends DatabaseConnection {

  /**
   * Whether this database connection supports savepoints.
   *
   * Version of sqlite lower then 3.6.8 can't use savepoints.
   * See http://www.sqlite.org/releaselog/3_6_8.html
   *
   * @var boolean
   */
  protected $savepointSupport = FALSE;

  /**
   * Error code for "Unable to open database file" error.
   */
  const DATABASE_NOT_FOUND = 14;

  /**
   * Whether or not the active transaction (if any) will be rolled back.
   *
   * @var boolean
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
   * @var boolean
   */
  var $tableDropped = FALSE;

  /**
   * Constructs a \Drupal\Core\Database\Driver\sqlite\Connection object.
   */
  public function __construct(\PDO $connection, array $connection_options) {
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
          $this->query('ATTACH DATABASE :database AS :prefix', array(':database' => $connection_options['database'] . '-' . $prefix, ':prefix' => $prefix));
        }

        // Add a ., so queries become prefix.table, which is proper syntax for
        // querying an attached database.
        $prefix .= '.';
      }
    }
    // Regenerate the prefixes replacement table.
    $this->setPrefix($prefixes);

    // Detect support for SAVEPOINT.
    $version = $this->query('SELECT sqlite_version()')->fetchField();
    $this->savepointSupport = (version_compare($version, '3.6.8') >= 0);
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
    $pdo->sqliteCreateFunction('length', 'strlen', 1);
    $pdo->sqliteCreateFunction('md5', 'md5', 1);
    $pdo->sqliteCreateFunction('concat', array(__CLASS__, 'sqlFunctionConcat'));
    $pdo->sqliteCreateFunction('concat_ws', array(__CLASS__, 'sqlFunctionConcatWs'));
    $pdo->sqliteCreateFunction('substring', array(__CLASS__, 'sqlFunctionSubstring'), 3);
    $pdo->sqliteCreateFunction('substring_index', array(__CLASS__, 'sqlFunctionSubstringIndex'), 3);
    $pdo->sqliteCreateFunction('rand', array(__CLASS__, 'sqlFunctionRand'));
    $pdo->sqliteCreateFunction('regexp', array(__CLASS__, 'sqlFunctionRegexp'));

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
            // Detach the database.
            $this->query('DETACH DATABASE :schema', array(':schema' => $prefix));
            // Destroy the database file.
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
   * {@inheritdoc}
   */
  protected function expandArguments(&$query, &$args) {
    $modified = parent::expandArguments($query, $args);

    // The PDO SQLite driver always replaces placeholders with strings, which
    // breaks numeric expressions (e.g., COUNT(*) >= :count). Replace numeric
    // placeholders in the query to work around this bug.
    // @see http://bugs.php.net/bug.php?id=45259
    if (empty($args)) {
      return $modified;
    }
    // Check if $args is a simple numeric array.
    if (range(0, count($args) - 1) === array_keys($args)) {
      // In that case, we have unnamed placeholders.
      $count = 0;
      $new_args = array();
      foreach ($args as $value) {
        if (is_float($value) || is_int($value) || is_numeric($value)) {
          if (is_float($value)) {
            // Force the conversion to float so as not to loose precision
            // in the automatic cast.
            $value = sprintf('%F', $value);
          }
          $query = substr_replace($query, $value, strpos($query, '?'), 1);
        }
        else {
          $placeholder = ':db_statement_placeholder_' . $count++;
          $query = substr_replace($query, $placeholder, strpos($query, '?'), 1);
          $new_args[$placeholder] = $value;
        }
      }
      $args = $new_args;
      $modified = TRUE;
    }
    // Otherwise this is using named placeholders.
    else {
      foreach ($args as $placeholder => $value) {
        if (is_float($value) || is_int($value) || is_numeric($value)) {
          if (is_float($value)) {
            // Force the conversion to float so as not to loose precision
            // in the automatic cast.
            $value = sprintf('%F', $value);
          }

          // We will remove this placeholder from the query as PDO throws an
          // exception if the number of placeholders in the query and the
          // arguments does not match.
          unset($args[$placeholder]);
          // PDO allows placeholders to not be prefixed by a colon. See
          // http://marc.info/?l=php-internals&m=111234321827149&w=2 for
          // more.
          if ($placeholder[0] != ':') {
            $placeholder = ":$placeholder";
          }
          // When replacing the placeholders, make sure we search for the
          // exact placeholder. For example, if searching for
          // ':db_placeholder_1', do not replace ':db_placeholder_11'.
          $query = preg_replace('/' . preg_quote($placeholder, '/') . '\b/', $value, $query);

          $modified = TRUE;
        }
      }
    }
    return $modified;
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
    );
    return isset($specials[$operator]) ? $specials[$operator] : NULL;
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

  public function rollback($savepoint_name = 'drupal_transaction') {
    if ($this->savepointSupport) {
      return parent::rollBack($savepoint_name);
    }

    if (!$this->inTransaction()) {
      throw new TransactionNoActiveException();
    }
    // A previous rollback to an earlier savepoint may mean that the savepoint
    // in question has already been rolled back.
    if (!isset($this->transactionLayers[$savepoint_name])) {
      return;
    }

    // We need to find the point we're rolling back to, all other savepoints
    // before are no longer needed.
    while ($savepoint = array_pop($this->transactionLayers)) {
      if ($savepoint == $savepoint_name) {
        // Mark whole stack of transactions as needed roll back.
        $this->willRollback = TRUE;
        // If it is the last the transaction in the stack, then it is not a
        // savepoint, it is the transaction itself so we will need to roll back
        // the transaction rather than a savepoint.
        if (empty($this->transactionLayers)) {
          break;
        }
        return;
      }
    }
    if ($this->supportsTransactions()) {
      $this->connection->rollBack();
    }
  }

  public function pushTransaction($name) {
    if ($this->savepointSupport) {
      return parent::pushTransaction($name);
    }
    if (!$this->supportsTransactions()) {
      return;
    }
    if (isset($this->transactionLayers[$name])) {
      throw new TransactionNameNonUniqueException($name . " is already in use.");
    }
    if (!$this->inTransaction()) {
      $this->connection->beginTransaction();
    }
    $this->transactionLayers[$name] = $name;
  }

  public function popTransaction($name) {
    if ($this->savepointSupport) {
      return parent::popTransaction($name);
    }
    if (!$this->supportsTransactions()) {
      return;
    }
    if (!$this->inTransaction()) {
      throw new TransactionNoActiveException();
    }

    // Commit everything since SAVEPOINT $name.
    while($savepoint = array_pop($this->transactionLayers)) {
      if ($savepoint != $name) continue;

      // If there are no more layers left then we should commit or rollback.
      if (empty($this->transactionLayers)) {
        // If there was any rollback() we should roll back whole transaction.
        if ($this->willRollback) {
          $this->willRollback = FALSE;
          $this->connection->rollBack();
        }
        elseif (!$this->connection->commit()) {
          throw new TransactionCommitFailedException();
        }
      }
      else {
        break;
      }
    }
  }

}
