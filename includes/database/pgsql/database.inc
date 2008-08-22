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

class DatabaseConnection_pgsql extends DatabaseConnection {

  protected $transactionSupport;

  public function __construct(Array $connection_options = array()) {

    $connection_options += array(
      'transactions' => TRUE,
    );
    $this->transactionSupport = $connection_options['transactions'];

    $dsn = 'pgsql:host=' . $connection_options['host'] . ' dbname=' . $connection_options['database'];
    if (!empty($connection_options['port'])) {
      $dsn .= ' port=' . $connection_options['port'];
    }

    parent::__construct($dsn, $connection_options['username'], $connection_options['password'], array(PDO::ATTR_STRINGIFY_FETCHES => TRUE));
  }

  public function query($query, Array $args = array(), $options = array()) {

    $options += $this->defaultOptions();
    
    try {
      if ($query instanceof DatabaseStatement) {
        $stmt = $query;
        $stmt->execute(NULL, $options);
      }
      else {
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
      if (!function_exists('module_implements')) {
        _db_need_install();
      }
      //watchdog('database', var_export($e, TRUE) . $e->getMessage(), NULL, WATCHDOG_ERROR);
      if ($options['throw_exception']) {
        if ($query instanceof DatabaseStatement) {
          $query_string = $stmt->queryString;
        }
        else {
          $query_string = $query;
        }
        throw new PDOException($query_string . " - \n" . print_r($args,1) . $e->getMessage());
      }
      return NULL;
    }
  }
  
  public function queryRange($query, Array $args, $from, $count, Array $options) {
    // Backward compatibility hack, temporary.
    $query = str_replace(array('%d' , '%f' , '%b' , "'%s'"), '?', $query);

    return $this->query($query . ' LIMIT ' . $count . ' OFFSET ' . $from, $args, $options);
  }

  public function queryTemporary($query, Array $args, $tablename) {
    $query = preg_replace('/^SELECT/i', 'CREATE TEMPORARY TABLE ' . $tablename . ' Engine=HEAP SELECT', $this->prefixTables($query));

    return $this->query($query, $args, $options);
  }

  public function driver() {
    return 'pgsql';
  }

  public function databaseType() {
    return 'pgsql';
  }

  public function supportsTransactions() {
    return $this->transactionSupport;
  }

  public function escapeTable($table) {
    return preg_replace('/[^A-Za-z0-9_]+/', '', $table);
  }

  public function mapConditionOperator($operator) {
    static $specials = array(
      // In PostgreSQL, 'LIKE' is case-sensitive.  For case-insensitive LIKE
      // statements, we need to use ILIKE instead.
      'LIKE' => array('operator' => 'ILIKE'),
    );

    return isset($specials[$operator]) ? $specials[$operator] : NULL;
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
