<?php

namespace Drupal\sqlite\Driver\Database\sqlite;

use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Database\StatementInterface;

/**
 * SQLite implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends DatabaseConnection {

  /**
   * Error code for "Unable to open database file" error.
   */
  const DATABASE_NOT_FOUND = 14;

  /**
   * {@inheritdoc}
   */
  protected $statementClass = NULL;

  /**
   * {@inheritdoc}
   */
  protected $statementWrapperClass = NULL;

  /**
   * Whether or not the active transaction (if any) will be rolled back.
   *
   * @var bool
   */
  protected $willRollback;

  /**
   * A map of condition operators to SQLite operators.
   *
   * We don't want to override any of the defaults.
   */
  protected static $sqliteConditionOperatorMap = [
    'LIKE' => ['postfix' => " ESCAPE '\\'"],
    'NOT LIKE' => ['postfix' => " ESCAPE '\\'"],
    'LIKE BINARY' => ['postfix' => " ESCAPE '\\'", 'operator' => 'GLOB'],
    'NOT LIKE BINARY' => ['postfix' => " ESCAPE '\\'", 'operator' => 'NOT GLOB'],
  ];

  /**
   * All databases attached to the current database.
   *
   * This is used to allow prefixes to be safely handled without locking the
   * table.
   *
   * @var array
   */
  protected $attachedDatabases = [];

  /**
   * Whether or not a table has been dropped this request.
   *
   * The destructor will only try to get rid of unnecessary databases if there
   * is potential of them being empty.
   *
   * This variable is set to public because Schema needs to
   * access it. However, it should not be manually set.
   *
   * @var bool
   */
  public $tableDropped = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $transactionalDDLSupport = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $identifierQuotes = ['"', '"'];

  /**
   * Constructs a \Drupal\sqlite\Driver\Database\sqlite\Connection object.
   */
  public function __construct(\PDO $connection, array $connection_options) {
    parent::__construct($connection, $connection_options);

    // Attach one database for each registered prefix.
    $prefixes = $this->prefixes;
    foreach ($prefixes as &$prefix) {
      // Empty prefix means query the main database -- no need to attach
      // anything.
      if ($prefix !== '') {
        $this->attachDatabase($prefix);
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
  public static function open(array &$connection_options = []) {
    // Allow PDO options to be overridden.
    $connection_options += [
      'pdo' => [],
    ];
    $connection_options['pdo'] += [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      // Convert numeric values to strings when fetching.
      \PDO::ATTR_STRINGIFY_FETCHES => TRUE,
    ];

    try {
      $pdo = new \PDO('sqlite:' . $connection_options['database'], '', '', $connection_options['pdo']);
    }
    catch (\PDOException $e) {
      if ($e->getCode() == static::DATABASE_NOT_FOUND) {
        throw new DatabaseNotFoundException($e->getMessage(), $e->getCode(), $e);
      }
      // SQLite doesn't have a distinct error code for access denied, so don't
      // deal with that case.
      throw $e;
    }

    // Create functions needed by SQLite.
    $pdo->sqliteCreateFunction('if', [__CLASS__, 'sqlFunctionIf']);
    $pdo->sqliteCreateFunction('greatest', [__CLASS__, 'sqlFunctionGreatest']);
    $pdo->sqliteCreateFunction('least', [__CLASS__, 'sqlFunctionLeast']);
    $pdo->sqliteCreateFunction('pow', 'pow', 2);
    $pdo->sqliteCreateFunction('exp', 'exp', 1);
    $pdo->sqliteCreateFunction('length', 'strlen', 1);
    $pdo->sqliteCreateFunction('md5', 'md5', 1);
    $pdo->sqliteCreateFunction('concat', [__CLASS__, 'sqlFunctionConcat']);
    $pdo->sqliteCreateFunction('concat_ws', [__CLASS__, 'sqlFunctionConcatWs']);
    $pdo->sqliteCreateFunction('substring', [__CLASS__, 'sqlFunctionSubstring'], 3);
    $pdo->sqliteCreateFunction('substring_index', [__CLASS__, 'sqlFunctionSubstringIndex'], 3);
    $pdo->sqliteCreateFunction('rand', [__CLASS__, 'sqlFunctionRand']);
    $pdo->sqliteCreateFunction('regexp', [__CLASS__, 'sqlFunctionRegexp']);

    // SQLite does not support the LIKE BINARY operator, so we overload the
    // non-standard GLOB operator for case-sensitive matching. Another option
    // would have been to override another non-standard operator, MATCH, but
    // that does not support the NOT keyword prefix.
    $pdo->sqliteCreateFunction('glob', [__CLASS__, 'sqlFunctionLikeBinary']);

    // Create a user-space case-insensitive collation with UTF-8 support.
    $pdo->sqliteCreateCollation('NOCASE_UTF8', ['Drupal\Component\Utility\Unicode', 'strcasecmp']);

    // Set SQLite init_commands if not already defined. Enable the Write-Ahead
    // Logging (WAL) for SQLite. See https://www.drupal.org/node/2348137 and
    // https://www.sqlite.org/wal.html.
    $connection_options += [
      'init_commands' => [],
    ];
    $connection_options['init_commands'] += [
      'wal' => "PRAGMA journal_mode=WAL",
    ];

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
          $count = $this->query('SELECT COUNT(*) FROM ' . $prefix . '.sqlite_master WHERE type = :type AND name NOT LIKE :pattern', [':type' => 'table', ':pattern' => 'sqlite_%'])->fetchField();

          // We can prune the database file if it doesn't have any tables.
          if ($count == 0 && $this->connectionOptions['database'] != ':memory:' && file_exists($this->connectionOptions['database'] . '-' . $prefix)) {
            // Detach the database.
            $this->query('DETACH DATABASE :schema', [':schema' => $prefix]);
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
    parent::__destruct();
  }

  /**
   * {@inheritdoc}
   */
  public function attachDatabase(string $database): void {
    // Only attach the database once.
    if (!isset($this->attachedDatabases[$database])) {
      // In memory database use ':memory:' as database name. According to
      // http://www.sqlite.org/inmemorydb.html it will open a unique database so
      // attaching it twice is not a problem.
      $database_file = $this->connectionOptions['database'] !== ':memory:' ? $this->connectionOptions['database'] . '-' . $database : $this->connectionOptions['database'];
      $this->query('ATTACH DATABASE :database_file AS :database', [':database_file' => $database_file, ':database' => $database]);
      $this->attachedDatabases[$database] = $database;
    }
  }

  /**
   * Gets all the attached databases.
   *
   * @return array
   *   An array of attached database names.
   *
   * @see \Drupal\sqlite\Driver\Database\sqlite\Connection::__construct()
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
   * SQLite compatibility implementation for the LEAST() SQL function.
   */
  public static function sqlFunctionLeast() {
    // Remove all NULL, FALSE and empty strings values but leaves 0 (zero) values.
    $values = array_filter(func_get_args(), 'strlen');

    return count($values) < 1 ? NULL : min($values);
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
    $pattern = str_replace(['%', '_'], ['.*?', '.'], preg_quote($pattern, '/'));
    return preg_match('/^' . $pattern . '$/', $subject);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare($statement, array $driver_options = []) {
    @trigger_error('Connection::prepare() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Database drivers should instantiate \PDOStatement objects by calling \PDO::prepare in their Connection::prepareStatement method instead. \PDO::prepare should not be called outside of driver code. See https://www.drupal.org/node/3137786', E_USER_DEPRECATED);
    return new Statement($this->connection, $this, $statement, $driver_options);
  }

  /**
   * {@inheritdoc}
   */
  protected function handleQueryException(\PDOException $e, $query, array $args = [], $options = []) {
    // The database schema might be changed by another process in between the
    // time that the statement was prepared and the time the statement was run
    // (e.g. usually happens when running tests). In this case, we need to
    // re-run the query.
    // @see http://www.sqlite.org/faq.html#q15
    // @see http://www.sqlite.org/rescode.html#schema
    if (!empty($e->errorInfo[1]) && $e->errorInfo[1] === 17) {
      @trigger_error('Connection::handleQueryException() is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Get a handler through $this->exceptionHandler() instead, and use one of its methods. See https://www.drupal.org/node/3187222', E_USER_DEPRECATED);
      return $this->query($query, $args, $options);
    }

    parent::handleQueryException($e, $query, $args, $options);
  }

  public function queryRange($query, $from, $count, array $args = [], array $options = []) {
    return $this->query($query . ' LIMIT ' . (int) $from . ', ' . (int) $count, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = [], array $options = []) {
    @trigger_error('Connection::queryTemporary() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/3211781', E_USER_DEPRECATED);
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
    if (!$db_directory->isDir() && !\Drupal::service('file_system')->mkdir($db_directory->getPathName(), 0755, TRUE)) {
      throw new DatabaseNotFoundException('Unable to create database directory ' . $db_directory->getPathName());
    }
  }

  public function mapConditionOperator($operator) {
    return static::$sqliteConditionOperatorMap[$operator] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareStatement(string $query, array $options, bool $allow_row_count = FALSE): StatementInterface {
    if (isset($options['return'])) {
      @trigger_error('Passing "return" option to ' . __METHOD__ . '() is deprecated in drupal:9.4.0 and is removed in drupal:11.0.0. For data manipulation operations, use dynamic queries instead. See https://www.drupal.org/node/3185520', E_USER_DEPRECATED);
    }

    try {
      $query = $this->preprocessStatement($query, $options);
      $statement = new Statement($this->connection, $this, $query, $options['pdo'] ?? [], $allow_row_count);
    }
    catch (\Exception $e) {
      $this->exceptionHandler()->handleStatementException($e, $query, $options);
    }
    return $statement;
  }

  public function nextId($existing_id = 0) {
    $this->startTransaction();
    // We can safely use literal queries here instead of the slower query
    // builder because if a given database breaks here then it can simply
    // override nextId. However, this is unlikely as we deal with short strings
    // and integers and no known databases require special handling for those
    // simple cases. If another transaction wants to write the same row, it will
    // wait until this transaction commits.
    $stmt = $this->prepareStatement('UPDATE {sequences} SET [value] = GREATEST([value], :existing_id) + 1', [], TRUE);
    $args = [':existing_id' => $existing_id];
    try {
      $stmt->execute($args);
    }
    catch (\Exception $e) {
      $this->exceptionHandler()->handleExecutionException($e, $stmt, $args, []);
    }
    if ($stmt->rowCount() === 0) {
      $this->query('INSERT INTO {sequences} ([value]) VALUES (:existing_id + 1)', $args);
    }
    // The transaction gets committed when the transaction object gets destroyed
    // because it gets out of scope.
    return $this->query('SELECT [value] FROM {sequences}')->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getFullQualifiedTableName($table) {
    $prefix = $this->tablePrefix($table);

    // Don't include the SQLite database file name as part of the table name.
    return $prefix . $table;
  }

  /**
   * {@inheritdoc}
   */
  public static function createConnectionOptionsFromUrl($url, $root) {
    $database = parent::createConnectionOptionsFromUrl($url, $root);

    // A SQLite database path with two leading slashes indicates a system path.
    // Otherwise the path is relative to the Drupal root.
    $url_components = parse_url($url);
    if ($url_components['path'][0] === '/') {
      $url_components['path'] = substr($url_components['path'], 1);
    }
    if ($url_components['path'][0] === '/' || $url_components['path'] === ':memory:') {
      $database['database'] = $url_components['path'];
    }
    else {
      $database['database'] = $root . '/' . $url_components['path'];
    }

    // User credentials and system port are irrelevant for SQLite.
    unset(
      $database['username'],
      $database['password'],
      $database['port']
    );

    return $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function createUrlFromConnectionOptions(array $connection_options) {
    if (!isset($connection_options['driver'], $connection_options['database'])) {
      throw new \InvalidArgumentException("As a minimum, the connection options array must contain at least the 'driver' and 'database' keys");
    }

    $db_url = 'sqlite://localhost/' . $connection_options['database'] . '?module=sqlite';

    if (isset($connection_options['prefix']) && $connection_options['prefix'] !== '') {
      $db_url .= '#' . $connection_options['prefix'];
    }

    return $db_url;
  }

}
