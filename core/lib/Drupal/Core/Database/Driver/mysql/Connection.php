<?php

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\Core\Database\DatabaseAccessDeniedException;
use Drupal\Core\Database\DatabaseExceptionWrapper;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Component\Utility\Unicode;

/**
 * @addtogroup database
 * @{
 */

/**
 * MySQL implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends DatabaseConnection {

  /**
   * Error code for "Unknown database" error.
   */
  const DATABASE_NOT_FOUND = 1049;

  /**
   * Error code for "Access denied" error.
   */
  const ACCESS_DENIED = 1045;

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
   * Flag to indicate if the cleanup function in __destruct() should run.
   *
   * @var bool
   */
  protected $needsCleanup = FALSE;

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
   * The list of MySQL reserved key words.
   *
   * @link https://dev.mysql.com/doc/refman/8.0/en/keywords.html
   */
  private $reservedKeyWords = [
    'accessible',
    'add',
    'admin',
    'all',
    'alter',
    'analyze',
    'and',
    'as',
    'asc',
    'asensitive',
    'before',
    'between',
    'bigint',
    'binary',
    'blob',
    'both',
    'by',
    'call',
    'cascade',
    'case',
    'change',
    'char',
    'character',
    'check',
    'collate',
    'column',
    'condition',
    'constraint',
    'continue',
    'convert',
    'create',
    'cross',
    'cube',
    'cume_dist',
    'current_date',
    'current_time',
    'current_timestamp',
    'current_user',
    'cursor',
    'database',
    'databases',
    'day_hour',
    'day_microsecond',
    'day_minute',
    'day_second',
    'dec',
    'decimal',
    'declare',
    'default',
    'delayed',
    'delete',
    'dense_rank',
    'desc',
    'describe',
    'deterministic',
    'distinct',
    'distinctrow',
    'div',
    'double',
    'drop',
    'dual',
    'each',
    'else',
    'elseif',
    'empty',
    'enclosed',
    'escaped',
    'except',
    'exists',
    'exit',
    'explain',
    'false',
    'fetch',
    'first_value',
    'float',
    'float4',
    'float8',
    'for',
    'force',
    'foreign',
    'from',
    'fulltext',
    'function',
    'generated',
    'get',
    'grant',
    'group',
    'grouping',
    'groups',
    'having',
    'high_priority',
    'hour_microsecond',
    'hour_minute',
    'hour_second',
    'if',
    'ignore',
    'in',
    'index',
    'infile',
    'inner',
    'inout',
    'insensitive',
    'insert',
    'int',
    'int1',
    'int2',
    'int3',
    'int4',
    'int8',
    'integer',
    'interval',
    'into',
    'io_after_gtids',
    'io_before_gtids',
    'is',
    'iterate',
    'join',
    'json_table',
    'key',
    'keys',
    'kill',
    'lag',
    'last_value',
    'lead',
    'leading',
    'leave',
    'left',
    'like',
    'limit',
    'linear',
    'lines',
    'load',
    'localtime',
    'localtimestamp',
    'lock',
    'long',
    'longblob',
    'longtext',
    'loop',
    'low_priority',
    'master_bind',
    'master_ssl_verify_server_cert',
    'match',
    'maxvalue',
    'mediumblob',
    'mediumint',
    'mediumtext',
    'middleint',
    'minute_microsecond',
    'minute_second',
    'mod',
    'modifies',
    'natural',
    'not',
    'no_write_to_binlog',
    'nth_value',
    'ntile',
    'null',
    'numeric',
    'of',
    'on',
    'optimize',
    'optimizer_costs',
    'option',
    'optionally',
    'or',
    'order',
    'out',
    'outer',
    'outfile',
    'over',
    'partition',
    'percent_rank',
    'persist',
    'persist_only',
    'precision',
    'primary',
    'procedure',
    'purge',
    'range',
    'rank',
    'read',
    'reads',
    'read_write',
    'real',
    'recursive',
    'references',
    'regexp',
    'release',
    'rename',
    'repeat',
    'replace',
    'require',
    'resignal',
    'restrict',
    'return',
    'revoke',
    'right',
    'rlike',
    'row',
    'rows',
    'row_number',
    'schema',
    'schemas',
    'second_microsecond',
    'select',
    'sensitive',
    'separator',
    'set',
    'show',
    'signal',
    'smallint',
    'spatial',
    'specific',
    'sql',
    'sqlexception',
    'sqlstate',
    'sqlwarning',
    'sql_big_result',
    'sql_calc_found_rows',
    'sql_small_result',
    'ssl',
    'starting',
    'stored',
    'straight_join',
    'system',
    'table',
    'terminated',
    'then',
    'tinyblob',
    'tinyint',
    'tinytext',
    'to',
    'trailing',
    'trigger',
    'true',
    'undo',
    'union',
    'unique',
    'unlock',
    'unsigned',
    'update',
    'usage',
    'use',
    'using',
    'utc_date',
    'utc_time',
    'utc_timestamp',
    'values',
    'varbinary',
    'varchar',
    'varcharacter',
    'varying',
    'virtual',
    'when',
    'where',
    'while',
    'window',
    'with',
    'write',
    'xor',
    'year_month',
    'zerofill',
  ];

  /**
   * Constructs a Connection object.
   */
  public function __construct(\PDO $connection, array $connection_options = []) {
    parent::__construct($connection, $connection_options);

    // This driver defaults to transaction support, except if explicitly passed FALSE.
    $this->transactionSupport = !isset($connection_options['transactions']) || ($connection_options['transactions'] !== FALSE);

    // MySQL never supports transactional DDL.
    $this->transactionalDDLSupport = FALSE;

    $this->connectionOptions = $connection_options;
  }

  /**
   * {@inheritdoc}
   */
  public function query($query, array $args = [], $options = []) {
    try {
      return parent::query($query, $args, $options);
    }
    catch (DatabaseException $e) {
      if ($e->getPrevious()->errorInfo[1] == 1153) {
        // If a max_allowed_packet error occurs the message length is truncated.
        // This should prevent the error from recurring if the exception is
        // logged to the database using dblog or the like.
        $message = Unicode::truncateBytes($e->getMessage(), self::MIN_MAX_ALLOWED_PACKET);
        $e = new DatabaseExceptionWrapper($message, $e->getCode(), $e->getPrevious());
      }
      throw $e;
    }
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
    ];

    try {
      $pdo = new \PDO($dsn, $connection_options['username'], $connection_options['password'], $connection_options['pdo']);
    }
    catch (\PDOException $e) {
      if ($e->getCode() == static::DATABASE_NOT_FOUND) {
        throw new DatabaseNotFoundException($e->getMessage(), $e->getCode(), $e);
      }
      if ($e->getCode() == static::ACCESS_DENIED) {
        throw new DatabaseAccessDeniedException($e->getMessage(), $e->getCode(), $e);
      }
      throw $e;
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

    $sql_mode = 'ANSI,STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,ONLY_FULL_GROUP_BY';
    // NO_AUTO_CREATE_USER is removed in MySQL 8.0.11
    // https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-11.html#mysqld-8-0-11-deprecation-removal
    $version_server = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
    if (version_compare($version_server, '8.0.11', '<')) {
      $sql_mode .= ',NO_AUTO_CREATE_USER';
    }
    $connection_options['init_commands'] += [
      'sql_mode' => "SET sql_mode = '$sql_mode'",
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
  public function escapeField($field) {
    $field = parent::escapeField($field);
    return $this->quoteIdentifier($field);
  }

  /**
   * {@inheritdoc}
   */
  public function escapeAlias($field) {
    // Quote fields so that MySQL reserved words like 'function' can be used
    // as aliases.
    $field = parent::escapeAlias($field);
    return $this->quoteIdentifier($field);
  }

  /**
   * Quotes an identifier if it matches a MySQL reserved keyword.
   *
   * @param string $identifier
   *   The field to check.
   *
   * @return string
   *   The identifier, quoted if it matches a MySQL reserved keyword.
   */
  private function quoteIdentifier($identifier) {
    // Quote identifiers so that MySQL reserved words like 'function' can be
    // used as column names. Sometimes the 'table.column_name' format is passed
    // in. For example,
    // \Drupal\Core\Entity\Sql\SqlContentEntityStorage::buildQuery() adds a
    // condition on "base.uid" while loading user entities.
    if (strpos($identifier, '.') !== FALSE) {
      list($table, $identifier) = explode('.', $identifier, 2);
    }
    if (in_array(strtolower($identifier), $this->reservedKeyWords, TRUE)) {
      // Quote the string for MySQL reserved keywords.
      $identifier = '"' . $identifier . '"';
    }
    return isset($table) ? $table . '.' . $identifier : $identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    // Cleanup the connection, much like __destruct() does it as well.
    if ($this->needsCleanup) {
      $this->nextIdDelete();
    }
    $this->needsCleanup = FALSE;

    return parent::serialize();
  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {
    if ($this->needsCleanup) {
      $this->nextIdDelete();
    }
  }

  public function queryRange($query, $from, $count, array $args = [], array $options = []) {
    return $this->query($query . ' LIMIT ' . (int) $from . ', ' . (int) $count, $args, $options);
  }

  public function queryTemporary($query, array $args = [], array $options = []) {
    $tablename = $this->generateTemporaryTableName();
    $this->query('CREATE TEMPORARY TABLE {' . $tablename . '} Engine=MEMORY ' . $query, $args, $options);
    return $tablename;
  }

  public function driver() {
    return 'mysql';
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
    $new_id = $this->query('INSERT INTO {sequences} () VALUES ()', [], ['return' => Database::RETURN_INSERT_ID]);
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
      $new_id = $this->query('INSERT INTO {sequences} () VALUES ()', [], ['return' => Database::RETURN_INSERT_ID]);
    }
    $this->needsCleanup = TRUE;
    return $new_id;
  }

  public function nextIdDelete() {
    // While we want to clean up the table to keep it up from occupying too
    // much storage and memory, we must keep the highest value in the table
    // because InnoDB uses an in-memory auto-increment counter as long as the
    // server runs. When the server is stopped and restarted, InnoDB
    // reinitializes the counter for each table for the first INSERT to the
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

}


/**
 * @} End of "addtogroup database".
 */
