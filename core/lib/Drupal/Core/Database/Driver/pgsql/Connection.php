<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\pgsql\Connection
 */

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Database\IntegrityConstraintViolationException;

/**
 * @addtogroup database
 * @{
 */

class Connection extends DatabaseConnection {

  /**
   * The name by which to obtain a lock for retrieve the next insert id.
   */
  const POSTGRESQL_NEXTID_LOCK = 1000;

  /**
   * Error code for "Unknown database" error.
   */
  const DATABASE_NOT_FOUND = 7;

  /**
   * Constructs a connection object.
   */
  public function __construct(\PDO $connection, array $connection_options) {
    parent::__construct($connection, $connection_options);

    // This driver defaults to transaction support, except if explicitly passed FALSE.
    $this->transactionSupport = !isset($connection_options['transactions']) || ($connection_options['transactions'] !== FALSE);

    // Transactional DDL is always available in PostgreSQL,
    // but we'll only enable it if standard transactions are.
    $this->transactionalDDLSupport = $this->transactionSupport;

    $this->connectionOptions = $connection_options;

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
  public static function open(array &$connection_options = array()) {
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
    //elseif (phpversion('pdo_pgsql') < 'version_this_was_fixed_in') {
    else {
      $connection_options['password'] = str_replace('\\', '\\\\', $connection_options['password']);
    }

    $connection_options['database'] = (!empty($connection_options['database']) ? $connection_options['database'] : 'template1');
    $dsn = 'pgsql:host=' . $connection_options['host'] . ' dbname=' . $connection_options['database'] . ' port=' . $connection_options['port'];

    // Allow PDO options to be overridden.
    $connection_options += array(
      'pdo' => array(),
    );
    $connection_options['pdo'] += array(
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
    );
    $pdo = new \PDO($dsn, $connection_options['username'], $connection_options['password'], $connection_options['pdo']);

    return $pdo;
  }


  public function query($query, array $args = array(), $options = array()) {

    $options += $this->defaultOptions();

    // The PDO PostgreSQL driver has a bug which
    // doesn't type cast booleans correctly when
    // parameters are bound using associative
    // arrays.
    // See http://bugs.php.net/bug.php?id=48383
    foreach ($args as &$value) {
      if (is_bool($value)) {
        $value = (int) $value;
      }
    }

    try {
      if ($query instanceof StatementInterface) {
        $stmt = $query;
        $stmt->execute(NULL, $options);
      }
      else {
        $this->expandArguments($query, $args);
        $stmt = $this->prepareQuery($query);
        $stmt->execute($args, $options);
      }

      switch ($options['return']) {
        case Database::RETURN_STATEMENT:
          return $stmt;
        case Database::RETURN_AFFECTED:
          $stmt->allowRowCount = TRUE;
          return $stmt->rowCount();
        case Database::RETURN_INSERT_ID:
          return $this->connection->lastInsertId($options['sequence_name']);
        case Database::RETURN_NULL:
          return;
        default:
          throw new \PDOException('Invalid return directive: ' . $options['return']);
      }
    }
    catch (\PDOException $e) {
      if ($options['throw_exception']) {
        // Match all SQLSTATE 23xxx errors.
        if (substr($e->getCode(), -6, -3) == '23') {
          $e = new IntegrityConstraintViolationException($e->getMessage(), $e->getCode(), $e);
        }
        // Add additional debug information.
        if ($query instanceof StatementInterface) {
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

  public function prepareQuery($query) {
    // mapConditionOperator converts LIKE operations to ILIKE for consistency
    // with MySQL. However, Postgres does not support ILIKE on bytea (blobs)
    // fields.
    // To make the ILIKE operator work, we type-cast bytea fields into text.
    // @todo This workaround only affects bytea fields, but the involved field
    //   types involved in the query are unknown, so there is no way to
    //   conditionally execute this for affected queries only.
    return parent::prepareQuery(preg_replace('/ ([^ ]+) +(I*LIKE|NOT +I*LIKE) /i', ' ${1}::text ${2} ', $query));
  }

  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    return $this->query($query . ' LIMIT ' . (int) $count . ' OFFSET ' . (int) $from, $args, $options);
  }

  public function queryTemporary($query, array $args = array(), array $options = array()) {
    $tablename = $this->generateTemporaryTableName();
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

    // If the PECL intl extension is installed, use it to determine the proper
    // locale.  Otherwise, fall back to en_US.
    if (class_exists('Locale')) {
      $locale = \Locale::getDefault();
    }
    else {
      $locale = 'en_US';
    }

    try {
      // Create the database and set it as active.
      $this->connection->exec("CREATE DATABASE $database WITH TEMPLATE template0 ENCODING='utf8' LC_CTYPE='$locale.utf8' LC_COLLATE='$locale.utf8'");
    }
    catch (\Exception $e) {
      throw new DatabaseNotFoundException($e->getMessage());
    }
  }

  public function mapConditionOperator($operator) {
    static $specials = array(
      // In PostgreSQL, 'LIKE' is case-sensitive. For case-insensitive LIKE
      // statements, we need to use ILIKE instead.
      'LIKE' => array('operator' => 'ILIKE'),
      'NOT LIKE' => array('operator' => 'NOT ILIKE'),
      'REGEXP' => array('operator' => '~*'),
    );
    return isset($specials[$operator]) ? $specials[$operator] : NULL;
  }

  /**
   * Retrieve a the next id in a sequence.
   *
   * PostgreSQL has built in sequences. We'll use these instead of inserting
   * and updating a sequences table.
   */
  public function nextId($existing = 0) {

    // Retrieve the name of the sequence. This information cannot be cached
    // because the prefix may change, for example, like it does in simpletests.
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
}

/**
 * @} End of "addtogroup database".
 */
