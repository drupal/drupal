<?php

namespace Drupal\core_fake\Driver\Database\CoreFakeWithAllCustomClasses\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;

/**
 * Specifies installation tasks for CoreFakeWithAllCustomClasses.
 */
class Tasks extends InstallTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return 'CoreFakeWithAllCustomClasses';
  }

}
