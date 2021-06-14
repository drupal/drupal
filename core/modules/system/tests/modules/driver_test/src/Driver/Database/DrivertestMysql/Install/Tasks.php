<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysql\Install;

use Drupal\Core\Database\Driver\mysql\Install\Tasks as CoreTasks;

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
