<?php

namespace Drupal\corefake\Driver\Database\corefake\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;

class Tasks extends InstallTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'corefake';
  }

}
