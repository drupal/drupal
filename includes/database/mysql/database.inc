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

  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    return $this->query($query . ' LIMIT ' . $from . ', ' . $count, $args, $options);
  }

  public function queryTemporary($query, array $args = array(), array $options = array()) {
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

  public function nextId($existing_id = 0) {
    static $shutdown_registered = FALSE;
    $new_id = $this->query('INSERT INTO {sequences} () VALUES ()', array(), array('return' => Database::RETURN_INSERT_ID));
    // This should only happen after an import or similar event.
    if ($existing_id >= $new_id) {
      // If we INSERT a value manually into the sequences table, on the next
      // INSERT, MySQL will generate a larger value. However, there is no way
      // of knowing whether this value already exists in the table. MySQL
      // provides an INSERT IGNORE which would work, but that can mask problems
      // other than duplicate keys. Instead, we use INSERT ... ON DUPLICATE KEY
      // UPDATE in such a way that the UPDATE does not do anything. This way,
      // duplicate keys do not generate errors but everything else does.
      $this->query('INSERT INTO {sequences} (value) VALUES (:value) ON DUPLICATE KEY UPDATE value = value', array(':value' => $existing_id));
      $new_id = $this->query('INSERT INTO {sequences} () VALUES ()', array(), array('return' => Database::RETURN_INSERT_ID));
    }
    if (!$shutdown_registered) {
      register_shutdown_function(array(get_class($this), 'nextIdDelete'));
      $shutdown_registered = TRUE;
    }
    return $new_id;
  }

  public static function nextIdDelete() {
    // While we want to clean up the table to keep it up from occupying too
    // much storage and memory, we must keep the highest value in the table
    // because InnoDB  uses an in-memory auto-increment counter as long as the
    // server runs. When the server is stopped and restarted, InnoDB
    // reinitializes the counter for each table for the first INSERT to the
    // table based solely on values from the table so deleting all values would
    // be a problem in this case. Also, TRUNCATE resets the auto increment
    // counter.
    $max_id = db_query('SELECT MAX(value) FROM {sequences}')->fetchField();
    // We know we are using MySQL here, so need for the slower db_delete().
    db_query('DELETE FROM {sequences} WHERE value < :value', array(':value' => $max_id));
  }
}


/**
 * @} End of "ingroup database".
 */
