<?php

namespace Drupal\mysql\Driver\Database\mysql;

use Drupal\Core\Database\DatabaseAccessDeniedException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\StatementWrapper;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Database\DatabaseConnectionRefusedException;
use Drupal\Core\Database\SupportsTemporaryTablesInterface;
use Drupal\Core\Database\TransactionNoActiveException;

/**
 * @addtogroup database
 * @{
 */

/**
 * MySQL implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends DatabaseConnection implements SupportsTemporaryTablesInterface {

  /**
   * Error code for "Unknown database" error.
   */
  const DATABASE_NOT_FOUND = 1049;

  /**
   * Error code for "Access denied" error.
   */
  const ACCESS_DENIED = 1045;

  /**
   * Error code for "Connection refused".
   */
  const CONNECTION_REFUSED = 2002;

  /**
   * Error code for "Can't initialize character set" error.
   */
  const UNSUPPORTED_CHARSET = 2019;

  /**
   * Driver-specific error code for "Unknown character set" error.
   */
  const UNKNOWN_CHARSET = 1115;

  /**
   * SQLSTATE error code for "Syntax error or access rule violation".
   */
  const SQLSTATE_SYNTAX_ERROR = 42000;

  /**
   * {@inheritdoc}
   */
  protected $statementWrapperClass = StatementWrapper::class;

  /**
   * Flag to indicate if the cleanup function in __destruct() should run.
   *
   * @var bool
   */
  protected $needsCleanup = FALSE;

  /**
   * Stores the server version after it has been retrieved from the database.
   *
   * @var string
   *
   * @see \Drupal\mysql\Driver\Database\mysql\Connection::version
   */
  private $serverVersion;

  /**
   * The minimal possible value for the max_allowed_packet setting of MySQL.
   *
   * @link https://mariadb.com/kb/en/mariadb/server-system-variables/#max_allowed_packet
   * @link https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_max_allowed_packet
   *
   * @var int
   */
  const MIN_MAX_ALLOWED_PACKET = 1024;

  /**
   * {@inheritdoc}
   */
  protected $identifierQuotes = ['"', '"'];

  /**
   * {@inheritdoc}
   */
  public function __construct(\PDO $connection, array $connection_options) {
    // If the SQL mode doesn't include 'ANSI_QUOTES' (explicitly or via a
    // combination mode), then MySQL doesn't interpret a double quote as an
    // identifier quote, in which case use the non-ANSI-standard backtick.
    //
    // Because we still support MySQL 5.7, check for the deprecated combination
    // modes as well.
    //
    // @see https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_ansi_quotes
    $ansi_quotes_modes = ['ANSI_QUOTES', 'ANSI', 'DB2', 'MAXDB', 'MSSQL', 'ORACLE', 'POSTGRESQL'];
    $is_ansi_quotes_mode = FALSE;
    foreach ($ansi_quotes_modes as $mode) {
      // None of the modes in $ansi_quotes_modes are substrings of other modes
      // that are not in $ansi_quotes_modes, so a simple stripos() does not
      // return false positives.
      if (stripos($connection_options['init_commands']['sql_mode'], $mode) !== FALSE) {
        $is_ansi_quotes_mode = TRUE;
        break;
      }
    }
    if ($this->identifierQuotes === ['"', '"'] && !$is_ansi_quotes_mode) {
      $this->identifierQuotes = ['`', '`'];
    }
    parent::__construct($connection, $connection_options);
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = []) {
    if (isset($connection_options['_dsn_utf8_fallback']) && $connection_options['_dsn_utf8_fallback'] === TRUE) {
      // Only used during the installer version check, as a fallback from utf8mb4.
      $charset = 'utf8';
    }
    else {
      $charset = 'utf8mb4';
    }
    // The DSN should use either a socket or a host/port.
    if (isset($connection_options['unix_socket'])) {
      $dsn = 'mysql:unix_socket=' . $connection_options['unix_socket'];
    }
    else {
      // Default to TCP connection on port 3306.
      $dsn = 'mysql:host=' . $connection_options['host'] . ';port=' . (empty($connection_options['port']) ? 3306 : $connection_options['port']);
    }
    // Character set is added to dsn to ensure PDO uses the proper character
    // set when escaping. This has security implications. See
    // https://www.drupal.org/node/1201452 for further discussion.
    $dsn .= ';charset=' . $charset;
    if (!empty($connection_options['database'])) {
      $dsn .= ';dbname=' . $connection_options['database'];
    }
    // Allow PDO options to be overridden.
    $connection_options += [
      'pdo' => [],
    ];
    $connection_options['pdo'] += [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      // So we don't have to mess around with cursors and unbuffered queries by default.
      \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
      // Make sure MySQL returns all matched rows on update queries including
      // rows that actually didn't have to be updated because the values didn't
      // change. This matches common behavior among other database systems.
      \PDO::MYSQL_ATTR_FOUND_ROWS => TRUE,
      // Because MySQL's prepared statements skip the query cache, because it's dumb.
      \PDO::ATTR_EMULATE_PREPARES => TRUE,
      // Limit SQL to a single statement like mysqli.
      \PDO::MYSQL_ATTR_MULTI_STATEMENTS => FALSE,
      // Convert numeric values to strings when fetching. In PHP 8.1,
      // \PDO::ATTR_EMULATE_PREPARES now behaves the same way as non emulated
      // prepares and returns integers. See https://externals.io/message/113294
      // for further discussion.
      \PDO::ATTR_STRINGIFY_FETCHES => TRUE,
    ];

    try {
      $pdo = new \PDO($dsn, $connection_options['username'], $connection_options['password'], $connection_options['pdo']);
    }
    catch (\PDOException $e) {
      switch ($e->getCode()) {
        case static::CONNECTION_REFUSED:
          if (isset($connection_options['unix_socket'])) {
            // Show message for socket connection via 'unix_socket' option.
            $message = 'Drupal is configured to connect to the database server via a socket, but the socket file could not be found.';
            $message .= ' This message normally means that there is no MySQL server running on the system or that you are using an incorrect Unix socket file name when trying to connect to the server.';
            throw new DatabaseConnectionRefusedException($e->getMessage() . ' [Tip: ' . $message . '] ', $e->getCode(), $e);
          }
          if (isset($connection_options['host']) && in_array(strtolower($connection_options['host']), ['', 'localhost'], TRUE)) {
            // Show message for socket connection via 'host' option.
            $message = 'Drupal was attempting to connect to the database server via a socket, but the socket file could not be found.';
            $message .= ' A Unix socket file is used if you do not specify a host name or if you specify the special host name localhost.';
            $message .= ' To connect via TPC/IP use an IP address (127.0.0.1 for IPv4) instead of "localhost".';
            $message .= ' This message normally means that there is no MySQL server running on the system or that you are using an incorrect Unix socket file name when trying to connect to the server.';
            throw new DatabaseConnectionRefusedException($e->getMessage() . ' [Tip: ' . $message . '] ', $e->getCode(), $e);
          }
          // Show message for TCP/IP connection.
          $message = 'This message normally means that there is no MySQL server running on the system or that you are using an incorrect host name or port number when trying to connect to the server.';
          $message .= ' You should also check that the TCP/IP port you are using has not been blocked by a firewall or port blocking service.';
          throw new DatabaseConnectionRefusedException($e->getMessage() . ' [Tip: ' . $message . '] ', $e->getCode(), $e);

        case static::DATABASE_NOT_FOUND:
          throw new DatabaseNotFoundException($e->getMessage(), $e->getCode(), $e);

        case static::ACCESS_DENIED:
          throw new DatabaseAccessDeniedException($e->getMessage(), $e->getCode(), $e);

        default:
          throw $e;
      }
    }

    // Force MySQL to use the UTF-8 character set. Also set the collation, if a
    // certain one has been set; otherwise, MySQL defaults to
    // 'utf8mb4_general_ci' (MySQL 5) or 'utf8mb4_0900_ai_ci' (MySQL 8) for
    // utf8mb4.
    if (!empty($connection_options['collation'])) {
      $pdo->exec('SET NAMES ' . $charset . ' COLLATE ' . $connection_options['collation']);
    }
    else {
      $pdo->exec('SET NAMES ' . $charset);
    }

    // Set MySQL init_commands if not already defined.  Default Drupal's MySQL
    // behavior to conform more closely to SQL standards.  This allows Drupal
    // to run almost seamlessly on many different kinds of database systems.
    // These settings force MySQL to behave the same as postgresql, or sqlite
    // in regards to syntax interpretation and invalid data handling.  See
    // https://www.drupal.org/node/344575 for further discussion. Also, as MySQL
    // 5.5 changed the meaning of TRADITIONAL we need to spell out the modes one
    // by one.
    $connection_options += [
      'init_commands' => [],
    ];

    $connection_options['init_commands'] += [
      'sql_mode' => "SET sql_mode = 'ANSI,TRADITIONAL'",
    ];

    // Execute initial commands.
    foreach ($connection_options['init_commands'] as $sql) {
      $pdo->exec($sql);
    }

    return $pdo;
  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {
    if ($this->needsCleanup) {
      $this->nextIdDelete();
    }
    parent::__destruct();
  }

  public function queryRange($query, $from, $count, array $args = [], array $options = []) {
    return $this->query($query . ' LIMIT ' . (int) $from . ', ' . (int) $count, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = [], array $options = []) {
    $tablename = 'db_temporary_' . uniqid();
    $this->query('CREATE TEMPORARY TABLE {' . $tablename . '} Engine=MEMORY ' . $query, $args, $options);
    return $tablename;
  }

  public function driver() {
    return 'mysql';
  }

  /**
   * {@inheritdoc}
   */
  public function version() {
    if ($this->isMariaDb()) {
      return $this->getMariaDbVersionMatch();
    }

    return $this->getServerVersion();
  }

  /**
   * Determines whether the MySQL distribution is MariaDB or not.
   *
   * @return bool
   *   Returns TRUE if the distribution is MariaDB, or FALSE if not.
   */
  public function isMariaDb(): bool {
    return (bool) $this->getMariaDbVersionMatch();
  }

  /**
   * Gets the MariaDB portion of the server version.
   *
   * @return string
   *   The MariaDB portion of the server version if present, or NULL if not.
   */
  protected function getMariaDbVersionMatch(): ?string {
    // MariaDB may prefix its version string with '5.5.5-', which should be
    // ignored.
    // @see https://github.com/MariaDB/server/blob/f6633bf058802ad7da8196d01fd19d75c53f7274/include/mysql_com.h#L42.
    $regex = '/^(?:5\.5\.5-)?(\d+\.\d+\.\d+.*-mariadb.*)/i';

    preg_match($regex, $this->getServerVersion(), $matches);
    return (empty($matches[1])) ? NULL : $matches[1];
  }

  /**
   * Gets the server version.
   *
   * @return string
   *   The PDO server version.
   */
  protected function getServerVersion(): string {
    if (!$this->serverVersion) {
      $this->serverVersion = $this->query('SELECT VERSION()')->fetchField();
    }
    return $this->serverVersion;
  }

  public function databaseType() {
    return 'mysql';
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
    // Escape the database name.
    $database = Database::getConnection()->escapeDatabase($database);

    try {
      // Create the database and set it as active.
      $this->connection->exec("CREATE DATABASE $database");
      $this->connection->exec("USE $database");
    }
    catch (\Exception $e) {
      throw new DatabaseNotFoundException($e->getMessage());
    }
  }

  public function mapConditionOperator($operator) {
    // We don't want to override any of the defaults.
    return NULL;
  }

  public function nextId($existing_id = 0) {
    $this->query('INSERT INTO {sequences} () VALUES ()');
    $new_id = $this->lastInsertId();
    // This should only happen after an import or similar event.
    if ($existing_id >= $new_id) {
      // If we INSERT a value manually into the sequences table, on the next
      // INSERT, MySQL will generate a larger value. However, there is no way
      // of knowing whether this value already exists in the table. MySQL
      // provides an INSERT IGNORE which would work, but that can mask problems
      // other than duplicate keys. Instead, we use INSERT ... ON DUPLICATE KEY
      // UPDATE in such a way that the UPDATE does not do anything. This way,
      // duplicate keys do not generate errors but everything else does.
      $this->query('INSERT INTO {sequences} (value) VALUES (:value) ON DUPLICATE KEY UPDATE value = value', [':value' => $existing_id]);
      $this->query('INSERT INTO {sequences} () VALUES ()');
      $new_id = $this->lastInsertId();
    }
    $this->needsCleanup = TRUE;
    return $new_id;
  }

  public function nextIdDelete() {
    // While we want to clean up the table to keep it up from occupying too
    // much storage and memory, we must keep the highest value in the table
    // because InnoDB uses an in-memory auto-increment counter as long as the
    // server runs. When the server is stopped and restarted, InnoDB
    // re-initializes the counter for each table for the first INSERT to the
    // table based solely on values from the table so deleting all values would
    // be a problem in this case. Also, TRUNCATE resets the auto increment
    // counter.
    try {
      $max_id = $this->query('SELECT MAX(value) FROM {sequences}')->fetchField();
      // We know we are using MySQL here, no need for the slower ::delete().
      $this->query('DELETE FROM {sequences} WHERE value < :value', [':value' => $max_id]);
    }
    // During testing, this function is called from shutdown with the
    // simpletest prefix stored in $this->connection, and those tables are gone
    // by the time shutdown is called so we need to ignore the database
    // errors. There is no problem with completely ignoring errors here: if
    // these queries fail, the sequence will work just fine, just use a bit
    // more database storage and memory.
    catch (DatabaseException $e) {
    }
  }

  /**
   * Overridden to work around issues to MySQL not supporting transactional DDL.
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
        $this->doCommit();
      }
      else {
        // Attempt to release this savepoint in the standard way.
        try {
          $this->query('RELEASE SAVEPOINT ' . $name);
        }
        catch (DatabaseExceptionWrapper $e) {
          // However, in MySQL (InnoDB), savepoints are automatically committed
          // when tables are altered or created (DDL transactions are not
          // supported). This can cause exceptions due to trying to release
          // savepoints which no longer exist.
          //
          // To avoid exceptions when no actual error has occurred, we silently
          // succeed for MySQL error code 1305 ("SAVEPOINT does not exist").
          if ($e->getPrevious()->errorInfo[1] == '1305') {
            // If one SAVEPOINT was released automatically, then all were.
            // Therefore, clean the transaction stack.
            $this->transactionLayers = [];
            // We also have to explain to PDO that the transaction stack has
            // been cleaned-up.
            $this->doCommit();
          }
          else {
            throw $e;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rollBack($savepoint_name = 'drupal_transaction') {
    // MySQL will automatically commit transactions when tables are altered or
    // created (DDL transactions are not supported). Prevent triggering an
    // exception to ensure that the error that has caused the rollback is
    // properly reported.
    if (!$this->connection->inTransaction()) {
      // On PHP 7 $this->connection->inTransaction() will return TRUE and
      // $this->connection->rollback() does not throw an exception; the
      // following code is unreachable.

      // If \Drupal\Core\Database\Connection::rollBack() would throw an
      // exception then continue to throw an exception.
      if (!$this->inTransaction()) {
        throw new TransactionNoActiveException();
      }
      // A previous rollback to an earlier savepoint may mean that the savepoint
      // in question has already been accidentally committed.
      if (!isset($this->transactionLayers[$savepoint_name])) {
        throw new TransactionNoActiveException();
      }

      trigger_error('Rollback attempted when there is no active transaction. This can cause data integrity issues.', E_USER_WARNING);
      return;
    }
    return parent::rollBack($savepoint_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function doCommit() {
    // MySQL will automatically commit transactions when tables are altered or
    // created (DDL transactions are not supported). Prevent triggering an
    // exception in this case as all statements have been committed.
    if ($this->connection->inTransaction()) {
      // On PHP 7 $this->connection->inTransaction() will return TRUE and
      // $this->connection->commit() does not throw an exception.
      $success = parent::doCommit();
    }
    else {
      // Process the post-root (non-nested) transaction commit callbacks. The
      // following code is copied from
      // \Drupal\Core\Database\Connection::doCommit()
      $success = TRUE;
      if (!empty($this->rootTransactionEndCallbacks)) {
        $callbacks = $this->rootTransactionEndCallbacks;
        $this->rootTransactionEndCallbacks = [];
        foreach ($callbacks as $callback) {
          call_user_func($callback, $success);
        }
      }
    }
    return $success;
  }

}


/**
 * @} End of "addtogroup database".
 */
