<?php

namespace Drupal\Core\Database\Driver\CoreFake\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;

/**
 * The database installer structure for the fake database driver.
 */
class Tasks extends InstallTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'CoreFake';
  }

}
