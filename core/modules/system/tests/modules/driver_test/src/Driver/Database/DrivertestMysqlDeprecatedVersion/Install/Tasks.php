<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysqlDeprecatedVersion\Install;

use Drupal\mysql\Driver\Database\mysql\Install\Tasks as CoreTasks;

/**
 * Specifies installation tasks for MySQL test databases.
 */
class Tasks extends CoreTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return t('MySQL deprecated version by the driver_test module');
  }

}
