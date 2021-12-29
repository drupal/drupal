<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysql;

include_once dirname(__DIR__, 8) . '/mysql/src/Driver/Database/mysql/Upsert.php';

use Drupal\mysql\Driver\Database\mysql\Upsert as CoreUpsert;

/**
 * MySQL test implementation of \Drupal\Core\Database\Query\Upsert.
 */
class Upsert extends CoreUpsert {}
