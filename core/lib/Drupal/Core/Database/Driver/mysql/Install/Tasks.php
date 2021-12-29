<?php

namespace Drupal\Core\Database\Driver\mysql\Install;

use Drupal\mysql\Driver\Database\mysql\Install\Tasks as MysqlTasks;

@trigger_error('\Drupal\Core\Database\Driver\mysql\Install\Tasks is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL database driver has been moved to the mysql module. See https://www.drupal.org/node/3129492', E_USER_DEPRECATED);

/**
 * Specifies installation tasks for MySQL and equivalent databases.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL
 *   database driver has been moved to the mysql module.
 *
 * @see https://www.drupal.org/node/3129492
 */
class Tasks extends MysqlTasks {}
