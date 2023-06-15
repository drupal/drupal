<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql\Install;

use Drupal\pgsql\Driver\Database\pgsql\Install\Tasks as CoreTasks;

/**
 * Specifies installation tasks for PostgreSQL databases.
 */
class Tasks extends CoreTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return t('PostgreSQL by the driver_test module');
  }

}
