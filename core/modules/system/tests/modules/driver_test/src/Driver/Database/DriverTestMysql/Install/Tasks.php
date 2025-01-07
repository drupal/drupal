<?php

declare(strict_types=1);

namespace Drupal\driver_test\Driver\Database\DriverTestMysql\Install;

use Drupal\mysql\Driver\Database\mysql\Install\Tasks as CoreTasks;

/**
 * Specifies installation tasks for MySQL test databases.
 */
class Tasks extends CoreTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return $this->t('MySQL by the driver_test module');
  }

}
