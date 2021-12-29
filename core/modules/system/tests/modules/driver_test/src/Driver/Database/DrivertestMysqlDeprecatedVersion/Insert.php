<?php

namespace Drupal\driver_test\Driver\Database\DrivertestMysqlDeprecatedVersion;

include_once dirname(__DIR__, 8) . '/mysql/src/Driver/Database/mysql/Insert.php';

use Drupal\mysql\Driver\Database\mysql\Insert as CoreInsert;

/**
 * MySQL test implementation of \Drupal\Core\Database\Query\Insert.
 */
class Insert extends CoreInsert {}
