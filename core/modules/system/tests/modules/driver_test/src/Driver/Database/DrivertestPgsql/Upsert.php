<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

include_once dirname(__DIR__, 8) . '/pgsql/src/Driver/Database/pgsql/Upsert.php';

use Drupal\pgsql\Driver\Database\pgsql\Upsert as CoreUpsert;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Upsert.
 */
class Upsert extends CoreUpsert {}
