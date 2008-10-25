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

  protected $transactionSupport;

  public function __construct(Array $connection_options = array()) {

    $connection_options += array(
      'transactions' => FALSE,
      'port' => 3306,
    );
    $this->transactionSupport = $connection_options['transactions'];

    $dsn = 'mysql:host=' . $connection_options['host'] . ';port=' . $connection_options['port'] . ';dbname=' . $connection_options['database'];
    parent::__construct($dsn, $connection_options['username'], $connection_options['password'], array(
      // So we don't have to mess around with cursors and unbuffered queries by default.
      PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
      // Because MySQL's prepared statements skip the query cache, because it's dumb.
      PDO::ATTR_EMULATE_PREPARES => TRUE,
    ));

    // Force MySQL to use the UTF-8 character set by default.
    $this->exec('SET NAMES "utf8"');

    // Enable MySQL's "strict mode", which removes most of MySQL's
    // "just be lazy" behaviors that end up causing more trouble than they're worth.
    $this->exec('SET sql_mode=STRICT_ALL_TABLES');
  }

  public function queryRange($query, Array $args, $from, $count, Array $options) {
    // Backward compatibility hack, temporary.
    $query = str_replace(array('%d' , '%f' , '%b' , "'%s'"), '?', $query);

    return $this->query($query . ' LIMIT ' . $from . ', ' . $count, $args, $options);
  }

  public function queryTemporary($query, Array $args, $tablename, $options = array()) {
    $query = preg_replace('/^SELECT/i', 'CREATE TEMPORARY TABLE ' . $tablename . ' Engine=HEAP SELECT', $this->prefixTables($query));

    return $this->query($query, $args, $options);
  }

  public function driver() {
    return 'mysql';
  }

  public function databaseType() {
    return 'mysql';
  }

  public function supportsTransactions() {
    return $this->transactionSupport;
  }

  public function escapeTable($table) {
    return preg_replace('/[^A-Za-z0-9_]+/', '', $table);
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
