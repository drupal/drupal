<?php

declare(strict_types=1);

namespace Drupal\driver_test\Driver\Database\DriverTestPgsql;

use Drupal\pgsql\Driver\Database\pgsql\Connection as CoreConnection;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends CoreConnection {

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'DriverTestPgsql';
  }

}
