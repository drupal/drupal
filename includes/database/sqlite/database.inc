<?php
// $Id: database.inc,v 1.33 2010/07/04 02:26:10 webchick Exp $

/**
 * @file
 * Database interface code for SQLite embedded database engine.
 */

/**
 * @ingroup database
 * @{
 */

include_once DRUPAL_ROOT . '/includes/database/prefetch.inc';

/**
 * Specific SQLite implementation of DatabaseConnection.
 */
class DatabaseConnection_sqlite extends DatabaseConnection {

  /**
   * Whether this database connection supports savepoints.
   *
   * Version of sqlite lower then 3.6.8 can't use savepoints.
   * See http://www.sqlite.org/releaselog/3_6_8.html
   *
   * @var bool
   */
  protected $savepointSupport = FALSE;

  /**
   * Whether or not the active transaction (if any) will be rolled back.
   *
   * @var boolean
   */
  protected $willRollback;

  public function __construct(array $connection_options = array()) {
    // We don't need a specific PDOStatement class here, we simulate it below.
    $this->statementClass = NULL;

    // This driver defaults to transaction support, except if explicitly passed FALSE.
    $this->transactionSupport = !isset($connection_options['transactions']) || $connection_options['transactions'] !== FALSE;

    $this->connectionOptions = $connection_options;

    parent::__construct('sqlite:' . $connection_options['database'], '', '', array(
      // Force column names to lower case.
      PDO::ATTR_CASE => PDO::CASE_LOWER,
    ));

    $this->exec('PRAGMA encoding="UTF-8"');

    // Detect support for SAVEPOINT.
    $version = $this->query('SELECT sqlite_version()')->fetchField();
    $this->savepointSupport = (version_compare($version, '3.6.8') >= 0);

    // Create functions needed by SQLite.
    $this->sqliteCreateFunction('if', array($this, 'sqlFunctionIf'));
    $this->sqliteCreateFunction('greatest', array($this, 'sqlFunctionGreatest'));
    $this->sqliteCreateFunction('pow', 'pow', 2);
    $this->sqliteCreateFunction('length', 'strlen', 1);
    $this->sqliteCreateFunction('md5', 'md5', 1);
    $this->sqliteCreateFunction('concat', array($this, 'sqlFunctionConcat'));
    $this->sqliteCreateFunction('substring', array($this, 'sqlFunctionSubstring'), 3);
    $this->sqliteCreateFunction('substring_index', array($this, 'sqlFunctionSubstringIndex'), 3);
    $this->sqliteCreateFunction('rand', array($this, 'sqlFunctionRand'));
  }

  /**
   * SQLite compatibility implementation for the IF() SQL function.
   */
  public function sqlFunctionIf($condition, $expr1, $expr2 = NULL) {
    return $condition ? $expr1 : $expr2;
  }

