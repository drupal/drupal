<?php

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\sqlite\Driver\Database\sqlite\Upsert as SqliteUpsert;

@trigger_error('\Drupal\Core\Database\Driver\sqlite\Upsert is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492', E_USER_DEPRECATED);

/**
 * SQLite implementation of \Drupal\Core\Database\Query\Upsert.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite
 *   database driver has been moved to the sqlite module.
 *
 * @see https://www.drupal.org/node/3129492
 */
class Upsert extends SqliteUpsert {}
