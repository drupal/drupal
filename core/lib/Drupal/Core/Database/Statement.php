<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Statement.
 */

namespace Drupal\Core\Database;

/**
 * Default implementation of StatementInterface.
 *
 * \PDO allows us to extend the \PDOStatement class to provide additional
 * functionality beyond that offered by default. We do need extra
 * functionality. By default, this class is not driver-specific. If a given
 * driver needs to set a custom statement class, it may do so in its
 * constructor.
 *
 * @see http://php.net/pdostatement
 */
class Statement extends \PDOStatement implements StatementInterface {

  /**
   * Reference to the database connection object for this statement.
   *
   * The name $dbh is inherited from \PDOStatement.
   *
   * @var \Drupal\Core\Database\Connection
   */
  public $dbh;

  /**
   * Is rowCount() execution allowed.
   *
   * @var bool
   */
  public $allowRowCount = FALSE;

  protected function __construct(Connection $dbh) {
    $this->dbh = $dbh;
    $this->setFetchMode(\PDO::FETCH_OBJ);
  }

  public function execute($args = array(), $options = array()) {
    if (isset($options['fetch'])) {
      if (is_string($options['fetch'])) {
        // \PDO::FETCH_PROPS_LATE tells __construct() to run before properties
        // are added to the object.
        $this->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $options['fetch']);
      }
      else {
        $this->setFetchMode($options['fetch']);
      }
    }

    $logger = $this->dbh->getLogger();
    if (!empty($logger)) {
      $query_start = microtime(TRUE);
    }

    $return = parent::execute($args);

    if (!empty($logger)) {
      $query_end = microtime(TRUE);
      $logger->log($this, $args, $query_end - $query_start);
    }

    return $return;
  }

  public function getQueryString() {
    return $this->queryString;
  }

  public function fetchCol($index = 0) {
    return $this->fetchAll(\PDO::FETCH_COLUMN, $index);
  }

  public function fetchAllAssoc($key, $fetch = NULL) {
    $return = array();
    if (isset($fetch)) {
      if (is_string($fetch)) {
        $this->setFetchMode(\PDO::FETCH_CLASS, $fetch);
      }
      else {
        $this->setFetchMode($fetch);
      }
    }

    foreach ($this as $record) {
      $record_key = is_object($record) ? $record->$key : $record[$key];
      $return[$record_key] = $record;
    }

    return $return;
  }

  public function fetchAllKeyed($key_index = 0, $value_index = 1) {
    $return = array();
    $this->setFetchMode(\PDO::FETCH_NUM);
    foreach ($this as $record) {
      $return[$record[$key_index]] = $record[$value_index];
    }
    return $return;
  }

  public function fetchField($index = 0) {
    // Call \PDOStatement::fetchColumn to fetch the field.
    return $this->fetchColumn($index);
  }

  public function fetchAssoc() {
    // Call \PDOStatement::fetch to fetch the row.
    return $this->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function rowCount() {
    // SELECT query should not use the method.
    if ($this->allowRowCount) {
      return parent::rowCount();
    }
    else {
      throw new RowCountException();
    }
  }

}
