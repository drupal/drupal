<?php
// $Id$

/**
 * @file
 * Database interface code for PostgreSQL database servers.
 */

/**
 * @ingroup database
 * @{
 */

/**
 * The name by which to obtain a lock for retrive the next insert id.
 */
define('POSTGRESQL_NEXTID_LOCK', 1000);

class DatabaseConnection_pgsql extends DatabaseConnection {

  public function __construct(array $connection_options = array()) {
    // This driver defaults to transaction support, except if explicitly passed FALSE.
    $this->transactionSupport = !isset($connection_options['transactions']) || ($connection_options['transactions'] !== FALSE);

    // Transactional DDL is always available in PostgreSQL,
    // but we'll only enable it if standard transactions are.
    $this->transactionalDDLSupport = $this->transactionSupport;

    // Default to TCP connection on port 5432.
    if (empty($connection_options['port'])) {
      $connection_options['port'] = 5432;
    }

    // PostgreSQL in trust mode doesn't require a password to be supplied.
    if (empty($connection_options['password'])) {
      $connection_options['password'] = null;
    }

    $dsn = 'pgsql:host=' . $connection_options['host'] . ' dbname=' . $connection_options['database'] . ' port=' . $connection_options['port'];
    parent::__construct($dsn, $connection_options['username'], $connection_options['password'], array(
      // Convert numeric values to strings when fetching.
      PDO::ATTR_STRINGIFY_FETCHES => TRUE,
      // Force column names to lower case.
      PDO::ATTR_CASE => PDO::CASE_LOWER,
    ));

    // Force PostgreSQL to use the UTF-8 character set by default.
    $this->exec("SET NAMES 'UTF8'");
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
      if ($query instanceof DatabaseStatementInterface) {
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
          return $stmt->rowCount();
        case Database::RETURN_INSERT_ID:
          return $this->lastInsertId($options['sequence_name']);
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

  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    return $this->query($query . ' LIMIT ' . $count . ' OFFSET ' . $from, $args, $options);
  }

  public function queryTemporary($query, array $args = array(), array $options = array()) {
    $tablename = $this->generateTemporaryTableName();
    $this->query(preg_replace('/^SELECT/i', 'CREATE TEMPORARY TABLE {' . $tablename . '} AS SELECT', $query), $args, $options);
    return $tablename;
  }

  public function driver() {
    return 'pgsql';
  }

  public function databaseType() {
    return 'pgsql';
  }

  public function mapConditionOperator($operator) {
    static $specials = array(
      // In PostgreSQL, 'LIKE' is case-sensitive. For case-insensitive LIKE
      // statements, we need to use ILIKE instead. Use backslash for escaping
      // wildcard characters.
      'LIKE' => array('operator' => 'ILIKE', 'postfix' => " ESCAPE '\\\\'"),
      'NOT LIKE' => array('operator' => 'NOT ILIKE', 'postfix' => " ESCAPE '\\\\'"),
    );

    return isset($specials[$operator]) ? $specials[$operator] : NULL;
  }

  /**
   * Retrive a the next id in a sequence.
   *
   * PostgreSQL has built in sequences. We'll use these instead of inserting
   * and updating a sequences table.
   */
  public function nextId($existing = 0) {

    // Retrive the name of the sequence. This information cannot be cached
    // because the prefix may change, for example, like it does in simpletests.
    $sequence_name = $this->makeSequenceName('sequences', 'value');

    // When PostgreSQL gets a value too small then it will lock the table,
    // retry the INSERT and if it's still too small then alter the sequence.
    $id = $this->query("SELECT nextval('" . $sequence_name . "')")->fetchField();
    if ($id > $existing) {
      return $id;
    }

    // PostgreSQL advisory locks are simply locks to be used by an
    // application such as Drupal. This will prevent other Drupal proccesses
    // from altering the sequence while we are.
    $this->query("SELECT pg_advisory_lock(" . POSTGRESQL_NEXTID_LOCK . ")");

    // While waiting to obtain the lock, the sequence may have been altered
    // so lets try again to obtain an adequate value.
    $id = $this->query("SELECT nextval('" . $sequence_name . "')")->fetchField();
    if ($id > $existing) {
      $this->query("SELECT pg_advisory_unlock(" . POSTGRESQL_NEXTID_LOCK . ")");
      return $id;
    }

    // Reset the sequence to a higher value than the existing id.
    $this->query("ALTER SEQUENCE " . $sequence_name . " RESTART WITH " . ($existing + 1));

    // Retrive the next id. We know this will be as high as we want it.
    $id = $this->query("SELECT nextval('" . $sequence_name . "')")->fetchField();

    $this->query("SELECT pg_advisory_unlock(" . POSTGRESQL_NEXTID_LOCK . ")");

    return $id;
  }
}

/**
 * @} End of "ingroup database".
 */
