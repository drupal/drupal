<?php

declare(strict_types=1);

namespace Drupal\mysqli\Driver\Database\mysqli\Install;

use Drupal\mysql\Driver\Database\mysql\Install\Tasks as BaseInstallTasks;

/**
 * Specifies installation tasks for MySQLi.
 */
class Tasks extends BaseInstallTasks {

  /**
   * {@inheritdoc}
   */
  public function installable() {
    return extension_loaded('mysqli');
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return $this->t('@parent via mysqli (Experimental)', ['@parent' => parent::name()]);
  }

}
