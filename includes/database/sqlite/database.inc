<?php
// $Id$

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

  public function __construct(array $connection_options = array()) {
    // We don't need a specific PDOStatement class here, we simulate it below.
    $this->statementClass = NULL;

    // This driver defaults to transaction support, except if explicitly passed FALSE.
    $this->transactionSupport = !isset($connection_options['transactions']) || $connection_options['transactions'] !== FALSE;

    parent::__construct('sqlite:' . $connection_options['database'], '', '', array(
      // Force column names to lower case.
      PDO::ATTR_CASE => PDO::CASE_LOWER,
    ));

    $this->exec('PRAGMA encoding="UTF-8"');

    // Create functions needed by SQLite.
    $this->sqliteCreateFunction('if', array($this, 'sqlFunctionIf'));
    $this->sqliteCreateFunction('greatest', array($this, 'sqlFunctionGreatest'));
    $this->sqliteCreateFunction('pow', 'pow', 2);
    $this->sqliteCreateFunction('length', 'strlen', 1);
    $this->sqliteCreateFunction('concat', array($this, 'sqlFunctionConcat'));
    $this->sqliteCreateFunction('substring', array($this, 'sqlFunctionSubstring'), 3);
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

  public function queryRange($query, array $args, $from, $count, array $options = array()) {
    return $this->query($query . ' LIMIT ' . $from . ', ' . $count, $args, $options);
  }

  public function queryTemporary($query, array $args, array $options = array()) {
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
    return NULL;
  }

  protected function prepareQuery($query, $cache = TRUE) {
    // It makes no sense to use the static prepared statement cache here,
    // because all the work in our implementation is done in
    // DatabaseStatement_sqlite::execute() and cannot be cached.
    return $this->prepare($this->prefixTables($query));
  }

  /**
   * @todo Remove this as soon as db_rewrite_sql() has been exterminated.
   */
  public function distinctField($table, $field, $query) {
    $field_to_select = 'DISTINCT(' . $table . '.' . $field . ')';
    // (?<!text) is a negative look-behind (no need to rewrite queries that already use DISTINCT).
    return preg_replace('/(SELECT.*)(?:' . $table . '\.|\s)(?<!DISTINCT\()(?<!DISTINCT\(' . $table . '\.)' . $field . '(.*FROM )/AUsi', '\1 ' . $field_to_select . '\2', $query);
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
          if (is_numeric($value)) {
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
          if (is_numeric($value)) {
            // We will remove this placeholder from the query and PDO throws an
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
