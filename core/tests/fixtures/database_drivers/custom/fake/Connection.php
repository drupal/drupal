<?php

namespace Drupal\Driver\Database\fake;

use Drupal\Core\Database\Connection as CoreConnection;
use Drupal\Core\Database\ExceptionHandler;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Truncate;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\Query\Upsert;
use Drupal\Core\Database\StatementWrapper;
use Drupal\Core\Database\Transaction;

/**
 * A fake Connection class for testing purposes.
 */
class Connection extends CoreConnection {

  /**
   * {@inheritdoc}
   */
  protected $statementClass = NULL;

  /**
   * {@inheritdoc}
   */
  protected $statementWrapperClass = StatementWrapper::class;

  /**
   * {@inheritdoc}
   */
  protected $identifierQuotes = ['"', '"'];

  /**
   * Public property so we can test driver loading mechanism.
   *
   * @var string
   * @see driver().
   */
  public $driver = 'fake';

  /**
   * {@inheritdoc}
   */
  public function queryRange($query, $from, $count, array $args = [], array $options = []) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return $this->driver;
  }

  /**
   * {@inheritdoc}
   */
  public function databaseType() {
    return 'fake';
  }

  /**
   * {@inheritdoc}
   */
  public function createDatabase($database) {}

  /**
   * {@inheritdoc}
   */
  public function mapConditionOperator($operator) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function nextId($existing_id = 0) {
    @trigger_error('Drupal\Core\Database\Connection::nextId() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Modules should use instead the keyvalue storage for the last used id. See https://www.drupal.org/node/3349345', E_USER_DEPRECATED);
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function exceptionHandler() {
    return new ExceptionHandler();
  }

  /**
   * {@inheritdoc}
   */
  public function select($table, $alias = NULL, array $options = []) {
    return new Select($this, $table, $alias, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function insert($table, array $options = []) {
    return new Insert($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function merge($table, array $options = []) {
    return new Merge($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function upsert($table, array $options = []) {
    return new Upsert($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function update($table, array $options = []) {
    return new Update($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($table, array $options = []) {
    return new Delete($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function truncate($table, array $options = []) {
    return new Truncate($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
    if (empty($this->schema)) {
      $this->schema = new Schema($this);
    }
    return $this->schema;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($conjunction) {
    return new Condition($conjunction, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function startTransaction($name = '') {
    return new Transaction($this, $name);
  }

}
