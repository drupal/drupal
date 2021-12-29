<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

include_once dirname(__DIR__, 8) . '/pgsql/src/Driver/Database/pgsql/Update.php';

use Drupal\pgsql\Driver\Database\pgsql\Update as CoreUpdate;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Update.
 */
class Update extends CoreUpdate {}
