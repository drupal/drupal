<?php

namespace Drupal\Core\Database\Driver\corefake;

use Drupal\Driver\Database\fake\Connection as BaseConnection;

/**
 * A connection for testing database drivers.
 */
class Connection extends BaseConnection {

  /**
   * {@inheritdoc}
   */
  public $driver = 'corefake';

}
