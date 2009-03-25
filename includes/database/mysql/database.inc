<?php
// $Id$

/**
 * @file
 * Database interface code for MySQL database servers.
 */

/**
 * @ingroup database
 * @{
 */

class DatabaseConnection_mysql extends DatabaseConnection {

  public function __construct(array $connection_options = array()) {
    // This driver defaults to non transaction support.
    $this->transactionSupport = !empty($connection_options['transactions']);

    // MySQL never supports transactional DDL.
    $this->transactionalDDLSupport = FALSE;

    // Default to TCP connection on port 3306.
    if (empty($connection_options['port'])) {
      $connection_options['port'] = 3306;
    }

    $dsn = 'mysql:host=' . $connection_options['host'] . ';port=' . $connection_options['port'] . ';dbname=' . $connection_options['database'];
    parent::__construct($dsn, $connection_options['username'], $connection_options['password'], array(
      // So we don't have to mess around with cursors and unbuffered queries by default.
      PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
      // Because MySQL's prepared statements skip the query cache, because it's dumb.
      PDO::ATTR_EMULATE_PREPARES => TRUE,
      // Force column names to lower case.
      PDO::ATTR_CASE => PDO::CASE_LOWER,
    ));

    // Force MySQL to use the UTF-8 character set by default.
    $this->exec('SET NAMES "utf8"');

    // Force MySQL's behavior to conform more closely to SQL standards.
    // This allows Drupal to run almost seamlessly on many different
    // kinds of database systems. These settings force MySQL to behave
    // the same as postgresql, or sqlite in regards to syntax interpretation
    // and invalid data handling. See http://drupal.org/node/344575 for further disscussion.
    $this->exec("SET sql_mode='ANSI,TRADITIONAL'");
  }

  public function queryRange($query, array $args, $from, $count, array $options = array()) {
    return $this->query($query . ' LIMIT ' . $from . ', ' . $count, $args, $options);
  }

  public function queryTemporary($query, array $args, array $options = array()) {
    $tablename = $this->generateTemporaryTableName();
    $this->query(preg_replace('/^SELECT/i', 'CREATE TEMPORARY TABLE {' . $tablename . '} Engine=MEMORY SELECT', $query), $args, $options);
    return $tablename;
  }

  public function driver() {
    return 'mysql';
  }

  public function databaseType() {
    return 'mysql';
  }

  public function mapConditionOperator($operator) {
    // We don't want to override any of the defaults.
    return NULL;
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
 * @} End of "ingroup database".
 */
