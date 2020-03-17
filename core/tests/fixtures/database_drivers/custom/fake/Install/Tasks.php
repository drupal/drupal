<?php

namespace Drupal\Driver\Database\fake\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;

class Tasks extends InstallTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'fake';
  }

}
