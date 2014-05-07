<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\Stub\StubConnection.
 */

namespace Drupal\Tests\Core\Database\Stub;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementEmpty;

/**
 * A stub of the abstract Connection class for testing purposes.
 *
 * Includes minimal implementations of Connection's abstract methods.
 */
class StubConnection extends Connection {

  /**
   * Public property so we can test driver loading mechanism.
   *
   * @var string
   * @see driver().
   */
  public $driver = 'stub';

  /**
   * {@inheritdoc}
   */
  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    return new StatementEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = array(), array $options = array()) {
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
    return 'stub';
  }

  /**
   * {@inheritdoc}
   */
  public function createDatabase($database) {
    return;
  }

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
