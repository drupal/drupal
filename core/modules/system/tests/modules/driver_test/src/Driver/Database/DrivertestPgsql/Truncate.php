<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

include_once dirname(__DIR__, 8) . '/pgsql/src/Driver/Database/pgsql/Truncate.php';

use Drupal\pgsql\Driver\Database\pgsql\Truncate as CoreTruncate;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Truncate.
 */
class Truncate extends CoreTruncate {}
