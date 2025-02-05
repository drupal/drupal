<?php

declare(strict_types=1);

namespace Drupal\driver_missing_dependency_test\Driver\Database\MissingDependency\Install;

use Drupal\Core\Database\Install\Tasks as CoreTasks;

/**
 * Specifies fake installation tasks for test.
 */
class Tasks extends CoreTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'Fake driver by the driver_missing_dependency_test module';
  }

}
