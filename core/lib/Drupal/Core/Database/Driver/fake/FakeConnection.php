<?php

/**
 * @file
 * Contains Drupal\Core\Database\Driver\fake\FakeConnection.
 */

namespace Drupal\Core\Database\Driver\fake;

use Drupal\Core\Database\Connection;

/**
 * Defines a fake connection for use during testing.
 */
class FakeConnection extends Connection {

  /**
   * Table prefix on the database.
   *
   * @var string
   */
  protected $tablePrefix;

  /**
   * Connection options for the database.
   *
   * @var array
   */
  protected $connectionOptions;

  /**
   * Constructs a FakeConnection.
   *
   * @param array $database_contents
   *   The database contents faked as an array. Each key is a table name, each
   *   value is a list of table rows.
   * @param array $connection_options
   *   (optional) The array of connection options for the database.
   * @param string $prefix
   *   (optional) The table prefix on the database.
   */
  public function __construct(array $database_contents, $connection_options = array(), $prefix = '') {
    $this->databaseContents = $database_contents;
    $this->connectionOptions = $connection_options;
    $this->tablePrefix = $prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnectionOptions() {
    return $this->connectionOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function tablePrefix($table = 'default') {
    return $this->tablePrefix;
  }

  /**
   * {@inheritdoc}
   */
  public function select($table, $alias = NULL, array $options = array()) {
    return new FakeSelect($this->databaseContents, $table, $alias);
  }

  /**
   * {@inheritdoc}
   */
  public function insert($table, array $options = array()) {
    return new FakeInsert($this->databaseContents, $table);
  }

  /**
   * {@inheritdoc}
   */
  public function merge($table, array $options = array()) {
    return new FakeMerge($this->databaseContents, $table);
  }

  /**
   * {@inheritdoc}
   */
  public function update($table, array $options = array()) {
    return new FakeUpdate($this->databaseContents, $table);
  }

  /**
   * {@inheritdoc}
   */
  public function truncate($table, array $options = array()) {
    return new FakeTruncate($this->databaseContents, $table);
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
    return new FakeDatabaseSchema($this->databaseContents);
  }

  /**
   * {@inheritdoc}
   */
  public function query($query, array $args = array(), $options = array()) {
    throw new \Exception('Method not supported');
  }

  /**
   * {@inheritdoc}
   */
  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    throw new \Exception('Method not supported');
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = array(), array $options = array()) {
    throw new \Exception('Method not supported');
  }

  /**
   * {@inheritdoc}
   */
  public function driver() {
    throw new \Exception('Method not supported');
  }

  /**
   * {@inheritdoc}
   */
  public function databaseType() {
    throw new \Exception('Method not supported');
  }

  /**
   * {@inheritdoc}
   */
  public function createDatabase($database) {
    // There is nothing to do.
  }

  /**
   * {@inheritdoc}
   */
  public function mapConditionOperator($operator) {
    throw new \Exception('Method not supported');
  }

  /**
   * {@inheritdoc}
   */
  public function nextId($existing_id = 0) {
    throw new \Exception('Method not supported');
  }

}
