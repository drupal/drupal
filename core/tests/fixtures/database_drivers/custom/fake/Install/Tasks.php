<?php

namespace Drupal\Driver\Database\fake\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;

/**
 * A task for testing database drivers.
 */
class Tasks extends InstallTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'fake';
  }

}
