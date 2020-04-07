<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysql;

use Drupal\Core\Database\Driver\mysql\Connection as CoreConnection;

/**
 * MySQL test implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends CoreConnection {

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'DrivertestMysql';
  }

}
