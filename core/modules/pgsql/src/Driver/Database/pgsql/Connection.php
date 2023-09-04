<?php

namespace Drupal\pgsql\Driver\Database\pgsql;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Database\DatabaseAccessDeniedException;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Database\StatementWrapperIterator;
use Drupal\Core\Database\SupportsTemporaryTablesInterface;

// cSpell:ignore ilike nextval

/**
 * @addtogroup database
 * @{
 */

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends DatabaseConnection implements SupportsTemporaryTablesInterface {

  /**
   * The name by which to obtain a lock for retrieve the next insert id.
   */
  const POSTGRESQL_NEXTID_LOCK = 1000;

  /**
   * Error code for "Unknown database" error.
   */
  const DATABASE_NOT_FOUND = 7;

  /**
   * Error code for "Connection failure" errors.
   *
   * Technically this is an internal error code that will only be shown in the
   * PDOException message. It will need to get extracted.
   */
  const CONNECTION_FAILURE = '08006';

  /**
   * {@inheritdoc}
   */
  protected $statementWrapperClass = StatementWrapperIterator::class;

  /**
   * A map of condition operators to PostgreSQL operators.
   *
   * In PostgreSQL, 'LIKE' is case-sensitive. ILIKE should be used for
   * case-insensitive statements.
   */
  protected static $postgresqlConditionOperatorMap = [
    'LIKE' => ['operator' => 'ILIKE'],
    'LIKE BINARY' => ['operator' => 'LIKE'],
    'NOT LIKE' => ['operator' => 'NOT ILIKE'],
    'REGEXP' => ['operator' => '~*'],
    'NOT REGEXP' => ['operator' => '!~*'],
  ];

  /**
   * {@inheritdoc}
   */
  protected $transactionalDDLSupport = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $identifierQuotes = ['"', '"'];

  /**
   * Constructs a connection object.
   */
  public function __construct(\PDO $connection, array $connection_options) {
    // Sanitize the schema name here, so we do not have to do it in other
    // functions.
    if (isset($connection_options['schema']) && ($connection_options['schema'] !== 'public')) {
      $connection_options['schema'] = preg_replace('/[^A-Za-z0-9_]+/', '', $connection_options['schema']);
    }

    // We need to set the connectionOptions before the parent, because setPrefix
    // needs this.
    $this->connectionOptions = $connection_options;

    parent::__construct($connection, $connection_options);

    // Force PostgreSQL to use the UTF-8 character set by default.
    $this->connection->exec("SET NAMES 'UTF8'");

    // Execute PostgreSQL init_commands.
    if (isset($connection_options['init_commands'])) {
      $this->connection->exec(implode('; ', $connection_options['init_commands']));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setPrefix($prefix) {
    assert(is_string($prefix), 'The \'$prefix\' argument to ' . __METHOD__ . '() must be a string');
    $this->prefix = $prefix;

    // Add the schema name if it is not set to public, otherwise it will use the
    // default schema name.
    $quoted_schema = '';
    if (isset($this->connectionOptions['schema']) && ($this->connectionOptions['schema'] !== 'public')) {
      $quoted_schema = $this->identifierQuotes[0] . $this->connectionOptions['schema'] . $this->identifierQuotes[1] . '.';
    }

    $this->tablePlaceholderReplacements = [
      $quoted_schema . $this->identifierQuotes[0] . str_replace('.', $this->identifierQuotes[1] . '.' . $this->identifierQuotes[0], $prefix),
      $this->identifierQuotes[1],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = []) {
    // Default to TCP connection on port 5432.
    if (empty($connection_options['port'])) {
      $connection_options['port'] = 5432;
    }

    // PostgreSQL in trust mode doesn't require a password to be supplied.
    if (empty($connection_options['password'])) {
      $connection_options['password'] = NULL;
    }
    // If the password contains a backslash it is treated as an escape character
    // http://bugs.php.net/bug.php?id=53217
    // so backslashes in the password need to be doubled up.
    // The bug was reported against pdo_pgsql 1.0.2, backslashes in passwords
    // will break on this doubling up when the bug is fixed, so check the version
    // elseif (phpversion('pdo_pgsql') < 'version_this_was_fixed_in') {
    else {
      $connection_options['password'] = str_replace('\\', '\\\\', $connection_options['password']);
    }

    $connection_options['database'] = (!empty($connection_options['database']) ? $connection_options['database'] : 'template1');
    $dsn = 'pgsql:host=' . $connection_options['host'] . ' dbname=' . $connection_options['database'] . ' port=' . $connection_options['port'];

    // Allow PDO options to be overridden.
    $connection_options += [
      'pdo' => [],
    ];
    $connection_options['pdo'] += [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      // Prepared statements are most effective for performance when queries
      // are recycled (used several times). However, if they are not re-used,
      // prepared statements become inefficient. Since most of Drupal's
      // prepared queries are not re-used, it should be faster to emulate
      // the preparation than to actually ready statements for re-use. If in
      // doubt, reset to FALSE and measure performance.
      \PDO::ATTR_EMULATE_PREPARES => TRUE,
      // Convert numeric values to strings when fetching.
      \PDO::ATTR_STRINGIFY_FETCHES => TRUE,
    ];

    try {
      $pdo = new \PDO($dsn, $connection_options['username'], $connection_options['password'], $connection_options['pdo']);
    }
    catch (\PDOException $e) {
      if (static::getSQLState($e) == static::CONNECTION_FAILURE) {
        if (str_contains($e->getMessage(), 'password authentication failed for user')) {
          throw new DatabaseAccessDeniedException($e->getMessage(), $e->getCode(), $e);
        }
        elseif (str_contains($e->getMessage(), 'database') && str_contains($e->getMessage(), 'does not exist')) {
          throw new DatabaseNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
      }
      throw $e;
    }

    return $pdo;
  }

  /**
   * {@inheritdoc}
   */
  public function query($query, array $args = [], $options = []) {
    $options += $this->defaultOptions();

    // The PDO PostgreSQL driver has a bug which doesn't type cast booleans
    // correctly when parameters are bound using associative arrays.
    // @see http://bugs.php.net/bug.php?id=48383
    foreach ($args as &$value) {
      if (is_bool($value)) {
        $value = (int) $value;
      }
    }

    // We need to wrap queries with a savepoint if:
    // - Currently in a transaction.
    // - A 'mimic_implicit_commit' does not exist already.
    // - The query is not a savepoint query.
    $wrap_with_savepoint = $this->inTransaction() &&
      !isset($this->transactionLayers['mimic_implicit_commit']) &&
      !(is_string($query) && (
        stripos($query, 'ROLLBACK TO SAVEPOINT ') === 0 ||
        stripos($query, 'RELEASE SAVEPOINT ') === 0 ||
        stripos($query, 'SAVEPOINT ') === 0
      )
    );
    if ($wrap_with_savepoint) {
      // Create a savepoint so we can rollback a failed query. This is so we can
      // mimic MySQL and SQLite transactions which don't fail if a single query
      // fails. This is important for tables that are created on demand. For
      // example, \Drupal\Core\Cache\DatabaseBackend.
      $this->addSavepoint();
      try {
        $return = parent::query($query, $args, $options);
        $this->releaseSavepoint();
      }
      catch (\Exception $e) {
        $this->rollbackSavepoint();
        throw $e;
      }
    }
    else {
      $return = parent::query($query, $args, $options);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareStatement(string $query, array $options, bool $allow_row_count = FALSE): StatementInterface {
    // mapConditionOperator converts some operations (LIKE, REGEXP, etc.) to
    // PostgreSQL equivalents (ILIKE, ~*, etc.). However PostgreSQL doesn't
    // automatically cast the fields to the right type for these operators,
    // so we need to alter the query and add the type-cast.
    $query = preg_replace('/ ([^ ]+) +(I*LIKE|NOT +I*LIKE|~\*|!~\*) /i', ' ${1}::text ${2} ', $query);
    return parent::prepareStatement($query, $options, $allow_row_count);
  }

  public function queryRange($query, $from, $count, array $args = [], array $options = []) {
    return $this->query($query . ' LIMIT ' . (int) $count . ' OFFSET ' . (int) $from, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = [], array $options = []) {
    $tablename = 'db_temporary_' . uniqid();
    $this->query('CREATE TEMPORARY TABLE {' . $tablename . '} AS ' . $query, $args, $options);
    return $tablename;
  }

  public function driver() {
    return 'pgsql';
  }

  public function databaseType() {
    return 'pgsql';
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
    $db_created = FALSE;

    // Try to determine the proper locales for character classification and
    // collation. If we could determine locales other than 'en_US', try creating
    // the database with these first.
    $ctype = setlocale(LC_CTYPE, 0);
    $collate = setlocale(LC_COLLATE, 0);
    if ($ctype && $collate) {
      try {
        $this->connection->exec("CREATE DATABASE $database WITH TEMPLATE template0 ENCODING='UTF8' LC_CTYPE='$ctype.UTF-8' LC_COLLATE='$collate.UTF-8'");
        $db_created = TRUE;
      }
      catch (\Exception $e) {
        // It might be that the server is remote and does not support the
        // locale and collation of the webserver, so we will try again.
      }
    }

    // Otherwise fall back to creating the database using the 'en_US' locales.
    if (!$db_created) {
      try {
        $this->connection->exec("CREATE DATABASE $database WITH TEMPLATE template0 ENCODING='UTF8' LC_CTYPE='en_US.UTF-8' LC_COLLATE='en_US.UTF-8'");
      }
      catch (\Exception $e) {
        // If the database can't be created with the 'en_US' locale either,
        // we're finally throwing an exception.
        throw new DatabaseNotFoundException($e->getMessage());
      }
    }
  }

  public function mapConditionOperator($operator) {
    return static::$postgresqlConditionOperatorMap[$operator] ?? NULL;
  }

  /**
   * Retrieve a the next id in a sequence.
   *
   * PostgreSQL has built in sequences. We'll use these instead of inserting
   * and updating a sequences table.
   */
  public function nextId($existing = 0) {

    // Retrieve the name of the sequence. This information cannot be cached
    // because the prefix may change, for example, like it does in tests.
    $sequence_name = $this->makeSequenceName('sequences', 'value');

    // When PostgreSQL gets a value too small then it will lock the table,
    // retry the INSERT and if it's still too small then alter the sequence.
    $id = $this->query("SELECT nextval('" . $sequence_name . "')")->fetchField();
    if ($id > $existing) {
      return $id;
    }

    // PostgreSQL advisory locks are simply locks to be used by an
    // application such as Drupal. This will prevent other Drupal processes
    // from altering the sequence while we are.
    $this->query("SELECT pg_advisory_lock(" . self::POSTGRESQL_NEXTID_LOCK . ")");

    // While waiting to obtain the lock, the sequence may have been altered
    // so lets try again to obtain an adequate value.
    $id = $this->query("SELECT nextval('" . $sequence_name . "')")->fetchField();
    if ($id > $existing) {
      $this->query("SELECT pg_advisory_unlock(" . self::POSTGRESQL_NEXTID_LOCK . ")");
      return $id;
    }

    // Reset the sequence to a higher value than the existing id.
    $this->query("ALTER SEQUENCE " . $sequence_name . " RESTART WITH " . ($existing + 1));

    // Retrieve the next id. We know this will be as high as we want it.
    $id = $this->query("SELECT nextval('" . $sequence_name . "')")->fetchField();

    $this->query("SELECT pg_advisory_unlock(" . self::POSTGRESQL_NEXTID_LOCK . ")");

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullQualifiedTableName($table) {
    $options = $this->getConnectionOptions();
    $schema = $options['schema'] ?? 'public';

    // The fully qualified table name in PostgreSQL is in the form of
    // <database>.<schema>.<table>.
    return $options['database'] . '.' . $schema . '.' . $this->getPrefix() . $table;
  }

  /**
   * Add a new savepoint with a unique name.
   *
   * The main use for this method is to mimic InnoDB functionality, which
   * provides an inherent savepoint before any query in a transaction.
   *
   * @param $savepoint_name
   *   A string representing the savepoint name. By default,
   *   "mimic_implicit_commit" is used.
   *
   * @see Drupal\Core\Database\Connection::pushTransaction()
   */
  public function addSavepoint($savepoint_name = 'mimic_implicit_commit') {
    if ($this->inTransaction()) {
      $this->pushTransaction($savepoint_name);
    }
  }

  /**
   * Release a savepoint by name.
   *
   * @param $savepoint_name
   *   A string representing the savepoint name. By default,
   *   "mimic_implicit_commit" is used.
   *
   * @see Drupal\Core\Database\Connection::popTransaction()
   */
  public function releaseSavepoint($savepoint_name = 'mimic_implicit_commit') {
    if (isset($this->transactionLayers[$savepoint_name])) {
      $this->popTransaction($savepoint_name);
    }
  }

  /**
   * Rollback a savepoint by name if it exists.
   *
   * @param $savepoint_name
   *   A string representing the savepoint name. By default,
   *   "mimic_implicit_commit" is used.
   */
  public function rollbackSavepoint($savepoint_name = 'mimic_implicit_commit') {
    if (isset($this->transactionLayers[$savepoint_name])) {
      $this->rollBack($savepoint_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasJson(): bool {
    try {
      return (bool) $this->query('SELECT JSON_TYPEOF(\'1\')');
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}

/**
 * @} End of "addtogroup database".
 */
