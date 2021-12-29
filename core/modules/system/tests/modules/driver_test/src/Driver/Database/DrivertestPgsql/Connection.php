<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

include_once dirname(__DIR__, 8) . '/pgsql/src/Driver/Database/pgsql/Connection.php';

use Drupal\pgsql\Driver\Database\pgsql\Connection as CoreConnection;

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
