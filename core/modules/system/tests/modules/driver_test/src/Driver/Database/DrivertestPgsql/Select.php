<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

include_once dirname(__DIR__, 8) . '/pgsql/src/Driver/Database/pgsql/Select.php';

use Drupal\pgsql\Driver\Database\pgsql\Select as CoreSelect;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Select.
 */
class Select extends CoreSelect {}