  /**
   * SQLite compatibility implementation for the GREATEST() SQL function.
   */
  public function sqlFunctionGreatest() {
    $args = func_get_args();
    foreach ($args as $k => $v) {
      if (is_null($v)) {
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
  public function sqlFunctionConcat() {
    $args = func_get_args();
    return implode('', $args);
  }

  /**
   * SQLite compatibility implementation for the SUBSTRING() SQL function.
   */
  public function sqlFunctionSubstring($string, $from, $length) {
    return substr($string, $from - 1, $length);
  }

  /**
   * SQLite compatibility implementation for the SUBSTRING_INDEX() SQL function.
   */
  public function sqlFunctionSubstringIndex($string, $delimiter, $count) {
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
  public function sqlFunctionRand($seed = NULL) {
    if (isset($seed)) {
      mt_srand($seed);
    }
    return mt_rand() / mt_getrandmax();
  }

  /**
   * SQLite-specific implementation of DatabaseConnection::prepare().
   *
   * We don't use prepared statements at all at this stage. We just create
   * a DatabaseStatement_sqlite object, that will create a PDOStatement
   * using the semi-private PDOPrepare() method below.
   */
  public function prepare($query, $options = array()) {
    return new DatabaseStatement_sqlite($this, $query, $options);
  }

  /**
   * NEVER CALL THIS FUNCTION: YOU MIGHT DEADLOCK YOUR PHP PROCESS.
   *
   * This is a wrapper around the parent PDO::prepare method. However, as
   * the PDO SQLite driver only closes SELECT statements when the PDOStatement
   * destructor is called and SQLite does not allow data change (INSERT,
   * UPDATE etc) on a table which has open SELECT statements, you should never
   * call this function and keep a PDOStatement object alive as that can lead
   * to a deadlock. This really, really should be private, but as
   * DatabaseStatement_sqlite needs to call it, we have no other choice but to
   * expose this function to the world.
   */
  public function PDOPrepare($query, array $options = array()) {
    return parent::prepare($query, $options);
  }

  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    return $this->query($query . ' LIMIT ' . (int) $from . ', ' . (int) $count, $args, $options);
  }

  public function queryTemporary($query, array $args = array(), array $options = array()) {
    $tablename = $this->generateTemporaryTableName();
    $this->query(preg_replace('/^SELECT/i', 'CREATE TEMPORARY TABLE {' . $tablename . '} AS SELECT', $query), $args, $options);
    return $tablename;
  }

  public function driver() {
    return 'sqlite';
  }

  public function databaseType() {
    return 'sqlite';
  }

  public function mapConditionOperator($operator) {
    // We don't want to override any of the defaults.
    static $specials = array(
      'LIKE' => array('postfix' => " ESCAPE '\\'"),
      'NOT LIKE' => array('postfix' => " ESCAPE '\\'"),
    );
    return isset($specials[$operator]) ? $specials[$operator] : NULL;
  }

  public function prepareQuery($query) {
    return $this->prepare($this->prefixTables($query));
  }

  public function nextId($existing_id = 0) {
    $transaction = $this->startTransaction();
    // We can safely use literal queries here instead of the slower query
    // builder because if a given database breaks here then it can simply
    // override nextId. However, this is unlikely as we deal with short strings
    // and integers and no known databases require special handling for those
    // simple cases. If another transaction wants to write the same row, it will
    // wait until this transaction commits.
    $stmt = $this->query('UPDATE {sequences} SET value = GREATEST(value, :existing_id) + 1', array(
      ':existing_id' => $existing_id,
    ));
    if (!$stmt->rowCount()) {
      $this->query('INSERT INTO {sequences} (value) VALUES (:existing_id + 1)', array(
        ':existing_id' => $existing_id,
      ));
    }
    // The transaction gets committed when the transaction object gets destroyed
    // because it gets out of scope.
    return (int) $this->query('SELECT value FROM {sequences}')->fetchField();
  }

  public function rollback($savepoint_name = 'drupal_transaction') {
    if ($this->savepointSupport) {
      return parent::rollBack($savepoint_name);
    }

    if (!$this->inTransaction()) {
      throw new DatabaseTransactionNoActiveException();
    }
    // A previous rollback to an earlier savepoint may mean that the savepoint
    // in question has already been rolled back.
    if (!in_array($savepoint_name, $this->transactionLayers)) {
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
      PDO::rollBack();
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
      throw new DatabaseTransactionNameNonUniqueException($name . " is already in use.");
    }
    if (!$this->inTransaction()) {
      PDO::beginTransaction();
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
      throw new DatabaseTransactionNoActiveException();
    }

    // Commit everything since SAVEPOINT $name.
    while($savepoint = array_pop($this->transactionLayers)) {
      if ($savepoint != $name) continue;

      // If there are no more layers left then we should commit or rollback.
      if (empty($this->transactionLayers)) {
        // If there was any rollback() we should roll back whole transaction.
        if ($this->willRollback) {
          $this->willRollback = FALSE;
          PDO::rollBack();
        }
        elseif (!PDO::commit()) {
          throw new DatabaseTransactionCommitFailedException();
        }
      }
      else {
        break;
      }
    }
  }

}

/**
 * Specific SQLite implementation of DatabaseConnection.
 *
 * See DatabaseConnection_sqlite::PDOPrepare() for reasons why we must prefetch
 * the data instead of using PDOStatement.
 *
 * @see DatabaseConnection_sqlite::PDOPrepare()
 */
class DatabaseStatement_sqlite extends DatabaseStatementPrefetch implements Iterator, DatabaseStatementInterface {

  /**
   * SQLite specific implementation of getStatement().
   *
   * The PDO SQLite layer doesn't replace numeric placeholders in queries
   * correctly, and this makes numeric expressions (such as COUNT(*) >= :count)
   * fail. We replace numeric placeholders in the query ourselves to work
   * around this bug.
   *
   * See http://bugs.php.net/bug.php?id=45259 for more details.
   */
  protected function getStatement($query, &$args = array()) {
    if (count($args)) {
      // Check if $args is a simple numeric array.
      if (range(0, count($args) - 1) === array_keys($args)) {
        // In that case, we have unnamed placeholders.
        $count = 0;
        $new_args = array();
        foreach ($args as $value) {
          if (is_float($value) || is_int($value)) {
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
      }
      else {
        // Else, this is using named placeholders.
        foreach ($args as $placeholder => $value) {
          if (is_float($value) || is_int($value)) {
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
            $query = preg_replace('/' . preg_quote($placeholder) . '\b/', $value, $query);
          }
        }
      }
    }

    return $this->dbh->PDOPrepare($query);
  }

  public function execute($args = array(), $options = array()) {
    try {
      $return = parent::execute($args, $options);
    }
    catch (PDOException $e) {
      if (!empty($e->errorInfo[1]) && $e->errorInfo[1] === 17) {
        // The schema has changed. SQLite specifies that we must resend the query.
        $return = parent::execute($args, $options);
      }
      else {
        // Rethrow the exception.
        throw $e;
      }
    }

    // In some weird cases, SQLite will prefix some column names by the name
    // of the table. We post-process the data, by renaming the column names
    // using the same convention as MySQL and PostgreSQL.
    $rename_columns = array();
    foreach ($this->columnNames as $k => $column) {
      // In some SQLite versions, SELECT DISTINCT(field) will return "(field)"
      // instead of "field".
      if (preg_match("/^\((.*)\)$/", $column, $matches)) {
        $rename_columns[$column] = $matches[1];
        $this->columnNames[$k] = $matches[1];
        $column = $matches[1];
      }

      // Remove "table." prefixes.
      if (preg_match("/^.*\.(.*)$/", $column, $matches)) {
        $rename_columns[$column] = $matches[1];
        $this->columnNames[$k] = $matches[1];
      }
    }
    if ($rename_columns) {
      // DatabaseStatementPrefetch already extracted the first row,
      // put it back into the result set.
      if (isset($this->currentRow)) {
        $this->data[0] = &$this->currentRow;
      }

      // Then rename all the columns across the result set.
      foreach ($this->data as $k => $row) {
        foreach ($rename_columns as $old_column => $new_column) {
          $this->data[$k][$new_column] = $this->data[$k][$old_column];
          unset($this->data[$k][$old_column]);
        }
      }

      // Finally, extract the first row again.
      $this->currentRow = $this->data[0];
      unset($this->data[0]);
    }

    return $return;
  }
}

/**
 * @} End of "ingroup database".
 */
