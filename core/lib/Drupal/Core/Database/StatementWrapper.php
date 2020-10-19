<?php

namespace Drupal\Core\Database;

// cSpell:ignore maxlen driverdata INOUT

/**
 * Implementation of StatementInterface encapsulating PDOStatement.
 */
class StatementWrapper implements \IteratorAggregate, StatementInterface {

  /**
   * The Drupal database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  public $dbh;

  /**
   * The client database Statement object.
   *
   * For a \PDO client connection, this will be a \PDOStatement object.
   *
   * @var object
   */
  protected $clientStatement;

  /**
   * Is rowCount() execution allowed.
   *
   * @var bool
   */
  public $allowRowCount = FALSE;

  /**
   * Constructs a StatementWrapper object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Drupal database connection object.
   * @param object $client_connection
   *   Client database connection object, for example \PDO.
   * @param string $query
   *   The SQL query string.
   * @param array $options
   *   Array of query options.
   */
  public function __construct(Connection $connection, $client_connection, string $query, array $options) {
    $this->dbh = $connection;
    $this->clientStatement = $client_connection->prepare($query, $options);
    $this->setFetchMode(\PDO::FETCH_OBJ);
  }

  /**
   * Implements the magic __get() method.
   *
   * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Access the
   *   client-level statement object via ::getClientStatement().
   *
   * @see https://www.drupal.org/node/3177488
   */
  public function __get($name) {
    if ($name === 'queryString') {
      @trigger_error("StatementWrapper::\${$name} should not be accessed in drupal:9.1.0 and will error in drupal:10.0.0. Access the client-level statement object via ::getClientStatement(). See https://www.drupal.org/node/3177488", E_USER_DEPRECATED);
      return $this->getClientStatement()->queryString;
    }
  }

  /**
   * Implements the magic __call() method.
   *
   * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Access the
   *   client-level statement object via ::getClientStatement().
   *
   * @see https://www.drupal.org/node/3177488
   */
  public function __call($method, $arguments) {
    if (is_callable([$this->getClientStatement(), $method])) {
      @trigger_error("StatementWrapper::{$method} should not be called in drupal:9.1.0 and will error in drupal:10.0.0. Access the client-level statement object via ::getClientStatement(). See https://www.drupal.org/node/3177488", E_USER_DEPRECATED);
      return call_user_func_array([$this->getClientStatement(), $method], $arguments);
    }
    throw new \BadMethodCallException($method);
  }

