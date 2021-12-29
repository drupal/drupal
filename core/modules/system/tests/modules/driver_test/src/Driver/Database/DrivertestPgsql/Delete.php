<?php

namespace Drupal\driver_test\Driver\Database\DrivertestPgsql;

include_once dirname(__DIR__, 8) . '/pgsql/src/Driver/Database/pgsql/Delete.php';

use Drupal\pgsql\Driver\Database\pgsql\Delete as CoreDelete;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Delete.
 */
class Delete extends CoreDelete {}
