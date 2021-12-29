<?php

namespace Drupal\Core\Database\Driver\pgsql\Install;

use Drupal\pgsql\Driver\Database\pgsql\Install\Tasks as PgsqlTasks;

@trigger_error('\Drupal\Core\Database\Driver\pgsql\Install\Tasks is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492', E_USER_DEPRECATED);

/**
 * Specifies installation tasks for PostgreSQL databases.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL
 *   database driver has been moved to the pgsql module.
 *
 * @see https://www.drupal.org/node/3129492
 */
class Tasks extends PgsqlTasks {}
