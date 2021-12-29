<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysql;

include_once dirname(__DIR__, 8) . '/mysql/src/Driver/Database/mysql/Connection.php';

use Drupal\mysql\Driver\Database\mysql\Connection as CoreConnection;

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
