<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

include_once dirname(__DIR__, 8) . '/pgsql/src/Driver/Database/pgsql/Insert.php';

use Drupal\pgsql\Driver\Database\pgsql\Insert as CoreInsert;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Insert.
 */
class Insert extends CoreInsert {}
