<?php

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\mysql\Driver\Database\mysql\Schema as MysqlSchema;

@trigger_error('\Drupal\Core\Database\Driver\mysql\Schema is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL database driver has been moved to the mysql module. See https://www.drupal.org/node/3129492', E_USER_DEPRECATED);

/**
 * MySQL implementation of \Drupal\Core\Database\Schema.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL
 *   database driver has been moved to the mysql module.
 *
 * @see https://www.drupal.org/node/3129492
 */
class Schema extends MysqlSchema {}