  /**
   * Returns the client-level database statement object.
   *
   * This method should normally be used only within database driver code.
   *
   * @return object
   *   The client-level database statement, for example \PDOStatement.
   */
  public function getClientStatement() {
    return $this->clientStatement;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($args = [], $options = []) {
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

    $return = $this->clientStatement->execute($args);

    if (!empty($logger)) {
      $query_end = microtime(TRUE);
      $logger->log($this, $args, $query_end - $query_start);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryString() {
    return $this->clientStatement->queryString;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchCol($index = 0) {
    return $this->fetchAll(\PDO::FETCH_COLUMN, $index);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllAssoc($key, $fetch = NULL) {
    $return = [];
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

  /**
   * {@inheritdoc}
   */
  public function fetchAllKeyed($key_index = 0, $value_index = 1) {
    $return = [];
    $this->setFetchMode(\PDO::FETCH_NUM);
    foreach ($this as $record) {
      $return[$record[$key_index]] = $record[$value_index];
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchField($index = 0) {
    // Call \PDOStatement::fetchColumn to fetch the field.
    return $this->clientStatement->fetchColumn($index);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAssoc() {
    // Call \PDOStatement::fetch to fetch the row.
    return $this->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchObject(string $class_name = NULL) {
    if ($class_name) {
      return $this->clientStatement->fetchObject($class_name);
    }
    return $this->clientStatement->fetchObject();
  }

  /**
   * {@inheritdoc}
   */
  public function rowCount() {
    // SELECT query should not use the method.
    if ($this->allowRowCount) {
      return $this->clientStatement->rowCount();
    }
    else {
      throw new RowCountException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFetchMode($mode, $a1 = NULL, $a2 = []) {
    // Call \PDOStatement::setFetchMode to set fetch mode.
    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to be pass the exact number of arguments we where given.
    switch (func_num_args()) {
      case 1:
        return $this->clientStatement->setFetchMode($mode);

      case 2:
        return $this->clientStatement->setFetchMode($mode, $a1);

      case 3:
      default:
        return $this->clientStatement->setFetchMode($mode, $a1, $a2);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL) {
    // Call \PDOStatement::fetchAll to fetch all rows.
    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to be pass the exact number of arguments we where given.
    switch (func_num_args()) {
      case 0:
        return $this->clientStatement->fetch();

      case 1:
        return $this->clientStatement->fetch($mode);

      case 2:
        return $this->clientStatement->fetch($mode, $cursor_orientation);

      case 3:
      default:
        return $this->clientStatement->fetch($mode, $cursor_orientation, $cursor_offset);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll($mode = NULL, $column_index = NULL, $constructor_arguments = NULL) {
    // Call \PDOStatement::fetchAll to fetch all rows.
    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to be pass the exact number of arguments we where given.
    switch (func_num_args()) {
      case 0:
        return $this->clientStatement->fetchAll();

      case 1:
        return $this->clientStatement->fetchAll($mode);

      case 2:
        return $this->clientStatement->fetchAll($mode, $column_index);

      case 3:
      default:
        return $this->clientStatement->fetchAll($mode, $column_index, $constructor_arguments);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->fetchAll());
  }

  /**
   * Bind a column to a PHP variable.
   *
   * @param mixed $column
   *   Number of the column (1-indexed) or name of the column in the result set.
   *   If using the column name, be aware that the name should match the case of
   *   the column, as returned by the driver.
   * @param mixed $param
   *   Name of the PHP variable to which the column will be bound.
   * @param int $type
   *   (Optional) data type of the parameter, specified by the PDO::PARAM_*
   *   constants.
   * @param int $maxlen
   *   (Optional) a hint for pre-allocation.
   * @param mixed $driverdata
   *   (Optional) optional parameter(s) for the driver.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0.
   *   StatementWrapper::bindColumn should not be called. Access the
   *   client-level statement object via ::getClientStatement().
   *
   * @see https://www.drupal.org/node/3177488
   */
  public function bindColumn($column, &$param, int $type = 0, int $maxlen = 0, $driverdata = NULL): bool {
    @trigger_error("StatementWrapper::bindColumn should not be called in drupal:9.1.0 and will error in drupal:10.0.0. Access the client-level statement object via ::getClientStatement(). See https://www.drupal.org/node/3177488", E_USER_DEPRECATED);
    switch (func_num_args()) {
      case 2:
        return $this->clientStatement->bindColumn($column, $param);

      case 3:
        return $this->clientStatement->bindColumn($column, $param, $type);

      case 4:
        return $this->clientStatement->bindColumn($column, $param, $type, $maxlen);

      case 5:
        return $this->clientStatement->bindColumn($column, $param, $type, $maxlen, $driverdata);

    }
  }

  /**
   * Binds a parameter to the specified variable name.
   *
   * @param mixed $parameter
   *   Parameter identifier. For a prepared statement using named placeholders,
   *   this will be a parameter name of the form :name.
   * @param mixed $variable
   *   Name of the PHP variable to bind to the SQL statement parameter.
   * @param int $data_type
   *   (Optional) explicit data type for the parameter using the PDO::PARAM_*
   *   constants. To return an INOUT parameter from a stored procedure, use the
   *   bitwise OR operator to set the PDO::PARAM_INPUT_OUTPUT bits for the
   *   data_type parameter.
   * @param int $length
   *   (Optional) length of the data type. To indicate that a parameter is an
   *   OUT parameter from a stored procedure, you must explicitly set the
   *   length.
   * @param mixed $driver_options
   *   (Optional) Driver options.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0.
   *   StatementWrapper::bindParam should not be called. Access the
   *   client-level statement object via ::getClientStatement().
   *
   * @see https://www.drupal.org/node/3177488
   */
  public function bindParam($parameter, &$variable, int $data_type = \PDO::PARAM_STR, int $length = 0, $driver_options = NULL) : bool {
    @trigger_error("StatementWrapper::bindParam should not be called in drupal:9.1.0 and will error in drupal:10.0.0. Access the client-level statement object via ::getClientStatement(). See https://www.drupal.org/node/3177488", E_USER_DEPRECATED);
    switch (func_num_args()) {
      case 2:
        return $this->clientStatement->bindParam($parameter, $variable);

      case 3:
        return $this->clientStatement->bindParam($parameter, $variable, $data_type);

      case 4:
        return $this->clientStatement->bindParam($parameter, $variable, $data_type, $length);

      case 5:
        return $this->clientStatement->bindParam($parameter, $variable, $data_type, $length, $driver_options);

    }
  }

}
