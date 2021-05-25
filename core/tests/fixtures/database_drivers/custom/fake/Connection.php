<?php

namespace Drupal\Driver\Database\fake;

use Drupal\Core\Database\Connection as CoreConnection;

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
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = [], array $options = []) {
    @trigger_error('Connection::queryTemporary() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/3211781', E_USER_DEPRECATED);
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
