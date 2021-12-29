<?php

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\mysql\Driver\Database\mysql\ExceptionHandler as MysqlExceptionHandler;

@trigger_error('\Drupal\Core\Database\Driver\mysql\ExceptionHandler is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL database driver has been moved to the mysql module. See https://www.drupal.org/node/3129492', E_USER_DEPRECATED);

/**
 * MySql database exception handler class.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL
 *   database driver has been moved to the mysql module.
 *
 * @see https://www.drupal.org/node/3129492
 */
class ExceptionHandler extends MysqlExceptionHandler {}
