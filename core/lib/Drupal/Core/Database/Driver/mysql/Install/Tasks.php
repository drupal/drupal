<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\mysql\Install\Tasks
 */

namespace Drupal\Core\Database\Driver\mysql\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;

/**
 * Specifies installation tasks for MySQL and equivalent databases.
 */
class Tasks extends InstallTasks {
  /**
   * The PDO driver name for MySQL and equivalent databases.
   *
   * @var string
   */
  protected $pdoDriver = 'mysql';

  /**
   * Returns a human-readable name string for MySQL and equivalent databases.
   */
  public function name() {
    return st('MySQL, MariaDB, or equivalent');
  }

  /**
   * Returns the minimum version for MySQL.
   */
  public function minimumVersion() {
    return '5.0.15';
  }
}
