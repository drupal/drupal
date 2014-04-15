<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\fake\Install\Tasks
 */

namespace Drupal\Core\Database\Driver\fake\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Database\DatabaseNotFoundException;

/**
 * Usually used to specify installation tasks but here we're only interested
 * in setting $error to TRUE.
 */
class Tasks extends InstallTasks {
  /**
   * Prevent the installer from recognising this as a potential database driver.
   * @TODO Looks like this is needed only if we define $pdoDriver to something valid e.g. mysql.
   * Not sure we need to do that. We may not need this file at all?
   *
   * @var boolean
   */
  protected $error = TRUE;

  /**
   * {@inheritdoc}
   */
  public function name() {
    return t('Fake database connection for use in unit tests');
  }
}
