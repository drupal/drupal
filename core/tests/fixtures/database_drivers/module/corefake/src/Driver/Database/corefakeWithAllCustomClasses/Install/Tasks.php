<?php

namespace Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;

/**
 * Specifies installation tasks for corefakeWithAllCustomClasses.
 */
class Tasks extends InstallTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'corefakeWithAllCustomClasses';
  }

}
