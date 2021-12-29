<?php

namespace Drupal\Core\Database\Driver\sqlite\Install;

use Drupal\sqlite\Driver\Database\sqlite\Install\Tasks as SqliteTasks;

@trigger_error('\Drupal\Core\Database\Driver\sqlite\Install\Tasks is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492', E_USER_DEPRECATED);

/**
 * Specifies installation tasks for SQLite databases.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite
 *   database driver has been moved to the sqlite module.
 *
 * @see https://www.drupal.org/node/3129492
 */
class Tasks extends SqliteTasks {}
