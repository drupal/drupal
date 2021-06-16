<?php

namespace Drupal\corefake\Driver\Database\corefake;

use Drupal\Driver\Database\fake\Connection as BaseConnection;

/**
 * Corefake implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends BaseConnection {

  /**
   * {@inheritdoc}
   */
  public $driver = 'corefake';

}
