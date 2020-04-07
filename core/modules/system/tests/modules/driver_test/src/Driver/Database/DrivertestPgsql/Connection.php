<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

use Drupal\Core\Database\Driver\pgsql\Connection as CoreConnection;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends CoreConnection {

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'DrivertestPgsql';
  }

}
