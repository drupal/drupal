<?php

namespace Drupal\Driver\Database\fake;

use Drupal\Core\Database\Connection as CoreConnection;
use Drupal\Core\Database\StatementEmpty;

/**
 * A fake Connection class for testing purposes.
 */
class Connection extends CoreConnection {

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
    return new StatementEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = [], array $options = []) {
    return '';
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
    return 0;
  }

}
