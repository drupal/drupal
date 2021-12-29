<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysql\Install;

include_once dirname(__DIR__, 9) . '/mysql/src/Driver/Database/mysql/Install/Tasks.php';

use Drupal\mysql\Driver\Database\mysql\Install\Tasks as CoreTasks;

/**
 * Specifies installation tasks for MySQL test databases.
 */
class Tasks extends CoreTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return t('MySQL by the driver_test module');
  }

}
