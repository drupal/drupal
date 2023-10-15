<?php

namespace Drupal\core_fake\Driver\Database\CoreFake\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;

/**
 * Specifies installation tasks for CoreFake.
 */
class Tasks extends InstallTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'CoreFake';
  }

}
