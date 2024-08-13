<?php

namespace Drupal\driver_test\Driver\Database\DriverTestMysql;

use Drupal\mysql\Driver\Database\mysql\Connection as CoreConnection;

/**
 * MySQL test implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends CoreConnection {

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'DriverTestMysql';
  }

}
