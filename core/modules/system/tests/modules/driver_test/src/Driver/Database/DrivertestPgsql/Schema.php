<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

include_once dirname(__DIR__, 8) . '/pgsql/src/Driver/Database/pgsql/Schema.php';

use Drupal\pgsql\Driver\Database\pgsql\Schema as CoreSchema;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Schema.
 */
class Schema extends CoreSchema {}
